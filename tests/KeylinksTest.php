<?php
/**
 * Test class for Keylinks
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Joshua Odmark <support@keylinks.co>
 * @copyright     Copyright 2015, Joshua Odmark <support@keylinks.co>
 * @link          https://github.com/odmarkj/keylinks-api
 * @since         0.0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 **/

/**
 * KeylinksTest class
 *
 */
 
class KeylinksTest extends PHPUnit_Framework_TestCase{
    public function setUp(){
        parent::setUp();
    }

    public function tearDown(){
        parent::tearDown();
    }

    /**
     * @covers Keylinks::filter
     */
    public function testFilter(){
	    $html = '';
	    $filtered = Keylinks::filter($html);
        $this->assertEquals(strlen($html), strlen($filtered));
    }
}
?>