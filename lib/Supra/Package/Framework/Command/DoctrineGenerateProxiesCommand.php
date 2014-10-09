<?php

namespace Supra\Package\Framework\Command;

use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineGenerateProxiesCommand extends GenerateProxiesCommand implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	protected $name = 'doctrine:generate:proxies';

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	protected function configure()
	{
		parent::configure();
		$this->setName('doctrine:generate:proxies');

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