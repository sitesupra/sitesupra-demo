<?php

namespace Supra\Tests;

/**
 * Description of Fixture
 */
class Fixture extends TestCase
{
	public function testFixture()
	{
		$input = new \Symfony\Component\Console\Input\StringInput('--force');
		$output = new \Symfony\Component\Console\Output\ConsoleOutput();
		
		// Drop model
		$command = new \Supra\Database\Console\SchemaDropCommand();
		$command->run($input, $output);
		
		// Create model
		$command = new \Supra\Database\Console\SchemaUpdateCommand();
		$command->run($input, $output);
		
		// Page fixtures
		$fixture = new Controller\Pages\Fixture\Fixture();
		$fixture->setName('testFixture');
		$result = $fixture->run();
		$this->passResult($fixture, $result);
		
		// Authorization fixtures
		$fixture = new Authorization\Fixture();
		$fixture->caller = '';
		$fixture->setName('testFixture');
		$result = $fixture->run();
		$this->passResult($fixture, $result);
	}
	
	private function passResult($fixture, \PHPUnit_Framework_TestResult $result)
	{
		if ( ! $result->wasSuccessful()) {
			/* @var $error \PHPUnit_Framework_TestFailure */
			foreach ($result->errors() as $error) {
				$this->getTestResultObject()
						->addError($fixture, $error->thrownException(), 0);
			}
			
			/* @var $failure \PHPUnit_Framework_TestFailure */
			foreach ($result->failures() as $failure) {
				$this->getTestResultObject()
						->addFailure($fixture, $failure->thrownException(), 0);
			}
		}
	}
}
