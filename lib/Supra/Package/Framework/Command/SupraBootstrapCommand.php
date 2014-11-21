<?php

namespace Supra\Package\Framework\Command;

use Nelmio\Alice\Fixtures;
use Supra\Core\Console\AbstractCommand;
use Supra\Core\Fixtures\Processor\UserProcessor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SupraBootstrapCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('supra:bootstrap')
			->setDescription('Bootstraps initial supra database from fixture file provided')
			->addArgument('file', InputArgument::OPTIONAL, 'Fixture file name relative to storage/data', 'fixtures.yml')
			->addOption('em', null, InputOption::VALUE_OPTIONAL, 'Entity manager to use', 'public');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$file = $input->getArgument('file');

		$dataDir = $this->container->getParameter('directories.storage') . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

		if (!is_readable($dataDir . $file)) {
			throw new \Exception(sprintf('Fixture file <info>%s</info> does not exist (checked path <info>%s</info>)', $file, $dataDir));
		}

		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));
		$userProcessor = new UserProcessor();
		$userProcessor->setContainer($this->container);

		$output->write(sprintf('Loading <info>%s</info>...', $file));

		Fixtures::load(
			$dataDir.$file,
			$em,
			array(
				'logger' => $this->container->getLogger()
			),
			array(
				$userProcessor
			)
		);

		$output->writeln('done!');
	}

}
