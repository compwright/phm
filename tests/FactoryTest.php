<?php

namespace phm\Test;

require_once dirname(__DIR__).'/src/phm/Exception/SemaphoreException.php';
require_once dirname(__DIR__).'/src/phm/Exception/SharedMemoryException.php';
require_once dirname(__DIR__).'/src/phm/Exception/MessageQueueException.php';
require_once dirname(__DIR__).'/src/phm/SharedMemory.php';
require_once dirname(__DIR__).'/src/phm/MessageQueue.php';
require_once dirname(__DIR__).'/src/phm/Lock/Mutex.php';
require_once dirname(__DIR__).'/src/phm/Lock/Semaphore.php';
require_once dirname(__DIR__).'/src/phm/Lock/Keyring.php';
require_once dirname(__DIR__).'/src/phm/Lock/Lightswitch.php';
require_once dirname(__DIR__).'/src/phm/Factory.php';

use phm\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
	public function testGetInstance()
	{
		$this->assertInstanceOf('phm\Factory', Factory::getInstance());
		$this->assertEquals(Factory::getInstance(), Factory::getInstance());
	}

	public function testNewMutex()
	{
		$id = 'phm\Test\FactoryTest::testNewMutex';
		$factory = Factory::getInstance();
		$this->assertInstanceOf('phm\Lock\Mutex', $factory->newMutex($id));
		$this->assertEquals($factory->newMutex($id)->getKey(), $factory->newMutex($id)->getKey());
	}

	public function testNewSharedMemory()
	{
		$id = 'phm\Test\FactoryTest::testNewSharedMemory';
		$factory = Factory::getInstance();
		$this->assertInstanceOf('phm\SharedMemory', $factory->newSharedMemory($id, 128));
		$this->assertEquals($factory->newSharedMemory($id, 128)->getKey(), $factory->newSharedMemory($id, 128)->getKey());
	}

	public function testNewMessageQueue()
	{
		$id = 'phm\Test\FactoryTest::testNewMessageQueue';
		$factory = Factory::getInstance();
		$this->assertInstanceOf('phm\MessageQueue', $factory->newMessageQueue($id));
		$this->assertEquals($factory->newMessageQueue($id)->getKey(), $factory->newMessageQueue($id)->getKey());
	}

	public function testNewSemaphore()
	{
		$id = 'phm\Test\FactoryTest::testNewSemaphore';
		$factory = Factory::getInstance();
		$this->assertInstanceOf('phm\Lock\Semaphore', $factory->newSemaphore($id));
		$this->assertEquals($factory->newSemaphore($id)->getKeys(), $factory->newSemaphore($id)->getKeys());
	}

	public function testNewLightswitch()
	{
		$id = 'phm\Test\FactoryTest::testNewLightswitch';
		$factory = Factory::getInstance();
		$this->assertInstanceOf('phm\Lock\Lightswitch', $factory->newLightswitch($id));
		$this->assertEquals($factory->newLightswitch($id)->getKeys(), $factory->newLightswitch($id)->getKeys());
	}
}

