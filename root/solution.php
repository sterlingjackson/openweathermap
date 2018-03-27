<?php

/*
EXERCISE
- Create an application that will track current weather measurements for a given set of zip codes.
- Store the following data at a minimum:
  - Zip code
  - General weather conditions (e.g. sunny, rainy, etc)
  - Atmospheric pressure
  - Temperature (in Fahrenheit)
  - Winds (direction and speed)
  - Humidity
  - Timestamp (in UTC)
  
REQUIREMENTS
- The application should be able to recover from any errors encountered.
- The application should be developed using a TDD approach. 100% code coverage is not required.
- The set of zip codes and their respective retrieval frequency should be contained in configuration file.
- Use the OpenWeatherMap API for data retrieval (https://openweathermap.org).

SUMMARY
1. Create tests to drive TDD approach and define the general structure of the application.
2. Determine which classes need to be created and define our data structure.
3. Pull sample data from OpenWeatherMap API to determine parsing/transformation steps.
4. Create parser to transform the API response and send it to our storage method.
5. NOTE: OpenWeatherMap suggests only making 1 request per 10 minutes or risk account closure so we will need to be careful.
*/


// Set to true to run unit tests; false to run application.
$runTests = false;

// Autoload our application classes from the classes directory.
spl_autoload_register(function ($class) {
    if (file_exists("classes/{$class}.php")) {
        try {
            include "classes/{$class}.php";
        }
        catch (Exception $e) {
            // Autoload exceptions aren't passed by design, we'll create a unit test instead.
        }
    }
});


// We could also create a runAllTests() function but calling them individually lets us pick and choos which tests to run.
if ($runTests === true) {
    $t = new Test();
    
    try {
        $t->testConfigExists(); // Verify that our configuration file exists and is readable.
        $t->testClassesExist(); // Verify that all of our classes have been loaded.
        $t->testSampleData(); // Verify that the sample data exists and is the right length.
        $t->testReadWrite(); // Verify that we can read/write to the data directory.
        //$t->testOpenWeatherMapAPI(); // Verify that we can read/write to the data directory. Be careful running this one too frequently.
        $t->testParseResponse(); // Verify that our parsing method is working.
        $t->testDegreesToDirection(); // Verify that our algorithm is accurate.
        $t->testSave(); // Attempt to save our test record to file.
        $t->showTestResults(); // Output the total/pass/fail results for all tests.
    }
    catch (Exception $e) {
        echo "Caught exception: {$e->getMessage()}\n";
    }
}
else {
    // Start our application.
    $app = new Application();
    $api = new OpenWeatherMapAPI($app);
    $dl = new DataLayer($app);
    
    // Our application needs to run in a loop, polling for new data periodically.
    // We will override the default execution timeout and create a loop that will run for the specified duration.
    $duration = 60; // Duration in minutes.
    $timeout = $duration * 60; // Timeout in seconds.
    ini_set('max_execution_time', $timeout); // Override default execution timeout.
    for ($i = 0; $i <= $duration; $i++) {
        // Our loop is evaluated once every minute. We could check more frequently but OpenWeatherMap discourages this.
        echo "(Loop #{$i})\n";
        foreach ($app->config['locations'] as $key => $val) {
            // Modulo $i by each location's retrieval setting and trigger if the result is 0.
            // If the schedule is every 15 minutes, we expect it to run at 0, 15, 30, 45, 60, 75...
            try {
                if ($i % $val === 0) {
                    //echo "$i: $key - $val\n";
                    echo "Getting current weather for {$key}\n";
                    $result = $api->getWeatherData($key);
                    $data = $dl->parseResponse($result, "JSON");
                    $save = $dl->save($data);
                }
            }
            catch (Exception $x) {
                echo "An error occurred.\n"; // Could build a smarter scheduler that retries a failed lookup instead of waiting until the next interval.
            }
        }
        sleep(60); // Wait for 1 minute before next interval.
    }
}
