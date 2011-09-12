<?php

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

/**
 * http://seleniumhq.org/projects/remote-control/
 * http://pear.phpunit.de/
 */


/*
This test executes fine:

yourusername@computer:/path/to/selenium-zoetrope/tests$ phpunit Tests.php 
PHPUnit 3.5.14 by Sebastian Bergmann.

.

Time: 25 seconds, Memory: 3.50Mb

OK (1 test, 0 assertions)


*/

class SeleniumTestTest extends PHPUnit_Extensions_SeleniumTestCase {
	protected function setUp() {
		$this->setBrowser ( '*firefox' );
		$this->setHost ('seleniumserver1.dev.recondo.no');
		$this->setPort (4444);
		$this->setBrowserUrl ( 'http://en.wikipedia.org/');
	}
	
	public function testCanOpenMainPage() {
		$this->open ( "/" );
	}
}
