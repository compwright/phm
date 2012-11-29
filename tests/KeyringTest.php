<?php

namespace phm\Test;

require_once dirname(__DIR__).'/src/phm/Exception/SemaphoreException.php';
require_once dirname(__DIR__).'/src/phm/Exception/SharedMemoryException.php';
require_once dirname(__DIR__).'/src/phm/SharedMemory.php';
require_once dirname(__DIR__).'/src/phm/Lock/Mutex.php';
require_once dirname(__DIR__).'/src/phm/Lock/Keyring.php';

use phm\Lock\Keyring;
use phm\Lock\Mutex;
use phm\SharedMemory;

class KeyringTest extends \PHPUnit_Framework_TestCase
{
	private $keyring;

	public function setUp()
	{
		$this->keyring = new Keyring(
			new Mutex(ftok(__FILE__, 1)),
			new SharedMemory(ftok(__FILE__, 2), 8*1024)
		);
	}

	public function testConstructor()
	{
		$this->assertInstanceOf('phm\Lock\Keyring', $this->keyring);
	}

	public function testCount()
	{
		$this->assertGreaterThanOrEqual(0, count($this->keyring));
		$this->assertEquals(count($this->keyring), $this->keyring->count());
	}

	public function testStat()
	{
		$stat = $this->keyring->stat();
		$this->assertTrue(is_array($stat));
		$this->assertCount(count($this->keyring), $stat);
	}

	public function testGenerateKey()
	{
		$this->assertTrue(is_integer(Keyring::generateKey()));
	}

	/**
	 * @expectedException \Exception
	 */
	public function testAddKey()
	{
		// Generate the key
		$id    = 'phm\Test\KeyringTest';
		$key   = $this->keyring->addKey($id);
		$stat  = $this->keyring->stat();
		$count = count($this->keyring);

		$this->assertTrue(is_integer($key));
		$this->assertArrayHasKey($id, $stat);

		$this->assertArrayHasKey('owner', $stat[$id]);
		$this->assertEquals(posix_getpid(), $stat[$id]['owner']);

		$this->assertArrayHasKey('created', $stat[$id]);
		$this->assertLessThanOrEqual(time(), $stat[$id]['created']);
		
		$this->assertArrayHasKey('file', $stat[$id]);
		$this->assertEquals(__FILE__, $stat[$id]['file']);

		$this->assertArrayHasKey('line',    $stat[$id]);

		$this->assertArrayHasKey('caller',  $stat[$id]);
		$this->assertEquals('phm\Test\KeyringTest->testAddKey()', $stat[$id]['caller']);

		// Regenerate the key (overwrite)
		$key2 = $this->keyring->addKey($id, true);

		$this->assertTrue($key2 != $key);
		$this->assertCount($count, $this->keyring);

		// The key already exists and $overwrite is false,
		// so this should throw an Exception
		$key3 = $this->keyring->addKey($id, false);
	}

	public function testGetKey()
	{
		// Generate the key
		$id  = uniqid();
		$cnt = count($this->keyring);
		$key = $this->keyring->getKey($id);
		
		$this->assertGreaterThan($cnt, count($this->keyring));
		$this->assertEquals($key, $this->keyring->getKey($id));

		$cnt = count($this->keyring);

		// Regenerate the key
		$key2 = $this->keyring->getKey($id, true);

		$this->assertCount($cnt, $this->keyring);
		$this->assertFalse($key == $key2);
	}

	public function testRemoveIdentifier()
	{
		// Generate the key
		$id  = uniqid();
		$cnt = count($this->keyring);
		$key = $this->keyring->addKey($id);
		
		$this->assertGreaterThan($cnt, count($this->keyring));

		$cnt = count($this->keyring);
		$this->keyring->removeIdentifier($id);

		$this->assertLessThan($cnt, count($this->keyring));

		$stat = $this->keyring->stat();

		$this->assertFalse(isset($stat[$id]));
	}

	public function testRemoveKey()
	{
		// Generate the key
		$id  = uniqid();
		$cnt = count($this->keyring);
		$key = $this->keyring->addKey($id);
		
		$this->assertGreaterThan($cnt, count($this->keyring));

		$cnt = count($this->keyring);
		$this->keyring->removeKey($key);

		$this->assertLessThan($cnt, count($this->keyring));

		$stat = $this->keyring->stat();

		$this->assertFalse(isset($stat[$id]));
	}

	public function testDelete()
	{
		$this->keyring->delete();
		$this->assertTrue(true);
	}
}

