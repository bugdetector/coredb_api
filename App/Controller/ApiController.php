<?php

namespace App\Controller;

use CoreDB\Kernel\ServiceController;

/**
 * @OA\Info(
 *     title="CoreDB Api",
 *     version="0.1"
 * )
 */
class ApiController extends ServiceController
{
    public function __construct(array $arguments)
    {
        header('Access-Control-Allow-Origin: ' . FRONTEND_URL);
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                // may also be using PUT, PATCH, HEAD etc
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            }

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }

            exit(0);
        }
        parent::__construct($arguments);
    }
}
