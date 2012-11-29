<?php

namespace phm\Test;

require_once dirname(__DIR__).'/src/phm/Exception/MessageQueueException.php';
require_once dirname(__DIR__).'/src/phm/MessageQueue.php';

use phm\MessageQueue;

class MessageQueueTest extends \PHPUnit_Framework_TestCase
{
	protected $queue;

	public function setUp()
	{
		$this->queue = new MessageQueue(ftok(__FILE__, 'a'));
	}

	public function tearDown()
	{
		if ($this->queue)
		{
			$this->queue->delete();
			unset($this->queue);
		}
	}

	public function testConstructor()
	{
		$this->assertInstanceOf('phm\MessageQueue', $this->queue);
		$this->assertArrayHasKey('msg_qbytes', $this->queue->getStatus());
	}

	public function testPermissions()
	{
		$this->assertTrue($this->queue->isConfigurable());
		$this->assertEquals(posix_getuid(), $this->queue->getOwner());
		$this->queue->setPermissions(posix_getuid(), posix_getgid(), 0600);
		$stats = $this->queue->getStatus();
		$this->assertEquals(0600, $stats['msg_perm.mode']);
	}

	public function testResize()
	{
		$this->queue->resize(256);
		$this->assertEquals(256, $this->queue->getSize());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSendFailMemoryFull()
	{
		$this->queue->resize(1024);
		$this->queue->send(str_repeat('.', 2048));
	}

	public function testSendReceive()
	{
		$message = 'Hello, world';
		$this->queue->send($message, 1);
		$this->assertCount(1, $this->queue);
		$this->assertEquals($message, $this->queue->receive(1));
		$this->assertEquals($message, $this->queue->getLastMessage());
		$this->assertEquals(1, $this->queue->getLastMessageType());
		$this->assertCount(0, $this->queue);
	}

	public function testDelete()
	{
		$queue = new MessageQueue(ftok(__FILE__, 'b'));
		$queue->delete();
		$this->assertTrue(true); // if we didn't get an exception, then it passes
	}

	/*
	 * These next three should fail since the shared memory segment has been deleted.
	 */

	/**
	 * @expectedException phm\Exception\MessageQueueException
	 */
	public function testDeleteFail()
	{
		$queue = new MessageQueue(ftok(__FILE__, 'b'));
		$queue->delete();
		$queue->delete();
	}
}

