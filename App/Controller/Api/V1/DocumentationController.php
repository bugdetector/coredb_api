<?php

namespace App\Controller\Api\V1;

use App\Theme\AppTheme;
use CoreDB\Kernel\BaseController;
use Src\Theme\ThemeInteface;

class DocumentationController extends BaseController
{
    public function checkAccess(): bool
    {
        return \CoreDB::currentUser()->isAdmin();
    }

    public function getTheme(): ThemeInteface
    {
        return new AppTheme();
    }

    public function getTemplateFile(): string
    {
        return "page-documentation.twig";
    }

    public function preprocessPage()
    {
        $this->setTitle("Api Documentation");
        $this->addJsFiles([
            "libraries/swagger/js/swagger-ui-bundle.js",
            "libraries/swagger/js/swagger-ui-standalone-preset.js",
            "libraries/swagger/js/swagger.js",
        ]);
        $this->addCssFiles([
            "libraries/swagger/css/swagger-ui.css",
            "libraries/swagger/css/swagger.css"
        ]);
    }
}
