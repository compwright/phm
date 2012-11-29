<?php

namespace phm\Process;

use phm\Exception\ProcessException;

/**
 * Process Factory class
 * 
 * @author Jonathon Hill
 * @package phm
 * @subpackage Process
 */
class Factory
{
	/**
	 * Create a Process object
	 * @param integer $id        Process ID
	 * @param integer $parent_id Parent process ID
	 * @param integer $user_id   User ID
	 * @param integer $group_id  Group ID
	 * @return phm\Process
	 */
	public function newInstance($id, $parent_id = NULL, $user_id = NULL, $group_id = NULL)
	{
		$process = new \phm\Process();
		$process->id        = $id;
		$process->parent_id = $parent_id;
		$process->user_id   = $user_id;
		$process->group_id  = $group_id;
		return $process;
	}

	/**
	 * Create a Process object representing the current process
	 * @return phm\Process
	 */
	public function currentInstance()
	{
		return $this->newInstance(posix_getpid(), posix_getppid(), posix_getuid(), posix_getgid());
	}

	/**
	 * Fork a new process and returns Process instances in both the parent and child processes
	 * @return phm\Process
	 * @throws phm\Exception\ProcessException
	 */
	public function fork()
	{
		$pid = pcntl_fork();

		if ($pid > 0)
		{
			return $this->newInstance($pid, posix_getpid());
		}
		elseif ($pid === 0)
		{
			return $this->currentInstance();
		}
		else
		{
			throw new ProcessException('Process fork failed');
		}
	}
}

