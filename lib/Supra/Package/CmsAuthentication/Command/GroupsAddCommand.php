<?php

namespace Supra\Package\CmsAuthentication\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Package\CmsAuthentication\Entity\Group;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GroupsAddCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('groups:add')
			->setDescription('Adds a new group, provide --em to use different EntityManager')
			->addOption('em', null, InputArgument::OPTIONAL, 'Entity manager name')
			->addArgument('name', InputArgument::REQUIRED, 'Group name')
			->addArgument('super', InputArgument::OPTIONAL, 'Are these guys superusers?', false);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));

		$group = new Group();
		$group->setName($input->getArgument('name'));
		$group->setIsSuper($input->getArgument('super'));

		$em->persist($group);
		$em->flush();

		$output->writeln('Group created!');
	}

}
