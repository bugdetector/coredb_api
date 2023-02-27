<?php

namespace App\Controller\Api\V1;

use App\Controller\Api\V1Controller;
use App\Form\ApiForgetPasswordForm;
use App\Form\ApiLoginForm;
use App\Form\ApiRegisterForm;
use App\Form\ApiResetPasswordForm;
use CoreDB;
use CoreDB\Kernel\ConfigurationManager;
use Exception;
use Src\Entity\ResetPassword;
use Src\Entity\Translation;

class AuthController extends V1Controller
{
    public function checkAccess(): bool
    {
        return true;
    }

    /**
     * @OA\Get(
     *   tags={"Auth"},
     *   path="/api/v1/auth/login",
     *   summary="Get csrf token for login form",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=403, description="Unauthorized"),
     * )
     * @OA\Post(
     *   tags={"Auth"},
     *   path="/api/v1/auth/login",
     *   summary="Login with credidentials",
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
     *                   property="username",
     *                   description="User username or email",
     *                   type="string",
     *                   example="username"
     *               ),
     *               @OA\Property(
     *                   property="password",
     *                   description="User password",
     *                   type="string",
     *                   example="password"
     *               ),
     *               @OA\Property(
     *                   property="remember_me",
     *                   description="Remember me active",
     *                   type="int",
     *                   example="0"
     *               ),
     *               @OA\Property(
     *                   property="form_id",
     *                   description="CSRF form_id",
     *                   type="string",
     *                   example="form_id"
     *               ),
     *               @OA\Property(
     *                   property="form_build_id",
     *                   description="CSRF form_build_id",
     *                   type="string",
     *                   example="form_build_id"
     *               ),
     *               @OA\Property(
     *                   property="form_token",
     *                   description="CSRF form_token",
     *                   type="string",
     *                   example="form_token"
     *               ),
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=400, description="Invalid Credidentials")
     * )
     */
    public function login()
    {
        return $this->processForm(
            new ApiLoginForm()
        );
    }

    /**
     * @OA\Get(
     *   tags={"Auth"},
     *   path="/api/v1/auth/logout",
     *   summary="Logout user.",
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
    public function logout()
    {
        CoreDB::userLogout();
        return true;
    }

    /**
     * @OA\Get(
     *   tags={"Auth"},
     *   path="/api/v1/auth/register",
     *   summary="Get csrf token for register form",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=403, description="Unauthorized"),
     * )
     * @OA\Post(
     *   tags={"Auth"},
     *   path="/api/v1/auth/register",
     *   summary="Register new user with provided data.",
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
     *                   property="name",
     *                   description="User's name",
     *                   type="string",
     *                   example="name"
     *               ),
     *               @OA\Property(
     *                   property="surname",
     *                   description="User's surname",
     *                   type="string",
     *                   example="surname"
     *               ),
     *               @OA\Property(
     *                   property="email",
     *                   description="User's email",
     *                   type="string",
     *                   example="booking@test.com"
     *               ),
     *               @OA\Property(
     *                   property="password",
     *                   description="Password.
     *                      Must be containt at least 1 uppercase, 1 lowercase, 1 number, 1 punctiation as 8 letters.",
     *                   type="string",
     *                   example="Aa.12345"
     *               ),
     *               @OA\Property(
     *                   property="password_again",
     *                   description="Password again.",
     *                   type="string",
     *                   example="Aa.12345"
     *               ),
     *               @OA\Property(
     *                   property="form_id",
     *                   description="CSRF form_id",
     *                   type="string",
     *                   example="form_id"
     *               ),
     *               @OA\Property(
     *                   property="form_build_id",
     *                   description="CSRF form_build_id",
     *                   type="string",
     *                   example="form_build_id"
     *               ),
     *               @OA\Property(
     *                   property="form_token",
     *                   description="CSRF form_token",
     *                   type="string",
     *                   example="form_token"
     *               ),
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=400, description="Invalid Credidentials")
     * )
     */
    public function register()
    {
        return $this->processForm(
            new ApiRegisterForm()
        );
    }

    /**
     * @OA\Get(
     *   tags={"Auth"},
     *   path="/api/v1/auth/forgetPassword",
     *   summary="Get csrf token for forget password form",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=403, description="Unauthorized"),
     * )
     * @OA\Post(
     *   tags={"Auth"},
     *   path="/api/v1/auth/forgetPassword",
     *   summary="Send forget password email for reset password.",
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
     *                   property="email",
     *                   description="User's email",
     *                   type="string",
     *                   example="booking@test.com"
     *               ),
     *               @OA\Property(
     *                   property="reset_url",
     *                   description="Reset password url link for frontend side",
     *                   type="string",
     *                   example="https://bookingsystem/reset"
     *               ),
     *               @OA\Property(
     *                   property="form_id",
     *                   description="CSRF form_id",
     *                   type="string",
     *                   example="form_id"
     *               ),
     *               @OA\Property(
     *                   property="form_build_id",
     *                   description="CSRF form_build_id",
     *                   type="string",
     *                   example="form_build_id"
     *               ),
     *               @OA\Property(
     *                   property="form_token",
     *                   description="CSRF form_token",
     *                   type="string",
     *                   example="form_token"
     *               ),
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=400, description="Invalid Credidentials")
     * )
     */
    public function forgetPassword()
    {
        return $this->processForm(
            new ApiForgetPasswordForm()
        );
    }

    /**
     * @OA\Get(
     *   tags={"Auth"},
     *   path="/api/v1/auth/resetPassword",
     *   summary="Get csrf token for reset password form",
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=403, description="Unauthorized"),
     *   @OA\Parameter(
     *      name="USER",
     *      in="query",
     *      required=true,
     *      description="User's ID",
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="KEY",
     *      in="query",
     *      required=true,
     *      description="Reset Password Key",
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     * )
     * @OA\Post(
     *   tags={"Auth"},
     *   path="/api/v1/auth/resetPassword",
     *   summary="Send forget password email for reset password.",
     *   @OA\Parameter(
     *      name="Bearer",
     *      in="header",
     *      required=true,
     *      description="Bearer token",
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="USER",
     *      in="query",
     *      required=true,
     *      description="User's ID",
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="KEY",
     *      in="query",
     *      required=true,
     *      description="Reset Password Key",
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
     *                   property="password",
     *                   description="Password",
     *                   type="string",
     *                   example="Aa.12345"
     *               ),
     *               @OA\Property(
     *                   property="password2",
     *                   description="Password again",
     *                   type="string",
     *                   example="Aa.12345"
     *               ),
     *               @OA\Property(
     *                   property="form_id",
     *                   description="CSRF form_id",
     *                   type="string",
     *                   example="form_id"
     *               ),
     *               @OA\Property(
     *                   property="form_build_id",
     *                   description="CSRF form_build_id",
     *                   type="string",
     *                   example="form_build_id"
     *               ),
     *               @OA\Property(
     *                   property="form_token",
     *                   description="CSRF form_token",
     *                   type="string",
     *                   example="form_token"
     *               ),
     *           )
     *       )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=400, description="Invalid Credidentials")
     * )
     */
    public function resetPassword()
    {
        $form = null;
        if (!$_GET) {
            throw new Exception(Translation::getTranslation("link_used"));
        } elseif (isset($_GET["USER"]) && isset($_GET["KEY"])) {
            $reset_password_queue = ResetPassword::get(["user" => $_GET["USER"], "key" => $_GET["KEY"]]);
            if (!$reset_password_queue) {
                throw new Exception(Translation::getTranslation("link_used"));
            } else {
                $userClass = ConfigurationManager::getInstance()->getEntityInfo("users")["class"];
                $user = $userClass::get($_GET["USER"]);
                $form = new ApiResetPasswordForm($user);
                $form->processForm();
            }
        }
        if (!$form) {
            $form = new ApiResetPasswordForm();
        }
        return $this->processForm($form);
    }
}
