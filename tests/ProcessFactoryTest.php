<?php

namespace phm\Test;

require_once dirname(__DIR__).'/src/phm/Exception/ProcessException.php';
require_once dirname(__DIR__).'/src/phm/Exception/PosixException.php';
require_once dirname(__DIR__).'/src/phm/Process.php';
require_once dirname(__DIR__).'/src/phm/Process/Factory.php';

use phm\Process\Factory;

class ProcessFactoryTest extends \PHPUnit_Framework_TestCase
{
	private $factory;

	public function setUp()
	{
		$this->factory = new Factory();
	}

	public function testNewInstance()
	{
		$pid = rand(1000, 2000);
		$instance = $this->factory->newInstance($pid);
		$this->assertInstanceOf('phm\Process', $instance);
		$this->assertEquals($pid, $instance->id);
	}

	public function testCurrentInstance()
	{
		$instance = $this->factory->currentInstance();
		$this->assertInstanceOf('phm\Process', $instance);
		$this->assertTrue($instance->isCurrent());
	}
}

