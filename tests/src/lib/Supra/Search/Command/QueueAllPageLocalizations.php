<?php

namespace Supra\Tests\Authorization\Fixture;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Command\QueueAllPageLocalizationsCommand;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class QueueAllPageLocalizations extends \PHPUnit_Framework_TestCase 
{
	function testFixture()
	{
		$input = new ArrayInput(array());
		$output = new ConsoleOutput();
		
		$command = new QueueAllPageLocalizationsCommand();
		$command->run($input, $output);
	}
}
