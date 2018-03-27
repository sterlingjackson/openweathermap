<?php
// This is a simple class for handling unit testing and summarizing the results of our tests.
// By determining desired results first we get a good idea about how the rest of the application should behave.
class Test
{
    public $tests = 0;
    public $pass = 0;
    public $fail = 0;
    private $classes = ['Test', 'OpenWeatherMapAPI', 'DataLayer'];
    private $sampleData = '37931|{"coord":{"lon":-84.12,"lat":35.99},"weather":[{"id":800,"main":"Clear","description":"clear sky","icon":"01n"}],"base":"stations","main":{"temp":274.81,"pressure":1031,"humidity":80,"temp_min":274.15,"temp_max":276.15},"visibility":16093,"wind":{"speed":1.06,"deg":103},"clouds":{"all":1},"dt":1518144900,"sys":{"type":1,"id":2534,"message":0.0036,"country":"US","sunrise":1518179389,"sunset":1518217921},"id":0,"name":"Knoxville","cod":200}';
    private $sampleDataLength = 448;
    private $app;
    private $api;
    private $dl;
    
    public function __construct() {
      $this->app = new Application();
      $this->api = new OpenWeatherMapAPI($this->app);
      $this->dl = new DataLayer($this->app);
    }
    
    // Check that all of our configuration file exists and is correctly loaded.
    // Our config file should live outside of the document root especially if it contains sensitive data that could be
    // compromised in the case of a permissions issue. If we put credentials in a PHP script and the server outputs 
    // the script as plain text then we've given away the keys to the city.
    public function testConfigExists() {
        if (isset($this->app->config) && sizeof($this->app->config) > 0) {
            $this->pass("The configuration file has been loaded.");
        }
        else {
            $this->fail("The configuration file was not loaded or is empty.");
        }
    }
    
    // Check that all of our classes exist as defined in the $classes array.
    public function testClassesExist() {
        foreach($this->classes as $class) {
            if (class_exists($class)) {
                $this->pass("$class was loaded.");
            }
            else {
                $this->fail("$class was not loaded.");
            }
        }
    }
    
    // Does the sample data exist and is it the expected length?
    public function testSampleData() {
        if (isset($this->sampleData) && strlen($this->sampleData) === $this->sampleDataLength) {
            $this->pass("The sample data exists and appears to be the correct length.");
        }
        else {
            $this->fail("The sample data is missing or damaged.");
        }
    }
    
    // Verify that we can read and write the /data directory.
    public function testReadWrite() {
        if (file_exists("data/testreadwrite.txt")) {
            unlink("data/testreadwrite.txt");
        }
        if (file_exists("data")) {
            file_put_contents("data/testreadwrite.txt", "1234567890");
            $input = file_get_contents("data/testreadwrite.txt");
            unlink("data/testreadwrite.txt");
        }
        if (isset($input) && $input === "1234567890") {
            $this->pass("We can read/write to the data directory.");
        }
        else {
            $this->fail("We are unable to read/write to the data directory.");
        }
    }
    
    // Verify that we can get a response from the OpenWeatherMap API.
    public function testOpenWeatherMapAPI() {
        $key = $this->api->key;
        if (isset($key) && strlen($key) === 32) {
            $response = $this->api->getWeatherData(37931);
            if ($response['data']) {
                $this->pass("We are able to get a response from the OpenWeatherMap API.");
            }
            else {
                $this->fail("We are unable to get a response from the OpenWeatherMap API.");
            }
        }
        else {
            $this->fail("We could not retrieve the API key.");
        }
    }
    
    // Test our JSON parsing method against our sample record.
    public function testParseResponse() {
        $data = $this->dl->parseResponse($this->sampleData, "JSON");
        $this->assertion($data['zip'], "37931", "Parsed zip should equal 37931.");
        $this->assertion($data['conditions'], "clear sky", "Parsed conditions should equal clear sky.");
        $this->assertion($data['pressure'], "1031", "Parsed pressure should equal 1031.");
        $this->assertion($data['temperature'], "35", "Parsed temperature should equal 35.");
        $this->assertion($data['windDirection'], "ESE", "Parsed wind direction should equal ESE.");
        $this->assertion($data['windSpeed'], "1.06", "Parsed wind speed should equal 1.06.");
        $this->assertion($data['humidity'], "80", "Parsed humidity should equal 80.");
        $this->assertion($data['timestamp'], "1518144900", "Parsed timestamp should equal 1518144900.");
    }
    
    // Verify that the degrees to compass direction calculation is producing accurate results.
    // http://snowfence.umn.edu/Components/winddirectionanddegreeswithouttable3.htm
    public function testDegreesToDirection() {
        $this->assertion($this->dl->degreesToDirection(100), "E", "100 degrees should equal E.");
        $this->assertion($this->dl->degreesToDirection(220), "SW", "220 degrees should equal SW.");
        $this->assertion($this->dl->degreesToDirection(340), "NNW", "340 degrees should equal NNW.");
        $this->assertion($this->dl->degreesToDirection(0), "N", "0 degrees should equal N.");
    }
    
    public function testSave() {
        $data = $this->dl->parseResponse($this->sampleData, "JSON");
        if ($this->dl->save($data)) {
            $this->pass("The sample record has been saved.");
        }
        else {
            $this->fail("The sample record could not be saved.");
        }
    }
    
    // Simple assertion testing method. I wrote this halfway through implementing tests but it genericizes the approach
    // and would probably work with minor adaptations for most of the tests that aren't using this method.
    public function assertion($actual, $expected, $description = "") {
        if ($actual == $expected) {
            $this->pass($description);
        }
        else {
            $this->fail($description);
        }
    }
    
    // Generic function for logging successful tests.
    public function pass($msg) {
        $this->tests++;
        $this->pass++;
        echo "[PASS] {$msg}\n";
    }
    
    // Generic function for logging failed tests.
    public function fail($msg) {
        $this->tests++;
        $this->fail++;
        echo "[FAIL] {$msg}\n";
    }
    
    // Summarize and display the results for the unit tests we ran.
    public function showTestResults() {
        echo "Tests: {$this->tests}\n";
        echo "Pass: {$this->pass}\n";
        echo "Fail: {$this->fail}\n";
    }
}
