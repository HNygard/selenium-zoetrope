<?php

require_once 'PHPUnit/Extensions/Selenium2TestCase.php';


/**
 * Wrapper to not modifiy all Selenium 1 command when moving to Selenium 2.
 */
class SeleniumTestCase_Selenium1Wrapper extends PHPUnit_Extensions_Selenium2TestCase {
    /**
     * @param $string
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     * @throws Exception
     */
    private function getCssSelectorFromSelenium1Stuff($string) {
        if (substr($string, 0, strlen('name=')) == 'name=') {
            return $this->byName(substr($string, strlen('name=')));
        }
        if (substr($string, 0, strlen('id=')) == 'id=') {
            return $this->byCssSelector('#' . substr($string, strlen('id=')));
        }
        if (substr($string, 0, strlen('css=')) == 'css=') {
            return $this->byCssSelector(substr($string, strlen('css=')));
        }
        if (substr($string, 0, strlen('//')) == '//') {
            return $this->byXPath($string);
        }
        if (substr($string, 0, strlen('link=')) == 'link=') {
            return $this->byLinkText(substr($string, strlen('link=')));
        }
        if ($string == 'body') {
            return $this->byCssSelector($string);
        }
        throw new Exception('Unknown Selenium 1 selector: ' . $string);
    }

    public function click($string) {
        $this->getCssSelectorFromSelenium1Stuff($string)
            ->click();
    }

    public function type($string, $text) {
        $element = $this->getCssSelectorFromSelenium1Stuff($string);
        $element->clear();
        $element->value($text);
    }

    public function isElementPresent($string) {
        try {
            $this->getCssSelectorFromSelenium1Stuff($string);
            return true;
        }
        catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            if (strpos($e->getMessage(), 'no such element') !== false
                && strpos($e->getMessage(), 'Unable to locate element:') !== false) {
                return false;
            }
            if (strpos($e->getMessage(), 'Returned node was not an HTML element') !== false) {
                return false;
            }
            if (strpos($e->getMessage(), 'Unable to locate element with name') !== false) {
                return false;
            }
            if (strpos($e->getMessage(), 'Unable to locate a node using') !== false) {
                return false;
            }
            throw $e;
        }
    }

    public function assertElementPresent($locator) {
        \PHPUnit\Framework\Assert::assertTrue($this->isElementPresent($locator));
    }

    public function assertElementNotPresent($locator) {
        \PHPUnit\Framework\Assert::assertFalse($this->isElementPresent($locator));
    }

    public function assertElementContainsText($locator, $expected) {
        \PHPUnit\Framework\Assert::assertContains($expected, $this->getText($locator));
    }

    public function assertElementNotContainsText($locator, $expected) {
        \PHPUnit\Framework\Assert::assertNotContains($expected, $this->getText($locator));
    }

    public function getText($string) {
        return $this->getCssSelectorFromSelenium1Stuff($string)
                    ->text();
    }

    public function getTitle() {
        return $this->title();
    }

    public function getHtmlSource() {
        return $this->source();
    }

    public function getXpathCount($string) {
        return count($this->elements($this->using('xpath')->value($string)));
    }

    public function mouseOver($locator) {
        $this->moveto($this->getCssSelectorFromSelenium1Stuff($locator));
    }

    public function assertTextPresent($string) {
        $this->assertElementContainsText('body', $string);
    }

    public function assertTextNotPresent($string) {
        $this->assertElementNotContainsText('body', $string);
    }

    public function isTextPresent($string) {
        return strpos($this->getText('body'), $string) === false;
    }

    public function isChecked($locator) {
        return $this->getCssSelectorFromSelenium1Stuff($locator)
            ->attribute('checked') == 'checked' || $this->getCssSelectorFromSelenium1Stuff($locator)->selected();
    }

    public function getAttribute($locator, $attribute_name) {
        return $this->getCssSelectorFromSelenium1Stuff($locator)
            ->attribute($attribute_name);
    }

    public function getValue($selector) {
        return $this->getCssSelectorFromSelenium1Stuff($selector)
            ->value();
    }

    public function selectByLabel($selector, $item) {
        parent::select($this->getCssSelectorFromSelenium1Stuff($selector))->selectOptionByLabel($item);
    }

    public function selectByValue($selector, $item) {
        parent::select($this->getCssSelectorFromSelenium1Stuff($selector))->selectOptionByValue($item);
    }

    public function assertChecked($selector) {
        \PHPUnit\Framework\Assert::assertTrue($this->getCssSelectorFromSelenium1Stuff($selector)->selected());
    }

    public function assertNotChecked($selector) {
        \PHPUnit\Framework\Assert::assertFalse($this->getCssSelectorFromSelenium1Stuff($selector)->selected());
    }
}

/**
 * Test case that contains
 * - Error handling controlled by Zoetrope
 * - Convenience methods
 */
class Zoetrope_SeleniumTestCase extends SeleniumTestCase_Selenium1Wrapper {

    // TODO: why is this public?

    public $selenium_timeout;
    public $captureScreenshotOnFailure;
    public $screenshotUrl;
    public $screenshotPath;

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
            $target_browser = 'chrome';
        }
        if(!isset($target_url)) {
            $target_url = 'http://targeturl.notset.example.com/';
        }

        if(isset($this->selenium_timeout)) {
            $this->setDefaultWaitUntilTimeout($this->selenium_timeout / 1000);
        }
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
        $screenshotNumber = 0;
        $filename = substr($this->getTestId(), 0, 50) . '_' . $screenshotNumber++ . '.png';
        $filepath = $this->screenshotPath . '/' . $filename;
        while (file_exists($filepath) && $screenshotNumber != 50) {
            $filename = substr($this->getTestId(), 0, 50) . '_' . $screenshotNumber++ . '.png';
            $filepath = $this->screenshotPath . '/' . $filename;
        }
        if ($screenshotNumber == 50) {
            throw new Exception('Screenshot already exist [' . $filepath . ']. Logical flaw in Zoetrope_SeleniumTestCase.');
        }

        $screenshot = $this->currentScreenshot();
        file_put_contents($filepath, $screenshot);
        echo 'Screenshot: ' . $filename . PHP_EOL;
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

    var $waitForVariable;
    public function setWaitForVariable($i) {
        $this->waitForVariable = $i;
    }
    public function getWaitForVariable() {
        return $this->waitForVariable;
    }

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
        $this->waitUntil(function() {
            return $this->execute(array('script' => 'return document.readyState;', 'args' => array())) == 'complete';
        }, $timeout);
        //parent::waitForPageToLoad($timeout);
        // Not needed in Selenium 2?
        $end = time();
        if (($end - $start) >= ($timeout / 1000)) {
            throw new SeleniumTimeoutException('Timed out after '.$timeout.'ms.');
        }
    }

    public function openAndWait ($url, $waittime=null) {
        if ($waittime == null) {
            $waittime = $this->selenium_timeout;
        }
        $this->url($url);
        $this->waitForPageToLoad($waittime);
    }

    public function goBackAndWait ($waittime=null) {
        if ($waittime == null) {
            $waittime = $this->selenium_timeout;
        }
        $this->back();
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
            if ($second >= 10) {
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

    public function waitForTextPresent ($pattern) {
        $this->setWaitForVariable($pattern);
        $this->waitUntil(function(Zoetrope_SeleniumTestCase $testCase) {
            return strpos($this->getText('body'), $testCase->getWaitForVariable()) !== FALSE;
        });
    }

    public function waitForElementPresent($locator) {
        for ($second = 0; ; $second++) {
            if ($second >= 10) {
                $this->fail('Timeout waiting for element "' . $locator . '"');
            }
            try {
                if ($this->isElementPresent($locator)) {
                    break;
                }
            } catch (Exception $e) {}
            sleep(1);
        }
    }

    public function waitForElementNotPresent($locator, $description = null) {
        $second = 0;
        while ($this->isElementPresent($locator) && $second < 10) {
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

    public function onNotSuccessfulTest($e) {
        if ($this->captureScreenshotOnFailure) {
            $this->captureScreenshotOf('');
        }

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
