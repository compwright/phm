<?php

namespace phm;

use phm\Exception\PosixException;

/**
 * Process class
 *
 * @author Jonathon Hill
 * @package phm
 * @subpackage Process
 */
class Process
{
    /**
     * Process ID
     * @var integer
     */
    public $id;

    /**
     * Parent process ID
     * @var integer
     */
    public $parent_id;
    
    /**
     * User ID
     * @var integer
     */
    public $user_id;
    
    /**
     * Group ID
     * @var integer
     */
    public $group_id;

    /**
     * Indicates whether this is the current process
     * @return boolean
     */
    public function isCurrent()
    {
        return $this->id == posix_getpid();
    }

    /**
     * Indicates whether this process has a parent process
     * @return boolean
     */
    public function isChild()
    {
        return $this->parent_id > 0;
    }

    /**
     * Sends a signal to this process
     * @param integer A valid POSIX process signal
     * @return boolean
     * @throws phm\Exception\PosixException
     */
    public function signal($signal)
    {
        if ( ! posix_kill($this->id, $signal))
        {
            throw new PosixException();
        }

        return true;
    }

    /**
     * Daemonize this process (throws a LogicException if this is not the current process)
     * @return boolean
     * @throws phm\Exception\PosixException
     * @throws \LogicException
     */
    public function daemonize()
    {
        if ($this->isCurrent())
        {
            if (posix_setsid() === -1)
            {
                throw new PosixException();
            }

            $this->parent_id = NULL;
            return TRUE;
        }
        else
        {
            throw new \LogicException('Cannot daemonize another process');
        }
    }
}

