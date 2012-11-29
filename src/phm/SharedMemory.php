<?php

namespace phm;

use phm\Exception\SharedMemoryException;

/**
 * Shared memory accessor class
 * @package phm
 * @author Jonathon Hill
 */
class SharedMemory
{
	/**
	 * System V IPC key used to open a shared memory resource
	 * @var integer $key
	 */
	protected $key;

	/**
	 * System V shared memory resource
	 * @var resource $shm
	 */
	protected $shm;

	/**
	 * Shared memory variable index
	 * @var integer $index
	 */
	protected $index = 0;

	/**
	 * Constructor, connect to a shared memory resource
	 * @param string $key System V IPC key for the shared memory segment (use ftok() to get a key)
	 * @param integer $bytes = NULL Memory segment size, in bytes (optional)
	 * @param integer $permissions = 0666 UNIX permission bitmask, usually given in octal
	 */
	public function __construct($key, $bytes = NULL, $permissions = 0666)
	{
		$this->key = $key;
		$this->shm = shm_attach($key, $bytes, $permissions);
		if ($this->shm === FALSE)
		{
			throw new SharedMemoryException("Failed to open shared memory segment 0x".dechex($this->key));
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
	 * Destructor, disconnect the shared memory resource
	 */
	public function __destruct()
	{
		@shm_detach($this->shm);
	}

	protected function read()
	{
		$data = shm_has_var($this->shm, $this->index)
		      ? shm_get_var($this->shm, $this->index)
		      : array();
		
		return $data;
	}

	protected function write(array $data)
	{
		return @shm_put_var($this->shm, $this->index, $data);
	}

	/**
	 * Checks if a variable is set in shared memory
	 * @param string $key
	 * @return boolean
	 */
	public function __isset($key)
	{
		$data = $this->read();
		return isset($data[$key]);
	}

	/**
	 * Gets a variable from shared memory
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		$data = $this->read();
		return $data[$key];
	}

	/**
	 * Set a variable in shared memory
	 * @param string $key
	 * @param mixed $value
	 * @throws \InvalidArgumentException
	 * @throws phm\Exception\SharedMemoryException
	 */
	public function __set($key, $value)
	{
		if (is_resource($value))
		{
			throw new \InvalidArgumentException('Cannot store resources in shared memory');
		}

		$data = $this->read();

		$data[$key] = $value;

		if ( ! $this->write($data))
		{
			throw new SharedMemoryException("Failed to write to shared memory segment 0x".dechex($this->key));
		}
	}

	/**
	 * Removes a variable from shared memory
	 * @param string $key
	 * @throws phm\Exception\SharedMemoryException
	 */
	public function __unset($key)
	{
		$data = $this->read();
		unset($data[$key]);

		if ( ! $this->write($data))
		{
			throw new SharedMemoryException("Failed to write to shared memory segment 0x".dechex($this->key));
		}
	}

	/**
	 * Nuke the shared memory block and this instance
	 * @throws phm\Exception\SharedMemoryException
	 */
	public function delete()
	{
		$success = @shm_remove($this->shm);
		$this->shm = NULL;

		if ( ! $success)
		{
			throw new SharedMemoryException("Failed to remove shared memory segment 0x".dechex($this->key));
		}
	}
}

