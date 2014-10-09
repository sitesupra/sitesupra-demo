<?php

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheListCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('cache:list')
			->setDescription('Dumps cache usage');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$cacheDir = $this->container->getParameter('directories.cache');

		$table = new Table($output);

		$table->setHeaders(array('Directory', 'Size'));

		foreach (glob($cacheDir.'/*') as $dir) {
			$table->addRow(array(
				basename($dir),
				0
			));
		}

		$table->render();
	}
}
