<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Supra\Core\HttpFoundation\SupraJsonResponse;
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

	public function userListAction(Request $request)
	{
		$result = array();

//		if ($appConfig instanceof ApplicationConfiguration) {
//			if ($appConfig->allowGroupEditing) {
				$groupRepository = $this->container->getDoctrine()->getManager()->getRepository(Group::CN());
				$groups = $groupRepository->findAll();

				/* @var $group Group */
				foreach($groups as $group) {

					$result[] = array(
						'id' => $group->getId(),
						'avatar' => null,
						'name' =>  '[' . $group->getName() . ']',
						'group' => $this->groupToDummyId($group)
					);
				}
			//}
		//}

		$users = $this->container->getDoctrine()->getManager()->getRepository(User::CN());

		/* @var $user User */
		foreach ($users as $user) {

			if (is_null($user->getGroup())) {

				$this->container->getLogger()->debug('User has no group: ', $user->getId());

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
}
