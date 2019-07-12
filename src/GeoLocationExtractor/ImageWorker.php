<?php declare(strict_types=1);

namespace GeoLocationExtractor;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Exception;

class ImageWorker
{
    private const VALID_FILE_EXTENSIONS = [
        'jpeg',
        'jpg',
    ];

    private $directory;
    private $validFiles = [];
    private $gpsData = [];

    /**
     * ImageWorker constructor.
     *
     * @param string $directory
     *
     * @throws \Exception
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;

        $directoryExists = file_exists($this->directory);
        if (!$directoryExists) {
            throw new Exception('Directory does not exist.');
        }

        $isLoaded = extension_loaded('exif');
        if (!$isLoaded) {
            throw new Exception('Exif extension is required.');
        }
    }

    /**
     * Retrieves a list of all valid images found within the provided directory.
     *
     * @throws \Exception
     */
    public function getValidPaths(): void
    {
        echo "Looking for images in: {$this->directory}" . PHP_EOL;

        $directoryIterator = new RecursiveDirectoryIterator($this->directory);
        $fileIterator = new RecursiveIteratorIterator($directoryIterator);

        foreach ($fileIterator as $file) {
            if ($file instanceof SplFileInfo) {
                $fileExtension = $file->getExtension();
                $isValidExtension = in_array($fileExtension, ImageWorker::VALID_FILE_EXTENSIONS);
                if ($file->isFile() && $isValidExtension) {
                    $this->validFiles[] = $file->getRealPath();
                }
            } else {
                throw new Exception('Unexpected object type.');
            }
        }

        sort($this->validFiles, SORT_STRING);

        foreach ($this->validFiles as $filePath) {
            echo "Found image: {$filePath}" . PHP_EOL;
        }
    }

    /**
     * Recurse over images and retrieve their geolocation data from exif.
     *
     * @throws \Exception
     */
    public function getGeoLocationData(): void
    {
        $filesExist = count($this->validFiles) > 0;
        if (!$filesExist) {
            throw new Exception('No files found.');
        }

        foreach ($this->validFiles as $filePath) {
            $filename = str_replace("{$this->directory}/", '', $filePath);
            $exifData = exif_read_data($filePath);

            $longitude = $exifData['GPSLongitude'] ?? [];
            $latitude = $exifData['GPSLatitude'] ?? [];
            $longitudeRef = $exifData['GPSLongitudeRef'] ?? '';
            $latitudeRef = $exifData['GPSLatitudeRef'] ?? '';

            if ($longitude && $longitudeRef && $latitude && $latitudeRef) {

                $gpsData = [
                    'filename'     => $filename,
                    'lonDecimal'   => $this->getDecimalCoordinate($longitude, $longitudeRef),
                    'lonFormatted' => $this->getFormattedCoordinate($longitude, $longitudeRef),
                    'latDecimal'   => $this->getDecimalCoordinate($latitude, $latitudeRef),
                    'latFormatted' => $this->getFormattedCoordinate($latitude, $latitudeRef),
                ];

                $this->gpsData[$filePath] = $gpsData;

                echo $filename . ' => ' . $gpsData['latFormatted'] . ' ' . $gpsData['lonFormatted'] . PHP_EOL;
            } else {
                echo $filename . ' => No geolocation found.' . PHP_EOL;
            }
        }
    }

    /**
     * Writes all geolocation data to a csv file.
     *
     * @throws \Exception
     */
    public function outputToFile(): void
    {
        $dataExist = count($this->gpsData) > 0;
        if (!$dataExist) {
            throw new Exception('No gps data was found.');
        }

        $outputFile = getcwd() . '/output.csv';
        $filePointer = fopen($outputFile, 'w');

        $headers = array_keys(reset($this->gpsData));
        fputcsv($filePointer, $headers);

        foreach ($this->gpsData as $fields) {
            $fields = array_values($fields);
            fputcsv($filePointer, $fields);
        }

        fclose($filePointer);

        echo "Output file: {$outputFile}" . PHP_EOL;
    }

    /**
     * Converts degrees, minutes, seconds (DMS) coordinates into decimal degrees (dd).
     *
     * @param array  $coordinates
     * @param string $hemisphere
     *
     * @return float
     */
    private function getDecimalCoordinate(array $coordinates, string $hemisphere): float
    {
        $degrees = count($coordinates) > 0 ? $this->convertCoordinate($coordinates[0]) : 0;
        $minutes = count($coordinates) > 1 ? $this->convertCoordinate($coordinates[1]) : 0;
        $seconds = count($coordinates) > 2 ? $this->convertCoordinate($coordinates[2]) : 0;

        $flip = ($hemisphere == 'W' or $hemisphere == 'S') ? -1 : 1;

        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
    }

    /**
     * Converts degrees, minutes, seconds (DMS) coordinates into a standard format for humans.
     * Format: d° m' s" {N|S}, d° m' s" {E|W}
     *
     * @param array  $coordinates
     * @param string $hemisphere
     *
     * @return string
     */
    private function getFormattedCoordinate(array $coordinates, string $hemisphere): string
    {
        $degrees = count($coordinates) > 0 ? $this->convertCoordinate($coordinates[0]) : 0;
        $minutes = count($coordinates) > 1 ? $this->convertCoordinate($coordinates[1]) : 0;
        $seconds = count($coordinates) > 2 ? $this->convertCoordinate($coordinates[2]) : 0;

        return "{$degrees}\u{00B0} {$minutes}\u{0027} {$seconds}\u{0022} {$hemisphere}";
    }

    /**
     * Decimals are represented by two values, the number and the decimal place.
     * This function will parse these values and provide the decimal number.
     * Ex: 12345/100 -> 123.45
     *
     * @param string $coordinate
     *
     * @return float
     */
    private function convertCoordinate(string $coordinate): float
    {
        $parts = explode('/', $coordinate);
        $decimalNumber = floatval($parts[0]);
        $decimalPlace = floatval($parts[1]);

        return $decimalNumber / $decimalPlace;
    }
}
