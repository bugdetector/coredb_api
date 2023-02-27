<?php

namespace App\Controller\Api;

use App\Controller\ApiController;
use App\Form\ApiFormAbstract;
use CoreDB;
use CoreDB\Kernel\Messenger;
use CoreDB\Kernel\SearchableInterface;
use OpenApi\Annotations\Server;
use Src\Form\SearchForm;
use Src\Form\Widget\InputWidget;
use Src\Form\Widget\SelectWidget;

class V1Controller extends ApiController
{
    public function checkAccess(): bool
    {
        return CoreDB::currentUser()->isLoggedIn();
    }

    public function docs()
    {
        $this->response_type = self::RESPONSE_TYPE_RAW;
        $openapi = @\OpenApi\Generator::scan([
            __DIR__ . "/.."
        ]);
        $openapi->servers = [
            new Server([
                "url" => BASE_URL,
                "description" => "This server"
            ])
        ];
        header("Content-Type: application/json");
        return @$openapi->toJson();
    }

    protected function processForm(ApiFormAbstract $form)
    {
        if ($form->request) {
            $form->processForm();
            if ($form->errors) {
                http_response_code(400);
                $this->messages[Messenger::ERROR] = array_map(function ($message) {
                    return $message->fields[0]->message;
                }, $form->errors);
            } else {
                $this->messages = CoreDB::messenger()->getMessages();
                CoreDB::messenger()->clearMessages();
                return $form->getResponse();
            }
        } else {
            return [
                "form_id" => $form->fields["form_id"]->value,
                "form_build_id" => $form->fields["form_build_id"]->value,
                "form_token" => $form->fields["form_token"]->value,
                "token" => session_id()
            ];
        }
    }

    protected function processSearchForm(SearchableInterface $query)
    {
        $form = SearchForm::createByObject($query);
        return [
            "search" => array_map(function ($widget) {
                $widgetData = [
                    "name" => $widget->name,
                    "label" => $widget->label,
                    "description" => $widget->description,
                    "value" => $widget->value,
                    "attributes" => $widget->attributes,
                ];
                if ($widget instanceof InputWidget) {
                    if (in_array("daterangeinput", $widget->classes)) {
                        $type = "daterange";
                    } elseif (in_array("datetimeinput", $widget->classes)) {
                        $type = "datetime";
                    } elseif (in_array("dateinput", $widget->classes)) {
                        $type = "date";
                    } elseif (in_array("timeinput", $widget->classes)) {
                        $type = "time";
                    } else {
                        $type = $widget->type;
                    }
                } elseif ($widget instanceof SelectWidget) {
                    $type = "select";
                    $widgetData["options"] = array_map(function ($option) {
                        return $option->label;
                    }, $widget->options);
                }
                $widgetData["type"] = $type;
                return $widgetData;
            }, $query->getSearchFormFields(true)),
            "headers" => $form->headers,
            "data" => $form->data,
            "pagination" => [
                "page" => $form->pagination->page,
                "total_count" => $form->pagination->total_count,
                "limit" => $form->pagination->limit
            ]
        ];
    }

    /**
     * @OA\Get(
     *   tags={"Auth"},
     *   path="/api/v1/me",
     *   summary="Get authenticated user data",
     *   @OA\Parameter(
     *      name="Bearer",
     *      in="header",
     *      required=true,
     *      description="Bearer token",
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=403, description="Unauthorized"),
     * )
     */
    public function me()
    {
        $currentUser = CoreDB::currentUser()->toArray();
        unset($currentUser["password"]);
        return $currentUser;
    }

    /**
     * @OA\Post(
     *   tags={"Autocomplete"},
     *   path="/api/v1/autocomplete",
     *   @OA\Parameter(
     *      name="Bearer",
     *      in="header",
     *      required=true,
     *      description="Bearer token",
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                   property="token",
     *                   description="Autocomplete token",
     *                   type="string",
     *                   example=""
     *               ),
     *               @OA\Property(
     *                   property="term",
     *                   description="Autocomplete term",
     *                   type="string",
     *                   example="test"
     *               ),
     *           )
     *       )
     *   ),
     *   summary="Get autocomplete data",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=403, description="Unauthorized"),
     * )
     */
    public function autocomplete()
    {
        $autocompleteToken = @$_POST["token"];
        $data = @$_POST["term"];
        if ($autocompleteToken && isset($_SESSION["autocomplete"][$autocompleteToken])) {
            $autocompleteData = $_SESSION["autocomplete"][$autocompleteToken];
            $referenceTable = $autocompleteData["referenceTable"];
            $referenceColumn = $autocompleteData["referenceColumn"];

            $data = "%{$data}%";
            $query = \CoreDB::database()->select($referenceTable)
                    ->limit(20);
            $condition = \CoreDB::database()->condition($query);
            $explodedColumns = explode("&", $referenceColumn);
            foreach ($explodedColumns as $column) {
                $condition->condition($column, $data, "LIKE", "OR");
            }
            $query->condition($condition);
            $query->selectWithFunction(
                [
                    "ID AS id",
                    "CONCAT(" . implode(", ' - ',", $explodedColumns) . ") AS text"
                ]
            );
            return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    }
}
