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
		global $selenium_server_host, $selenium_server_port, $target_browser, $target_url;
		
		$this->setBrowser ( $target_browser );
		$this->setHost ($selenium_server_host);
		$this->setPort ($selenium_server_port);
		$this->setBrowserUrl ( $target_url );
	}
	
	public function testCanOpenMainPage() {
		$this->open ( "/" );
	}
}
