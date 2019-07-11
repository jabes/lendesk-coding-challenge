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

$acceptedFiles = [];
$acceptedFileExtensions = [
    'jpeg',
    'jpg',
];

try {
    $directoryIterator = new RecursiveDirectoryIterator($directory);
    $fileIterator = new RecursiveIteratorIterator($directoryIterator);
    foreach($fileIterator as $file) {
        if ($file instanceof SplFileInfo) {
            $fileExtension = $file->getExtension();
            $isAcceptedExtension = in_array($fileExtension, $acceptedFileExtensions);
            if ($file->isFile() && $isAcceptedExtension) {
                $acceptedFiles[] = $file->getRealPath();
            }
        } else {
            throw new Exception('Unexpected object type.');
        }
    }
} catch (Throwable $exception) {
    $message = $exception->getMessage() . PHP_EOL;
    die($message);
}
