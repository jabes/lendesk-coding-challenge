<?php declare(strict_types=1);

use GeoLocationExtractor\ImageWorker;

spl_autoload_register(function (string $class): void {
    $filename = str_replace('\\', '/', $class);
    include "src/{$filename}.php";
});

$shortOpts = 'd:';
$longOpts = ['directory:'];
$options = getopt($shortOpts, $longOpts);
$directory = $options['d'] ?? $options['directory'] ?? 'gps_images';
$directory = realpath($directory);

try {
    $imageWorker = new ImageWorker($directory);
    $imageWorker->getValidPaths();
    $imageWorker->getGeoLocationData();
    $imageWorker->outputToFile();
} catch (Throwable $exception) {
    $message = $exception->getMessage() . PHP_EOL;
    die($message);
}
