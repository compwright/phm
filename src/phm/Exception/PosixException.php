<?php

namespace phm\Exception;

use \Exception;

class PosixException extends Exception
{
	public function __construct()
	{
		$this->code = posix_get_last_error();
        $this->message = posix_strerror($this->code);
	}
}

