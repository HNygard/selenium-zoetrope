<?php

/**
 * @author hallvard.nygard
 */
class Zoetrope_Result_Test {
    private $name;
    private $hasError;


    /**
     * @param  bool  $hasError
     */
    public function setHasError($hasError)
    {
        $this->hasError = $hasError;
    }

    public function hasError()
    {
        return $this->hasError;
    }

    /**
     * @param  string  $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
