<?php

/**
 * @author hallvard.nygard
 */
class Zoetrope_Result {

    private $timeStart;
    private $baseUrl;
    private $testsDirectory;
    private $isOnScreen;
    private $externalUrl;
    private $testsDirectoryRelative;
    private $duration;

    /* @var $tests Zoetrope_Result_Testcase[] */
    private $tests = array();


    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param bool $isOnScreen
     */
    public function setIsOnScreen($isOnScreen)
    {
        $this->isOnScreen = $isOnScreen;
    }

    /**
     * @return bool
     */
    public function isOnScreen()
    {
        return $this->isOnScreen;
    }

    public function setTestsDirectory($testsDirectory)
    {
        $this->testsDirectory = $testsDirectory;
    }

    public function getTestsDirectory()
    {
        return $this->testsDirectory;
    }

    public function setTimeStart($timeStart)
    {
        $this->timeStart = $timeStart;
    }

    public function getTimeStart()
    {
        return $this->timeStart;
    }

    public function setExternalUrl($externalUrl)
    {
        $this->externalUrl = $externalUrl;
    }

    public function getExternalUrl()
    {
        return $this->externalUrl;
    }

    public function setTestsDirectoryRelative($testsDirectoryRelative)
    {
        $this->testsDirectoryRelative = $testsDirectoryRelative;
    }

    public function getTestsDirectoryRelative()
    {
        return $this->testsDirectoryRelative;
    }

    public function addTest(Zoetrope_Result_Testcase $test)
    {
        $this->tests[] = $test;
    }

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

}
