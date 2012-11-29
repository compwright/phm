<?php

namespace phm\Lock;

use phm\Lock\Semaphore;
use phm\Lock\Mutex;
use phm\SharedMemory;

/**
 * "Lightswitch" locking pattern class
 *
 * A special type of lock that activates with the first one enters the
 * room and deactivates when the last one leaves.
 *
 * See "The Little Book of Semaphores", by Allen B. Downey, chapter 4, p.76
 * 
 * @author Jonathon Hill
 * @package phm
 * @subpackage Lock
 */
class Lightswitch
{
	protected $mutex;
	protected $counter;
	protected $semaphore;

	public function __construct(Mutex $mutex, SharedMemory $counter, Semaphore $semaphore)
	{
		$this->mutex = $mutex;
		$this->counter = $counter;
		$this->semaphore = $semaphore;

		if ( ! isset($this->counter->value))
		{
			$this->counter->value = 0;
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
			'shm'   => $this->counter->getKey(),
			'sem'   => $this->semaphore->getKeys(),
		);
	}

	/**
	 * Lock, but only if we are the first one in.
	 */
	public function lock()
	{
		$this->mutex->acquire();
		$this->counter->value++;
		if ($this->counter->value == 1)
		{
			$this->semaphore->down();
		}
		$this->mutex->release();
	}

	/**
	 * Unlock, but only if we are the last one out.
	 */
	public function unlock()
	{
		$this->mutex->acquire();
		$this->counter->value--;
		if ($this->counter->value == 0)
		{
			$this->semaphore->up();
		}
		$this->mutex->release();
	}
}

