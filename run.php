<?php

try {
    $isLoaded = extension_loaded('exif');
    if (!$isLoaded) {
        throw new Exception('Exif extension is required.');
    }
} catch (Throwable $exception) {
    $message = $exception->getMessage() . PHP_EOL;
    die($message);
}

$shortOpts = 'd:';
$longOpts = ['directory:'];
$options = getopt($shortOpts, $longOpts);
$directory = $options['d'] ?? $options['directory'] ?? getcwd() . '/gps_images';

try {
    $directoryExists = file_exists($directory);
    if (!$directoryExists) {
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

try {
    $filesExist = count($acceptedFiles) > 0;
    if (!$filesExist) {
        throw new Exception('No files found.');
    }
} catch (Throwable $exception) {
    $message = $exception->getMessage() . PHP_EOL;
    die($message);
}

foreach($acceptedFiles as $filePath) {
    $exifData = exif_read_data($filePath);
}
