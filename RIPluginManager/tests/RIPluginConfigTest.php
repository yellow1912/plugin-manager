<?php
/*
require(dirname(__FILE__).'/../../../includes/configure.php');
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . DIR_FS_CATALOG);
chdir(DIR_FS_CATALOG); 
require_once('includes/application_top.php');

require_once 'PHPUnit/Autoload.php';
*/

require_once dirname(__FILE__) . '/../etc/RIPluginProvider.php';


/**
 * @backupGlobals disabled
 */
class RIPluginConfigTest extends PHPUnit_Framework_TestCase {
	
  /**
   * @var RIPluginConfig
   */
  protected $object;
	protected $backupGlobals = FALSE;
	
	public function run(PHPUnit_Framework_TestResult $result = NULL)
    {
        $this->setPreserveGlobalState(false);
        return parent::run($result);
    }
    
  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
      $this->object = new RIPluginConfig;
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {

  }

  /**
   * tests both get and set methods at once
   */
  public function testSetGet() {
  	// test setting value
    $this->object->set('myplugin','config','name','value1');
    $this->assertEquals('value1', $this->object->get('myplugin','config','name'));
    
    // test setting base
    $this->object->set('myplugin','config','','value2');
    $this->assertEquals('value2', $this->object->get('myplugin','config',''));
    
    // test setting plugin only
    $this->object->set('myplugin','','','value3');
    $this->assertEquals('value3', $this->object->get('myplugin','',''));
  }

  /**
   * tests both __get and __set methods at once
   */
  public function test__set__get() {
    // test setting value
    $this->object->myplugin__config__name = 'value1';
    $this->assertEquals('value1', $this->object->myplugin__config__name);
    
    // test setting base
    $this->object->myplugin__config = 'value2';
    $this->assertEquals('value2', $this->object->myplugin__config);
    
    // test setting plugin only
    $this->object->myplugin = 'value3';
    $this->assertEquals('value3', $this->object->myplugin);
  }
  
  /**
   * @todo write a more complete test
   */
  public function testLoad() {
  	$this->object->load('RIPluginManager', 'config_local');
  	$this->assertArrayHasKey('config_local', $this->object->RIPluginManager);
  }
}