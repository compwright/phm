<?php

namespace phm\Test;

require_once dirname(__DIR__).'/src/phm/Exception/ProcessException.php';
require_once dirname(__DIR__).'/src/phm/Exception/PosixException.php';
require_once dirname(__DIR__).'/src/phm/Process.php';

use phm\Process;

class ProcessTest extends \PHPUnit_Framework_TestCase
{
	private $proc;
	private $pipes;

	public function randomProcessProvider()
	{
		$process = new Process();
		$process->id = rand(1000, 2000);
		return $process;
	}

	public function childProcessProvider()
	{
		$process = $this->randomProcessProvider();
		$process->parent_id = rand(1000, 2000);
		return $process;
	}

	public function selfProcessProvider()
	{
		$process = new Process;
		$process->id        = posix_getpid();
		$process->parent_id = posix_getppid();
		$process->user_id   = posix_getuid();
		$process->group_id  = posix_getgid();
		return $process;
	}

	/**
	 * @dataProvider dataForTestIsCurrent
	 */
	public function testIsCurrent($process, $expected)
	{
		$this->assertEquals($expected, $process->isCurrent());
	}

	public function dataForTestIsCurrent()
	{
		return array(
			array($this->randomProcessProvider(), FALSE),
			array($this->childProcessProvider(), FALSE),
			array($this->selfProcessProvider(), TRUE),
		);
	}

	/**
	 * @dataProvider dataForTestIsChild
	 */
	public function testIsChild($process, $expected)
	{
		$this->assertEquals($expected, $process->isChild());
	}

	public function dataForTestIsChild()
	{
		return array(
			array($this->randomProcessProvider(), FALSE),
			array($this->childProcessProvider(), TRUE),
		);
	}

	/**
	 * @expectedException phm\Exception\PosixException
	 */
	public function testSignalFail()
	{
		$process = $this->randomProcessProvider();
		$process->signal(2); // SIGINT
	}

	public function testSignalSucceed()
	{
		// Open a process for a test signal
		// ftp waits for input, and is ubiquitous...
		$spec = array(
			0 => array('pipe', 'r'), // stdin
			1 => array('pipe', 'w'), // stdout
			2 => array('pipe', 'r'), // stderr
		);
		$proc = proc_open('ftp', $spec, $pipes);
		$proc_status = proc_get_status($proc);

		$process = new Process();
		$process->id = $proc_status['pid'];

		$this->assertTrue($process->signal(SIGKILL));
		usleep(500000); // Give the process time to exit
		$proc_status = proc_get_status($proc);
		
		proc_close($proc);
		$this->assertFalse($proc_status['running']);
		$this->assertEquals(SIGKILL, $proc_status['termsig']);
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testDaemonizeFail()
	{
		$process = $this->randomProcessProvider();
		$process->daemonize();
	}
}

