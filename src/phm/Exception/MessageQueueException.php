<?php

namespace phm\Exception;

use \Exception;

class MessageQueueException extends Exception
{
	public static $messages = array(
		MSG_ENOMSG => 'No message provided',
		MSG_EAGAIN => 'Message queue full, try again later',
	);

	public function __construct($message = '', $code = 0, Exception $previous = NULL)
	{
		if (isset(self::$messages[$code]) && empty($message))
		{
			$message = self::$messages[$code];
		}
		
		parent::__construct($message, $code, $previous);
	}
}

