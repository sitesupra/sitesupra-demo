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

use Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineCacheClearResultCommand extends ResultCommand implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	protected $name = 'doctrine:cache-clear:result';

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	protected function configure()
	{
		parent::configure();
		$this->setName('doctrine:cache-clear:result');

		$this->addOption('em', null, InputOption::VALUE_OPTIONAL, 'Entity manager to use')
			->addOption('con', null, InputOption::VALUE_OPTIONAL, 'Connection to use');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$registry = $this->container->getDoctrine();

		$em = $registry->getManager($input->getOption('em'));
		$con = $registry->getConnection($input->getOption('con'));

		$helperSet = $this->getApplication()->getHelperSet();

		$helperSet->set(new EntityManagerHelper($em), 'em');

		parent::execute($input, $output);
	}

}