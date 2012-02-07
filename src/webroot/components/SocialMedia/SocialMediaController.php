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
use Doctrine\ORM\NoResultException;

class SocialMediaController extends SimpleController
{
	const PAGE_SOCIAL = '/social';
	const PAGE_SELECT_PAGE = '/social/select-page';
	const PAGE_EDIT_PAGE = '/social/edit-page';
	const PAGE_VIEW_PAGE = '/social/view-page';
	const PAGE_CREATE_TAB = '/social/create-tab';
	const PAGE_VIEW_TAB = '/social/view-tab';

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
		/* @var $response \Supra\Response\TwigResponse */

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		$savedPages = $this->getAvailablePages();

		$facebook = new Adapter($user);
		try {
			$pageCollectionFromFacebook = $facebook->getUserPages();
			$pageCollectionFromFacebook = array_diff_key($pageCollectionFromFacebook, $savedPages);
			$response->assign('fetchedPages', $pageCollectionFromFacebook);
		} catch (FacebookApiException $e) {
			
		}

		$response->assign('addedPages', $savedPages);

		$response->outputTemplate('select-page.html.twig');
	}

	public function createTabAction()
	{
		$response = $this->getResponse();
		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */
		/* @var $response \Supra\Response\TwigResponse */

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		$pageId = $request->getParameter('page_id');
		if (empty($pageId)) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

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
		$pageRepo = $em->getRepository('\Supra\User\Entity\UserFacebookPage');
		$existingPage = $pageRepo->findOneByPageId($pageId);

		if ($existingPage instanceof UserFacebookPage) {
			// TODO: Give access to user to manage that page
			$logger = ObjectRepository::getLogger($this);
			$logger->info('Page ' . $existingPage->getPageTitle() . ' is already linked to supra');
			return;
		}

		$page = new UserFacebookPage();
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
		$response = $this->getResponse();
		/* @var $request Request\HttpRequest */
		/* @var $response Response\HttpResponse */
		if ( ! $request->isPost()) {
			throw new \Exception();
		}

		$post = $request->getPost();
		/* @var $post Supra\Request\RequestData */
		$em = ObjectRepository::getEntityManager($this);

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

		if (empty($title)) {
			$response->redirect(self::PAGE_CREATE_TAB . '?page_id=' . $pageId);
			return;
		}

		if ( ! $post->has('content')) {
			$response->redirect(self::PAGE_CREATE_TAB . '?page_id=' . $pageId);
			return;
		}

		$content = $post->get('content');

		if (empty($content)) {
			$response->redirect(self::PAGE_CREATE_TAB . '?page_id=' . $pageId);
			return;
		}

		$em->persist($tab);
		$tab->setHtml($content);
		$tab->setTabTitle($title);
		$tab->setPage($page);
		// TODO: Draft / publish
		$tab->setPublished(false);
		$em->flush();

		$response->redirect(self::PAGE_VIEW_PAGE . '?page_id=' . $pageId);
	}

	public function viewPageAction()
	{
		$response = $this->getResponse();
		$request = $this->getRequest();
		/* @var $response Response\HttpResponse */
		/* @var $request Request\HttpRequest */

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		$request = $this->getRequest();

		$pageId = $request->getParameter('page_id');
		if (empty($pageId)) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

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
				'published' => $tab->isPublished(),
			);
		}
		// change to database page
		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('tabs', $output);
		$response->assign('page', $this->getPageData($page));

		$response->outputTemplate('view-page.html.twig');
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

		if ($tab->isPublished()) {
			try {
				$facebook = new Adapter($user);
				$facebook->removeTabFromPage($tab);
			} catch (FacebookApiException $exc) {
				$logger = ObjectRepository::getLogger($this);
				$logger->error($exc->getMessage());
				$response->redirect(self::PAGE_VIEW_PAGE . '?page_id=' . $pageId);
				return;
			}
		}

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

	public function publishTabAction()
	{
		$response = $this->getResponse();
		$request = $this->getRequest();
		$logger = ObjectRepository::getLogger($this);
		/* @var $response Response\TwigResponse */
		/* @var $request Request\HttpRequest */

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		$publish = $request->getParameter('publish');
		if ( ! in_array($publish, array('0', '1'))) {
			$publish = false;
			$logger->warn('"publish" is not boolean, will set publish to false');
		}

		$publish = (bool) $publish;

		$em = ObjectRepository::getEntityManager($this);
		$tabId = $request->getParameter('tab_id');
		$repo = $em->getRepository('Supra\User\Entity\UserFacebookPageTab');
		$tab = $repo->findOneById($tabId);

		if ( ! $tab instanceof UserFacebookPageTab) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		$facebook = new Adapter($user);
		$pageId = $tab->getPage()->getPageId();
		try {
			if ($publish) {

				$facebook->addTabToPage($tab);

				// find already published pages and unpublish them
				$publishedPages = $repo->findBy(array('published' => true));
				foreach ($publishedPages as $publishedPage) {
					$em->persist($publishedPage);
					$publishedPage->setPublished(false);
					$em->flush();
				}
			} else {
				$facebook->removeTabFromPage($tab);
			}
		} catch (FacebookApiException $exc) {
			$logger = ObjectRepository::getLogger($this);
			$logger->error($exc->getMessage());
			$response->redirect(self::PAGE_VIEW_PAGE . '?page_id=' . $pageId);
			return;
		}

		$em->persist($tab);
		$tab->setPublished($publish);
		$em->flush();

		$response->redirect(self::PAGE_VIEW_PAGE . '?page_id=' . $pageId);
	}

	public function viewTabAction()
	{
		$response = $this->getResponse();
		$request = $this->getRequest();
		/* @var $response Response\TwigResponse */
		/* @var $request Request\HttpRequest */
		$em = ObjectRepository::getEntityManager($this);
		$tabId = $request->getParameter('tab_id');
		$repo = $em->getRepository('Supra\User\Entity\UserFacebookPageTab');
		$tab = $repo->findOneById($tabId);

		// TODO: Redirect to default supra tab?
		if ( ! $tab instanceof UserFacebookPageTab) {
			$response->outputTemplate('no-tab.html.twig');
			return;
		}

		$response->assign('title', $tab->getTabTitle());
		$response->assign('content', $tab->getHtml());

		$response->outputTemplate('view-tab.html.twig');
	}

	// TODO: Need https to finnish
	public function parseRequestAction()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();
		/* @var $request Request\HttpRequest */
		/* @var $response Response\HttpResponse */
		if ( ! $request->isPost()) {
			throw new \Exception();
		}

		$data = array();

		$post = $request->getPost();
		/* @var $post Supra\Request\RequestData */
		if ($post->has('signed_request')) {
			$data = $this->parseSignedRequest($post->get('signed_request'));
		}

		if ( ! isset($data['page'])) {
			throw new \Exception('Page data is empty');
		}

		$pageId = $data['page']['id'];

		$em = ObjectRepository::getEntityManager($this);
		$query = $em->createQuery('SELECT t.id FROM Supra\User\Entity\UserFacebookPageTab t JOIN t.page p WHERE p.pageId = :page_id AND t.published = 1');
		$query->setParameter('page_id', $pageId);

		$response = $this->getResponse();
		try {
			$tabId = $query->getSingleScalarResult();
		} catch (NoResultException $exc) {
			$response->outputTemplate('no-tab.html.twig');
			return;
		}

		$response->redirect(self::PAGE_VIEW_TAB . '?tab_id=' . $tabId);
	}

	private function parseSignedRequest($signedRequest)
	{
		list($encodedSignature, $payload) = explode('.', $signedRequest, 2);

		// decode the data
		$sig = $this->base64UrlDecode($encodedSignature);
		$data = json_decode($this->base64UrlDecode($payload), true);

		if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
			throw new \Exception('Unknown algorithm. Expected HMAC-SHA256');
		}

		// TODO: FIXME Always failed
//		$expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
//		if ($sig !== $expected_sig) {
//			error_log('Bad Signed JSON signature!');
//			return null;
//		}

		return $data;
	}

	private function base64UrlDecode($input)
	{
		return base64_decode(strtr($input, '-_', '+/'));
	}

}