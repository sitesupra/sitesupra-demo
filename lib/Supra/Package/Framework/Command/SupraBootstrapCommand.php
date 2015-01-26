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
