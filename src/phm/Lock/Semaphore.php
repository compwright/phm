<?php

namespace phm\Lock;

use phm\Lock\Mutex;
use phm\SharedMemory;
use phm\MessageQueue;
use phm\Exception\SemaphoreException;

// A true readable counting semaphore, implemented with a mutex,
// a counter in shared memory, and an IPC message queue

class Semaphore
{
	const SEM_ACQUIRE = 2;

	protected $max_count;
	protected $mutex;
	protected $shm;
	protected $queue;

	/**
	 * Constructor, connect to a semaphore
	 * @param phm\Mutex $mutex Mutex for accessing shared memory
	 * @param phm\SharedMemory $shm Shared memory segment to hold the semaphore value
	 * @param phm\MessageQueue $queue Message queue for notifying waiting processes when the semaphore has incremented
	 * @param integer $max_count Maximum semaphore value
	 */
	public function __construct(Mutex $mutex, SharedMemory $shm, MessageQueue $queue, $max_count = NULL)
	{
		$this->mutex = $mutex;
		$this->shm   = $shm;
		$this->queue = $queue;

		if (isset($this->shm->max_count))
		{
			// We assume that since the max_count is only set when first initialized,
			// it is probably safe to read this value without locking first.
			// This would not work for reading the semaphore value, however.
			$this->max_count = $this->shm->max_count;
		}
		else
		{
			$this->max_count = $max_count;
			
			// Initialize the Semaphore
			// (we'll start with the maximum value, and decrement from there)
			$this->mutex->acquire();
			$this->shm->max_count = $max_count;
			$this->shm->value = $max_count;
			$this->mutex->release();
		}
	}

	/**
	 * Get the IPC keys
	 * @return array
	 */
	public function getKeys()
	{
		return array(
			'mutex' => $this->mutex->getKey(),
			'shm'   => $this->shm->getKey(),
			'queue' => $this->queue->getKey(),
		);
	}

	/**
	 * Get the current value of the semaphore atomically
	 * @return integer
	 */
	public function read()
	{
		$this->mutex->acquire();
		$value = $this->shm->value;
		$this->mutex->release();
		return $value;
	}

	/**
	 * Acquire (increment) the semaphore
	 * @throws phm\Exception\SemaphoreException
	 */
	public function acquire()
	{
		// Pseudocode:
		//   mutex down
		//     if shm > 0
		//       val = shm
		//       shm--
		//       mutex up
		//     else
		//       mutex up
		//       wait for shm > 0
		//       down (recursion)

		while (true)
		{
			// Get exclusive access to the semaphore value
			$this->mutex->acquire();

			// Can the semaphore be decremented? If not, block.
			if ($this->shm->value > 0)
			{
				// Immediately decrement the semaphore and return
				$this->shm->value--;
				$this->mutex->release();
				return;
			}
			else
			{
				// Release the mutex so that other processes can acquire
				// access to the semaphore and increment it
				$this->mutex->release();

				// Wait for a message from the first process that increments the semaphore.
				// Note that we could poll the semaphore, but that would require excessive
				// locking and unlocking of the mutex, preventing other processes from
				// incrementing the semaphore.
				$this->queue->receive(Semaphore::SEM_ACQUIRE, MessageQueue::BLOCKING);
			}

			// Loop, since we have not yet decremented the semaphore,
			// and can only do so atomically, after having acquired the mutex
		}
	}

	/**
	 * Alas for acquire(). Decrements the semaphore.
	 * @throws phm\Exception\SemaphoreException
	 */
	public function down()
	{
		$this->acquire();
	}

	/**
	 * Release (decrement) the semaphore
	 */
	public function release()
	{
		// Pseudocode:
		//   mutex down
		//     val = shm
		//     shm++
		//     if val = 0
		//       signal shm > 0
		//     mutex up

		// Get exclusive access to the semaphore value
		$this->mutex->acquire();

		// Increment the semaphore
		$value = $this->shm->value;
		$this->shm->value++;

		// If the value was 0 before incrementing, then queue a signal to any
		// waiting processes that the semaphore may be decremented now
		if ($value == 0)
		{
			$this->queue->send(Semaphore::SEM_ACQUIRE, Semaphore::SEM_ACQUIRE);
		}

		$this->mutex->release();
	}

	/**
	 * Alas for release(). Increments the semaphore.
	 * @throws phm\Exception\SemaphoreException
	 */
	public function up()
	{
		$this->release();
	}

	/**
	 * Nuke the semaphore and this instance
	 * @throws phm\Exception\SemaphoreException
	 */
	public function delete()
	{
		if ($this->mutex)
		{
			try
			{
				$this->mutex->delete();
			}
			catch (SemaphoreException $e)
			{
				// ignore
			}
		}

		if ($this->shm)
		{
			try
			{
				$this->shm->delete();
			}
			catch (SharedMemoryException $e)
			{
				// ignore
			}
		}

		if ($this->queue)
		{
			try
			{
				$this->queue->delete();
			}
			catch (MessageQueueException $e)
			{
				// ignore
			}
		}

		$this->mutex = null;
		$this->shm   = null;
		$this->queue = null;
	}
}

