<?php

namespace phm\Test;

require_once dirname(__DIR__).'/src/phm/Exception/SemaphoreException.php';
require_once dirname(__DIR__).'/src/phm/Lock/Mutex.php';

use phm\Lock\Mutex;

class MutexTest extends \PHPUnit_Framework_TestCase
{
	protected $mutex;

	public function setUp()
	{
		$this->mutex = new Mutex(ftok(__FILE__, 'a'), 2);
	}

	public function tearDown()
	{
		//$this->mutex->delete();
		unset($this->mutex);
	}

	public function testConstructor()
	{
		$this->assertInstanceOf('phm\Lock\Mutex', $this->mutex);
	}

	public function testAcquireRelease()
	{
		$s = new Mutex(ftok(__FILE__, 'a'), 2);
		$s->acquire();
		$s->release();
		$this->assertTrue(true); // if we didn't get an exception, then it passes
	}

	public function testDelete()
	{
		$s = new Mutex(ftok(__FILE__, 'a'), 2);
		$s->delete();
		$this->assertTrue(true); // if we didn't get an exception, then it passes
	}

	/*
	 * These next three should fail since the mutex has been deleted.
	 */

	/**
	 * @expectedException phm\Exception\SemaphoreException
	 */
	public function testDeleteFail()
	{
		$this->assertInstanceOf('phm\Lock\Mutex', $this->mutex);
		$this->mutex->delete();
		$this->mutex->delete();
	}

	/**
	 * @expectedException phm\Exception\SemaphoreException
	 */
	public function testReleaseFail()
	{
		$this->assertInstanceOf('phm\Lock\Mutex', $this->mutex);
		$this->mutex->acquire();
		$this->mutex->delete();
		$this->mutex->release();
	}

	/**
	 * @expectedException phm\Exception\SemaphoreException
	 */
	public function testAcquireFail()
	{
		$this->assertInstanceOf('phm\Lock\Mutex', $this->mutex);
		$this->mutex->delete();
		$this->mutex->acquire();
	}
}

