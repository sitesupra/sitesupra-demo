<?php

namespace Supra\Package\CmsAuthentication\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Package\CmsAuthentication\Entity\Group;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GroupsUpdateCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('groups:update')
			->setDescription('Removes a group, provide --em to use different EntityManager')
			->addOption('em', null, InputArgument::OPTIONAL, 'Entity manager name')
			->addArgument('name', InputArgument::REQUIRED, 'Group name')
			->addOption('isSuper', null, InputOption::VALUE_OPTIONAL, 'Has super users?', false);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));

		$group = $em->getRepository('CmsAuthentication:Group')->findOneByName($input->getArgument('name'));

		if (!$group) {
			throw new \Exception(sprintf('Group "%s" does not exist', $input->getArgument('name')));
		}

		if (!is_null($input->getOption('isSuper'))) {
			$group->setIsSuper((bool)$input->getOption('isSuper'));
		}

		$em->flush();

		$output->writeln('Group updated!');
	}

}
