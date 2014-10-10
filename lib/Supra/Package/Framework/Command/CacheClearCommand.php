<?php

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('cache:clear')
			->setDescription('Clears cache, all or particular segment')
			->addArgument('segment', InputArgument::OPTIONAL, 'Segment to delete');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$cacheDir = $this->container->getParameter('directories.cache');

		$segments = array();

		$segment = $input->getArgument('segment');

		if ($segment) {
			$segments[] = $segment;
		} else {
			$segments = array_map(function ($value) {
				return basename($value);
			}, glob($cacheDir . '/*'));
		}

		foreach ($segments as $segment) {
			if (!is_dir($cacheDir . DIRECTORY_SEPARATOR . $segment)) {
				throw new \Exception(sprintf('Cache segment "%s" does not exist', $segment));
			}

			$output->writeln(sprintf('Deleting segment <info>%s</info>...', $segment));

			$this->rmDir($cacheDir . DIRECTORY_SEPARATOR . $segment);
		}
	}

	protected function rmDir($dir)
	{
		if (!is_dir($dir)) {
			throw new \Exception(sprintf('Argument "%s" is not a directory', $dir));
		}

		foreach(glob($dir . '/*') as $item) {
			if (is_dir($item)) {
				$this->rmDir($item);
			} else {
				unlink($item);
			}
		}

		rmdir($dir);
	}
}
