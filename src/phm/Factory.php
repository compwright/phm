<?php

namespace phm;

use phm\Exception\SemaphoreException;
use phm\Lock\Keyring;
use phm\Lock\Mutex;
use phm\Lock\Semaphore;
use phm\Lock\Lightswitch;
use phm\SharedMemory;
use phm\MessageQueue;

/**
 * Process Factory class
 * 
 * @author Jonathon Hill
 * @package phm
 */
class Factory
{
	protected $keyring;
	protected $retry_limit = 0;
	protected static $instance;

	/**
	 * Factory constructor
	 *
	 * @var phm\Lock\Keyring $keyring Lock manager object (keyring)
	 */
	public function __construct(Keyring $keyring)
	{
		$this->keyring = $keyring;
	}

	/**
	 * Adjust the retry limit for creating object instances, in case a key is already in use
	 *
	 * @var integer $limit
	 */
	public function setRetryLimit($limit)
	{
		$this->retry_limit = $limit;
	}

	/**
	 * Return a singleton phm\Factory instance, with a keyring shared among all processes using this factory
	 *
	 * @return phm\Factory
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self(
				new Keyring(
					new Mutex(ftok(__FILE__, 1)),
					new SharedMemory(ftok(__FILE__, 2), 8*1024)
				)
			);
		}

		return self::$instance;
	}

	public function getKeyring()
	{
		return $this->keyring;
	}

	/**
	 * Instantiate a phm object, and retry a limited number of times if an error occurs
	 * while constructing
	 *
	 * Methods:
	 *   newMutex($identifier, $maxCount)
	 *   newSharedMemory($identifier, $bytes)
	 *   newMessageQueue($identifier)
	 *
	 * @var string $method
	 * @var array $arguments
	 * @return unknown
	 */
	public function __call($method, $arguments)
	{
		if ( ! method_exists($this, '_' . $method))
		{
			throw new \BadMethodCallException("Method $method does not exist on this object");
		}

		$i = 0;

		do
		{
			try
			{
				return call_user_func_array(array($this, '_' . $method), $arguments);
			}
			catch (Exception $e)
			{
				$this->keyring->getKey($identifier, true); // regenerate the key
				$i++;
			}
		}
		while ($i < $this->retry_limit);

		throw new \Exception("Failed to get a key after $i attempts", 0, $e);
	}

	/**
	 * Instantiate a phm\Lock\Mutex object
	 *
	 * @var string $identifier
	 * @var integer $maxCount
	 * @return phm\Mutex
	 */
	protected function _newMutex($identifier, $maxCount = 1)
	{
		$key = $this->keyring->getKey($identifier);
		return new Mutex($key, $maxCount);
	}

	/**
	 * Instantiate a phm\SharedMemory object
	 *
	 * @var string $identifier
	 * @var integer $bytes
	 * @return phm\SharedMemory
	 */
	protected function _newSharedMemory($identifier, $bytes = NULL)
	{
		$key = $this->keyring->getKey($identifier);
		return new SharedMemory($key, $bytes);
	}

	/**
	 * Instantiate a phm\MessageQueue object
	 *
	 * @var string $identifier
	 * @return phm\MessageQueue
	 */
	protected function _newMessageQueue($identifier)
	{
		$key = $this->keyring->getKey($identifier);
		return new MessageQueue($key);
	}

	/**
	 * Instantiate a phm\Lock\Semaphore object
	 *
	 * @var string $identifier
	 * @var integer $maxCount
	 * @return phm\Lock\Semaphore
	 */
	public function newSemaphore($identifier, $maxCount = 1)
	{
		return new Semaphore(
			$this->newMutex($identifier . '_lck'),
			$this->newSharedMemory($identifier . '_shm', 512),
			$this->newMessageQueue($identifier . '_msg'),
			$maxCount
		);
	}

	/**
	 * Instantiate a phm\Lock\Lightswitch object
	 *
	 * @var string $identifier
	 * @return phm\Lock\Lightswitch
	 */
	public function newLightswitch($identifier)
	{
		return new Lightswitch(
			$this->newMutex($identifier . '_lck'),
			$this->newSharedMemory($identifier . '_shm', 512),
			$this->newSemaphore($identifier . '_sem', 1)
		);
	}
}

