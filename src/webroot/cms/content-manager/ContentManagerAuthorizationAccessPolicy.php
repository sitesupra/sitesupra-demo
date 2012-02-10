<?php

namespace Supra\Cms\ContentManager;

use Supra\Cms\CmsThreewayWithEntitiesAccessPolicy;
use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity as PageEntity;
use Supra\User\Entity\AbstractUser;
use Doctrine\ORM\EntityRepository;
use Supra\Locale\LocaleManager;
use Supra\Authorization\Exception\RuntimeException as AuthorizationRuntimeException;
use Supra\Validator\FilteredInput;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\Page;
use Supra\Controller\Pages\Entity\PageLocalization;

class ContentManagerAuthorizationAccessPolicy extends CmsThreewayWithEntitiesAccessPolicy
//class ContentManagerAuthorizationAccessPolicy extends AuthorizationThreewayWithEntitiesAccessPolicy
{

	public function __construct()
	{
		parent::__construct('pages', PageEntity\Abstraction\Entity::CN());
	}

	public function configure()
	{
		parent::configure();

		$this->permissionHierarchy = array(
			\Supra\Controller\Pages\Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE => array(
				\Supra\Controller\Pages\Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE,
			),
			\Supra\Controller\Pages\Entity\Abstraction\Entity::PERMISSION_NAME_SUPERVISE_PAGE => array(
				\Supra\Controller\Pages\Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE,
				\Supra\Controller\Pages\Entity\Abstraction\Entity::PERMISSION_NAME_SUPERVISE_PAGE
			),
		);

		$this->permission['subproperty']['localized'] = true;
	}

	protected function getEntityPermissionArray(AbstractUser $user, $itemId, $allowed, $denied)
	{
		if (empty($allowed)) {
			return array();
		}

		$em = ObjectRepository::getEntityManager($this);
		$page = $em->find(PageEntity\Abstraction\AbstractPage::CN(), $itemId);

		if (empty($page)) {
			$page = $em->find(PageEntity\Abstraction\Localization::CN(), $itemId);
		}

		$locale = '';
		$title = '';

		if ($page instanceof PageEntity\PageLocalization) {

			// If item is a page loaclization, read locale value and 
			// fetch title directly.
			$locale = $page->getLocale();
			$title = $page->getTitle();
		} else if ($page instanceof PageEntity\GroupLocalization) {

			// If item is a page group loaclization, read locale value and 
			// fetch title directly.
			$locale = $page->getLocale();
			$title = $page->getTitle();
		} else if ($page instanceof PageEntity\GroupPage) {

			$title = $page->getTitle();
		} else if ($page instanceof PageEntity\Page) {

			$localeManager = ObjectRepository::getLocaleManager($this);

			// Otherwise, if this is some master page, fetch current or first page localization 
			// and get title from that.
			$localization = $page->getLocalization($localeManager->getCurrent()->getId());

			if (empty($localization)) {
				$localization = $page->getLocalizations()->first();
			}

			$title = $localization->getTitle();
		} else {
			// $title = 'Deleted object #' . $itemId;
			return array();
		}

		$itemPermission = parent::getEntityPermissionArray($user, $itemId, $allowed, array());

		$itemPermission['locale'] = $locale;
		$itemPermission['title'] = $title;

		return $itemPermission;
	}

	public function getEntityTree(FilteredInput $input)
	{
		$entityTree = array();

		$localeId = $input->get('locale');

		$em = ObjectRepository::getEntityManager($this);
		$pageRepo = $em->getRepository(PageEntity\Abstraction\AbstractPage::CN());
		$rootNodes = $pageRepo->getRootNodes();

		foreach ($rootNodes as $rootNode) {

			// Skip templates.
			if ($rootNode instanceof PageEntity\Template) {
				continue;
			}

			$tree = $this->buildContentTreeArray($rootNode, $localeId);

			if ( ! is_null($tree)) {
				array_push($entityTree, $tree);
			}
		}

		// This will make the group page localizations permanent
		$em->flush(); // !!!

		return $entityTree;
	}

	private function buildContentTreeArray(PageEntity\Page $page, $locale)
	{
		$itemId = null;
		$localization = null;

		if (empty($locale)) {

			$itemId = $page->getId();

			$lm = ObjectRepository::getLocaleManager($this);
			$localization = $page->getLocalization($lm->getCurrent()->getId());

			if (empty($localization)) {
				$allLocalizations = $page->getLocalizations();
				$localization = $allLocalizations->first();
			}
		} else {

			$localization = $page->getLocalization($locale);

			if (empty($localization)) {
				return;
			}

			if ($page instanceof PageEntity\GroupPage) {
				//\Log::debug('PERSISTING GROUP LOCALIZATION FOR ' . $page->getTitle() . ' FOR ' . $locale);
				$page->persistLocalization($localization);
				$em = ObjectRepository::getEntityManager($this);
				$em->persist($localization);
			}

			$itemId = $localization->getId();
		}

		if (empty($localization)) {
			return null;
		}

		\Log::debug('TREE HAS ITEM: ', $itemId);

		$array = array(
			'id' => $itemId,
//				'title' => '[' . $locale . '] ' . $localization->getTitle(),
			'title' => $localization->getTitle(),
			'icon' => 'page',
		);

//		if ($page instanceof PageEntity\GroupPage) {
//			$array['title'] = '{PG} ' . $array['title'];
//		}

		$array['children'] = array();

		foreach ($page->getChildren() as $child) {

			$childArray = $this->buildContentTreeArray($child, $locale);

			if ( ! empty($childArray)) {
				$array['children'][] = $childArray;
			}
		}

		if (count($array['children']) == 0) {
			unset($array['children']);
		} else {
			$array['icon'] = 'folder';
		}

		return $array;
	}

	protected function getApplicationAccessValue(AbstractUser $user)
	{
		return parent::getApplicationAccessValue($user);
	}

	public function getAuthorizedEntityFromId($id)
	{
		$em = $this->getEntityManager();

		$classesToTry = array(
			Localization::CN(),
			Page::CN()
		);

		$entity = null;

		foreach ($classesToTry as $className) {

			$repo = $em->getRepository($className);

			$entity = $repo->find($id);

			if ( ! empty($entity)) {
				break;
			}
		}

		return $entity;
	}

	/**
	 * @param AbstractUser $user
	 * @param AuthorizedEntityInterface $entity
	 * @return array
	 */
	public function getPermissionStatusesForAuthorizedEntity(AbstractUser $user, $entity)
	{
 		$result = parent::getPermissionStatusesForAuthorizedEntity($user, $entity);

		$allAccessGranted = $this->isApplicationAllAccessGranted($user);
		if ($allAccessGranted) {
			return $result;
		}

		$someAccessGranted = $this->isApplicationSomeAccessGranted($user);
		if ( ! $someAccessGranted) {
			return $result;
		}

		$em = $this->getEntityManager();

		$editPagePermissionName = \Supra\Controller\Pages\Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE;

		$repo = $em->getRepository(PageLocalization::CN());

		if ($result[$editPagePermissionName]) {

			$pageLocalization = $repo->find($entity->getAuthorizationId());

			if ( ! empty($pageLocalization)) {
				$scheduleTime = $pageLocalization->getScheduleTime();

				if ( ! empty($scheduleTime)) {
					$result[$editPagePermissionName] = false;
				}
			}
		}

		return $result;
	}

}
