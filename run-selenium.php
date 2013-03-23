#!/usr/bin/php -q
<?php

/*

Make executable:
chmod +x run-selenium.php

Look at arguments:
./run-selenium.php

Run against Google:
./run-selenium.php ./tests/ ./junit-reports/ http://www.google.com/ selenium.server.com 4444

*/


require __DIR__ . '/services.php';

// Load arguments from the shell.
$opts = array();
$opts['h'] = '                       = Help (this page)';
$opts['p:'] = ' [port]                = Selenium server port. "random" for random port number.';
$opts['t:'] = ' [dir/file]            = Path to test directory(/file for single test). This is where tests are discovered.';
$opts['r:'] = ' [dir]                 = Path to result directory (any files already in directory will be deleted!).';
$opts['u:'] = ' [url]                 = URL to website (The website to run the tests against)';
$opts['o:'] = ' [output type]         = What to output';
$longopts = array();
$longopts ['host:'] = ' [ip/host]         = Selenium server hostname or ip';

$opt_string = '';
foreach ($opts as $key => $option) {
    $opt_string .= $key;
}
$longopts2 = array();
foreach ($longopts as $key => $option) {
    $longopts2[] = $key;
}
$options = getopt($opt_string, $longopts2);

// Set defaults
$base_url = null; // The default host to run the tests against
$selenium_host = '127.0.0.1'; // The default hostname for Selenium RC
$selenium_port = 4444; // The default port number for Selenium RC
$tests_directory = __DIR__ . '/tests'; // The default location of tests
$results_directory = __DIR__ . '/results'; // The default location to put results directory
// Other options
$output_type = 'full'; // What output to show

// Override defaults with user input
if (isset($options['h'])) {
    foreach ($opts as $key => $option) {
        echo '-' . str_replace(':', '', $key) . ' ' . $option . chr(10);
    }
    foreach ($longopts as $key => $option) {
        echo '--' . str_replace(':', '', $key) . ' ' . $option . chr(10);
    }
    exit;
}

if (isset($options['u'])) $base_url = $options['u'];
if (isset($options['host'])) $selenium_host = $options['host'];
if (isset($options['o'])) $output_type = $options['o'];
if (isset($options['p'])) {
    if ($options['p'] == 'random' || $options['p'] == 'rnd' || $options['p'] == 'rand')
        $selenium_port = rand(10000, 20000);
    else
        $selenium_port = $options['p'];
}
if (isset($options['t'])) {
    $tests_directory = $options['t'];
    if (strpos($tests_directory, '/') === false) $tests_directory = __DIR__ . '/' . $tests_directory;
}
if (isset($options['r'])) {
    $results_directory = $options['r'];
    if (strpos($results_directory, '/') === false) $results_directory = __DIR__ . '/' . $results_directory;
}

$results_directory = realpath($results_directory);

// Check output type
if ($output_type == 'simple') {
    $output_startup_settings = false;
    $output_tests_failed = true;
    $output_tests_success = false;
    $output_tests_skipped = false;
    $output_startup_services = false;
} else {
    // -> $output_type = full
    $output_startup_settings = true;
    $output_tests_failed = true;
    $output_tests_success = true;
    $output_tests_skipped = true;
    $output_startup_services = true;
}

echo PHP_EOL;
echo '####################################################################' . PHP_EOL;
echo '## Running Selenium tests' . PHP_EOL;
echo '####################################################################' . PHP_EOL;


if (is_null($base_url)) {
    echo PHP_EOL . PHP_EOL . 'Error: Missing base url' . PHP_EOL;
    exit(1);
}

// Check if test directory or file exists, exit if not
if (!is_dir($tests_directory) && !file_exists($tests_directory)) {
    echo 'FATAL ERROR: Test directory or file does not exist, exiting...' . PHP_EOL;
    exit(1);
}

// Prepare a clean environment.
if ($output_startup_settings) {
    echo "base_url:          $base_url\n";
    echo "selenium_host:     $selenium_host\n";
    echo "selenium_port:     $selenium_port\n";
    echo "tests_directory:   $tests_directory\n";
    echo "results_directory: $results_directory\n";
}

error_reporting(E_ALL);

// Ensure a clean destination for results exists.
exec('rm -rf "' . $results_directory . '"');
mkdir($results_directory);

mkdir($results_directory . '/xml');
mkdir($results_directory . '/video');
mkdir($results_directory . '/screenshots');

// Start job-wide services.
$selenium_is_running = selenium_is_running($selenium_host, $selenium_port);
$xvfb = NULL;
$selenium = NULL;
if ($selenium_is_running) {
    if ($output_startup_services) {
        echo 'Selenium is already running. Using the existing service.' . PHP_EOL;
    }
    $selenium = new SeleniumExternalService($selenium_host, $selenium_port);
} else {
    if ($output_startup_services) {
        echo 'Selenium is not running. Starting a new, local service.' . PHP_EOL;
    }
    // Use the Selenium port for the X display number, too.
    $x_display_number = $selenium_port;
    $xvfb = new XvfbBackgroundService($x_display_number, 1200, 2000);
    $selenium = new SeleniumBackgroundService($xvfb, $selenium_port);
}

// Run tests.
$tests = selenium_get_all_tests($tests_directory, $selenium, $base_url);
if (!empty($tests)) {
    foreach ($tests as $test) {
        echo PHP_EOL;
        echo '####################################################################' . PHP_EOL;
        echo '## Running test: ' . $test->getTestClassName() . PHP_EOL;
        echo '####################################################################' . PHP_EOL;

        $junit_file = realpath($results_directory) . '/xml/' . $test->getTestClassName() . '.xml';

        // Record a screencast if there's a valid X buffer.
        if (isset($xvfb)) {
            $screencast_file = $results_directory . '/video/' . $test->getTestClassName() . '.mp4';
            $screencast = new ScreencastBackgroundService($xvfb, $screencast_file);
        }


        // -------------------------------------------------------------
        // ------------- Run the test and store the output -------------
        // -------------------------------------------------------------

        $test->run($junit_file);

        // -------------------------------------------------------------


        // Stop the screencast if one is active.
        if (isset($screencast)) {
            unset($screencast);
        }

        // Unload the test.
        unset($test);
    }
} else {
    echo 'No tests found.' . PHP_EOL;
}
