<?php

class OpenWeatherMapAPI
{
    private $app;
    public $key;
    
    public function __construct($app) {
        $this->app = $app;
        $this->key = $this->app->config['system']['owmapikey'];
    }
    
    // Get data for a particular zip code from the OpenWeatherMap API.
    // I've saved a sample response for reference in data/sampleresponse.json
    public function getWeatherData($zip) {
        if (strlen($zip) === 5) {
            try {
                // We could use CURL but I wanted this to run on the PHP CLI without Apache.
                // We should see a default timeout setting of 60 seconds for this request.
                // We prepend the zip code to use later because the API doesn't include it in its response.
                $url = "http://api.openweathermap.org/data/2.5/weather?appid={$this->key}&zip={$zip},US";
                $data = "{$zip}|" . file_get_contents($url);
                
                // If we get data back we can return it for parsing.
                // Using CURL we'd want to verify the HTTP codes indicated the response was OK.
                if ($data) {
                    return $data;
                }
                else {
                    echo "There was a problem with the request.\n";
                }
            }
            catch (Exception $e) {
                echo "Caught an exception while requesting data from OpenWeatherMap API.\n";
            }
        }
    }
}
