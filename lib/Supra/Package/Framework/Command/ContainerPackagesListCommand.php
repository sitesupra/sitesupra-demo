<?php

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ContainerPackagesListCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('container:packages:list')
			->setDescription('Lists packages loaded');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>List of packages, in registration order:</info>');

		$table = new Table($output);

		$table->setHeaders(array('Package', 'Class'));

		foreach ($this->container->getApplication()->getPackages() as $package) {
			$table->addRow(array($package->getName(), get_class($package)));
		}

		$table->render();
	}

}
