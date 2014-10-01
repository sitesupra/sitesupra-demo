<?php

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Core\Event\ConsoleEvent;
use Supra\Core\Package\PackageLocator;
use Supra\Package\Framework\Event\FrameworkConsoleEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AssetsPublishCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('assets:publish')
			->setDescription('Symlinks package assets into web root');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$application = $this->container->getApplication();
		$webRoot = $application->getWebRoot();

		foreach ($application->getPackages() as $package) {
			$target = $application->locatePublicFolder($package);
			$link = $webRoot . '/public/' . $package->getName();

			if (is_dir($target)) {
				$output->writeln(sprintf(
					'Publishing assets for package <info>%s</info>, <info>%s</info> -> <info>%s</info>',
					$package->getName(),
					$target,
					$link
				));

				if (is_link($link)) {
					unlink($link);
				}

				symlink($target, $link);
			} else {
				$output->writeln(sprintf(
					'Skipping assets for package <info>%s</info>, no <info>Resources/public</info> folder found', $package->getName()
				));
			}
		}

		$event = new ConsoleEvent($this, $input, $output);
		$event->setData(array(
			'webRoot' => $webRoot,
			'webRootPublic' => $webRoot . '/public/'
		));

		$this->container->getEventDispatcher()->dispatch(FrameworkConsoleEvent::ASSETS_PUBLISH, $event);
	}

}
