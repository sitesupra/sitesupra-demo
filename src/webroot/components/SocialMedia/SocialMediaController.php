<?php

namespace Project\SocialMedia;

use Supra\Request;
use Supra\Response;
use Supra\Controller\SimpleController;
use SocialMedia\Facebook\Exception\FacebookApiException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;
use SocialMedia\Facebook\Adapter;
use Supra\User\Entity\UserFacebookData;
use Supra\User\Entity\UserFacebookPage;
use Supra\User\Entity\UserFacebookPageTab;

class SocialMediaController extends SimpleController
{
	const PAGE_SOCIAL = '/social';
	const PAGE_SELECT_PAGE = '/social/select-page';
	const PAGE_EDIT_PAGE = '/social/edit-page';
	const PAGE_VIEW_PAGE = '/social/view-page';
	const PAGE_CREATE_TAB = '/social/create-tab';

	private $output = array(
		'success' => 0,
		'data' => null,
		'errorMessage' => null,
	);

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		if ($request instanceof Request\HttpRequest) {
			return new Response\TwigResponse($this);
		}

		return new Response\EmptyResponse();
	}

	public function indexAction()
	{

		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		$facebook = new Adapter($user);

		$repo = ObjectRepository::getEntityManager($this)->getRepository('\Supra\User\Entity\UserFacebookData');
		$facebookData = $repo->findOneByUser($user->getId());

		if ($facebookData instanceof UserFacebookData) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		$pages = $this->getAvailablePages();

		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('appId', $facebook->getAppId());
		$response->assign('addedPages', $pages);

		$response->outputTemplate('index.html.twig');
	}

	public function facebookAction()
	{
		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$this->output['success'] = 1;
			$this->output['redirect_url'] = '/cms';
			return;
		}

		$facebook = new Adapter($user);
		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */
		$response = $this->getResponse();
		/* @var $response Response\HttpResponse */

		if ( ! $request->isPost()) {
			$this->setErrorOutput('Expected POST request');
		}

		$logger = ObjectRepository::getLogger($this);
		$status = $request->getPostValue('status');

		if ($status != 'connected') {
			$this->setErrorOutput('Authentication failed');
		}

		$authResponse = $request->getPostValue('authResponse');
		$accessToken = $authResponse['accessToken'];

		try {
			$facebook->checkAppPermissions();
		} catch (FacebookApiException $e) {
			$this->setErrorOutput($e->getMessage());
			return;
		}

		$em = ObjectRepository::getEntityManager($this);
		$facebookData = new UserFacebookData();
		$em->persist($facebookData);
		$facebookData->setUser($user);
		$facebookData->setFacebookUserId($facebook->getUserId());
		$facebookData->setFacebookAccessToken($accessToken);
		$em->flush();

		$this->output['success'] = 1;
		$this->output['redirect_url'] = self::PAGE_SELECT_PAGE;

		$response->output(json_encode($this->output));
	}

	public function selectPageAction()
	{

		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		/* @var $response Response\HttpResponse */

		$savedPages = $this->getAvailablePages();

		$facebook = new Adapter($user);
		try {
			$pageCollectionFromFacebook = $facebook->getUserPages();
			$pageCollectionFromFacebook = array_diff_key($pageCollectionFromFacebook, $savedPages);
			$response->assign('fetchedPages', $pageCollectionFromFacebook);
		} catch (FacebookApiException $e) {
			
		}

		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('addedPages', $savedPages);

		$response->outputTemplate('select-page.html.twig');
	}

	public function createTabAction()
	{
		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		/* @var $response Response\HttpResponse */
//		$facebook = new Adapter($user);
//
//		try {
//			$facebook->checkAppPermissions();
//		} catch (FacebookApiException $e) {
//			$response->redirect(self::PAGE_SOCIAL);
//			return;
//		}

		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */

		$pageId = $request->getParameter('page_id');
		if (empty($pageId)) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}
//
//		if ( ! $facebook->checkUserPage($pageId)) {
//			$response->redirect(self::PAGE_SELECT_PAGE);
//			return;
//		}

		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('pageId', $pageId);

		$response->outputTemplate('create-tab.html.twig');
	}

	private function setErrorOutput($message)
	{
		$response = $this->getResponse();
		$this->output['errorMessage'] = $message;
		$response->output(json_encode($this->output));
	}

	public function addPageAction()
	{
		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		/* @var $response Response\HttpResponse */
		$facebook = new Adapter($user);

		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */

		$pageId = $request->getParameter('page_id');
		if (empty($pageId)) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		if ( ! $facebook->checkUserPage($pageId)) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		$pageData = $facebook->getUserPage($pageId, false);

		$em = ObjectRepository::getEntityManager($this);
		$page = new \Supra\User\Entity\UserFacebookPage();
		$em->persist($page);
		$page->setPageId($pageId);
		$page->setPageTitle($pageData['name']);
		$page->setPageIcon($pageData['picture']);
		$page->setPageLink($pageData['link']);

		$repo = $em->getRepository('\Supra\User\Entity\UserFacebookData');
		$facebookData = $repo->findOneByUser($user->getId());
		if ( ! $facebookData instanceof UserFacebookData) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		$page->setUserData($facebookData);
		$em->flush();

		$response->redirect(self::PAGE_SELECT_PAGE);
	}

	/**
	 * @return User 
	 */
	private function getCurrentCmsUser()
	{

		$userProvider = ObjectRepository::getUserProvider($this);
		$user = $userProvider->getSignedInUser();

		if ( ! $user instanceof User) {
			return null;
		}

		return $user;
	}

	public function saveTabAction()
	{
		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */
		$response = $this->getResponse();
		/* @var $response Response\HttpResponse */
		if ( ! $request->isPost()) {
			throw new \Exception();
		}

		$post = $request->getPost();
		$em = ObjectRepository::getEntityManager($this);

		/* @var $post Supra\Request\RequestData */

		if ( ! $post->has('page_id')) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		$pageId = $post->get('page_id');

		$repo = $em->getRepository('Supra\User\Entity\UserFacebookPage');

		$page = $repo->findOneByPageId($pageId);

		if ( ! $page instanceof UserFacebookPage) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		$tab = new UserFacebookPageTab();

		if ($post->has('tab_id')) {
			$tabId = $post->get('tab_id');
			$repo = $em->getRepository('Supra\User\Entity\UserFacebookPageTab');
			$tab = $repo->findOneById($tabId);

			if ( ! $tab instanceof UserFacebookPageTab) {
				$response->redirect(self::PAGE_VIEW_PAGE . '?page_id=' . $pageId);
				return;
			}
		}

		if ( ! $post->has('tab-title')) {
			$response->redirect(self::PAGE_CREATE_TAB . '?page_id=' . $pageId);
			return;
		}

		$title = $post->get('tab-title');

		if ( ! $post->has('content')) {
			$response->redirect(self::PAGE_CREATE_TAB . '?page_id=' . $pageId);
			return;
		}

		$content = $post->get('content');

		$em->persist($tab);
		$tab->setHtml($content);
		$tab->setTabTitle($title);
		$tab->setPage($page);
		$em->flush();

		$response->redirect(self::PAGE_VIEW_PAGE . '?page_id=' . $pageId);
	}

	public function viewPageAction()
	{
		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		/* @var $response Response\HttpResponse */
//		$facebook = new Adapter($user);
//
//		try {
//			$facebook->checkAppPermissions();
//		} catch (FacebookApiException $e) {
//			$response->redirect(self::PAGE_SOCIAL);
//			return;
//		}

		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */

		$pageId = $request->getParameter('page_id');
		if (empty($pageId)) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

//		if ( ! $facebook->checkUserPage($pageId)) {
//			$response->redirect(self::PAGE_SELECT_PAGE);
//			return;
//		}

		$output = array();

		$pageRepo = ObjectRepository::getEntityManager($this)->getRepository('\Supra\User\Entity\UserFacebookPage');
		$page = $pageRepo->findOneByPageId($pageId);

		$tabsRepo = ObjectRepository::getEntityManager($this)->getRepository('\Supra\User\Entity\UserFacebookPageTab');
		$tabs = $tabsRepo->findByPage($page->getId());


		foreach ($tabs as $tab) {
			/* @var $tab UserFacebookPageTab */
			$output[] = array(
				'id' => $tab->getId(),
				'title' => $tab->getTabTitle(),
			);
		}
		// change to database page
		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('tabs', $output);
		$response->assign('page', $this->getPageData($page));

		$response->outputTemplate('view-page.html.twig');
	}

	public function createDummyAction()
	{
		$user = $this->getCurrentCmsUser();
		$facebook = new Adapter($user);

		$em = ObjectRepository::getEntityManager($this);
		$em->persist($user);

		$pages = array_keys($facebook->getUserPages());

		$facebookPage = new \Supra\User\Entity\UserFacebookPage();
		$facebookPage->setUser($user);
		$facebookPage->setPageId($pages[array_rand($pages)]);

		$tab = new \Supra\User\Entity\UserFacebookPageTab();
		$tab->setTabTitle('Welcome ' . mt_rand(0, 10000));
		$tab->setHtml('<div>Hello World ' . mt_rand(0, 10000) . '!</div>');
		$tab->setTabId($facebookPage->getPageId() . '-' . $tab->getId());

		$tab->setPage($facebookPage);

		$em->flush();
	}

	public function getAvailablePages()
	{
		//TODO Check current user access rights to particular page
		$repo = ObjectRepository::getEntityManager($this)->getRepository('\Supra\User\Entity\UserFacebookPage');
		$databasePages = $repo->findAll();

		$savedPages = array();
		foreach ($databasePages as $page) {
			/* @var $page \Supra\User\Entity\UserFacebookPage */
			$savedPages[$page->getPageId()] = $this->getPageData($page);
		}

		return $savedPages;
	}

	protected function getPageData(UserFacebookPage $page)
	{
		if ( ! $page instanceof UserFacebookPage) {
			return null;
		}

		return array(
			'id' => $page->getPageId(),
			'name' => $page->getPageTitle(),
			'picture' => $page->getPageIcon(),
			'link' => $page->getPageLink(),
			'tabs' => $page->getTabs()->count(),
		);
	}

	public function deleteTabAction()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		$tabId = $request->getParameter('tab_id');

		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository('\Supra\User\Entity\UserFacebookPageTab');
		$tab = $repo->findOneById($tabId);
		/* @var $tab UserFacebookPageTab */
		$pageId = $tab->getPage()->getPageId();
		$em->remove($tab);
		$em->flush();

		$response->redirect(self::PAGE_VIEW_PAGE . '?page_id=' . $pageId);
	}

	public function editTabAction()
	{
		$response = $this->getResponse();
		$request = $this->getRequest();
		/* @var $response Response\TwigResponse */
		/* @var $request Request\HttpRequest */

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		$em = ObjectRepository::getEntityManager($this);
		$tabId = $request->getParameter('tab_id');
		$repo = $em->getRepository('Supra\User\Entity\UserFacebookPageTab');
		$tab = $repo->findOneById($tabId);

		if ( ! $tab instanceof UserFacebookPageTab) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		$pageId = $tab->getPage()->getPageId();

		$response->assign('title', $tab->getTabTitle());
		$response->assign('content', $tab->getHtml());
		$response->assign('pageId', $pageId);
		$response->assign('tabId', $tabId);

		$response->outputTemplate('create-tab.html.twig');
	}

}