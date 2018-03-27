<?php

class DataLayer
{
    private $app;
    private $recordSize = 8;
    
    public function __construct($app) {
        $this->app = $app;
    }
    
    // We can change the underlying storage layer (e.g. from filesystem to database to AWS) here without affecting anything else.
    // We expect the data to come through as an array after being processed in DataLayer::parseResponse.
    public function save(array $data) {
        if (isset($data) && count($data) === $this->recordSize) { // Verify that the expected number of indices are in our data array.
            try {
                // Overwrite a unique file with the latest data, e.g. 12345.txt and append to a file shared between all locations.
                $output = implode("|", $data) . PHP_EOL;
                file_put_contents("data/{$data['zip']}.txt", $output);
                file_put_contents("data/weatherlog.txt", $output, FILE_APPEND | LOCK_EX);
                return true;
            }
            catch (Exception $e) {
                echo "We were unable to save the data to file.\n";
            }
        }
        else {
            echo "There was a problem with the data array and the record will not be stored.\n";
        }
    }
    
    public function parseResponse($data, $type = "JSON") {
        if ($data) {
            if ($type === "JSON") {
                // Split to get the zip code and the data because the API doesn't include zip code in its response.
                $parts = explode("|", $data);
                if ($parts[0] && $parts[1]) { // Make sure our record is complete before trying to parse it.
                    try {
                        $json = json_decode($parts[1], true);
                        $weather = $json['weather'][0];
                        $main = $json['main'];
                        $wind = $json['wind'];
                        $zip = $parts[0];
                        $conditions = $weather['description']; // Sunny, rainy, etc.
                        $pressure = $main['pressure'];
                        $temperature = $this->kelvinToFahrenheit($main['temp']); // Kelvin to Fahrenheit.
                        $windDirection = $this->degreesToDirection($wind['deg']); // Degrees to direction.
                        $windSpeed = $wind['speed'];
                        $humidity = $main['humidity'];
                        $timestamp = $json['dt'];
                        return [
                            'zip' => $zip,
                            'conditions' => $conditions,
                            'pressure' => $pressure,
                            'temperature' => $temperature,
                            'windDirection' => $windDirection,
                            'windSpeed' => $windSpeed,
                            'humidity' => $humidity,
                            'timestamp' => $timestamp
                        ];
                    }
                    catch (Exception $e) {
                        echo "Caught an exception while parsing API response.\n";
                    }
                }
            }
            elseif ($type === "XML") {
                // XML Parsing Logic
            }
            elseif ($type === "CSV") {
                // CSV Parsing Logic
            }
            else {
                echo "Unsupported data format specified. Use JSON, XML or CSV.\n";
            }
        }
        else {
            echo "Failed to parse the response.";
        }
    }
    
    // https://www.rapidtables.com/convert/temperature/how-kelvin-to-fahrenheit.html
    public function kelvinToFahrenheit($degrees) : int {
        if ($degrees) {
            return round(($degrees * (9 / 5)) - 459.67);
        }
    }
    
    // https://stackoverflow.com/questions/7490660/converting-wind-direction-in-angles-to-text-words
    public function degreesToDirection($degrees) : string {
        if (isset($degrees)) {
            $i = ($degrees / 22.5) + 0.5;
            $d = ["N","NNE","NE","ENE","E","ESE", "SE", "SSE","S","SSW","SW","WSW","W","WNW","NW","NNW"];
            return $d[$i % 16];
        }
    }
}
