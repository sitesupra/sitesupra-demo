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

		$table->setHeaders(array('Directory', 'Size', 'Files'));

		foreach (glob($cacheDir.'/*') as $dir) {
			list($size, $count) = $this->dirMetrics($dir);
			$table->addRow(array(
				basename($dir),
				sprintf('%.2fM', $size / 1024 / 1024),
				$count
			));
		}

		$table->render();
	}

	protected function dirMetrics($dir)
	{
		$size = $count = 0;

		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
			$count ++;
			$size += $file->getSize();
		}

		return array($size, $count);
	}
}
