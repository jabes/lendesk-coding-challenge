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

$gpsData = [];

foreach($acceptedFiles as $filePath) {
    $exifData = exif_read_data($filePath);
    $longitude = $exifData['GPSLongitude'] ?? null;
    $longitudeRef = $exifData['GPSLongitudeRef'] ?? null;
    $latitude = $exifData['GPSLatitude'] ?? null;
    $latitudeRef = $exifData['GPSLatitudeRef'] ?? null;
    if ($longitude && $longitudeRef && $latitude && $latitudeRef) {
        $gpsData[] = [
            'longitude'        => getGps($longitude, $longitudeRef),
            'latitude'         => getGps($latitude, $latitudeRef),
            'exifLongitude'    => serialize($longitude),
            'exifLatitude'     => serialize($latitude),
            'exifLongitudeRef' => $longitudeRef,
            'exifLatitudeRef'  => $latitudeRef,
        ];
    }
}

function getGps(array $coordinates, string $hemisphere) {
    $degrees = count($coordinates) > 0 ? convertCoordinate($coordinates[0]) : 0;
    $minutes = count($coordinates) > 1 ? convertCoordinate($coordinates[1]) : 0;
    $seconds = count($coordinates) > 2 ? convertCoordinate($coordinates[2]) : 0;
    $flip = ($hemisphere == 'W' or $hemisphere == 'S') ? -1 : 1;
    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
}

function convertCoordinate(string $coordinate) {
    $parts = explode('/', $coordinate);
    if (count($parts) <= 0) return 0;
    if (count($parts) == 1) return $parts[0];
    return floatval($parts[0]) / floatval($parts[1]);
}
