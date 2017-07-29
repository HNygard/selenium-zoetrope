<?php

/**
 * @author hallvard.nygard
 */
class Zoetrope_Result_Testcase {

    private $className;
    private $videoWidth;
    private $videoHeight;
    private $logJunitXmlName;
    private $logJunitXml;

    private $numberOfAssertions;
    private $numberOfFailure;
    private $numberOfErrors;
    private $numberOfTests;
    private $runTime;

    /* @var $errors Zoetrope_Result_Error[] */
    private $errors = array();
    /* @var $failures Zoetrope_Result_Error[] */
    private $failures = array();

    /* var $screenshots string[] */
    private $screenshots = array();

    private $isUnstable = false;
    private $hasSkipped = false;
    private $hasErrors = false;
    private $hasFailures = false;

    private $sourcecode = 'No source code found.';
    private $sourcecodeNumberOfLines = 0;

    private $sourcecodeLinesError = array();
    private $sourcecodeLinesFailure = array();

    private $descriptionFilename;
    private $description;

    private $duration;

    /* @var $tests Zoetrope_Result_Test[] */
    private $tests = array();

    /* @var $systemOut String */
    private $systemOut;


    /**
     * @param int  $i  Line number
     * @return bool
     */
    public function lineHasError($i) {
        return isset($this->sourcecodeLinesError[$i]);
    }
    /**
     * @param int  $i  Line number
     * @return bool
     */
    public function lineHasFailure($i) {
        return isset($this->sourcecodeLinesFailure[$i]);
    }
    /**
     * @param int    $i          Line number
     * @param string $screenshot Screenshot filename
     */
    public function addLineError($i, $screenshot=false) {
        $this->sourcecodeLinesError[$i] = $screenshot;
    }
    /**
     * @param int    $i          Line number
     * @param string $screenshot Screenshot filename
     */
    public function addLineFailure($i, $screenshot=false) {
        $this->sourcecodeLinesFailure[$i] = $screenshot;
    }

    /**
     * @param int    $i          Line number
     */
    public function getScreenshotOnLine($i) {
        if (isset($this->sourcecodeLinesError[$i])) {
            return $this->sourcecodeLinesError[$i];
        }
        else if (isset($this->sourcecodeLinesFailure[$i])) {
            return $this->sourcecodeLinesFailure[$i];
        }
        else {
            return false;
        }
    }

    public function setClassName($className)
    {
        $this->className = $className;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function setVideoWidth($videoWidth)
    {
        $this->videoWidth = $videoWidth;
    }

    public function getVideoWidth()
    {
        return $this->videoWidth;
    }

    public function setVideoHeight($videoHeight)
    {
        $this->videoHeight = $videoHeight;
    }

    public function getVideoHeight()
    {
        return $this->videoHeight;
    }

    public function setLogJunitXmlName($logJunitXmlName)
    {
        $this->logJunitXmlName = $logJunitXmlName;
    }

    public function getLogJunitXmlName()
    {
        return $this->logJunitXmlName;
    }

    public function setLogJunitXml($logJunitXml)
    {
        $this->logJunitXml = $logJunitXml;
    }

    public function getLogJunitXml()
    {
        return $this->logJunitXml;
    }

    public function setNumberOfFailure($numberOfFailure)
    {
        $this->numberOfFailure = $numberOfFailure;
    }

    public function getNumberOfFailure()
    {
        return $this->numberOfFailure;
    }

    public function setNumberOfErrors($numberOfErrors)
    {
        $this->numberOfErrors = $numberOfErrors;
    }

    public function getNumberOfErrors()
    {
        return $this->numberOfErrors;
    }

    public function setNumberOfAssertions($numberOfAssertions)
    {
        $this->numberOfAssertions = $numberOfAssertions;
    }

    public function getNumberOfAssertions()
    {
        return $this->numberOfAssertions;
    }

    public function setNumberOfTests($numberOfTests)
    {
        $this->numberOfTests = $numberOfTests;
    }

    public function getNumberOfTests()
    {
        return $this->numberOfTests;
    }

    public function setRunTime($runTime)
    {
        $this->runTime = $runTime;
    }

    public function getRunTime()
    {
        return $this->runTime;
    }

    public function addFailure(Zoetrope_Result_Error $failure)
    {
        $this->failures[] = $failure;
    }

    public function getFailures()
    {
        return $this->failures;
    }

    public function addError(Zoetrope_Result_Error $error)
    {
        $this->errors[] = $error;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $screenshotFilename Filename of screenshot
     */
    public function addScreenshot($screenshotFilename) {
        $this->screenshots[] = $screenshotFilename;
    }

    /**
     * @return string[] Screenshot filenames
     */
    public function getScreenshots() {
        return $this->screenshots;
    }

    public function setSourcecode($sourcecode)
    {
        $this->sourcecode = $sourcecode;
    }

    public function getSourcecode()
    {
        return $this->sourcecode;
    }

    public function setSourcecodeNumberOfLines($sourcecodeNumberOfLines)
    {
        $this->sourcecodeNumberOfLines = $sourcecodeNumberOfLines;
    }

    public function getSourcecodeNumberOfLines()
    {
        return $this->sourcecodeNumberOfLines;
    }

    public function setHasErrorsInTests($hasErrors)
    {
        $this->hasErrors = $hasErrors;
    }

    public function hasErrorTests()
    {
        return $this->hasErrors;
    }

    public function setHasFailedTests($hasFailures)
    {
        $this->hasFailures = $hasFailures;
    }

    public function hasFailedTests()
    {
        return $this->hasFailures;
    }

    public function setHasSkippedTests($hasSkipped)
    {
        $this->hasSkipped = $hasSkipped;
    }

    public function hasSkippedTests()
    {
        return $this->hasSkipped;
    }

    public function setUnstableTest($isUnstable)
    {
        $this->isUnstable = $isUnstable;
    }

    public function isUnstableTest()
    {
        return $this->isUnstable;
    }

    public function setDescriptionFilename($descriptionFilename)
    {
        $this->descriptionFilename = $descriptionFilename;
    }

    public function getDescriptionFilename()
    {
        return $this->descriptionFilename;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function addTest(Zoetrope_Result_Test $test)
    {
        $this->tests[] = $test;
    }

    /**
     * @return Zoetrope_Result_Test[]
     */
    public function getTests()
    {
        return $this->tests;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function addSystemOut($param) {
        $this->systemOut = $param;
    }

    public function getSystemOut() {
        return $this->systemOut;
    }
}
