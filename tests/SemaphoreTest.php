<?php

namespace phm\Test;

require_once dirname(__DIR__).'/src/phm/Exception/SemaphoreException.php';
require_once dirname(__DIR__).'/src/phm/Exception/MessageQueueException.php';
require_once dirname(__DIR__).'/src/phm/Exception/SharedMemoryException.php';
require_once dirname(__DIR__).'/src/phm/MessageQueue.php';
require_once dirname(__DIR__).'/src/phm/SharedMemory.php';
require_once dirname(__DIR__).'/src/phm/Lock/Mutex.php';
require_once dirname(__DIR__).'/src/phm/Lock/Semaphore.php';

use phm\Lock\Mutex;
use phm\Lock\Semaphore;
use phm\SharedMemory;
use phm\MessageQueue;

class SemaphoreTest extends \PHPUnit_Framework_TestCase
{
	protected $semaphore;

	public function setUp()
	{
		$this->semaphore = new Semaphore(
			new Mutex(ftok(__FILE__, 1)),
			new SharedMemory(ftok(__FILE__, 2), 256),
			new MessageQueue(ftok(__FILE__, 3)),
			3
		);
	}

	public function tearDown()
	{
		if ($this->semaphore)
		{
			$this->semaphore->delete();
			unset($this->semaphore);
		}
	}

	public function testConstructor()
	{
		$this->assertInstanceOf('phm\Lock\Semaphore', $this->semaphore);
	}

	public function testRead()
	{
		$this->assertEquals(3, $this->semaphore->read());
	}

	public function testUpDown()
	{
		$this->semaphore->down();
		$this->assertEquals(2, $this->semaphore->read());
		$this->semaphore->down();
		$this->assertEquals(1, $this->semaphore->read());
		$this->semaphore->down();
		$this->assertEquals(0, $this->semaphore->read());

		$this->semaphore->up();
		$this->assertEquals(1, $this->semaphore->read());
		$this->semaphore->up();
		$this->assertEquals(2, $this->semaphore->read());
		$this->semaphore->up();
		$this->assertEquals(3, $this->semaphore->read());
	}

	public function testDelete()
	{
		$semaphore = new Semaphore(
			new Mutex(ftok(__FILE__, 4)),
			new SharedMemory(ftok(__FILE__, 5), 256),
			new MessageQueue(ftok(__FILE__, 6)),
			3
		);
		$semaphore->delete();
		$this->assertTrue(true); // if we didn't get an exception, then it passes
	}
}

