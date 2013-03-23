<?php

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

/**
 * Test case that contains
 * - Error handling controlled by Zoetrope
 * - Convenience methods
 *
 * Already added in PHPUnit_Extensions_SeleniumTestCase in dev branch (github)
 * @method   assertTextPresent()
 * @method   assertTextNotPresent()
 * @method   assertChecked()
 * @method   assertNotChecked()
 * @method   assertElementNotPresent
 * @method   assertElementPresent
 *
 *
 * Not in dev branch @ Github:
 * @method   stop()
 * @method   start()
 * @method   setTimeout()
 * @method   setWaitForPageToLoad()
 * @method   setHost()
 * @method   setPort()
 * @method   setBrowser()
 * @method   setBrowserUrl()
 * @method   assertNotHtmlSource()
 */
class Zoetrope_SeleniumTestCase extends PHPUnit_Extensions_SeleniumTestCase {

    // TODO: why is this public?

    public $selenium_timeout;

    protected function setUp() {
        global $selenium_server_host, $selenium_server_port, $target_browser, $target_url, $codecoverage_url, $test_file, $screenshot_url;

        // Use default value if not set by Zoetrope
        if(!isset($selenium_server_host)) {
            $selenium_server_host = 'localhost';
        }
        if(!isset($selenium_server_port)) {
            $selenium_server_port = 4444;
        }
        if(!isset($target_browser)) {
            $target_browser = '*chrome';
        }
        if(!isset($target_url)) {
            $target_url = 'http://targeturl.notset.example.com/';
        }

        if(isset($this->selenium_timeout)) {
            $this->setTimeout($this->selenium_timeout / 1000);
        }
        $this->setWaitForPageToLoad(true);
        $this->setHost($selenium_server_host);
        $this->setPort($selenium_server_port);
        $this->setBrowser($target_browser);
        $this->setBrowserUrl($target_url);

        // Code coverage from run-selenium
        if(isset($codecoverage_url)) {
            $this->coverageScriptUrl = $codecoverage_url;
        }

        $this->captureScreenshotOnFailure = FALSE;
        // Take a screenshot of errors
        if (isset($screenshot_url)) {
            $this->screenshotUrl = $screenshot_url;
            $this->screenshotPath = pathinfo($test_file, PATHINFO_DIRNAME);
            $this->captureScreenshotOnFailure = TRUE;
        }
    }

    protected function captureScreenshotOf ( $descriptionOfScreenshot ) {
        $uniqueid = ''; // TODO:!!
        $this->drivers[0]->captureEntirePageScreenshot($this->screenshotPath.'/'.$this->testId.'.png');
        echo 'Screenshot of "'.$descriptionOfScreenshot.'": ' . $this->screenshotUrl .'/'. $this->testId . '.png'.PHP_EOL;
    }


    /**
     * Assert value of a input field
     *
     * @param string  $locator      Selenium locator
     * @param mixed   $pattern      Expected value
     * @param string  $description
     * @param bool    $ignore_case
     */
    public function assertValue ($locator, $pattern, $description = '', $ignore_case = FALSE) {
        $delta = 0; // Default PHPUnit value
        $maxDepth = 10; // Default PHPUnit value
        $canonicalize = FALSE; // Default PHPUnit value
        $this->assertThat($pattern, $this->equalTo($this->getValue($locator), $delta, $maxDepth, $canonicalize, $ignore_case), $description);
    }

    /**
     * Assert that a text on the site has a value
     *
     * @param  string  $locator      Selenium locator
     * @param  string  $pattern      Expected value
     * @param  string  $description
     */
    public function assertText ($locator, $pattern, $description = '') {
        $this->assertEquals($pattern, $this->getText($locator), $description);
    }

    /**
     * Assert that a text on the site starts with a value
     *
     * @param  string  $locator      Selenium locator
     * @param  string  $pattern      Expected value
     * @param  string  $description
     */
    public function assertTextStartsWith ($locator, $pattern, $description = '') {
        $this->assertEquals($pattern, substr($this->getText($locator), 0, strlen($pattern)), $description);
    }

    // Temporary solution to always get line number for failed tests
    // Replacing verify with assert, because verify does not provide line number
    public function verifyText ($locator, $pattern, $description=null) {
        /*if ($description != null) $this->assertText($locator, $pattern, $description);
        else*/ $this->assertText($locator, $pattern);
        // Apparently adding a description to assertText is not supported TODO: Find workaround
    }
    public function verifyValue ($locator, $pattern, $description=null) {
        if ($description != null) $this->assertValue($locator, $pattern, $description);
        else $this->assertValue($locator, $pattern);
    }
    public function verifyChecked ($locator, $description=null) {
        if ($description != null) $this->assertChecked($locator, $description);
        else $this->assertChecked($locator);
    }
    public function verifyNotChecked ($locator, $description=null) {
        if ($description != null) $this->assertNotChecked($locator, $description);
        else $this->assertNotChecked($locator);
    }
    public function verifyTextPresent($pattern, $description=null) {
        if ($description != null) $this->assertTextPresent($pattern, $description);
        else $this->assertTextPresent($pattern);
    }
    public function verifyTextNotPresent($pattern, $description=null) {
        if ($description != null) $this->assertTextNotPresent($pattern, $description);
        else $this->assertTextNotPresent($pattern);
    }
    public function verifyElementPresent($locator, $description=null) {
        if ($description != null) $this->assertElementPresent($locator, $description);
        else $this->assertElementPresent($locator);
    }
    public function verifyElementNotPresent($locator, $description=null) {
        if ($description != null) $this->assertElementNotPresent($locator, $description);
        else $this->assertElementNotPresent($locator);
    }
    public function verifyEquals($pattern1, $pattern2, $description=null) {
        if ($description != null) $this->assertEquals($pattern1, $pattern2, $description);
        else $this->assertEquals($pattern1, $pattern2);
    }
    // End temporary solution


    /**
     * Temporary workaround to timeout on page loads not working as it should
     * http://stackoverflow.com/questions/9291829/selenium-test-timeout-doesnt-stop-the-test-phpunit-3-6-10-selenium-rc-2-19-0
     *
     * @throws SeleniumTimeoutException
     * @param int|null $timeout Time to wait in milliseconds
     */
    public function waitForPageToLoad($timeout=null) {
        if ( is_null( $timeout ) ) {
            $timeout = $this->selenium_timeout;
        }
        $start = time();
        parent::waitForPageToLoad($timeout);
        $end = time();
        if (($end - $start) >= ($timeout / 1000)) {
            throw new SeleniumTimeoutException('Timed out after '.$timeout.'ms.');
        }
    }

    public function openAndWait ($url, $waittime=null) {
        if ($waittime == null) {
            $waittime = $this->selenium_timeout;
        }
        $this->open($url);
        $this->waitForPageToLoad($waittime);
    }

    public function goBackAndWait ($waittime=null) {
        if ($waittime == null) {
            $waittime = $this->selenium_timeout;
        }
        $this->goBack();
        $this->waitForPageToLoad($waittime);
    }

    public function clickAndWait ($locator, $waittime=null) {
        if ($waittime == null) {
            $waittime = $this->selenium_timeout;
        }
        $this->click($locator);
        $this->waitForPageToLoad($waittime);
    }

    public function waitForText ($locator, $pattern, $description = null) {
        for ($second = 0; ; $second++) {
            if ($second >= 60) {
                if (!is_null($description)) {
                    $this->fail($description);
                }
                else {
                    $this->fail('Timeout waiting for string "' . $pattern . '"');
                }
            }
            try {
                if ($pattern == $this->getText($locator)) break;
            } catch (Exception $e) {}
            sleep(1);
        }
    }

    public function waitForTextPresent ($pattern, $description = null) {
        for ($second = 0; ; $second++) {
            if ($second >= 60) {
                if(!is_null($description)) {
                    $this->fail($description);
                }
                else {
                    $this->fail('Timeout waiting for string "' . $pattern . '"');
                }
            }
            try {
                if ($this->isTextPresent($pattern)) break;
            } catch (Exception $e) {}
            sleep(1);
        }
    }

    public function waitForElementPresent($locator, $description = null) {
        for ($second = 0; ; $second++) {
            if ($second >= 60) {
                if(!is_null($description)) {
                    $this->fail($description);
                }
                else {
                    $this->fail('Timeout waiting for element "' . $locator . '"');
                }
            }
            try {
                if ($this->isElementPresent($locator)) break;
            } catch (Exception $e) {}
            sleep(1);
        }
    }

    public function waitForElementNotPresent($locator, $description = null) {
        $second = 0;
        while ($this->isElementPresent($locator) && $second < 60) {
            sleep(1);
            $second++;
        }
        if ($this->isElementPresent($locator)) {
            if (!is_null($description)) {
                $this->fail($description);
            }
            else {
                $this->fail('Timeout waiting for element "'.$locator.'" to stop being its current state or disappear.');
            }
        }
    }

    /**
     * Converts camel case names to human readable
     *
     * @return string
     */
    public function getNameHumanReadable () {
        return substr(trim(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $this->getName())), strlen('test_'));
    }


    /**
     * Helper function for temporary workaround PHPUnit 3.6 not showing line numbers
     * Returns a string formatted stack trace if the filename is the same as $test_file
     *
     * Example output: /path/to/test/testcase.php:123 PHPUnit_Framework_Assert::assertTrue
     *
     * See https://github.com/sebastianbergmann/phpunit-selenium/issues/81
     * http://stackoverflow.com/questions/9227232/selenium-dont-show-failed-number-lines
     * Credits to: http://stackoverflow.com/a/6608751
     *
     * @param Exception $e
     * @return string A string formatted stack trace
     */
    protected function dumpStack(Exception $e) {
        global $test_file;

        $stack = '';
        foreach ($e->getTrace() as $trace) {
            if (isset($trace['file'])        &&
                $trace['file'] == $test_file && // Only show line numbers from test file
                isset($trace['line'])        &&
                isset($trace['class'])       &&
                isset($trace['function']))
            {
                $stack .= PHP_EOL .
                    $trace['file']     . ':' .
                    $trace['line']     . ' ' .
                    $trace['class']    . '::' .
                    $trace['function'];
            }
        }
        return $stack;
    }

    protected function onNotSuccessfulTest(Exception $e) {

        // Sleep for 10 seconds to keep the window open when recording and keep the browser open when running locally
        // This ensures that the video will contain some seconds of the actual failure scenario - the last frame.
        sleep(10);

        parent::onNotSuccessfulTest($e);
    }

    /**
     * Asserts that an elements text is a given string
     *
     * @param string  $locator
     * @param string  $expected
     * @param string  $message
     */
    public function assertElementText ($locator, $expected, $message = '') {
        $this->assertEquals($expected, $this->getText($locator), $message);
    }

    /**
     * Asserts that an elements text is not a given string
     *
     * @param string  $locator
     * @param string  $expected
     * @param string  $message
     */
    public function assertElementTextNotEquals ($locator, $expected, $message = '') {
        $this->assertNotEquals($expected, $this->getText($locator), $message);
    }

    /**
     * Asserts that an elements text starts with a given string
     *
     * @param string  $locator
     * @param string  $expectedStart
     * @param string  $message
     */
    public function assertElementTextStartsWith ($locator, $expectedStart, $message = '') {
        $this->assertEquals($expectedStart, substr($this->getText($locator), 0, strlen($expectedStart)), $message);
    }
}


class SeleniumTimeoutException extends Exception {

}
