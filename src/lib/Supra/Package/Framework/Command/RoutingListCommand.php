<?php

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;

class RoutingListCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('framework:routing:list');
	}
}