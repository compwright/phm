<?php

include dirname(dirname(__FILE__)).'/src/autoload.php';

class Buffer
{
	protected $size;
	protected $mutex;
	protected $counter;
	protected $memory;
	
	public function __construct($size, phm\Lock\Mutex $mutex, phm\Lock\Semaphore $counter, phm\SharedMemory $memory)
	{
		$this->size = $size;
		$this->mutex = $mutex;
		$this->counter = $counter;
		$this->memory = $memory;

		// Initialize the buffer
		if ( ! isset($this->memory->buffer))
		{
			$this->memory->buffer = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
		}
	}

	public function add(array $data)
	{
		$this->mutex->acquire();
		$buffer = $this->memory->buffer;
		
		for ($i = 0; $i < count($data); $i++)
		{
			array_push($buffer, $data[$i]);
			$this->counter->up();
		}

		$this->memory->buffer = $buffer;
		$this->mutex->release();

		return $buffer;
	}

	public function remove($n)
	{
		$data = array();

		$this->mutex->acquire();
		$buffer = $this->memory->buffer;

		for ($i = 0; $i < $n; $i++)
		{
			$this->counter->down();
			$data[] = array_shift($buffer);
		}

		$this->memory->buffer = $buffer;
		$this->mutex->release();

		return array($buffer, $data);
	}
}

class ProducerConsumer
{
	protected $process_factory;
	protected $process;
	protected $buffer;
	protected $screen_mutex;

	public function __construct(phm\Process\Factory $process_factory, Buffer $buffer, phm\Lock\Mutex $screen_mutex)
	{
		$this->process_factory = $process_factory;
		$this->buffer = $buffer;
		$this->screen_mutex = $screen_mutex;
	}

	public function __destruct()
	{
		// Terminate the related child process when the parent process shuts down
		if ( ! $this->process->is_current())
		{
			$this->process->signal(SIGKILL);
		}
	}

	public function produce()
	{
		$letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$o = rand(0, 25);
		$n = rand(4, 12);
		$array = array();
		for ($i = 0; $i < $n; $i++)
		{
			$array[] = $letters[($o + $i) % 26];
		}
		$buffer = $this->buffer->add($array);

		$this->screen_mutex->acquire();
		echo sprintf("+%02d: ", $n).implode('', $buffer)."\n";
		$this->screen_mutex->release();

		usleep(rand(100000, 200000));
	}

	public function consume()
	{
		$n = rand(5, 10);
		list($buffer, $data) = $this->buffer->remove($n);

		$this->screen_mutex->acquire();
		echo sprintf("-%02d: ", $n).implode('', $buffer)."\n";
		$this->screen_mutex->release();

		usleep(rand(100000, 200000));
	}

	public function activate($verb)
	{
		$this->process = $this->process_factory->fork();
		
		if ($this->process->isCurrent())
		{
			// This is the child process
			while (true)
			{
				$this->$verb();
			}
		}
		else
		{
			// This is the parent process
			return;
		}
	}
}

echo "\n";
echo "-------------------------------------------------\n";
echo "The Producer-Consumer demo (press CTRL+C to exit)\n";
echo "-------------------------------------------------\n";
echo "\n";

$phm_factory = phm\Factory::getInstance();

$buffer_size = 80;

$pc = new ProducerConsumer(
	new phm\Process\Factory(),
	new Buffer(
		$buffer_size,
		$phm_factory->newMutex('buffer_mutex'),
		$phm_factory->newSemaphore('buffer_semaphore', $buffer_size),
		$phm_factory->newSharedMemory('buffer_memory', 8*1024) // 8k buffer
	),
	$phm_factory->newMutex('screen_mutex')
);

$pc->activate('produce');
$pc->activate('consume');

while (true)
{
	sleep(1);
}

echo "\n\n";
