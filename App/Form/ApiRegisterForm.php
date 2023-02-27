<?php

namespace App\Form;

use CoreDB\Kernel\ConfigurationManager;
use Exception;
use Src\Entity\Translation;
use Src\Entity\User;
use Src\Form\Widget\InputWidget;

class ApiRegisterForm extends ApiFormAbstract
{
    public string $method = "POST";

    public function __construct()
    {
        $this->addField(
            InputWidget::create("name")
        );
        $this->addField(
            InputWidget::create("surname")
        );
        $this->addField(
            InputWidget::create("email")
                ->setType("email")
        );
        $this->addField(
            InputWidget::create("password")
                ->setType("password")
        );
        $this->addField(
            InputWidget::create("password_again")
                ->setType("password")
        );
        parent::__construct();
    }

    public function getFormId(): string
    {
        return "register_form";
    }

    public function validate(): bool
    {
        foreach ($this->fields as $fieldName => $field) {
            if (!$this->request[$fieldName]) {
                $this->setError($fieldName, Translation::getTranslation("cannot_empty", [
                    $this->fields[$fieldName]->label
                ]));
            }
        }
        if ($this->request["password"] != $this->request["password_again"]) {
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
        try {
            $user = new User();
            $mapData = $this->request;
            $mapData["status"] = User::STATUS_ACTIVE;
            $mapData["username"] = $this->generateUsername($this->request["email"]);
            $user->map($mapData);
            $user->save();
            $user = User::get($user->ID->getValue());
            \CoreDB::userLogin($user);
        } catch (Exception $ex) {
            $this->setError("", $ex->getMessage());
        }
    }

    public static function generateUsername(string $email)
    {
        $mailStart = explode("@", $email)[0];
        $tempUserName = $mailStart;
        while (User::getUserByUsername($tempUserName)) {
            $tempUserName = $mailStart . random_int(0, 100);
        }
        return $tempUserName;
    }

    public function getResponse()
    {
        return [
            "token" => session_id()
        ];
    }
}
