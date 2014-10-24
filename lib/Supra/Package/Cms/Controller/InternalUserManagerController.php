<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\CmsAuthentication\Entity\AbstractUser;
use Supra\Package\CmsAuthentication\Entity\Group;
use Supra\Package\CmsAuthentication\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class InternalUserManagerController extends Controller
{
	/**
	 * @var array
	 */
	protected $dummyGroupMap;

	/**
	 * @var array
	 */
	protected $reverseDummyGroupMap;

	protected $application = 'internal-user-manager';

	public function __construct()
	{
		$this->dummyGroupMap = array('admins' => 1, 'contribs' => 3, 'supers' => 2);
		$this->reverseDummyGroupMap = array_flip($this->dummyGroupMap);
	}

	public function indexAction(Request $request)
	{
		return $this->renderResponse('index.html.twig');
	}

	public function updateAction(Request $request)
	{
		$userId = $request->request->get('user_id');
		$newGroupDummyId = $request->request->get('group');
		$newGroupName = $this->dummyGroupIdToGroupName($newGroupDummyId);

		$user = $this->container->getDoctrine()->getRepository('CmsAuthentication:User')
			->findOneById($userId);

		/* @var $user User */

		if (empty($user)) {
			throw new CmsException(null, 'Requested user was not found');
		}

		if ($user->isSuper() && $user->getId() == $this->getUser()->getId()) {
			throw new CmsException(null, 'You cannot change group for yourself');
		}

		/* @var $groupRepository EntityRepository */
		//$groupRepository = $this->entityManager->getRepository(Entity\Group::CN());
		//$newGroup = $groupRepository->findOneBy(array('name' => $newGroupName));

		$newGroup = $this->container->getDoctrine()->getRepository('CmsAuthentication:Group')
			->findOneByName($newGroupName);

		/* @var $newGroup Group */

		// On user group change all user individual permissions are unset
		if($user->getGroup()->getId() != $newGroup->getId()) {
			//todo: unser permissions here whe the is acl
			$user->setGroup($newGroup);
		}

		return new SupraJsonResponse(null);
	}

	/**
	 * Password reset action
	 */
	public function resetAction(Request $request)
	{
		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "
		if (!$request->request->has('user_id')) {
			throw new CmsException(null, 'User id is not set');
		}

		$userId = $request->request->get('user_id');

		$user = $this->container->getDoctrine()->getRepository('CmsAuthentication:User')->findOneById($userId);

		if (empty($user)) {
			throw new CmsException(null, 'Can\'t find user with such id');
		}

		$password = substr(md5(rand() . time()), 0, 8);

		$user->setPassword($this->encodePassword($user, $password));
		$this->container->getDoctrine()->getManager()->flush();

		$message = \Swift_Message::newInstance(
			'SiteSupra password reset',
			sprintf('Hi %s, your new password is: %s', $user->getLogin(), $password)
		);

		$message->setTo($user->getEmail());
		$message->setFrom($this->container->getParameter('framework.swiftmailer.default_from'));

		$this->container->getMailer()->send($message);

		$this->checkActionPermission($user->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);

		return new SupraJsonResponse(null);
	}

	/**
	 * Delete user action
	 */
	public function deleteAction(Request $request)
	{
		// TODO: Add validation class to have ability check like " if (empty($validation['errors'])){} "
		if (!$request->request->get('user_id')) {
			throw new CmsException(null, 'User id is not set');
		}

		$userId = $request->request->get('user_id');

		$currentUser = $this->getUser();
		$currentUserId = $currentUser->getId();

		if ($currentUserId == $userId) {
			throw new CmsException(null, 'You can\'t delete current user account');
		}

		$user = $this->container->getDoctrine()->getRepository('CmsAuthentication:User')->findOneById($userId);

		if (empty($user)) {
			throw new CmsException(null, 'Can\'t find user with such id');
		}

		$this->checkActionPermission($user->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);

		$this->container->getDoctrine()->getManager()->remove($user);
		$this->container->getDoctrine()->getManager()->flush();

		return new SupraJsonResponse(null);
	}


	/**
	 * User save
	 */
	public function saveAction(Request $request)
	{
		$user = $this->getUserOrGroup($request->request->get('user_id'));

		if ($user->getId() != $this->getUser()->getId()) {
			$this->checkActionPermission($user->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);
		}

		//TODO: temporary solution for groups, don't save anything
		if ( ! $user instanceof User) {
			$response = $this->userToArray($user);

			return new SupraJsonResponse($response);
		}

		if ($request->request->has('name')) {
			$name = $request->request->get('name');
			$user->setName($name);
		}

		if ($request->request->has('email')) {
			$email = $request->request->filter('email', null, false, FILTER_VALIDATE_EMAIL);
			$user->setEmail($email);
		}

		$this->container->getDoctrine()->getManager()->flush();

		return new SupraJsonResponse($this->userToArray($user));
	}

	public function loadAction(Request $request)
	{
		if (!$request->query->get('user_id')) {
			throw new CmsException(null, 'User id is not set');
		}

		/* @var $user AbstractUser */
		$user = $this->getUserOrGroup($request->query->get('user_id'));

		if ($user->getId() != $this->getUser()->getId()) {
			$this->checkActionPermission($user->getGroup(), Group::PERMISSION_MODIFY_USER_NAME);
		}

		$response = $this->userToArray($user);
		$response['permissions'] = $this->permissionsToArray($user);

		//this has always been a hardcode
		$response['canUpdate'] = true;
		$response['canDelete'] = true;
		$response['canCreate'] = true;

		return new SupraJsonResponse($response);
	}

	public function listAction(Request $request)
	{
		$result = array();

//		if ($appConfig instanceof ApplicationConfiguration) {
//			if ($appConfig->allowGroupEditing) {
//				$groupRepository = $this->container->getDoctrine()->getManager()->getRepository(Group::CN());
//				$groups = $groupRepository->findAll();
//
//				foreach($groups as $group) {
//
//					$result[] = array(
//						'id' => $group->getId(),
//						'avatar' => null,
//						'name' =>  '[' . $group->getName() . ']',
//						'group' => $this->groupToDummyId($group)
//					);
//				}
			//}
		//}

		$users = $this->container->getDoctrine()->getManager()->getRepository(User::CN())->findAll();

		/* @var $user User */
		foreach ($users as $user) {

			if (is_null($user->getGroup())) {

				$this->container->getLogger()->debug('User has no group: '.$user->getId());

				continue;
			}

			$result[] = array(
				'id' => $user->getId(),
				'avatar' => $user->getGravatarUrl(48, $request->isSecure() ? 'https' : 'http'),
				'name' => $user->getName(),
				'group' => $this->groupToDummyId($user->getGroup())
			);
		}

		return new SupraJsonResponse($result);
	}

	/**
	 * Insert action
	 */
	public function insertAction(Request $request)
	{
		$email = $request->request->filter('email', null, false, FILTER_VALIDATE_EMAIL);

		$existingUser = $this->container->getDoctrine()->getRepository('CmsAuthentication:User')->findOneByEmail($email);

		if ( ! empty($existingUser)) {
			throw new CmsException(null, 'User with this email is already registered!');
		}

		$name = $request->request->get('name');
		$dummyGroupId = $request->request->get('group');

		$groupName = $this->dummyGroupIdToGroupName($dummyGroupId);
		$group = $this->container->getDoctrine()->getRepository('CmsAuthentication:Group')->findOneByName($groupName);

		$this->checkActionPermission($group, Group::PERMISSION_MODIFY_USER_NAME);

		$user = new User();

		$user->setLogin($email);
		$user->setName($name);
		$user->setEmail($email);
		$user->setGroup($group);

		$password = substr(md5(rand() . time()), 0, 8);

		$user->setPassword($this->encodePassword($user, $password));
		$this->container->getDoctrine()->getManager()->flush();

		$message = \Swift_Message::newInstance(
			'SiteSupra new user',
			sprintf('Hi %s, you are registered to SiteSupra at %s, your password is: %s', $user->getLogin(), $request->getSchemeAndHttpHost(), $password)
		);

		$message->setTo($user->getEmail());
		$message->setFrom($this->container->getParameter('framework.swiftmailer.default_from'));

		$this->container->getMailer()->send($message);

		$this->container->getDoctrine()->getManager()->persist($user);
		$this->container->getDoctrine()->getManager()->flush();

		return new SupraJsonResponse(array('user_id' => $user->getId()));
	}

	protected function permissionsToArray($user)
	{
		/*$config = CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray();

		$permissions = array();

		foreach ($appConfigs as $appConfig) {*/
			/* @var $appConfig ApplicationConfiguration  */
		/*if ( ! is_null($appConfig->authorizationAccessPolicy)) {
			$permissions[$appConfig->id] = $appConfig->authorizationAccessPolicy->getAccessPolicy($user);
		}
	}

return $permissions;*/
		return array();
	}

	protected function userToArray(AbstractUser $user)
	{
		$response = array(
			'user_id' => $user->getId(),
			'name' => $user->getName(),
		);

		if ($user instanceof User) {
			$response['email'] = $user->getEmail();
			$response['group'] = $this->groupToDummyId($user->getGroup());
		} else {
			$response['email'] = 'N/A';
			$response['group'] = $this->groupToDummyId($user);
			$response['group_id'] = $this->groupToDummyId($user);
		}

		if (empty($response['avatar'])) {
			$response['avatar'] = '/public/cms/supra/img/avatar-default-48x48.png';
		}

		return $response;
	}

	/**
	 * @param $id
	 * @return AbstractUser
	 */
	protected function getUserOrGroup($id)
	{
		$user = $this->container->getDoctrine()->getManager()->getRepository('CmsAuthentication:User')->findOneBy(array('id' => $id));

		if ($user) {
			return $user;
		}

		$group = $this->container->getDoctrine()->getManager()->getRepository('CmsAuthentication:Group')->findOneBy(array('id' => $id));

		if ($group) {
			return $group;
		}

		throw new CmsException(null, sprintf('No user or group with id "%s" has been found', $id));
	}

	/**
	 * @param string $dummyId
	 * @return string
	 */
	protected function dummyGroupIdToGroupName($dummyId)
	{
		return $this->reverseDummyGroupMap[$dummyId];
	}

	/**
	 * @param Group $group
	 * @return string
	 */
	protected function groupToDummyId(Group $group)
	{
		return $this->dummyGroupMap[$group->getName()];
	}

	protected function encodePassword(User $user, $password)
	{
		$encoder = $this->container['cms_authentication.encoder_factory']
			->getEncoder($user);

		return $encoder->encodePassword($password, $user->getSalt());
	}
}
