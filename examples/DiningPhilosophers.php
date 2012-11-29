<?php

include dirname(dirname(__FILE__)).'/src/autoload.php';

/*
	The "Dining Philosophers" problem by Djikstra

	Five silent philosophers sit at a table around a bowl of spaghetti.
	A fork is placed between each pair of adjacent philosophers.
	Each philosopher must alternately think and eat.
	However, a philosopher can only eat spaghetti when he has both left and right forks.
	Each fork can be held by only one philosopher and so a philosopher can use the fork
	  only if it's not being used by another philosopher.
	After he finishes eating, he needs to put down both the forks so they become available to others.
	A philosopher can grab the fork on his right or the one on his left as they become available,
	  but can't start eating before getting both of them.
	Eating is not limited by the amount of spaghetti left: assume an infinite supply.

	The problem is how to design a discipline of behavior (a concurrent algorithm) such that
	  each philosopher won't starve, i.e. can forever continue to alternate between eating and thinking,
	  assuming that any philosopher can not know when others may want to eat or think.

	-- http://en.wikipedia.org/wiki/Dining_philosophers_problem
*/

class Philosopher
{
	protected $name;
	protected $color;
	protected $state = 'thinking';
	protected $left_fork;
	protected $right_fork;
	protected $process;

	public function __construct(phm\Process\Factory $process_factory, phm\Lock\Mutex $left_fork, phm\Lock\Mutex $right_fork, $name, $color)
	{
		$this->left_fork = $left_fork;
		$this->right_fork = $right_fork;
		$this->process_factory = $process_factory;
		$this->name = $name;
		$this->color = $color;
	}

	public function __destruct()
	{
		// Terminate the related child process when the parent process shuts down
		if ( ! $this->process->isCurrent())
		{
			$this->process->signal(SIGKILL);
		}
	}

	public function __get($var)
	{
		return $this->$var;
	}

	public function __isset($var)
	{
		return isset($this->$var);
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function activate()
	{
		$this->process = $this->process_factory->fork();
		
		if ($this->process->isCurrent())
		{
			// This is the child process
			while (true)
			{
				$this->think();
				$this->eat();
			}
		}
		else
		{
			// This is the parent process
			return;
		}
	}

	public function think()
	{
		$this->state = 'thinking';
		$this->outLn("$this is thinking.");
		sleep(rand(1, 5));
	}

	public function eat()
	{
		$this->outLn("$this is hungry.");
		
		$this->left_fork->acquire();
		$this->outLn("$this has the left fork: 0x".dechex($this->left_fork->getKey()));

		$this->right_fork->acquire();
		$this->outLn("$this has the right fork: 0x".dechex($this->right_fork->getKey()));

		$this->state = 'eating';
		$this->outLn("$this is eating.");
		sleep(rand(1, 5));
		
		$this->right_fork->release();
		$this->left_fork->release();
	}

	public function outLn($str)
	{
		echo $this->color . $str . "\033[0m\n";
	}
}

$forks = array();
$philosophers = array();
$names = array(
	'Aristotle',
	'  Archimedes',
	'    Plato',
	'      Socrates',
	'        Pythagoras',
);
$colors = array(
	"\033[0;35m", // purple
	"\033[0;31m", // red
	"\033[0;32m", // green
	"\033[0;33m", // brown
	"\033[0;34m", // blue
);
$process_factory = new phm\Process\Factory();
$phm_factory = phm\Factory::getInstance();

// Create 5 mutual exclusion semaphores (mutexes), each representing a fork
for ($i = 0; $i < 5; $i++)
{
	$forks[$i] = $phm_factory->newMutex('Fork '.$i);
}

echo "\n";
echo "-------------------------------------------------------\n";
echo "The five philosophers are dining (press CTRL+C to exit)\n";
echo "-------------------------------------------------------\n";
echo "\n";

//print_r(phm\Factory::getInstance()->getKeyring()->stat());

// Create 5 independent processes, each representing a philosopher
// Each process will receive two mutexes, representing individual forks:
//   0/1, 1/2, 2/3, 3/4, 4/0
for ($i = 0, $j = 1; $i < 5; $i++, $j = ($i + 1) % 5)
{
	// Forks should be acquired in ascending order numerically in order
	// to avoid deadlocks. This is a hack to accomplish this by assigning
	// the lowest numbered fork as the "left" fork
	// (even though it may really be the right fork).
	if ($j > $i)
	{
		$philosophers[$i] = new Philosopher($process_factory, $forks[$i], $forks[$j], $names[$i], $colors[$i]);
	}
	else
	{
		$philosophers[$i] = new Philosopher($process_factory, $forks[$j], $forks[$i], $names[$i], $colors[$i]);
	}

	// Process forking occurs here
	$philosophers[$i]->activate();
}

// Set up a signal handler to clean up our resources when CTRL+C (CMD+C) is pressed
declare(ticks = 1);
$signal_handler = function($signal) use(&$philosophers, &$forks, $phm_factory)
{
	// Murder all of the philosophers via SIGKILL
	// (see Philosopher::__destruct())
	unset($philosophers);

	// Wait 500ms to make sure they are all dead
	usleep(500000);

	// Free up the System V Semaphore resources and
	// remove the associated keys from the keyring
	foreach ($forks as $i => $mutex)
	{
		$key = $mutex->getKey();
		$mutex->delete();
		$phm_factory->getKeyring()->removeKey($key);
	}

	exit(0);
};
pcntl_signal(SIGINT, $signal_handler);
pcntl_signal(SIGTERM, $signal_handler);

// Wait for the user to interrupt the program
while (true)
{
	sleep(rand(1, 60));
}

echo "\n\n";

