<?php

use App\Kernel;

$_SERVER['APP_ENV'] = 'prod';
$_SERVER['APP_DEBUG'] = '1';

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
