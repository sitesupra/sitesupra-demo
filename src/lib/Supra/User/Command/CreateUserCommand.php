<?php

namespace Supra\User\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\UserProvider;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\FileStorage\Entity\SlashFolder;
use Supra\Authorization\Permission\PermissionStatus;
use Supra\FileStorage\Entity\Abstraction\File;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\Controller\Pages\Entity\Abstraction\Entity;
use Supra\Controller\Pages\Entity\Template;
use Supra\User\UserProviderInterface;
use Doctrine\ORM\EntityManager;
use Supra\Authorization\AuthorizationProvider;

/**
 * CreateUserCommand
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class CreateUserCommand extends Command
{
	/**
	 * @var UserProviderInterface
	 */
	private $userProvider;

	/**
	 * @var EntityManager
	 */
	private $entityManager;

	/**
	 * @var AuthorizationProvider
	 */
	private $authorizationProvider;

	/**
	 * Adds an option.
	 *
	 * @param string  $name        The option name
	 * @param string  $shortcut    The shortcut (can be null)
	 * @param integer $mode        The option mode: One of the InputOption::VALUE_* constants
	 * @param string  $description A description text
	 * @param mixed   $default     The default value (must be null for InputOption::VALUE_REQUIRED or self::VALUE_NONE)
	 *
	 * @return Command The current instance 
	 */
	protected function configure()
	{
		$this->setName('su:user:create_user')
				->setDescription('Creates new user')
				->setHelp('Creates new user')
				->addOption('email', null, Console\Input\InputOption::VALUE_REQUIRED, 'User email. Confirmation link will be sent to that email')
				->addOption('group', null, Console\Input\InputOption::VALUE_REQUIRED, 'User group. Can be one of admins, contribs or supers.', 'admins')
				->addOption('name', null, Console\Input\InputOption::VALUE_REQUIRED, 'User first name and last name');
	}

	public function __construct($name = null)
	{
		parent::__construct($name);

		$this->userProvider = ObjectRepository::getUserProvider($this);
		$this->entityManager = ObjectRepository::getEntityManager($this);
		$this->authorizationProvider = ObjectRepository::getAuthorizationProvider('Supra\Cms');
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		//User groups must be created beforehand if missing.

		$email = $input->getOption('email');
		if (is_null($email)) {
			throw new RuntimeException('Email is required parameter');
		}

		$name = $input->getOption('name');
		if (is_null($name)) {
			$name = strstr($email, '@', true);
		}

		// if not found any group
		$repo = $this->entityManager->getRepository('Supra\User\Entity\Group');
//		$databaseGroup = $repo->findOneBy(array());
		$databaseGroup = $repo->findOneByName('admins');

		$groupName = trim(strtolower($input->getOption('group')));


		if ( ! $this->userProvider instanceof UserProvider) {
			throw new RuntimeException('Internal error: Could not reach user provider');
		}
		
		$group = $this->userProvider->findGroupByName($groupName);

		if (is_null($group) && $databaseGroup instanceof \Supra\User\Entity\Group) {
			throw new RuntimeException('There is no group "' . $groupName . '" in database');
		} else {
			$groups = $this->createGroups();
			$group = $groups['admins'];
		}

		$user = $this->userProvider->createUser();

		$user->setName($name);
		$user->setEmail($email);

		$user->setGroup($group);

		$this->userProvider->validate($user);

		$authAdapter = $this->userProvider->getAuthAdapter();
		$authAdapter->credentialChange($user);
		$this->userProvider->updateUser($user);

		$output->writeln('Added user "' . $name . '" to "' . $groupName . '" group');

		$userAction = new InternalUserManagerAbstractAction();
		ObjectRepository::setCallerParent($userAction, $this, true);

		$userAction->sendPasswordChangeLink($user, 'createpassword');
	}

	private function createGroups()
	{
		$groups = array(
			'admins' => null,
			'supers' => null,
			'contribs' => null,
		);

		foreach ($groups as $groupName => $groupObject) {
			$group = $this->makeGroup($groupName);
			$groups[$groupName] = $group;
		}

		$adminsGroup = $groups['admins'];
		$adminsGroup->setIsSuper(true);
		$this->userProvider->updateGroup($group);


		$permissions = array(
			'supers' => array(
				'object' => $groups['supers'],
				'deny' => array(
					\Supra\Cms\InternalUserManager\InternalUserManagerController::CN()
				),
			),
			'contribs' => array(
				'object' => $groups['contribs'],
				'deny' => array(
					\Supra\Cms\InternalUserManager\InternalUserManagerController::CN(),
					\Supra\Cms\BannerManager\BannerManagerController::CN(),
				),
			),
		);

		foreach (CmsApplicationConfiguration::getInstance()->getArray() as $appConfig) {
			foreach ($permissions as $groupId => $data) {
				if ( ! in_array($appConfig->id, $data['deny'])) {
					$appConfig->authorizationAccessPolicy->grantApplicationSomeAccessPermission($data['object']);
				}
			}
		}
		// Allow upload everywhere (Media Library application).
		$this->authorizationProvider->setPermsissionStatus(
				$groups['contribs'], new SlashFolder(), File::PERMISSION_UPLOAD_NAME, PermissionStatus::ALLOW
		);
		
		// Locate content root node and allow editing for everytghing below it.
		$localEntityManager = ObjectRepository::getEntityManager('');
		$pr = $localEntityManager->getRepository(AbstractPage::CN());
		$rootNodes = $pr->getRootNodes();
		
		foreach ($rootNodes as $rootNode) {

			// Skip templates.
			if ($rootNode instanceof Template) {
				continue;
			}
			
			$this->authorizationProvider->setPermsissionStatus(
					$groups['contribs'], $rootNode, Entity::PERMISSION_NAME_EDIT_PAGE, PermissionStatus::ALLOW
			);

			break;
		}

		return $groups;
	}

	private function makeGroup($groupName)
	{
		$group = $this->userProvider->findGroupByName($groupName);

		if (empty($group)) {

			$group = $this->userProvider->createGroup();
			$group->setName($groupName);

			$this->userProvider->updateGroup($group);
		}

		return $group;
	}

}