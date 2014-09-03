<?php

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RoutingListCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('framework:routing:list');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

	}

}
