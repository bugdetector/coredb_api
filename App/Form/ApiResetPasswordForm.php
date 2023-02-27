<?php

namespace App\Form;

use CoreDB\Kernel\ConfigurationManager;
use Exception;
use Src\Entity\Logins;
use Src\Entity\ResetPassword;
use Src\Entity\Translation;
use Src\Entity\User;
use Src\Form\Widget\InputWidget;

class ApiResetPasswordForm extends ApiFormAbstract
{
    public string $method = "POST";

    private ?User $user = null;
    public function __construct(User $user = null)
    {
        $this->user = $user;
        parent::__construct();
        $this->addClass("user");
        $this->addField(
            InputWidget::create("password")
                ->setLabel(Translation::getTranslation("password"))
                ->setType("password")
                ->addClass("form-control-user")
                ->addAttribute("placeholder", Translation::getTranslation("password"))
                ->addAttribute("required", "true")
                ->addAttribute("autocomplete", "new-password")
        );
        $this->addField(
            InputWidget::create("password2")
                ->setLabel(Translation::getTranslation("password_again"))
                ->setType("password")
                ->addClass("form-control-user")
                ->addAttribute("placeholder", Translation::getTranslation("password_again"))
                ->addAttribute("required", "true")
                ->addAttribute("autocomplete", "new-password")
        );
    }

    public function getFormId(): string
    {
        return "api_reset_password_form";
    }

    public function validate(): bool
    {
        if ($this->request["password"] != $this->request["password2"]) {
            $this->setError("password", Translation::getTranslation("password_match_error"));
        }
        $userClass = ConfigurationManager::getInstance()->getEntityInfo("users")["class"];
        if (!$userClass::validatePassword($_POST["password"])) {
            $this->setError("password", Translation::getTranslation("password_validation_error"));
        }
        return empty($this->errors);
    }

    public function submit()
    {
        $this->user->map([
            "password" => $this->request["password"],
            "status" => User::STATUS_ACTIVE
        ]);
        $this->user->save();

        $reset_password_queue = ResetPassword::get(["user" => $this->user->ID, "key" => $_GET["KEY"]]);
        $reset_password_queue->delete();

        \CoreDB::database()->delete(Logins::getTableName())
        ->condition("username", $this->user->username)
        ->condition("ip_address", $this->user->getUserIp(), "OR")
        ->execute();

        $message = Translation::getTranslation("password_reset_success");
        $username = $this->user->getFullName();

        if (
            !\CoreDB::HTMLMail($this->user->email, Translation::getTranslation("reset_password"), $message, $username)
        ) {
            throw new Exception(
                Translation::getTranslation("an_error_occured")
            );
        }
    }
    public function getResponse()
    {
        return [
            "status" => "success",
            "message" => Translation::getTranslation("password_reset_success")
        ];
    }
}
