<?php

namespace App\Form;

use Src\Form\Form;

abstract class ApiFormAbstract extends Form
{
    abstract public function getResponse();
}
