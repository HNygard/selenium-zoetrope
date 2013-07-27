<?php

/**
 * Generic class for just-in-time daemonized services.
 */
abstract class BackgroundService
{
    protected $service_log_file;
    protected $pid_file_name;
    protected $output_file_name;

    protected function startProcess($log_file, $command, $shellscript = false)
    {
        global $output_startup_services;
        if ($output_startup_services) {
            echo 'Starting service: ' . $command . ' (logging to ' . $log_file . ')' . PHP_EOL;
        }
        $this->service_log_file = $log_file;

        $unique = time() . rand(0, 100);
        $this->pid_file_name = 'background-service-' . $unique . '.pid';
        $this->output_file_name = 'background-service-' . $unique . '.out';

        // If shellscript, pass pid-file as last argument to command
        if ($shellscript) {
            exec(sprintf("%s \"%s\" > \"%s\" 2>&1", $command, $this->pid_file_name, $this->output_file_name));
        } else {
            exec(sprintf("%s > \"%s\" 2>&1 & echo $! > \"%s\"", $command, $this->output_file_name, $this->pid_file_name));
        }

        $remaining_tries = 60;
        while ($remaining_tries > 0 && !$this->isReady()) {
            sleep(2);
            --$remaining_tries;
        }

        if ($remaining_tries == 0) {
            echo 'Service failed:' . PHP_EOL;
            echo $this->getOutput();
            throw new Exception('Service did not start.');
        }
    }

    protected function isReady()
    {
        return TRUE;
    }

    public function getOutput()
    {
        return file_get_contents($this->output_file_name);
    }

    public function getPid()
    {
        if (isset($this->pid_file_name)) {
            return file_get_contents($this->pid_file_name);
        } else {
            return NULL;
        }
    }

    public function __destruct()
    {
        global $output_startup_services;
        $pid = $this->getPid();
        if (isset($pid)) {
            if ($output_startup_services) {
                echo 'Killing service ' . $this->output_file_name . PHP_EOL;
            }
            exec('kill ' . $pid);
            unlink($this->pid_file_name);
        }

        // Copy log file to results dir
        copy($this->output_file_name, $this->service_log_file);

        // Comment out for less verbose logging
        //echo PHP_EOL.'########## Flushing log ##########'.PHP_EOL;
        //echo $this->getOutput().PHP_EOL.PHP_EOL;

        unlink($this->output_file_name);
    }
}

/**
 * Interface for an X Windows server.
 */
interface XWindowsServiceInterface
{
    public function getDisplay();

    public function getWidth();

    public function getHeight();
}

/**
 * Starts an X Windows virtual frame buffer.
 */
class XvfbBackgroundService extends BackgroundService implements XWindowsServiceInterface
{
    protected $displayNumber;
    protected $width;
    protected $height;

    public function __construct($display_number, $log_file, $width = 1050, $height = 1800)
    {
        $this->displayNumber = $display_number;
        $this->width = $width;
        $this->height = $height;

        $command = '/usr/bin/Xvfb :' . $this->displayNumber . ' -ac -screen 0 ' . $this->width . 'x' . $this->height . 'x24';
        $this->startProcess($log_file, $command);
    }

    public function getDisplay()
    {
        return ':' . $this->displayNumber;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }
}

interface SeleniumServiceInterface
{
    public function getHost();

    public function getPort();
}

/**
 * Starts an instance of Selenium RC.
 */
class SeleniumBackgroundService extends BackgroundService implements SeleniumServiceInterface
{
    protected $port;

    public function __construct(XWindowsServiceInterface $display, $port, $log_file)
    {
        global $selenium_firefox_profile;

        $this->port = $port;
        $command =
            'export DISPLAY="' . $display->getDisplay() . '" && ' .
                'java' .
                ' -jar ' . str_replace(' ', '\ ', __DIR__ . '/selenium-server-*.jar') .
                ' -singlewindow' .
                ' -port ' . $this->port;

        if(!empty($selenium_firefox_profile)) {
                $command .= ' -firefoxProfileTemplate "' . $selenium_firefox_profile . '"' ;
        }

        $this->startProcess($log_file, $command);
    }

    public function getHost()
    {
        global $selenium_host;
        return $selenium_host;
    }

    public function getPort()
    {
        return $this->port;
    }

    protected function isReady()
    {
        return selenium_is_running($this->getHost(), $this->getPort());
    }
}

class SeleniumForegroundService extends BackgroundService implements SeleniumServiceInterface
{
    protected $port;

    public function __construct($port, $log_file)
    {
        $this->port = $port;
        $command = 'java' .
            ' -jar ' . str_replace(' ', '\ ', __DIR__ . '/selenium-server-*.jar') .
            ' -singlewindow' .
        //    ' -firefoxProfileTemplate "' . __DIR__ . '/ffProfile"' .
            ' -port ' . $this->port;

        $this->startProcess($log_file, $command);
    }

    public function getHost()
    {
        return '127.0.0.1';
    }

    public function getPort()
    {
        return $this->port;
    }

    protected function isReady()
    {
        return selenium_is_running($this->getHost(), $this->getPort());
    }
}

class SeleniumExternalService implements SeleniumServiceInterface
{
    protected $host;
    protected $port;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }
}

/**
 * Starts an X Windows virtual frame buffer.
 */
class ScreencastBackgroundService extends BackgroundService
{
    public function __construct(XWindowsServiceInterface $display, $video_file_name, $log_file)
    {
        // -an     No audio.
        // -f      Force format.
        // -y      Overwrite output files.
        // -r      Frame rate

        // MP4 / Mpeg4:
        //$command = 'ffmpeg -an -f x11grab -y -r 5 -s ' . $display->getWidth() . 'x' . $display->getHeight() . ' -i ' . $display->getDisplay() . '.0+0,0+nomouse -vcodec mpeg4 -sameq ' . $video_file_name;

        // FLV
        //$command = 'ffmpeg -an -f x11grab -y -r 5 -s ' . $display->getWidth() . 'x' . $display->getHeight() . ' -i ' . $display->getDisplay() . '.0+0,0+nomouse -vcodec flv -sameq ' . $video_file_name;

        // OGG
        $command = '/usr/bin/ffmpeg -an -f x11grab -y -r 5 -s ' . $display->getWidth() . 'x' . $display->getHeight() .
            ' -i ' . $display->getDisplay() . '.0+0,0+nomouse -vcodec libtheora -qmin 31 -b 1024k "' . $video_file_name . '"';
        //$command = __DIR__ . '/ffmpeg.sh -an -f x11grab -y -r 5 -s ' . $display->getWidth() . 'x' . $display->getHeight() .
        //    ' -i ' . $display->getDisplay() . '.0+0,0+nomouse -vcodec libtheora -qmin 31 -b 1024k ' . $video_file_name;
        // Pass true as second argument if command run is shellscript
        $this->startProcess($log_file, $command);
    }

//  public function __destruct() {
    // Sleep for two seconds to capture last frame
//    sleep(10);
//    parent::__destruct();
//    echo 'Sleeping for 1 second to allow ffmpeg to cleanly shut down before new test' . PHP_EOL;
//    sleep(1);
//  }
}

/**
 * Selenium test.
 */
class SeleniumTest
{
    protected $bootstrapFile;
    protected $testClassName;
    protected $testFileName;
    protected $testGroup;

    public function __construct(SeleniumServiceInterface $selenium_server, $test_file, $test_group, $base_url, $browser = '*firefox')
    {
        global $codecoverage_url, $screenshot_url;
        $this->testFileName = $test_file;
        $this->testGroup = $test_group;
        $this->testClassName = pathinfo($test_file, PATHINFO_FILENAME);

        // Construct a bootstrap file for each test, that contains global variables
        // for the Selenium server to connect to, as well as the browser and URL to
        // run against. The alternative to this would be to pass command line
        // parameters to phpunit call in $this->run(). This method seems a tiny bit
        // cleaner.
        $text = '<?php' . PHP_EOL;
        $text .= '$selenium_server_host = \'' . $selenium_server->getHost() . '\';' . PHP_EOL;
        $text .= '$selenium_server_port = ' . $selenium_server->getPort() . ';' . PHP_EOL;
        $text .= '$target_browser = \'' . $browser . '\';' . PHP_EOL;
        $text .= '$target_url = \'' . $base_url . '\';' . PHP_EOL;
        $text .= '$test_file = \'' . $test_file . '\';' . PHP_EOL;

        if (!empty ($codecoverage_url)) {
            $text .= '$codecoverage_url = \'' . $codecoverage_url . '\'; ' . PHP_EOL;
        }
        if (!empty ($screenshot_url)) {
            $text .= '$screenshot_url = \'' . $screenshot_url . '\'; ' . PHP_EOL;
        }

        // Store a unique bootstrap file per test.
        $directory = sys_get_temp_dir() . '/selenium-bootstrap';
        @mkdir($directory);
        $this->bootstrapFile = tempnam($directory, 'bootstrap-');

        // echo 'Storing bootstrap: ' . $this->bootstrapFile . PHP_EOL;
        // echo 'Contents: ' . $text . PHP_EOL;

        file_put_contents($this->bootstrapFile, $text);
    }

    public function run($file_log_junit, $file_testdox_text)
    {
        global $output_startup_services, $codecoverage_url, $phpunit_printer, $phpunit_includepath;
        $old_working_directory = getcwd();
        chdir(dirname($this->testFileName));
        $command = 'phpunit ';
        $command .= '--log-junit "' . $file_log_junit . '" ';
        $command .= '--bootstrap "' . $this->bootstrapFile . '" ';
        $command .= '--testdox-text "' . $file_testdox_text . '" ';

        // :: Optional PHPUnit parameters
        if (!empty ($codecoverage_url)) {
            // -> Generate code coverage html report if code coverage url is sett
            $command .= '--coverage-html "' . $file_testdox_text . '.html" ';
        }
        if ($this->testGroup != '') {
            $command .= '--group "' . $this->testGroup . '" ';
        }
        if (!empty($phpunit_printer)) {
            $command .= '--printer "' . $phpunit_printer . '" ';
        }
        if (!empty($phpunit_includepath)) {
            $command .= '--include-path "' . $phpunit_includepath . '" ';
        }

        $command .= '"' . basename($this->testFileName) . '"';

        if ($output_startup_services) {
            echo 'Running: ' . $command . PHP_EOL;
        }

        $output = shell_exec($command);
        echo $output;

        chdir($old_working_directory);
    }

    public function getTestClassName()
    {
        return $this->testClassName;
    }

    /**
     * Returns the path/location of the test
     *
     * @return string
     */
    public function getTestLocation()
    {
        return $this->testFileName;
    }

    public function __destruct()
    {
        unlink($this->bootstrapFile);
    }
}

/**
 * @param unknown_type $directory
 * @param SeleniumServiceInterface $selenium
 * @param unknown_type $test_group
 * @param string $base_url Base URL for Selenium to start on
 * @param string $browser Selenium browser
 * @return SeleniumTest[]
 */
function selenium_get_all_tests($directory, SeleniumServiceInterface $selenium, $test_group, $base_url, $browser = '*firefox')
{
    $tests = array();

    // If only one file is passed, handle it (hacky)
    if (!is_dir($directory)) {
        $test_files = array($directory);
    }
    else {
        $test_files = explode(PHP_EOL, trim(shell_exec('find "' . $directory . '" | grep .php$')));
    }

    if (!empty($test_files) && !empty($test_files[0])) {
        foreach ($test_files as $test_file) {
            $tests[] = new SeleniumTest($selenium, $test_file, $test_group, $base_url, $browser);
        }
    }
    return $tests;
}

function selenium_is_running($host, $port)
{
    global $output_startup_services;
    if ($output_startup_services) {
        echo 'Checking if Selenium is active at ' . $host . ':' . $port . PHP_EOL;
    }
    $success = FALSE;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/selenium-server/driver/?cmd=testComplete');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (curl_exec($ch) !== FALSE) {
        $success = TRUE;
    }
    curl_close($ch);
    return $success;
}

/**
 * Duration function for more readable runtime in HTML result file
 *
 * @param  int $seconds_count
 * @return string
 */
function duration($seconds_count)
{
    $seconds = $seconds_count % 60;
    $minutes = floor($seconds_count / 60);
    $hours = floor($seconds_count / 3600);

    if ($hours > 0) $hours = $hours . 'h';
    else $hours = '';
    if ($minutes > 0) $minutes = $minutes . 'm';
    else $minutes = '';
    if ($seconds > 0) $seconds = $seconds . 's';
    else $seconds = '';

    return "$hours$minutes$seconds";
}

/**
 * Replace links in text with html links
 *
 * @param string  $text            Input string containing URLs
 * @param boolean $shorturl        Show a short URL?
 * @param boolean $includeprotocol Include the protocol in the URL?
 * @param string  $urlparams       Extra anchor tag parameters
 * @return string Returns the text given with URLs replaced with HTML links
 */
function auto_link_text($text)
{
    $pattern = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
    //$pattern = '#(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#';
    $callback = create_function('$matches', '
        $url       = array_shift($matches);
        $url_parts = parse_url($url);

        // No protocol found - use default (http)
        if (substr_count($url, "://") == 0) $url = "http://" . $url;

        return sprintf(\'<a target="_blank" href="%s">%s</a>\', $url, $url);
    ');

    return preg_replace_callback($pattern, $callback, $text);
}

// Find relative path from frompath to topath
function find_relative_path($frompath, $topath)
{
    $startpath = explode('/', $frompath); // Start path
    $path = explode('/', $topath); // Find relative path from start path
    $relpath = '';

    $i = 0;
    while (isset($startpath[$i]) && isset($path[$i])) {
        if ($startpath[$i] != $path[$i]) break;
        $i++;
    }
    $j = count($startpath) - 1;
    while ($i <= $j) {
        if ($startpath[$j] != '') $relpath .= '../';
        $j--;
    }
    while (isset($path[$i])) {
        if ($path[$i] != '') $relpath .= $path[$i] . '/';
        $i++;
    }

    return $relpath;
}

// Replace newlines with <br/> and HTML chars with their respective html codes
// Returns html-ified string
function htmlify($text, $onlynewlines = false, $codewhitespace = false, $codeand = false)
{
    if ($codeand) $text = str_replace('&', '&amp;', $text);

    if (!$onlynewlines) {
        $search = array('<', '>', '"', "'");
        $replace = array('&lt;', '&gt;', '&quot;', '&apos;');
        $text = str_replace($search, $replace, $text);
    }
    $search = array("\r\n", "\r", "\n");
    $replace = array('<br/>', '<br/>', '<br/>');
    $text = str_replace($search, $replace, $text);

    if ($codewhitespace) $text = str_replace(' ', '&nbsp;', $text);

    return $text;
}

// Trim newlines at end of string
function trim_newlines($text, $html = false)
{
    if ($html) {
        return preg_replace('#(?i)(?:<br ?\/>)*$#', '', $text);
    } else {
        return preg_replace('#(?i)(?:' . "\n" . ')*$#', '', $text);
    }
}

// Remove duplicates which match the pattern from the input string
function removeDuplicatesBasedOnPattern($pattern, $split, $inputstring)
{
    $alreadyaddedscreenshots = array();
    $output = array();
    $lines = explode($split, $inputstring);
    foreach ($lines as $line) {
        preg_match($pattern, $line, $sslinematch);
        if (isset($sslinematch[0])) {
            $added = false;
            foreach ($alreadyaddedscreenshots as $alreadyaddedscreenshot) {
                if ($alreadyaddedscreenshot == $sslinematch[0]) {
                    $added = true;
                    break;
                }
            }
            if (!$added) {
                $output[] = $line;
                $alreadyaddedscreenshots[] = $line;
            }
        } else {
            $output[] = $line;
        }
    }
    return implode(PHP_EOL, $output);
}


/**
 * Copy tests, and move test screenshots and test descriptions into results directory if specified
 * @param  string  $tests_directory
 * @param  string  $results_directory
 */
function copyAndMoveTestResultsToResultsDirectory($tests_directory, $results_directory)
{
    global $output_startup_services;

    if (function_exists('glob')) {
        // glob only exists in PHP >= 5.3
        // Copy tests to the results
        foreach (glob($tests_directory . '/*.php') as $filename) {
            if ($output_startup_services) {
                echo 'Copying "' . $filename . '" to "' . $results_directory . '"' . PHP_EOL;
            }
            exec('cp "' . $filename . '" "' . $results_directory . '"');
        }
        // Move auto-generated descriptions
        foreach (glob($tests_directory . '/*_gen.txt') as $filename) {
            if ($output_startup_services) {
                echo 'Moving "' . $filename . '" to "' . $results_directory . '"' . PHP_EOL;
            }
            exec('mv "' . $filename . '" "' . $results_directory . '"');
        }
        // Copy .txt description
        foreach (glob($tests_directory . '/*.txt') as $filename) {
            if ($output_startup_services) {
                echo 'Copying "' . $filename . '" to "' . $results_directory . '"' . PHP_EOL;
            }
            exec('cp "' . $filename . '" "' . $results_directory . '"');
        }
        // Copy .html description
        foreach (glob($tests_directory . '/*.html') as $filename) {
            if ($output_startup_services) {
                echo 'Copying "' . $filename . '" to "' . $results_directory . '"' . PHP_EOL;
            }
            exec('cp "' . $filename . '" "' . $results_directory . '"');
        }
        // Move auto-generated screenshots
        foreach (glob($tests_directory . '/*.png') as $filename) {
            if ($output_startup_services) {
                echo 'Moving "' . $filename . '" to "' . $results_directory . '"' . PHP_EOL;
            }
            exec('mv "' . $filename . '" "' . $results_directory . '"');
        }
    } else {
        // -> Glob() does not exits. PHP version must be less than 5.3.
        // Copy tests to the results
        exec('cp "' . $tests_directory . '/"*.php "' . $results_directory . '"');
        // Move auto-generated descriptions
        exec('mv "' . $tests_directory . '/"*_gen.txt "' . $results_directory . '"');
        // Copy .txt description
        exec('cp "' . $tests_directory . '/"*.txt "' . $results_directory . '"');
        // Copy .html description
        exec('cp "' . $tests_directory . '/"*.html "' . $results_directory . '"');
        // Move auto-generated screenshots
        exec('mv "' . $tests_directory . '/"*.png "' . $results_directory . '"');
    }
}


/**
 * Analyze a Junit XML file
 *
 * @param  string                    $file_log_junit2
 * @param  Zoetrope_Result_Testcase  $testresult
 */
function analyzeJunitXmlFileAndAddToResult($file_log_junit2, Zoetrope_Result_Testcase $testresult)
{
    try {
        $xml = simplexml_load_file($file_log_junit2);
        $num_assertions = $xml->xpath('/testsuites/testsuite/@assertions');
        $num_failures = $xml->xpath('/testsuites/testsuite/@failures');
        $num_errors = $xml->xpath('/testsuites/testsuite/@errors');
        $num_tests = $xml->xpath('/testsuites/testsuite/@tests');
        $run_time = $xml->xpath('/testsuites/testsuite/@time');

        $testresult->setNumberOfAssertions((int)$num_assertions[0]);
        $testresult->setNumberOfFailure((int)$num_failures[0]);
        $testresult->setNumberOfErrors((int)$num_errors[0]);
        $testresult->setNumberOfTests((int)$num_tests[0]);
        $testresult->setRunTime((int)$run_time[0]);


        // HTML-ify jUnit XML log stacktrace
        $xmlerrorstack = $xml->xpath('/testsuites/testsuite/testcase/error');
        $xmlfailstack = $xml->xpath('/testsuites/testsuite/testcase/failure');

        // Find lines that cause errors in the code, for use in linenumber highlighting - also check if build is unstable
        foreach ($xmlerrorstack as $stack) {
            $testresult->addError(new Zoetrope_Result_Error((string)$stack['type'], (string)$stack));

            // Regex checking for error messages in stacktrace which should trigger unstable build
            $unstable = preg_match('#(?:Timed out after \d+ms\.|BUILD_UNSTABLE\:.*|PHPUnit_Framework_Exception\: Could not connect to the Selenium RC server\.)#', (string)$stack);

            // Mark build + specific test unstable if we find any matches to unstable regex
            if ($unstable && !isset($this_test_unstable)) {
                // Only unstable if no real failures
                $this_test_unstable = true;
            } else if (!$unstable) {
                // A single (real) failure is enough to not be unstable
                $this_test_unstable = false;
            }
        }
    } catch (Exception $e) {
        $testresult->addError(new Zoetrope_Result_Error('XML PARSING FAILED', $e->getMessage()));

        $this_test_unstable = false;
    }
    // Only sets test unstable if all tests in it unstable
    if (isset($this_test_unstable)) {
        $testresult->setUnstableTest($this_test_unstable);
    }

    // Find lines that cause fails in the code, for use in linenumber highlighting - also check if build is unstable
    /* TODO: TEMPORARILY UNCOMMENTED AS LONG AS WE'RE THROWING A SPECIFIC ERROR EVERY TIME A TEST FAILS
    foreach ( $xmlfailstack as $stack ) {
        $testresult->addFailure(new Zoetrope_Result_Error((string)$stack['type'], (string)$stack));

        // Regex checking for failure messages in stacktrace which should trigger unstable build
        $unstable = preg_match( '#BUILD_UNSTABLE\:.*#', (string)$stack );

        // Mark build + specific test unstable if we find any matches to unstable regex
        if ( $unstable && !isset( $this_test_unstable ) ) {
            $this_test_unstable = true;
            $build_unstable = true;
        }
        else if ( !$unstable ) {
            $this_test_unstable = false;
            $build_unstable = false;
        }
    }
    */

}


function testdescriptionTestFile($path_test, $test_name, $tests_directory, &$description_file)
{
    $test_file_contents = file_get_contents($path_test);
    $tokens = token_get_all($test_file_contents); // contains all "code-elements" in a php file

    // Find all tokens with public and function in them
    $linearray = array();
    foreach ($tokens as $token) {
        if ($token[0] == T_PUBLIC) {
            $linearray["public"][$token[2]] = true;
        }
        if ($token[0] == T_FUNCTION) {
            $linearray["function"][$token[2]] = true;
        }
    }

    // Put valid testnames in array by using our knowledge of what lines "public function" is on
    $testnames = array();
    foreach ($tokens as $token) {
        if ($token[0] == T_STRING) {
            // Function starts with test and public and function is on the same line
            if (substr($token[1], 0, 4) == "test" && isset($linearray["public"][$token[2]]) && isset($linearray["function"][$token[2]])) {
                // $token[1] is a valid test name
                $line = $token[2];
                $testname = $token[1];
                $testnames[$line] = substr($testname, 4);
            }
        }
    }

    $tests_with_comments = '';
    // Get all doc comments and put in string along with related testnames
    foreach ($tokens as $token) {
        if ($token[0] == T_DOC_COMMENT) {
            // $token[1] is a doc comment
            $line = $token[2];
            $comment = $token[1];
            $comment_lines = count(explode("\n", $comment));
            // Find test name on the doc comment following line
            if (isset($testnames[$line + $comment_lines])) $tests_with_comments .= $testnames[$line + $comment_lines] . "\n" . $comment . "\n\n";
        }
    }
    $testdescription = $tests_with_comments;
    if ($testdescription == '') {
        $testdescription = 'No test description found.';
    } else {
        // Write description to file
        $description_file = $test_name . '_gen.txt';
        $file = fopen($tests_directory . '/' . $description_file, "w");
        fwrite($file, $testdescription);
        fclose($file);
        // HTML-ify testdescription (new lines = <br/> and so on)
        $testdescription = htmlify($testdescription);
        // Replace URLs with actual HTML links
        $testdescription = auto_link_text($testdescription);
    }
    $testdescription = utf8_decode($testdescription);
    return $testdescription;
}


function testdescriptionTxtFile($filename_txt)
{
    $handle = fopen($filename_txt, "r");
    // HTML-ify and get testdescription
    $testdescription = htmlify(fread($handle, filesize($filename_txt)));
    // Replace URLs with actual links
    $testdescription = auto_link_text($testdescription);

    fclose($handle);
}

function testdescriptionHtmlFile($filename_html)
{
    $handle = fopen($filename_html, "r");
    $testdescription = fread($handle, filesize($filename_html));

    fclose($handle);
    return $testdescription;
}
