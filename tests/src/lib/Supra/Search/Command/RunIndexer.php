<?php

namespace Supra\Tests\Authorization\Fixture;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Command\RunIndexerCommand;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class RunIndexer extends \PHPUnit_Framework_TestCase 
{
	function testFixture()
	{
		$input = new ArrayInput(array());
		$output = new ConsoleOutput();
		
		$command = new RunIndexerCommand();
		$command->run($input, $output);
	}
}