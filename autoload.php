<?php

$loader = require __DIR__.'/vendor/autoload.php';

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('', __DIR__.'/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs');
}

return $loader;
