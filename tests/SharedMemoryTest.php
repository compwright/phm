<?php

namespace phm\Test;

require_once dirname(__DIR__).'/src/phm/Exception/SharedMemoryException.php';
require_once dirname(__DIR__).'/src/phm/SharedMemory.php';

use phm\SharedMemory;

class SharedMemoryTest extends \PHPUnit_Framework_TestCase
{
	protected $shm;

	public function setUp()
	{
		$this->shm = new SharedMemory(ftok(__FILE__, 'a'), 128);
	}

	public function tearDown()
	{
		//$this->shm->delete();
		unset($this->shm);
	}

	public function testConstructor()
	{
		$this->assertInstanceOf('phm\SharedMemory', $this->shm);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSetFailResource()
	{
		$this->shm->resource = fopen(tempnam(sys_get_temp_dir(), 'tst'), 'r');
	}

	/**
	 * @expectedException phm\Exception\SharedMemoryException
	 */
	public function testSetFailMemoryFull()
	{
		$this->shm->array = str_repeat('*', 256);
	}

	public function testSetGet()
	{
		$this->shm->foo = 'Bar';
		$shm = new SharedMemory(ftok(__FILE__, 'a'), 128);
		$this->assertTrue(isset($shm->foo));
		$this->assertEquals('Bar', $shm->foo);
	}

	public function testUnset()
	{
		$shm = new SharedMemory(ftok(__FILE__, 'a'), 128);
		
		$this->assertFalse(isset($shm->baz));
		unset($this->shm->baz);
		$this->assertFalse(isset($shm->baz));

		unset($this->shm->foo);
		$this->assertFalse(isset($shm->foo));
	}

	public function testDelete()
	{
		$shm = new SharedMemory(ftok(__FILE__, 'a'), 128);
		$shm->delete();
		$this->assertTrue(true); // if we didn't get an exception, then it passes
	}

	/*
	 * These next three should fail since the shared memory segment has been deleted.
	 */

	/**
	 * @expectedException phm\Exception\SharedMemoryException
	 */
	public function testSetFail()
	{
		$this->assertInstanceOf('phm\SharedMemory', $this->shm);
		$this->shm->foo = str_repeat('.', 256);
	}

	/**
	 * @expectedException phm\Exception\SharedMemoryException
	 */
	public function testDeleteFail()
	{
		$this->assertInstanceOf('phm\SharedMemory', $this->shm);
		$this->shm->delete();
		$this->shm->delete();
	}
}

