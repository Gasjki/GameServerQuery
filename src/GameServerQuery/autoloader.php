<?php

spl_autoload_register(function ($class) {
    $prefix        = 'GameServerQuery\\';
    $baseDirectory = __DIR__ . DIRECTORY_SEPARATOR;

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    $relativeClass = substr($class, strlen($prefix));

    $file = $baseDirectory . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});