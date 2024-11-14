<?php

spl_autoload_register(function ($class) {
    // Prefix for PHPWord classes
    $prefix = 'PhpOffice\\PhpWord\\';
    // Base directory where PhpWord is located
    $baseDir = __DIR__ . '/PHPWord/src/PhpWord/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relativeClass = substr($class, $len);

    // Map the relative class name to a file path, replacing namespace separators with directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Include the class file if it exists
    if (file_exists($file)) {
        require $file;
    } else {
        echo "Class file not found: $file";
    }
});