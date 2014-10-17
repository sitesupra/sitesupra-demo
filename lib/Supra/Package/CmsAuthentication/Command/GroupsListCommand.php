<?php

namespace Supra\Package\CmsAuthentication\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Package\CmsAuthentication\Entity\Group;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GroupsListCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('groups:list')
			->setDescription('Lists groups, provide --em to use different EntityManager')
			->addOption('em', null, InputArgument::OPTIONAL, 'Entity manager name');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));

		$users = $em->getRepository('CmsAuthentication:Group')->findBy(array(), array('name' => 'ASC'));

		$table = new Table($output);

		$table->setHeaders(array('ID', 'Name', 'Super'));

		$table->addRows(array_map(function ($group) {
			/* @var $group Group */

			return array(
				$group->getId(),
				$group->getName(),
				$group->isSuper() ? '<info>Yes</info>' : 'No'
			);
		}, $users));

		$table->render();
	}

}
