<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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
