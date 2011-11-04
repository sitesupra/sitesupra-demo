<?php

namespace Supra\Tests\Authorization\Fixture;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\Authorization\Permission\PermissionStatus;
use Supra\Cms\ApplicationConfiguration;
use Supra\User\Entity\Abstraction\User;
use Supra\User\Entity\User as RealUser;
use Supra\User\Entity\Group as RealGroup;
use Supra\FileStorage\Entity as FileEntity;
use Supra\Controller\Pages\Entity as PageEntity;
use Doctrine\ORM\EntityRepository;

class FixtureHelper
{

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var AuthorizationProvider
	 */
	private $ap;

	/**
	 * @var \Supra\User\UserProvider;
	 */
	private $up;

	/**
	 * Namespace argument which is used for ObjectRepository calls
	 * @var mixed
	 */
	public $namespace;

	function __construct($namespace)
	{
		$this->namespace = $namespace;

		$this->em = ObjectRepository::getEntityManager($this->namespace);
		$this->up = ObjectRepository::getUserProvider($this->namespace);

		// ACL model drop/create
		try {
			$this->em->getConnection()->getWrappedConnection()->exec(file_get_contents(SUPRA_PATH . '/../database/authorization-mysql.sql'));
		}
		catch (\Exception $e) {
			
		}

		$this->ap = ObjectRepository::getAuthorizationProvider($this->namespace);
	}

	/**
	 * Creates user
	 * @param string $userName
	 * @param RealGroup $group
	 * @return RealUser
	 */
	public function makeUser($userName, RealGroup $group = null)
	{
		/* @var $user RealUser */
		$user = $this->up->findUserByLogin($userName);

		$em = $this->up->getEntityManager();
		
		if (empty($user)) {

			$user = new RealUser();
			$em->persist($user);

			$user->setLogin($userName);
			$user->setName($userName);
			$user->setEmail($userName . '@supra7.vig');
			$plainPassword = $userName;
			$password = new \Supra\Authentication\AuthenticationPassword($plainPassword);

			$this->up->getAuthAdapter()->credentialChange($user, $password);
			$em->flush();
		}

		if ( ! empty($group)) {

			$user->setGroup($group);
			$em->flush();
		}

		return $user;
	}

	/**
	 * Creates new group
	 * @param string $groupName
	 * @return RealGroup
	 */
	public function makeGroup($groupName)
	{
		$group = $this->up->findGroupByName($groupName);
		$em = $this->up->getEntityManager();

		if (empty($group)) {

			$group = new \Supra\User\Entity\Group();
			$em->persist($group);
			$group->setName($groupName);
			$em->flush();
		}

		return $group;
	}

	/**
	 * Runs fixtures
	 */
	public function build()
	{
		$adminUserName = 'admin';
		$superUserName = 'super';
		$contribUserName = 'contrib';

		$groups = array();
		$users = array();

		foreach (array($adminUserName, $superUserName, $contribUserName) as $userName) {

			$groupName = $userName . 's';

			$group = $this->makeGroup($groupName);
			$user = $this->makeUser($userName, $group);

			$groups[$groupName] = $group;
			$users[$userName] = $user;
		}

		$adminsGroup = $users[$adminUserName]->getGroup();
		$adminsGroup->setIsSuper(true);
		$this->up->getEntityManager()->flush();

		$superUser = $users[$superUserName];
		foreach (CmsApplicationConfiguration::getInstance()->getArray() as $appConfig) {

			if (in_array($appConfig->id, array('internal-user-manager'))) {
				//$appConfig->authorizationAccessPolicy->grantApplicationSomeAccessPermission($superUser->getGroup());
			}
			else {
				$appConfig->authorizationAccessPolicy->grantApplicationAllAccessPermission($superUser->getGroup());
			}
		}

		\Log::debug('==============================');

		$contribUser = $users[$contribUserName];
		foreach (CmsApplicationConfiguration::getInstance()->getArray() as $appConfig) {
			
			if ( ! in_array($appConfig->id, array('internal-user-manager', 'banner-manager'))) {
				$appConfig->authorizationAccessPolicy->grantApplicationSomeAccessPermission($contribUser->getGroup());
			}
		}

		// Allow upload everywhere (Media Library application).
		$this->ap->setPermsissionStatus(
				$contribUser->getGroup(), 
				new FileEntity\SlashFolder(),
				FileEntity\Abstraction\File::PERMISSION_UPLOAD_NAME, 
				PermissionStatus::ALLOW
		);
		
		// Locate content root node and allow editing for everytghing below it.
		$pr = $this->em->getRepository(PageEntity\Abstraction\AbstractPage::CN());
		$rootNodes = $pr->getRootNodes();

		foreach ($rootNodes as $rootNode) {
			
			// Skip templates.
			if( $rootNode instanceof PageEntity\Template) {
				continue;
			}
			
			$this->ap->setPermsissionStatus(
					$contribUser->getGroup(), 
					$rootNode,
					PageEntity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE,
					PermissionStatus::ALLOW
			);
			
			break;
		}

		\Log::debug('==============================');
	}
}

