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

class RemoteFindUserCommand extends Command
{

	/**
	 * @var array 
	 */
	public $output = array(
		'user' => null,
		'error' => null,
	);

	/**
	 * @var OutputInterface
	 */
	public $outputInstance;

	/**
	 * @var \Supra\User\RemoteUserProvider
	 */
	public $userProvider;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	public $em;

	/**
	 * @var \Supra\Log\Writer\WriterAbstraction
	 */
	public $log;

	/**
	 * Field allowed values
	 * @var array 
	 */
	public $allowedFields = array(
		'id',
		'email',
		'login',
		'name',
		'username'
	);

	public function __construct($name = null)
	{
		parent::__construct($name);
		$this->userProvider = ObjectRepository::getUserProvider($this);
		$this->em = ObjectRepository::getEntityManager($this);
		$this->log = ObjectRepository::getLogger($this);
	}

	protected function configure()
	{
		$this->setName('su:remote:find_user')
				->setDescription('Remote client to search for user in supra instance.')
				->setDefinition(new InputDefinition(array(
							new InputArgument('field', InputArgument::OPTIONAL, 'Field to search for. One of ' . join(', ', $this->allowedFields) . ' fields'),
							new InputArgument('value', InputArgument::OPTIONAL),
							new InputOption('all-users', null, InputOption::VALUE_NONE, 'If option is set, will ignore all arguments and will return all users')
						)));
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$user = null;
		$users = array();

		$this->outputInstance = $output;

		$field = $input->getArgument('field');
		$value = $input->getArgument('value');
		$findAllUsers = $input->getOption('all-users');

		// check if all fields are not empty
		if (empty($field) && empty($value) && ! $findAllUsers) {
			throw new Exception\RuntimeException('Fill arguments or provide --all-users option');
			return;
		}

		// if provided field and value find user with such criteria
		if ( ! empty($field) && ! empty($value)) {
			$user = $this->findUser($field, $value);
		}

		// else if is set --all-users option 
		else if ($findAllUsers) {
			$users = $this->userProvider->findAllUsers();
		}

		// 
		else {
			throw new Exception\RuntimeException('Error occured');
		}

		if ($user instanceof Entity\User) {
			$this->outputUser($user);
			return;
		}

		if ( ! empty($users)) {
			$this->outputUsers($users);
			return;
		}
	}

	/**
	 * @param OutputInterface $this->outputInstance
	 * @param array $array 
	 */
	public function writeArrayToOutput($array, $depth = 1)
	{
		$tab = str_repeat("\t", $depth);
		if ($depth <= 1) {
			$this->outputInstance->writeln("\n{$tab}User is found:\n");
		}

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$this->outputInstance->writeln("{$tab}[$key] => ");
				$this->writeArrayToOutput($value,  ++ $depth);
				-- $depth;
			} else {
				$this->outputInstance->writeln("{$tab}[$key] => \"$value\"");
			}
		}
		$this->outputInstance->writeln('');
	}

	/**
	 *
	 * @param type $field
	 * @param type $value
	 * @return type 
	 */
	private function findUser($field, $value)
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

		$user = null;

		switch (strtolower($field)) {
			case 'id':
				$user = $this->userProvider->findUserById($value);
				break;
			case 'email':
				$user = $this->userProvider->findUserByEmail($value);
				break;
			case 'login':
				$user = $this->userProvider->findUserByLogin($value);
				break;
			case 'name':
				$user = $this->userProvider->findUserByName($value);
				break;

			default:
				throw new Exception\RuntimeException('Wrong search field');
				break;
		}

		if (empty($user)) {
			$message = 'There is no any user with such details';
			$this->outputInstance->writeln($message);
			if ($this->outputInstance instanceof CommandOutputWithData) {
				$this->output['error'] = $message;
				$this->outputInstance->setData($this->output);
			}

			return;
		}

		return $user;
	}

	/**
	 *
	 * @param Entity\User $user
	 * @return type 
	 */
	private function outputUser(Entity\User $user)
	{

		if ($this->outputInstance instanceof CommandOutputWithData) {
			$this->output['user'] = $this->assignUserGroup($user);
			$this->outputInstance->setData($this->output);

			return;
		}

		if ($user instanceof Entity\User) {
			$this->writeArrayToOutput($this->getUserData($user));

			return;
		}
	}

	/**
	 *
	 * @param Entity\User $user
	 * @return type 
	 */
	private function getUserData(Entity\User $user)
	{
		$userData = array(
			'id' => $user->getId(),
			'email' => $user->getEmail(),
			'name' => $user->getName(),
			'created_at' => $user->getCreationTime()->format('d.m.Y H:i:s'),
			'modified_at' => $user->getModificationTime()->format('d.m.Y H:i:s'),
		);

		return $userData;
	}

	/**
	 * @param Entity\User $user
	 * @return Entity\User 
	 */
	private function assignUserGroup(Entity\User $user)
	{
		$userId = $user->getId();
		$repo = $this->em->getRepository(SiteUser::CN());
		$siteUser = $repo->findOneByUser($userId);

		// additional proxy initialization if user is not SiteUser
		if ($user->getGroup() instanceof Entity\Group) {
			$user->getGroup()->getId();
		}

		if ( ! $siteUser instanceof SiteUser) {
			$this->log->debug('Site user data is empty. Failed to set group for user ' . $user->getEmail());
			return $user;
		}

		$userGroup = $siteUser->getUserGroup();
		if ( ! $userGroup instanceof Entity\Group) {
			$this->log->debug('Site user group is not defined. Failed to set group for user ' . $user->getEmail());
			return $user;
		}

		$user->setGroup($userGroup);

		return $user;
	}

	private function outputUsers(array $users)
	{

		$userData = array();

		foreach ($users as $user) {
			if ( ! $user instanceof Entity\User) {
				continue;
			}

			if ($this->outputInstance instanceof CommandOutputWithData) {
				$userData[] = $this->assignUserGroup($user);
			} else {
				$userData[] = $this->getUserData($user);
			}
		}

		if ($this->outputInstance instanceof CommandOutputWithData) {
			$this->output['user'] = $userData;
			$this->outputInstance->setData($this->output);

			return;
		}


		$this->writeArrayToOutput($userData);
	}

}
