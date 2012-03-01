<?php

namespace Supra\Remote\Command;

use Symfony\Component\Console\Command\Command;
use Supra\Log\Log;
use Supra\Console\Output\ArrayOutput;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Supra\Remote\Client\RemoteCommandService;
use Supra\Console\Output\CommandOutputWithData;
use Supra\User\Entity;
use SupraPortal\SiteUser\Entity\SiteUser;
use Supra\Remote\RemoteFindAbstraction;

class RemoteFindGroupCommand extends RemoteFindAbstraction
{

	/**
	 * Field allowed values
	 * @var array 
	 */
	public $allowedFields = array(
		'id',
		'name',
	);

	protected function configure()
	{
		$this->setName('su:remote:find_group')
				->setDescription('Remote client to search for group in supra instance.')
				->setDefinition(new InputDefinition(array(
							new InputArgument('field', InputArgument::OPTIONAL, 'Field to search for. One of ' . join(', ', $this->allowedFields) . ' fields'),
							new InputArgument('value', InputArgument::OPTIONAL),
							new InputOption('site-key', null, InputOption::VALUE_NONE, 'Site key'),
							new InputOption('all-groups', null, InputOption::VALUE_NONE, 'If option is set, will ignore all arguments and will return all groups'),
						)));
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$group = null;
		$groups = array();

		$this->outputInstance = $output;

		$field = $input->getArgument('field');
		$value = $input->getArgument('value');
		$findAllGroups = $input->getOption('all-groups');
		$siteKey = $input->getOption('site-key');

		if (empty($siteKey)) {
			$this->log->warn('Empty site key. Aborting');
			return;
		}

		$this->userProvider->setSiteKey($siteKey);

		// check if all fields are not empty
		if (empty($field) && empty($value) && ! $findAllGroups) {
			throw new Exception\RuntimeException('Fill arguments or provide --all-groups option');
			return;
		}

		// if provided field and value find group with such criteria
		if ( ! empty($field) && ! empty($value)) {
			$group = $this->findGroup($field, $value);
		}

		// else if is set --all-groups option 
		else if ($findAllGroups && (empty($field) || empty($value))) {
			$groups = $this->userProvider->findAllGroups();
		}

		// 
		else {
			throw new Exception\RuntimeException('Error occured');
		}

		if ($group instanceof Entity\Group) {
			$this->outputGroup($group);
			return;
		}

		if ( ! empty($groups)) {
			$this->outputGroups($groups);
			return;
		}
	}

	/**
	 *
	 * @param type $field
	 * @param type $value
	 * @return type 
	 */
	private function findGroup($field, $value)
	{
		if ( ! in_array($field, $this->allowedFields, true)) {
			$message = "Field {$field} was not found in allowed field list. Allowed fields: " . join(', ', $this->allowedFields) . '.';
			$this->outputInstance->writeln($message);
			if ($this->outputInstance instanceof CommandOutputWithData) {
				$this->output['error'] = $message;
				$this->outputInstance->setData($this->output);
			}

			return;
		}

		$group = null;

		switch (strtolower($field)) {
			case 'id':
				$group = $this->userProvider->findGroupById($value);
				break;
			case 'name':
				$group = $this->userProvider->findGroupByName($value);
				break;

			default:
				throw new Exception\RuntimeException('Wrong search field');
				break;
		}

		if (empty($group)) {
			$message = 'There is no any group with such details';
			$this->outputInstance->writeln($message);
			if ($this->outputInstance instanceof CommandOutputWithData) {
				$this->output['error'] = $message;
				$this->outputInstance->setData($this->output);
			}

			return;
		}

		return $group;
	}

	/**
	 *
	 * @param Entity\Group $group
	 * @return type 
	 */
	private function outputGroup(Entity\Group $group)
	{
		if ($this->outputInstance instanceof CommandOutputWithData) {
			$this->output['data'] = $group;
			$this->outputInstance->setData($this->output);

			return;
		}

		if ($group instanceof Entity\Group) {
			$this->writeArrayToOutput($this->getGroupData($group));

			return;
		}
	}

	/**
	 *
	 * @param Entity\Group $group
	 * @return type 
	 */
	private function getGroupData(Entity\Group $group)
	{
		$groupData = array(
			'id' => $group->getId(),
			'name' => $group->getName(),
			'created_at' => $group->getCreationTime()->format('d.m.Y H:i:s'),
			'modified_at' => $group->getModificationTime()->format('d.m.Y H:i:s'),
		);

		return $groupData;
	}

	private function outputGroups(array $groups)
	{

		$groupData = array();

		foreach ($groups as $group) {
			if ( ! $group instanceof Entity\Group) {
				continue;
			}

			if ($this->outputInstance instanceof CommandOutputWithData) {
				$groupData[] = $group;
			} else {
				$groupData[] = $this->getGroupData($group);
			}
		}

		if ($this->outputInstance instanceof CommandOutputWithData) {
			$this->output['data'] = $groupData;
			$this->outputInstance->setData($this->output);

			return;
		}


		$this->writeArrayToOutput($groupData);
	}

}
