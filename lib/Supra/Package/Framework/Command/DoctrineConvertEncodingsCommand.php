<?php

namespace Supra\Package\Framework\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DoctrineConvertEncodingsCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('doctrine:convert-encodings')
			->setDescription('Converts table encodings for old Supra installations')
			->addOption('con', null, InputOption::VALUE_OPTIONAL, 'Connection to use', 'default');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$questionHelper = $this->getHelper('question');

		$question = new ConfirmationQuestion('Are you sure to continue? [y/N] ', false);

		$output->writeln(
				'<comment>'.
				wordwrap(sprintf('ATTENTION! This command will blindly convert all existing tables  for connection "%s" to utf8. You may loose your data, experience problems with foreign keys and / or indexes. Random data truncation may occur. This command will work with MySQL only.', $input->getOption('con'))).
				'</comment>'
			);

		if (!$questionHelper->ask($input, $output, $question)) {
			return;
		}

		$connection = $this->container->getDoctrine()->getConnection($input->getOption('con'));
		/* @var $connection Connection */

		$connection->exec('SET FOREIGN_KEY_CHECKS = 0');

		$manager = $connection->getSchemaManager();
		/* @var $manager AbstractSchemaManager */

		foreach ($manager->listTables() as $table) {
			$output->writeln(sprintf('Processing table <info>%s</info>...', $table->getName()));

			//yep, this is right. PDO/doctrine does not escape table names so there's no sense to use parameters
			$connection->exec(sprintf('ALTER TABLE `%s` CHARSET utf8', $table->getName()));
			$connection->exec(sprintf('ALTER TABLE `%s` CONVERT TO CHARSET utf8', $table->getName()));
		}

		$connection->exec('SET FOREIGN_KEY_CHECKS = 1');
	}

}
