<?php

namespace App\Form;

use CoreDB;
use CoreDB\Kernel\ConfigurationManager;
use Src\Entity\Logins;
use Src\Entity\Session;
use Src\Entity\Translation;
use Src\Entity\User;
use Src\Form\Widget\InputWidget;

class ApiLoginForm extends ApiFormAbstract
{
    protected const PASSWORD_FALSE_COUNT = "PASSWORD_FALSE_COUNT";
    protected const LOGIN_UNTRUSTED_ACTIONS = "LOGIN_UNTRUSTED_ACTIONS";
    public string $method = "POST";

    protected ?User $user;

    public function __construct()
    {
        parent::__construct();
        $this->addClass("user");
        $this->addField(
            InputWidget::create("username")
        );
        $this->addField(
            InputWidget::create("password")
        );
    }

    public function getFormId(): string
    {
        return "api_login_form";
    }

    public function validate(): bool
    {
        //if ip address is blocked not let to login
        if (User::isIpAddressBlocked()) {
            $this->setError("username", Translation::getTranslation("ip_blocked"));
        }
        $userClass = ConfigurationManager::getInstance()->getEntityInfo("users")["class"];
        $this->user = $userClass::getUserByUsername($this->request["username"]) ?:
                    $userClass::getUserByEmail($this->request["username"]);
        if ($this->user && $this->user->status->getValue() != User::STATUS_ACTIVE) {
            switch ($this->user->status->getValue()) {
                case User::STATUS_BLOCKED:
                    $this->setError("username", Translation::getTranslation("account_blocked"));
                    break;
                case User::STATUS_BANNED:
                    $this->setError("username", Translation::getTranslation("account_banned"));
                    break;
            }
        }

        //if login fails for more than 10 times block this ip
        if (isset($_SESSION[self::LOGIN_UNTRUSTED_ACTIONS]) && $_SESSION[self::LOGIN_UNTRUSTED_ACTIONS] > 10) {
            if (User::getLoginTryCountOfIp() > 10) {
                User::blockIpAddress();
            }
            if (User::getLoginTryCountOfUser($this->request["username"]) > 10) {
                //blocking user
                $this->user->map([
                    "status" => User::STATUS_BLOCKED
                ]);
                $this->user->save();
            }
            $this->setError("username", Translation::getTranslation("ip_blocked"));
        }
        if (
            empty($this->errors) &&
            (!$this->user || !password_verify($this->request["password"], $this->user->password))
        ) {
            if (isset($_SESSION[self::LOGIN_UNTRUSTED_ACTIONS])) {
                $_SESSION[self::LOGIN_UNTRUSTED_ACTIONS]++;
                if ($_SESSION[self::LOGIN_UNTRUSTED_ACTIONS] > 3) {
                    $this->setError("password", Translation::getTranslation("too_many_login_fails"));
                }
            } else {
                $_SESSION[self::LOGIN_UNTRUSTED_ACTIONS] = 1;
            }
            $this->setError("password", Translation::getTranslation("wrong_credidental"));
        }

        if (!empty($this->errors)) {
            //Logging failed login actions
            $login_log = new Logins();
            $login_log->ip_address->setValue(User::getUserIp());
            $login_log->username->setValue($_POST["username"]);
            $login_log->save();
            return false;
        }

        return true;
    }

    protected function csrfTokenCheckFailed()
    {
        parent::csrfTokenCheckFailed();
        if (isset($_SESSION[self::LOGIN_UNTRUSTED_ACTIONS])) {
            $_SESSION[self::LOGIN_UNTRUSTED_ACTIONS]++;
        } else {
            $_SESSION[self::LOGIN_UNTRUSTED_ACTIONS] = 1;
        }
    }

    public function submit()
    {
        //login successful
        \CoreDB::userLogin($this->user, @$_POST["remember_me"] ? true : false);

        unset($_SESSION[self::PASSWORD_FALSE_COUNT]);
        unset($_SESSION[self::LOGIN_UNTRUSTED_ACTIONS]);

        //Clearing failed login actions
        CoreDB::database()->delete(Logins::getTableName())
            ->condition("username", $this->user->username)
            ->execute();
    }

    public function getResponse()
    {
        /** @var Session */
        $session = Session::get(["session_key" => session_id()]);
        return [
            "token" => $session->remember_me_token->getValue() ?: $session->session_key->getValue(),
            "logged_in" => $this->user->isLoggedIn()
        ];
    }
}
