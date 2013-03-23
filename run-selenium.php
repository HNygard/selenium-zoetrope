#!/usr/bin/php -q
<?php

require __DIR__ . '/services.php';
require __DIR__ . '/classes/Zoetrope_Result.php';
require __DIR__ . '/classes/Zoetrope_Result_Test.php';
require __DIR__ . '/classes/Zoetrope_Result_Testcase.php';
require __DIR__ . '/classes/Zoetrope_Result_Error.php';

Make executable:
chmod +x run-selenium.php

Look at arguments:
./run-selenium.php

Run against Google:
./run-selenium.php ./tests/ ./junit-reports/ http://www.google.com/ selenium.server.com 4444

*/



// Load arguments from the shell.
$opts = array();
$opts['h'] = '                       = Help (this page)';
$opts['b'] = '                       = Run in background (with video recording)';
$opts['c'] = '                       = Copy tests to results directory (useful for documenting what tests were used to produce results if tests are updated)';
$opts['m'] = '                       = Generate a mail friendly HTML report (mail.html) based on the mail_report.php template in addition to the traditional HTML report.' . PHP_EOL .
    '                            (Example for including it in Jenkins is to use Email-ext plugin and put ${FILE,path=path/to/zoetrope/results/mail.html} in "Default Content")';
$opts['p:'] = ' [port]                = Selenium server port. "random" for random port number.';
$opts['t:'] = ' [dir/file]            = Path to test directory(/file for single test). This is where tests are discovered.';
$opts['r:'] = ' [dir]                 = Path to result directory (any files already in directory will be deleted!).';
$opts['u:'] = ' [url]                 = URL to website (The website to run the tests against)';
$opts['e:'] = ' [url]                 = URL to external website hosting result videos';
$opts['g:'] = ' [group]               = Group of tests to run';
$opts['o:'] = ' [output type]         = What to output';
$longopts = array();
$longopts ['host:'] = ' [ip/host]         = Selenium server hostname or ip';
$longopts['cc:'] = '[url]                = URL to collect code coverage from' . chr(10) .
    '                            (see http://www.phpunit.de/manual/3.6/en/selenium.html).' . chr(10) .
    '                            Add $this->coverageScriptUrl = $GLOBALS[\'codecoverage_url\'] in setUp() for your test(s)';
$longopts['ss:'] = '[screenshot URL]     = URL to screenshots (for the user) - this needs to be specified in the tests somehow,' . PHP_EOL .
    '                            Example: $this->screenshotUrl = $GLOBALS[\'screenshot_url\'];' . PHP_EOL .
    '                            $this->screenshotPath = pathinfo($test_file, PATHINFO_DIRNAME);' . PHP_EOL .
    '                            $this->captureScreenshotOnFailure = TRUE;';
$longopts['browser:'] = '[browser]       = Selenium browser to use. Example: "*firefox" or "*firefox /path/to/firefoxexecutable".' . chr(10) .
    '                            See http://stackoverflow.com/questions/2569977/list-of-selenium-rc-browser-launchers';
$longopts['resolution:'] = '[resolution] = Set browser width and height, example: 1024x768';
$longopts['printer:'] = '[class]         = Set PHPUnit printer. Example: MyPHPUnitTestListener (extends PHPUnit_TextUI_ResultPrinter)';
$longopts['include-path:'] = '[path]     = Set PHPUnit include-path.';

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
$selenium_browser = '*firefox'; // The default browser for Selenium RC
$tests_directory = __DIR__ . '/tests'; // The default location of tests
$results_directory = __DIR__ . '/results'; // The default location to put results directory
$onscreen = true; // Pr. default run in foreground or background?
$copytests = false; // Pr. default copy tests to results directory?
$external_url = ''; // If videos are moved by a script to a different URL, use this option to set it, so result.html shows correct video
$group = ''; // Group of tests to run against
$screenshot_url = ''; // URL to screenshots (for the user)
// Other options
$res_width = 988; // Width of Xvfb desktop (and effectively also browser window)
$res_height = 1760; // Height of Xvfb desktop (and effectively also browser window)
$res_multi = 0.56; // Multiplier to determine web report video size
$single_test = '';
$output_type = 'full'; // What output to show
$codecoverage_url = ''; // URL for code coverage
$phpunit_printer = ''; // The default is not to give phpunit "--printer" (empty value)
$phpunit_includepath = ''; // The default is not to give phpunit "--include-path" (empty value)
$results_mail = ''; // The default is not to generate a mail report (empty value)

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
if (isset($options['g'])) $group = $options['g'];
if (isset($options['host'])) $selenium_host = $options['host'];
if (isset($options['b'])) $onscreen = false;
if (isset($options['c'])) $copytests = true;
if (isset($options['o'])) $output_type = $options['o'];
if (isset($options['cc'])) $codecoverage_url = $options['cc'];
if (isset($options['ss'])) $screenshot_url = $options['ss'];
if (isset($options['browser'])) $selenium_browser = $options['browser'];
if (isset($options['printer'])) $phpunit_printer = $options['printer'];
if (isset($options['include-path'])) $phpunit_includepath = $options['include-path'];
if (isset($options['resolution'])) {
    // Make sure resolution > 0x0 and no bigger than 9999x9999 (and valid)
    if (preg_match('#^[1,9]\d{0,3}x[1,9]\d{0,3}$#', $options['resolution'])) {
        $resolution = explode('x', $options['resolution']);
        $res_width = $resolution[0];
        $res_height = $resolution[1];
    } else {
        echo 'Warning: Invalid resolution format! Using default instead.' . PHP_EOL;
    }
}
if (isset($options['p'])) {
    if ($options['p'] == 'random' || $options['p'] == 'rnd' || $options['p'] == 'rand')
        $selenium_port = rand(10000, 20000);
    else
        $selenium_port = $options['p'];
}
if (isset($options['e'])) {
    $external_url = $options['e'];
    if (substr($external_url, -1) != '/') $external_url .= '/';
}
if (isset($options['t'])) {
    $tests_directory = $options['t'];
    if (strpos($tests_directory, '/') === false) $tests_directory = __DIR__ . '/' . $tests_directory;
}
if (isset($options['r'])) {
    $results_directory = $options['r'];
    if (strpos($results_directory, '/') === false) $results_directory = __DIR__ . '/' . $results_directory;
}

if (isset($options['m'])) $results_mail = $results_directory . '/mail.html';

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

// Check if single test or not
if (substr($tests_directory, -4) == '.php' && !is_dir($tests_directory)) {
    $single_test = end(explode('/', $tests_directory));
    if ($output_startup_settings) {
        echo 'SINGLE TEST=' . $single_test . PHP_EOL;
    }
    $tests_directory = dirname($tests_directory);
}

$results_file = $results_directory . '/result.html';

// Prepare a clean environment.
if ($output_startup_settings) {
    echo "base_url:          $base_url\n";
    echo "selenium_host:     $selenium_host\n";
    echo "selenium_port:     $selenium_port\n";
    echo "selenium_browser:  $selenium_browser\n";
    echo "onscreen:          $onscreen\n";
    echo "copytests:         $copytests\n";
    echo "tests_directory:   $tests_directory\n";
    echo "results_directory: $results_directory\n";
    echo "results_file:      $results_file\n";
    echo "results_mail:      $results_mail\n";
    echo "output type:       $output_type\n";
    echo "code coverage url: $codecoverage_url\n";
    echo "screenshot url:    $screenshot_url\n";
    echo "printer:           $phpunit_printer\n";
    echo "include-path:      $phpunit_includepath\n";
    echo 'error reporting:   E_ALL' . chr(10);
}

error_reporting(E_ALL);

// Ensure a clean destination for results exists.
exec('rm -rf "' . $results_directory . '"');
mkdir($results_directory);
exec('cp -R "' . __DIR__ . '/fancybox" "' . $results_directory . '"');

// Set path to stuff in the HTML report
if ($copytests) {
    $reltestpath = '';
} else {
    // Find a best guess relative path between results directory and tests directory to use for linking from result.html to tests
    $reltestpath = find_relative_path($results_directory, $tests_directory);
}

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
    if (!$onscreen) {
        // Use the Selenium port for the X display number, too.
        $x_display_number = $selenium_port;
        $xvfb = new XvfbBackgroundService($x_display_number, $results_directory . '/xvfb.log', $res_width, $res_height);
        $selenium = new SeleniumBackgroundService($xvfb, $selenium_port, $results_directory . '/selenium.log');
    } else {
        $selenium = new SeleniumForegroundService($selenium_port, $results_directory . '/selenium.log');
    }
}

// No tests have failed yet
$build_error = false;
$build_unstable = false;

// Run tests.
$tests = selenium_get_all_tests($tests_directory . '/' . $single_test, $selenium, $group, $base_url, $selenium_browser);
if (!empty($tests)) {

    $result = new Zoetrope_Result();
    $result->setTimeStart(time());
    $result->setBaseUrl($base_url);
    $result->setTestsDirectory($tests_directory);
    $result->setIsOnScreen($onscreen);
    $result->setExternalUrl($external_url);
    $result->setTestsDirectoryRelative($reltestpath);

    ob_start();

    // Vars used for e-mail stats
    $total_assertions = 0;
    $total_tests = 0;
    $assertion_failures = 0;
    $assertion_errors = 0;
    $tests_failed = 0;
    $tests_errored = 0;
    $tests_unstable = 0;
    $tests_skipped = 0;
    $tests_ok = 0;

    foreach ($tests as $test) {
        /* @var $test SeleniumTest */

        $testresult = new Zoetrope_Result_Testcase();
        $testresult->setClassName($test->getTestClassName());
        $testresult->setVideoWidth($res_width);
        $testresult->setVideoHeight($res_height);

        echo PHP_EOL;
        echo '####################################################################' . PHP_EOL;
        echo '## Running test: ' . $test->getTestClassName() . PHP_EOL;
        echo '####################################################################' . PHP_EOL;

        // Save JUnit XML and Testdox text to results dir
        $file_log_junit1 = $test->getTestClassName() . '.xml';
        $file_log_junit2 = $results_directory . '/' . $file_log_junit1;
        $file_testdox_text1 = $test->getTestClassName() . '_testdox.txt';
        $file_testdox_text2 = $results_directory . '/' . $file_testdox_text1;

        $testresult->setLogJunitXmlName($file_log_junit1);
        $testresult->setLogJunitXml($file_log_junit2);

        // Only start screen grab if we are not running in foreground (ie. user has started it "command line")
        if (!$onscreen) {
            // Record a screencast if there's a valid X buffer.
            if (isset($xvfb)) {
                $screencast_file = $results_directory . '/' . $test->getTestClassName() . '.ogg';
                $screencast = new ScreencastBackgroundService($xvfb, $screencast_file, $results_directory . '/' . $test->getTestClassName() . '_ffmpeg.log');
            }
        }

        $currenttime = time();


        // -------------------------------------------------------------
        // ------------- Run the test and store the output -------------
        // -------------------------------------------------------------

        $test->run($file_log_junit2, $file_testdox_text2);

        // -------------------------------------------------------------

        // Stop the screencast if one is active
        if (isset($screencast)) {
            unset($screencast);
        }

        $testresult->setDuration(time() - $currenttime);

        // Start generating result.html

        // Source code of test
        $filename_php = $tests_directory . '/' . $test->getTestClassName() . '.php';

        if (file_exists($filename_php)) {
            $handle = fopen($filename_php, "r");
            $sourcecode = fread($handle, filesize($filename_php));

            $testresult->setSourcecodeNumberOfLines(substr_count($sourcecode, "\n"));

            $search = array("\r\n", "\r");
            $replace = array("\n", "\n");
            $sourcecode = str_replace($search, $replace, $sourcecode);
            $sourcecode = utf8_decode(highlight_string($sourcecode, true));

            $testresult->setSourcecode($sourcecode);

            fclose($handle);
        }

        // Description of test
        $filename_html = $tests_directory . '/' . $test->getTestClassName() . '.html';
        $filename_txt = $tests_directory . '/' . $test->getTestClassName() . '.txt';

        // Prioritize HTML-descriptions if available
        if (file_exists($filename_html)) {
            $description_file = basename($filename_html);
            $testdescription = testdescriptionHtmlFile($filename_html);
        } elseif (file_exists($filename_txt)) {
            // Fallback to .txt if available
            $description_file = basename($filename_txt);
            $testdescription = testdescriptionTxtFile($filename_txt);
        } else {
            // Fallback to comments in testfile if available
            $description_file = '#';
            $testdescription = testdescriptionTestFile($test->getTestLocation(), $test->getTestClassName(), $tests_directory, $description_file);
        }

        $testresult->setDescriptionFilename($description_file);
        $testresult->setDescription($testdescription);


        if (isset($this_test_unstable)) {
            unset($this_test_unstable);
        }

        // Parse testdox text file (get test names and format them as we want them)
        if (file_exists($file_testdox_text2)) {
            foreach (explode(chr(10), file_get_contents($file_testdox_text2)) as $sometest) {
                if (substr(trim($sometest), 0, 4) == '[x] ') {
                    $sometest2 = new Zoetrope_Result_Test();
                    $sometest2->setName(substr(trim($sometest), 4));
                    $sometest2->setHasError(false);
                    $testresult->addTest($sometest2);
                } elseif (substr(trim($sometest), 0, 4) == '[ ] ') {
                    $sometest2 = new Zoetrope_Result_Test();
                    $sometest2->setName(substr(trim($sometest), 4));
                    $sometest2->setHasError(true);
                    $testresult->addTest($sometest2);
                }
            }
        }

        // Read from jUnit XML to find assertions, failures, errors and stacktraces
        if (file_exists($file_log_junit2)) {
            analyzeJunitXmlFileAndAddToResult($file_log_junit2, $testresult);
        } else {
            $testresult->setNumberOfAssertions(0);
            $testresult->setNumberOfFailure(0);
            $testresult->setNumberOfErrors(0);
            $testresult->setNumberOfTests(0);
            $testresult->setRunTime(0);
        }

        $total_assertions += $testresult->getNumberOfAssertions();
        $total_tests += $testresult->getNumberOfTests();
        $assertion_failures += $testresult->getNumberOfFailure();
        $assertion_errors += $testresult->getNumberOfErrors();

        // Define test status
        $this_test_failed = false;
        $this_test_skipped = false;
        if ($testresult->isUnstableTest()) {
            $this_test_failed = true; // For logging / outputting what happened
            $build_unstable = true;
            $tests_unstable++;
        } else if ($testresult->getNumberOfErrors() == 0 && $testresult->getNumberOfFailure() == 0 && $testresult->getNumberOfAssertions() == 0 && $testresult->getRunTime() == 0) {
            $this_test_skipped = true;
            $testresult->setHasSkippedTests(true);
            $tests_skipped++;
        } // Only mark test as error if not unstable
        else if ($testresult->getNumberOfErrors() > 0) {
            $this_test_failed = true;
            $testresult->setHasErrorsInTests(true);
            $build_error = true; // >0 tests have errors
            $tests_errored++;
        } else if ($testresult->getNumberOfFailure() > 0) {
            $this_test_failed = true;
            $testresult->setHasFailedTests(true);
            $build_error = true; // >0 tests have failed
            $tests_failed++;
        } else {
            $tests_ok++;
        }

        // Loop through line by line the failures and errors so we can add screenshots to screenshot list and line numbers
        $ob_lines = explode(PHP_EOL, ob_get_contents());
        foreach ($ob_lines as $ob_line) {
            // Look for something that indicates we're on a new failure or error
            // Example: 1) testcase::testMyTest
            preg_match('#^(\d+)\) \w+\::test\w+#', $ob_line, $newError);
//            preg_match( '#^\#\# (FAILURE|ERROR)\: #', $ob_line, $newError );

            // ?: New error or failure
            if (isset($newError[1])) {
                // ([1] is the first parenthesis match in the regex, [0] is the entire match)
                $crashType = $newError[1]; // 'FAILURE' or 'ERROR'
                // -> Invalidate last screenshot
                $currentScreenshot = '';
                // -> Set current crash
                $currentCrash = 'ERROR'; //$crashType;
            }
            // See if this line contains a screenshot
            // Example: Screenshot: http://mydomain.com/randomhexadecimal1235af33aa4af.png
            preg_match('#Screenshot: .+\/([a-f0-9]+\.png)#', $ob_line, $screenshot);
            // ?: Screenshot URL
            if (isset($screenshot[1]) && isset($screenshot_url)) {
                $screenshotFilename = $screenshot[1];
                // -> Set the last screenshot we took
                $currentScreenshot = $screenshotFilename;
                // -> Add screenshot to array
                $testresult->addScreenshot($screenshotFilename);
            }

            // Check if this line shows the linenumber of a failure or error
            // Example: /some/path/test/testcase.php:118 PHPUnit_Framework_Assert::assertTrue
            preg_match('#' . $test->getTestClassName() . '\.php\:(\d+)#', $ob_line, $linematch);
            // $linematch[1] = line number
            // ?: Error or failure on a line
            if (isset($linematch[1])) {
                $linenumber = $linematch[1];
                // ?: Check if it's an error or a failure
                if (isset($currentCrash) && $currentCrash == 'FAILURE') {
                    // -> Line has a failure, add screenshot to faillines if there is a related screenshot
                    if (isset($currentScreenshot) && !empty($currentScreenshot)) {
                        $testresult->addLineFailure($linenumber, $currentScreenshot);
                    } else {
                        $testresult->addLineFailure($linenumber);
                    }
                } else { // -> $currentCrash == 'ERROR'
                    // -> Line has an error, add screenshot to errorlines if there is a related screenshot
                    if (isset($currentScreenshot) && !empty($currentScreenshot)) {
                        $testresult->addLineError($linenumber, $currentScreenshot);
                    } else {
                        $testresult->addLineError($linenumber);
                    }
                }
            }
        }

        // Print test to console
        $this_test_success = !($this_test_failed || $this_test_skipped);
        if (
            ($this_test_failed && $output_tests_failed) ||
            ($this_test_skipped && $output_tests_skipped) ||
            ($this_test_success && $output_tests_success)
        ) {
            ob_flush();
        } else {
            ob_clean();
        }

        $result->addTest($testresult);

        // Unload the test.
        unset($test);

    }
    $result->setDuration(time() - $result->getTimeStart());


    // :: Get HTML report
    ob_start();
    include __DIR__ . '/templates/html_report.php';
    $tohtml = ob_get_clean();

    // Write HTML to file
    $file = fopen($results_file, "w");
    fwrite($file, $tohtml);
    fclose($file);

    if (!empty($results_mail)) {
        // :: Get mail report
        ob_start();
        include __DIR__ . '/templates/mail_report.php';
        $tomail = ob_get_clean();

        // Write mail report to file
        $file = fopen($results_mail, "w");
        fwrite($file, $tomail);
        fclose($file);
    }

    // :: Copy tests to results directory
    if ($copytests) {
        copyAndMoveTestResultsToResultsDirectory($tests_directory, $results_directory);
    }

    // Flush remaining contents of output buffer
    ob_end_flush();

    echo '####################################################################' . PHP_EOL;
    echo '####################################################################' . PHP_EOL;
    echo '#### Summary' . PHP_EOL;
    echo '#### ------------------------------------' . PHP_EOL;
    echo '#### Testfiles: ' . count($tests) . PHP_EOL;
    echo '#### Errors: ' . $tests_errored . PHP_EOL;
    echo '#### Failures: ' . $tests_failed . PHP_EOL;
    echo '#### Unstable: ' . $tests_unstable . PHP_EOL;
    echo '#### Skipped: ' . $tests_skipped . PHP_EOL;
    echo '#### OK: ' . $tests_ok . PHP_EOL;
    echo '#### ------------------------------------' . PHP_EOL;
    echo '#### Actual tests run: ' . $total_tests . PHP_EOL;
    echo '#### Assertions: ' . $total_assertions . PHP_EOL;
    echo '#### Assertions with errors: ' . $assertion_errors . PHP_EOL;
    echo '#### Assertions with failures: ' . $assertion_failures . PHP_EOL;
    echo '#### ------------------------------------' . PHP_EOL;
    if ($total_tests > 0) {
        echo '#### Avg. run time pr. test: ' . duration((time() - $result->getTimeStart()) / $total_tests) . PHP_EOL;
        echo '#### Avg. assertions pr. test: ' . $total_assertions / $total_tests . PHP_EOL;
    }
    echo '#### Avg. tests pr. testfile: ' . $total_tests / count($tests) . PHP_EOL;
    echo '#### ------------------------------------' . PHP_EOL;
    echo '#### Run time: ' . duration(time() - $result->getTimeStart()) . PHP_EOL;
    if ($build_error) {
        echo '#### Result: Build failed!' . PHP_EOL;
    } else if ($build_unstable) {
        echo '#### Result: Build unstable!' . PHP_EOL;
    } else {
        echo '#### Result: Build successful!' . PHP_EOL;
    }
    echo '####################################################################' . PHP_EOL;
    echo '####################################################################' . PHP_EOL;

    // Sleep 3 seconds to allow ffmpeg to cleanly shut down on last test
    if (!$onscreen) {
        sleep(3);
    }
} else {
    echo 'No tests found.' . PHP_EOL;
}

// If any tests have failed, fail the build
if ($build_error) {
    exit(1);
}
// If build is unstable, exit with no errors (success) and allow CI-application do what it wants with unstable builds
if ($build_unstable) {
    echo 'Build state: UNSTABLE' . PHP_EOL;
    exit(0);
}
