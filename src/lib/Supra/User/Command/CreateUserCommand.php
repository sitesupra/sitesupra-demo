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
use Supra\User\Entity\Group;
use Supra\Controller\Pages\Event\CmsUserCreateEventArgs;
use Supra\Cms\CmsController;

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

		$this->userProvider = ObjectRepository::getUserProvider('Supra\Cms\CmsController');

		if (empty($this->userProvider)) {
			throw new RuntimeException('Could not get user provider.');
		}

		$this->entityManager = ObjectRepository::getEntityManager($this);
		$this->authorizationProvider = ObjectRepository::getAuthorizationProvider('Supra\Cms');
	}

	private function validateGroupName($groupName)
	{
		$validGroupNames = array(
			'admins',
			'contribs',
			'supers'
		);

		if ( ! in_array($groupName, $validGroupNames)) {
			throw new RuntimeException('Bad group name "' . $groupName . '".');
		}
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$userProvider = $this->userProvider;

		// User groups must exist.
		$this->ensureGroupsExist();

		$email = $input->getOption('email');
		if (is_null($email)) {
			throw new RuntimeException('Email is required option.');
		}

		$name = $input->getOption('name');
		if (is_null($name)) {
			$name = strstr($email, '@', true);
		}

		$groupName = trim(strtolower($input->getOption('group')));

		$this->validateGroupName($groupName);

		$group = $userProvider->findGroupByName($groupName);

		$user = $userProvider->createUser();
		$user->setName($name);
		$user->setEmail($email);
		$user->setGroup($group);

		$userProvider->validate($user);
		$userProvider->credentialChange($user);
		$userProvider->updateUser($user);

		$output->writeln('Added user "' . $name . '" to "' . $groupName . '" group');

		$userAction = new InternalUserManagerAbstractAction();
		ObjectRepository::setCallerParent($userAction, $this, true);

		//$userAction->sendPasswordChangeLink($user, 'createpassword');

		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = new CmsUserCreateEventArgs($user);
		$eventArgs->setUserProvider($this->userProvider);
		$eventManager->fire(CmsController::EVENT_POST_USER_CREATE, $eventArgs);
	}

	private function ensureAdminsGroupExist()
	{
		$userProvider = $this->userProvider;

		$group = $userProvider->findGroupByName('admins');

		if (empty($group)) {

			$group = $this->createGroup('admins');

			$group->setIsSuper(true);

			$userProvider->updateGroup($group);
		}
	}

	/**
	 * @param Group $group
	 * @param array $allowedApplicationIds 
	 */
	private function setApplicationAccessPermissions(Group $group, $allowedApplicationIds)
	{
		foreach (CmsApplicationConfiguration::getInstance()->getArray(true) as $appId => $appConfig) {

			if (in_array($appId, $allowedApplicationIds)) {
				$appConfig->authorizationAccessPolicy->grantApplicationAllAccessPermission($group);
			} else {
				$appConfig->authorizationAccessPolicy->revokeApplicationAllAccessPermission($group);
			}
		}
	}

	private function ensureSupersGroupExist()
	{
		$userProvider = $this->userProvider;

		$group = $userProvider->findGroupByName('supers');

		if (empty($group)) {

			$group = $this->createGroup('supers');

			$allowedApplicationIds = array(
				'Supra\Cms\ContentManager',
				//'Supra\Cms\InternalUserManager\InternalUserManagerController',
				'Supra\Cms\BannerManager',
				'Supra\Cms\MediaLibrary',
			);

			$this->setApplicationAccessPermissions($group, $allowedApplicationIds);
		}
	}

	private function ensureContribsGroupExist()
	{
		$userProvider = $this->userProvider;
		$authorizationProvider = $this->authorizationProvider;

		$group = $userProvider->findGroupByName('contribs');

		if (empty($group)) {

			$group = $this->createGroup('contribs');

			$allowedApplicationIds = array(
				'Supra\Cms\ContentManager',
				//'Supra\Cms\InternalUserManager\InternalUserManagerController',
				//'Supra\Cms\BannerManager',
				'Supra\Cms\MediaLibrary',
			);

			$this->setApplicationAccessPermissions($group, $allowedApplicationIds);

			// Allow upload to everywhere.
			$authorizationProvider->setPermsissionStatus(
					$group, new SlashFolder(), File::PERMISSION_UPLOAD_NAME, PermissionStatus::ALLOW
			);

			// Locate (first) content root node and allow editing for everytghing below it.
			$localEntityManager = ObjectRepository::getEntityManager('');
			$pr = $localEntityManager->getRepository(AbstractPage::CN());
			$rootNodes = $pr->getRootNodes();
			foreach ($rootNodes as $rootNode) {

				// Skip templates.
				if ( ! $rootNode instanceof Template) {

					$this->authorizationProvider->setPermsissionStatus(
							$group, $rootNode, Entity::PERMISSION_NAME_EDIT_PAGE, PermissionStatus::ALLOW
					);

					break;
				}
			}
		}
	}

	private function ensureGroupsExist()
	{
		$this->ensureAdminsGroupExist();
		$this->ensureSupersGroupExist();
		$this->ensureContribsGroupExist();
	}

}
