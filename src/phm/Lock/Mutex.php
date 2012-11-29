<?php

namespace phm\Lock;

use phm\Exception\SemaphoreException;

class Mutex
{
	/**
	 * System V IPC key used to access a semaphore
	 * @var integer $key
	 */
	protected $key;

	/**
	 * System V semaphore ID
	 * @var integer $sem
	 */
	protected $sem;

	/**
	 * Array of timestamps for each un-released acquisition of this semaphore by the current process
	 * @var array $acquisitions
	 */
	protected $acquisitions = array();

	/**
	 * Constructor, connect to a semaphore
	 * @param string $key System V IPC key
	 * @param string $maxCount Sets the range for a counting semaphore, or the number of processes that may access it
	 * @throws phm\Exception\SempahoreException
	 */
	public function __construct($key, $maxCount = 1)
	{
		$this->key = $key;
		$this->sem = sem_get($key, $maxCount);
		if ($this->sem === FALSE)
		{
			throw new SemaphoreException("Could not open semaphore $key");
		}
	}

	/**
	 * Get the IPC key
	 * @return integer
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * Acquire the mutex
	 * @throws phm\Exception\SemaphoreException
	 * @throws \LogicException
	 */
	public function acquire()
	{
		if ( ! empty($this->acquisitions))
		{
			throw new \LogicException('Cannot acquire a mutex without releasing it first');
		}

		if (@sem_acquire($this->sem))
		{
			array_push($this->acquisitions, microtime(TRUE));
		}
		else
		{
			throw new SemaphoreException("Failed to acquire semaphore $this->key");
		}
	}

	/**
	 * Release the mutex
	 * @throws phm\Exception\SemaphoreException
	 * @throws \LogicException
	 */
	public function release()
	{
		if (empty($this->acquisitions))
		{
			throw new \LogicException('Cannot release a mutex without acquiring it first');
		}
		
		if (@sem_release($this->sem))
		{
			array_pop($this->acquisitions);
		}
		else
		{
			throw new SemaphoreException("Failed to release semaphore $this->key");
		}
	}

	/**
	 * Get an array of timestamps for each outstanding acquisition of this semaphore by the current process
	 * @return array
	 */
	public function getAcquisitions()
	{
		return $this->acquisitions;
	}

	/**
	 * Nuke the semaphore and this instance
	 * @throws phm\Exception\SemaphoreException
	 */
	public function delete()
	{
		if ( ! @sem_remove($this->sem))
		{
			throw new SemaphoreException("Could not remove semaphore $this->key");
		}
	}
}

