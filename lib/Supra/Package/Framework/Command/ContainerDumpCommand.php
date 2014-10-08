<?php

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ContainerDumpCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('container:dump')
			->setDescription('Dumps container parameters and services');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Container parameters:</info>');

		$table = new Table($output);

		$table->setHeaders(array('Parameter', 'Value'));

		foreach ($this->container->getParameters() as $key) {
			$table->addRow(array($key, $this->stringify($this->container->getParameter($key))));
		}

		$table->render();

		$output->writeln('<info>Container services:</info>');

		$table = new Table($output);

		$table->setHeaders(array('ID'));

		foreach ($this->container->keys() as $id) {
			$table->addRow(array($id));
		}

		$table->render();
	}

	protected function getType($value)
	{
		if (is_scalar($value)) {
			return 'scalar';
		} elseif (is_object($value)) {
			return 'object';
		} else {
			return 'other';
		}
	}

	protected function stringify($value)
	{
		if (is_bool($value)) {
			return $value ? 'TRUE' : 'FALSE';
		} elseif (is_scalar($value)) {
			return (string)$value;
		} elseif (is_object($value)) {
			return get_class($value);
		} else {
			return gettype($value);
		}
	}

}
