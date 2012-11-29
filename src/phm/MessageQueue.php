<?php

namespace phm;

use phm\Exception\MessageQueueException;

/**
 * IPC message queue class
 * @package phm
 * @author Jonathon Hill
 */
class MessageQueue implements \Countable
{
	const BLOCKING = 0;
	const NON_BLOCKING = MSG_IPC_NOWAIT;

	/**
	 * System V IPC key used to open a shared memory resource
	 * @var integer $key
	 */
	protected $key;

	/**
	 * System V shared memory resource
	 * @var resource $shm
	 */
	protected $queue;

	protected $last_message;
	protected $last_message_type;

	/**
	 * Constructor, connect to a shared message queue resource
	 * @param string $key System V IPC key for the shared memory segment (use ftok() to get a key)
	 * @param integer $permissions = 0666 UNIX permission bitmask, usually given in octal
	 */
	public function __construct($key, $permissions = 0666)
	{
		$this->key = $key;
		$this->queue = msg_get_queue($key, $permissions);
		if ($this->queue === FALSE)
		{
			throw new MessageQueueException("Failed to open message queue $key");
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
	 * Checks to see if the current user has permission to change the message queue configuration
	 * @return boolean
	 */
	public function isConfigurable()
	{
		$data = $this->getStatus();
		return (
			! empty($data) &&
			(
				// The user is root
				posix_getuid() == 0 ||
				// The user owns this queue
				$data['msg_perm.uid'] == posix_getuid() ||
				// The group owns this queue
				$data['msg_perm.gid'] == posix_getgid() ||
				// Write permission was granted
				$data['msg_perm.mode'] & 0222
			)
		);
	}

	/**
	 * Resize the message queue
	 * @param integer $bytes
	 * @throws phm\Exception\MessageQueueException
	 */
	public function resize($bytes)
	{
		if ( ! $this->isConfigurable())
		{
			throw new MessageQueueException("Could not resize the message queue to $bytes bytes: insufficient permissions");
		}

		$settings = array(
			'msg_qbytes' => $bytes
		);

		if ( ! msg_set_queue($this->queue, $settings))
		{
			throw new MessageQueueException("Could not resize the message queue to $bytes bytes");
		}
	}

	/**
	 * Set the message queue owner, group, and permissions
	 * @param integer $user ID of the owning user
	 * @param integer $group ID of the owning group
	 * @param integer $mode Permissions bitmask
	 * @throws phm\Exception\MessageQueueException
	 */
	public function setPermissions($user, $group, $mode)
	{
		if ( ! $this->isConfigurable())
		{
			throw new MessageQueueException("Could not change the message queue permissions: insufficient permissions");
		}

		$settings = array(
			'msg_perm.uid' => $user,
			'msg_perm.gid' => $group,
			'msg_perm.mode' => $mode,
		);

		if ( ! msg_set_queue($this->queue, $settings))
		{
			throw new MessageQueueException('Could not change the message queue permissions');
		}
	}

	/**
	 * Get the number of messages in the queue
	 * @returns integer
	 */
	public function count()
	{
		$data = $this->getStatus();
		return isset($data['msg_qnum'])
		     ? (int) $data['msg_qnum']
		     : 0;
	}

	/**
	 * Get detailed queue status information
	 * @return array
	 */
	public function getStatus()
	{
		$data = msg_queue_exists($this->key)
		      ? msg_stat_queue($this->queue)
		      : array();

		return $data;
	}

	/**
	 * Get the message queue size, in bytes
	 * @return integer|boolean Size of the message queue in bytes, or false if the queue is not valid
	 */
	public function getSize()
	{
		$data = $this->getStatus();
		return isset($data['msg_qbytes'])
		     ? $data['msg_qbytes']
		     : false;
	}

	/**
	 * Get the message queue owner user ID
	 * @return integer|boolean User ID, or false if the queue is not valid
	 */
	public function getOwner()
	{
		$data = $this->getStatus();
		return isset($data['msg_perm.uid'])
		     ? $data['msg_perm.uid']
		     : false;
	}

	/**
	 * Get the most recent message received from the queue
	 * @return mixed
	 */
	public function getLastMessage()
	{
		return $this->last_message;
	}

	/**
	 * Get the most recent message type received from the queue
	 * @return integer|null
	 */
	public function getLastMessageType()
	{
		return $this->last_message_type;
	}

	/**
	 * Send a message
	 * @param mixed $message Message
	 * @param integer $type = 1 Message type (user defined)
	 * @param boolean $block = false
	 * @return boolean
	 * @throws phm\Exception\MessageQueueException
	 */
	public function send($message, $type = 1, $block = false)
	{
		$message_size = strlen(serialize($message));
		$queue_size = $this->getSize();
		if ($message_size > $queue_size)
		{
			throw new \InvalidArgumentException("Message is larger than the queue size ($queue_size bytes)");
		}

		if (msg_send($this->queue, $type, $message, true, $block, $error))
		{
			return true;
		}
		else
		{
			throw new MessageQueueException(NULL, $error);
		}
	}

	/**
	 * Receive a message
	 * @param integer $desired_type = 0 Desired message type (user defined)
	 * @param integer $flags = MessageQueue::NON_BLOCKING Message queue flags (defaults to non-blocking)
	 * @param integer $max_size = false Maximum message size to receive, in bytes. If omitted, defaults to the message queue size.
	 * @return mixed
	 * @throws phm\Exception\MessageQueueException
	 */
	public function receive($desired_type = 0, $flags = MessageQueue::NON_BLOCKING, $max_size = false)
	{
		if ( ! $max_size)
		{
			$max_size = $this->getSize();
		}

		if (msg_receive($this->queue, $desired_type, $this->last_message_type, $max_size, $this->last_message, true, $flags, $error))
		{
			return $this->last_message;
		}
		else
		{
			throw new MessageQueueException(NULL, $error);
		}
	}

	/**
	 * Nuke the message queue
	 * @throws phm\Exception\MessageQueueException
	 */
	public function delete()
	{
		if ( ! msg_remove_queue($this->queue))
		{
			throw new MessageQueueException("Could not remove message queue $this->key");
		}
	}
}

