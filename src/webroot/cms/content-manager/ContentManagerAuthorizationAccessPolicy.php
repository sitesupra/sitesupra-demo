<?php

namespace Supra\Cms\ContentManager;

use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity as PageEntity;
use Supra\User\Entity\Abstraction\User;
use Doctrine\ORM\EntityRepository;
use Supra\Locale\LocaleManager;
use Supra\Request\RequestInterface;
use Supra\Request\HttpRequest;
use Supra\Authorization\Exception\RuntimeException as AuthorizationRuntimeException;

class ContentManagerAuthorizationAccessPolicy extends AuthorizationThreewayWithEntitiesAccessPolicy
{

	/**
	 * Page repository.
	 * @var EntityRepository
	 */
	private $pr;

	/**
	 * Page localization repository.
	 * @var EntityRepository 
	 */
	private $lr;

	/**
	 * Locale manager.
	 * @var LocaleManager
	 */
	private $lm;

	/**
	 * Entity manager.
	 * @var EntityManager
	 */
	private $em;

	function __construct()
	{
		parent::__construct('pages', PageEntity\Abstraction\Entity::CN());
	}

	public function configure()
	{
		parent::configure();

		$this->lm = ObjectRepository::getLocaleManager($this);

		$this->em = ObjectRepository::getEntityManager($this);

		$this->pr = $this->em->getRepository(PageEntity\Abstraction\AbstractPage::CN());
		$this->lr = $this->em->getRepository(PageEntity\Abstraction\Localization::CN());

		$this->permission['subproperty']['localized'] = true;
	}

	protected function getEntityPermissionArray(User $user, $itemId, $allowed, $denied)
	{
		if (empty($allowed)) {
			return array();
		}

		$page = $this->pr->find($itemId);

		if (empty($page)) {
			$page = $this->lr->find($itemId);
		}

		$locale = '';
		$title = '';

		if ($page instanceof PageEntity\PageLocalization) {

			// If item is a page loaclization, read locale value and 
			// fetch title directly.
			$locale = $page->getLocale();
			$title = $page->getTitle();
		}
		else if ($page instanceof PageEntity\GroupLocalization) {

			// If item is a page group loaclization, read locale value and 
			// fetch title directly.
			$locale = $page->getLocale();
			$title = $page->getTitle();
		}
		else if ($page instanceof PageEntity\GroupPage) {

			$title = '{GP} ' . $page->getTitle();
		}
		else if ($page instanceof PageEntity\Page) {

			// Otherwise, if this is some master page, fetch current or first page localization 
			// and get title from that.
			$localization = $page->getLocalization($this->lm->getCurrent()->getId());

			if (empty($localization)) {
				$localization = $page->getLocalizations()->first();
			}

			$title = $localization->getTitle();
		}
		else {
			$title = 'Deleted object #' . $itemId;
		}

		$itemPermission = parent::getEntityPermissionArray($user, $itemId, $allowed, array());

		$itemPermission['locale'] = $locale;
		$itemPermission['title'] = $title;

		return $itemPermission;
	}

	public function getEntityTree(RequestInterface $request)
	{
		if ( ! ($request instanceof HttpRequest)) {
			throw new AuthorizationRuntimeException('Do not know what to do with non-HTTP request.');
		}

		$entityTree = array();

		/* @var $request HttpRequest */
		$localeId = $request->getQueryValue('locale');

		$rootNodes = $this->pr->getRootNodes();

		foreach ($rootNodes as $rootNode) {

			// Skip templates.
			if ($rootNode instanceof PageEntity\Template) {
				continue;
			}

			$tree = $this->buildContentTreeArray($rootNode, $localeId);
			// TODO: hardcoded
			$tree['icon'] = 'home';
			$tree['title'] = '[' . $localeId . '] Home';

			$entityTree[] = $tree;
		}

		$this->em->flush(); // !!!

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
		}
		else {

			$localization = $page->getLocalization($locale);

			if (empty($localization)) {
				return;
			}

			if ($localization instanceof PageEntity\GroupLocalization) {
				//\Log::debug('PERSISTING GROUP LOCALIZATION FOR ' . $page->getTitle() . ' FOR ' . $locale);
				$page->persistLocalization($localization);
				$this->em->persist($localization);
			}

			$itemId = $localization->getId();
		}

		if (empty($localization)) {
			return null;
		}

		\Log::debug('TREE HAS ITEM: ', $itemId);

		$array = array(
				'id' => $itemId,
				'title' => '[' . $locale . '] ' . $localization->getTitle(),
				'icon' => 'page',
				'preview' => '/cms/lib/supra/img/sitemap/preview/page-1.jpg'
		);

		if ($page instanceof PageEntity\GroupPage) {
			$array['title'] = '{PG} ' . $array['title'];
		}

		$array['children'] = array();

		foreach ($page->getChildren() as $child) {

			$childArray = $this->buildContentTreeArray($child, $locale);

			if ( ! empty($childArray)) {
				$array['children'][] = $childArray;
			}
		}

		if (count($array['children']) == 0) {
			unset($array['children']);
		}
		else {
			$array['icon'] = 'folder';
		}

		return $array;
	}

	protected function getApplicationAccessValue(User $user)
	{
		return parent::getApplicationAccessValue($user);
	}

}
