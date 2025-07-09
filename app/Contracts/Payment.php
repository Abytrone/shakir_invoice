<?php

namespace App\Contracts;

abstract class Payment
{
    abstract function initialize();
    abstract function process();
    abstract function verify();

}
