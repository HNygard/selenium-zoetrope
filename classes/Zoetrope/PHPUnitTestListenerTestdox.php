<?php

/**
 *
 * Inspired by
 * http://www.phpunit.de/manual/3.6/en/extending-phpunit.html#extending-phpunit.examples.SimpleTestListener.php
 * http://www.phpunit.de/manual/3.6/en/appendixes.configuration.html#appendixes.configuration.test-listeners
 * http://aaronsaray.com/blog/2011/12/20/add-phpunit-listeners-to-watch-for-long-running-tests/
 * http://roysimkes.net/blog/2008/11/writing-you-own-phpunit-listener/
 *
 * @author hallvard.nygard
 */
class Zoetrope_PHPUnitTestListenerTestdox extends PHPUnit_TextUI_ResultPrinter implements PHPUnit_Framework_TestListener {

    private function printTestdox (PHPUnit_Framework_Test $test, $char) {

        if($test instanceof Zoetrope_SeleniumTestCase) {
            /* @var $test Zoetrope_SeleniumTestCase */
            $test_name = $test->getNameHumanReadable();
        }
        else {
            $test_name = 'Unknown test in '.get_class($test);
        }

        echo '['.$char.'] '.$test_name.chr(10);
    }

    /**
     * An error occurred.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception              $e
     * @param  float                  $time
     */
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        //echo __METHOD__.chr(10);
        //parent::addError($test, $e, $time);

        $this->lastTestFailed = TRUE;
        $this->printTestdox($test, ' ');
    }

    /**
     * A failure occurred.
     *
     * @param  PHPUnit_Framework_Test                 $test
     * @param  PHPUnit_Framework_AssertionFailedError $e
     * @param  float                                  $time
     */
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        //echo __METHOD__.chr(10);
        //parent::addFailure($test, $e, $time);


        $this->lastTestFailed = TRUE;
        $this->printTestdox($test, ' ');


        /*
        echo chr(10);
        echo '### EXCEPTION: '.get_class($e).chr(10);
        echo $e->getMessage().chr(10);
        echo $e->getTraceAsString().chr(10);

        */
    }

    /**
     * Incomplete test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception              $e
     * @param  float                  $time
     */
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        //echo __METHOD__.chr(10);
        //parent::addIncompleteTest($test, $e, $time);

        $this->lastTestFailed = true;
        $this->printTestdox($test, 'I');
    }

    /**
     * Skipped test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception              $e
     * @param  float                  $time
     * @since  Method available since Release 3.0.0
     */
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        //echo __METHOD__.chr(10);
        //parent::addSkippedTest($test, $e, $time);

        $this->lastTestFailed = true;
        $this->printTestdox($test, ' ');

    }

    /**
     * A test suite started.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        //echo __METHOD__.chr(10);

        parent::startTestSuite($suite);
    }

    /**
     * A test suite ended.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        //echo __METHOD__.chr(10);

        parent::endTestSuite($suite);
    }

    /**
     * A test started.
     *
     * @param  PHPUnit_Framework_Test $test
     */
    public function startTest(PHPUnit_Framework_Test $test)
    {
        //echo __METHOD__.chr(10);

        parent::startTest($test);
    }

    /**
     * A test ended.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  float                  $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        //echo __METHOD__.chr(10);

        if(!$this->lastTestFailed) {
            $this->printTestdox($test, 'X');
            $this->lastTestFailed = true; // Make parent::endTest() shut up
        }

        parent::endTest($test, $time);
    }
}
