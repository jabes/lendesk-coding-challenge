<?php

$short_opts = 'd:';
$long_opts = ['directory:'];
$options = getopt($short_opts, $long_opts);

$directory = $options['d'] ?? $options['directory'] ?? getcwd() . '/gps_images';

try {
    $directory_exists = file_exists($directory);
    if (!$directory_exists) {
        throw new Exception('Directory does not exist.');
    }
} catch (Throwable $exception) {
    $message = $exception->getMessage() . PHP_EOL;
    die($message);
}
