<?php
namespace eTools\Tests\Utils;

use eTools\Utils\Singleton;

class SingletonTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		Singleton::clear_instances();
	}

	/**
	 * This tests ensures that the Singleton::getInstance() method always returns the same object every time you call it
	 */
	public function testGetInstance() {
		/** @var SingletonSubclass $obj */
		$obj = SingletonSubclass::getInstance();
		$this->assertEquals(false, $obj->getCheck());
		$obj->toggleCheck();

		/** @var SingletonSubclass $obj2 */
		$obj2 = SingletonSubclass::getInstance();
		$this->assertEquals(true, $obj2->getCheck());

		$obj2->toggleCheck();
		$this->assertEquals(false, $obj->getCheck());
	}

	public function testGetInstanceSetsConstructorArgs() {
		/** @var SingletonSubclass $obj */
		$obj = SingletonSubclass::getInstance('arg1', 'arg2');
		$this->assertEquals('arg1', $obj->getConstructorArg1());
		$this->assertEquals('arg2', $obj->getConstructorArg2());
	}

	/**
	 * This test ensures that the Singleton::set_override_instance() method overrides classes correctly
	 */
	public function testOverrideInstances() {
		// Before setting an override, we should get the normal class
		$singletonSubclass = SingletonSubclass::getInstance();
		$this->assertInstanceOf('eTools\Tests\Utils\SingletonSubclass', $singletonSubclass);

		// Set the override and try again, we should now get the overridden method
		Singleton::set_override_instance(
			'eTools\Tests\Utils\SingletonSubclass',
			'eTools\Tests\Utils\SingletonOverrideSubclass'
		);

		// Ensure it hasn't changed the existing instance somehow
		$this->assertInstanceOf('eTools\Tests\Utils\SingletonSubclass', $singletonSubclass);
		$this->assertInstanceOf('eTools\Tests\Utils\SingletonOverrideSubclass', SingletonSubclass::getInstance());

		// Ensure override works every time once it's set
		$this->assertInstanceOf('eTools\Tests\Utils\SingletonOverrideSubclass', SingletonSubclass::getInstance());
	}
}

/**
 * Class SingletonSubclass
 * @package eTools\Tests\Utils
 *
 * This is a sample class - it should be used only for testing
 */
class SingletonSubclass extends Singleton {
	private $check = false;
	private $constructorArg1 = null;
	private $constructorArg2 = null;

	public function __construct($arg1 = null, $arg2 = null) {
		$this->constructorArg1 = $arg1;
		$this->constructorArg2 = $arg2;
	}

	public function toggleCheck() {
		$this->check = !$this->check;
	}

	public function getCheck() {
		return $this->check;
	}

	public function getConstructorArg1() {
		return $this->constructorArg1;
	}

	public function getConstructorArg2() {
		return $this->constructorArg2;
	}
}

class SingletonOverrideSubclass extends Singleton {}