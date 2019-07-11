<?php

declare(strict_types = 1);

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
    $longitude = $exifData['GPSLongitude'] ?? [];
    $longitudeRef = $exifData['GPSLongitudeRef'] ?? '';
    $latitude = $exifData['GPSLatitude'] ?? [];
    $latitudeRef = $exifData['GPSLatitudeRef'] ?? '';
    if ($longitude && $longitudeRef && $latitude && $latitudeRef) {
        $gpsData[$filePath] = [
            'longitude'      => getDecimalCoordinate($longitude, $longitudeRef),
            'latitude'       => getDecimalCoordinate($latitude, $latitudeRef),
            'humanLongitude' => getFormattedCoordinate($longitude, $longitudeRef),
            'humanLatitude'  => getFormattedCoordinate($latitude, $latitudeRef),
        ];
    }
}

function getDecimalCoordinate(array $coordinates, string $hemisphere): float {
    $degrees = count($coordinates) > 0 ? convertCoordinate($coordinates[0]) : 0;
    $minutes = count($coordinates) > 1 ? convertCoordinate($coordinates[1]) : 0;
    $seconds = count($coordinates) > 2 ? convertCoordinate($coordinates[2]) : 0;
    $flip = ($hemisphere == 'W' or $hemisphere == 'S') ? -1 : 1;
    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
}

function getFormattedCoordinate(array $coordinates, string $hemisphere): string {
    $degrees = count($coordinates) > 0 ? convertCoordinate($coordinates[0]) : 0;
    $minutes = count($coordinates) > 1 ? convertCoordinate($coordinates[1]) : 0;
    $seconds = count($coordinates) > 2 ? convertCoordinate($coordinates[2]) : 0;
    return "{$degrees}\u{00B0} {$minutes}\u{0027} {$seconds}\u{0022} {$hemisphere}";
}

function convertCoordinate(string $coordinate): float {
    $parts = explode('/', $coordinate);
    if (count($parts) <= 0) return 0;
    if (count($parts) == 1) return $parts[0];
    return floatval($parts[0]) / floatval($parts[1]);
}

try {
    $dataExist = count($gpsData) > 0;
    if (!$dataExist) {
        throw new Exception('No gps data was found.');
    }
} catch (Throwable $exception) {
    $message = $exception->getMessage() . PHP_EOL;
    die($message);
}

$filePointer = fopen('output.csv', 'w');

$headers = array_keys(reset($gpsData));
fputcsv($filePointer, $headers);

foreach ($gpsData as $fields) {
    $fields = array_values($fields);
    fputcsv($filePointer, $fields);
}

fclose($filePointer);
