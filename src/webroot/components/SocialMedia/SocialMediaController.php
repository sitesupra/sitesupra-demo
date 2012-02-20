<?php

namespace Project\SocialMedia;

use Supra\Request;
use Supra\Response;
use Supra\Controller\SimpleController;
use Supra\Social\Facebook\Exception\FacebookApiException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;
use Supra\Social\Facebook\Adapter;
use Supra\User\Entity\UserFacebookData;
use Supra\User\Entity\UserFacebookPage;
use Supra\User\Entity\UserFacebookPageTab;
use Doctrine\ORM\NoResultException;
use Supra\Controller\Pages\Entity\Page;

/**
 * @TODO Now only 3-4 functions in use, remove everything if Facebook will stay as Page block
 */
class SocialMediaController extends SimpleController
{
	const PAGE_SOCIAL = '/social';
	const PAGE_SELECT_PAGE = '/social/select-page';
	const PAGE_EDIT_PAGE = '/social/edit-page';
	const PAGE_VIEW_PAGE = '/social/view-page';
	const PAGE_CREATE_TAB = '/social/create-tab';
	const PAGE_VIEW_TAB = '/social/view-tab';

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		if ($request instanceof Request\HttpRequest) {
			return new Response\JsonResponse();
		}

		return new Response\EmptyResponse();
	}

	public function indexAction()
	{

		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$this->setErrorOutput('User is not logged in into cms');
			return;
		}

		$facebook = new Adapter($user);

		$data['application_id'] = $facebook->getAppId();

		$repo = ObjectRepository::getEntityManager($this)->getRepository('\Supra\User\Entity\UserFacebookData');
		$facebookData = $repo->findOneByUser($user->getId());

		$data['facebook_data'] = false;
		if ($facebookData instanceof UserFacebookData && $facebookData->isActive()) {
			$data['facebook_data'] = true;
			$this->log->debug('User facebook account is already linked with supra.');
		}

		$data = $data + $this->getAllAvailablePages();

		/* @var $response \Supra\Response\JsonResponse */
		$response->setResponseData($data);
	}

	public function facebookAction()
	{
		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$this->setErrorOutput('User is not logged in into cms');
			return;
		}

		$facebook = new Adapter($user);
		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */
		$response = $this->getResponse();
		/* @var $response Response\JsonResponse */

		if ( ! $request->isPost()) {
			$this->setErrorOutput('Expected POST request');
		}

		$status = $request->getPostValue('status');

		if ($status != 'connected') {
			$this->setErrorOutput('Authentication failed');
		}

		$authResponse = $request->getPostValue('authResponse');
		$accessToken = $authResponse['accessToken'];

		try {
			$facebook->checkAppPermissions(false);
		} catch (FacebookApiException $e) {
			$this->setErrorOutput($e->getMessage());

			return;
		}

		$em = ObjectRepository::getEntityManager($this);
		$facebookData = new UserFacebookData();

		$userDataRepo = $em->getRepository('\Supra\User\Entity\UserFacebookData');
		$userDataRecord = $userDataRepo->findOneByUser($user->getId());

		if ($userDataRecord instanceof UserFacebookData) {
			$facebookData = $userDataRecord;
		}

		$em->persist($facebookData);
		$facebookData->setUser($user);
		$facebookData->setFacebookUserId($facebook->getUserId());
		$facebookData->setFacebookAccessToken($accessToken);
		$facebookData->setActive(true);
		$em->flush();

		$data = $this->getAllAvailablePages();

		$response->setResponseData($data);
	}

	public function selectPageAction()
	{

		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			$this->log->debug('User is not logged in. Redirecting to /cms');

			return;
		}

		$savedPages = $this->getAvailablePages();

		$facebook = new Adapter($user);
		try {
			$pageCollectionFromFacebook = $facebook->getUserPages();
			$pageCollectionFromFacebook = array_diff_key($pageCollectionFromFacebook, $savedPages);
			$response->assign('fetchedPages', $pageCollectionFromFacebook);
		} catch (FacebookApiException $e) {
			// if we receive "has not authorized application" exception - then removing already stored data
			if ((strpos($e->getMessage(), 'has not authorized application') != false)
					|| $e->getCode() == FacebookApiException::CODE_PERMISSIONS_PROBLEM) {
				$this->deactivateUserDataRecord($user);
				$response->redirect(self::PAGE_SOCIAL);

				return;
			}
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
			$this->log->debug('User is not logged in. Redirecting to /cms');

			return;
		}

		$pageId = $request->getParameter('page_id');
		if (empty($pageId)) {
			$this->log->error('GET parameter "page_id" is empty');
			$response->redirect(self::PAGE_SELECT_PAGE);

			return;
		}

		$response->assign('pageId', $pageId);
	}

	private function setErrorOutput($message)
	{
		$response = $this->getResponse();
		/* @var $response Response\JsonResponse */
		$response->setErrorMessage($message);
		$this->log->debug($message);
	}

	public function addPageAction()
	{
		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$this->setErrorOutput('User is not logged in into cms');
			return;
		}

		/* @var $response Response\HttpResponse */
		$facebook = new Adapter($user);

		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */

		if ( ! $request->isPost()) {
			$this->setErrorOutput('Expected POST request');
		}

		$pageId = $request->getPostValue('page_id');
		if (empty($pageId)) {
			$this->setErrorOutput('POST parameter "page_id" is empty');
			return;
		}

		try {
			$facebook->checkUserPage($pageId);
		} catch (FacebookApiException $e) {

			// if we receive "has not authorized application" exception - then removing already stored data
			if ((strpos($e->getMessage(), 'has not authorized application') != false)
					|| $e->getCode() == FacebookApiException::CODE_PERMISSIONS_PROBLEM) {
				$this->deactivateUserDataRecord($user);
				
				return;
			}

			$this->setErrorOutput($e->getMessage());
			return;
		}

		$em = ObjectRepository::getEntityManager($this);

		try {
			$pageData = $facebook->getUserPage($pageId, false);
		} catch (FacebookApiException $e) {

			$this->setErrorOutput($e->getMessage());

			// if we receive "has not authorized application" exception - then removing already stored data
			if ((strpos($e->getMessage(), 'has not authorized application') != false)
					|| $e->getCode() == FacebookApiException::CODE_PERMISSIONS_PROBLEM) {
				$this->deactivateUserDataRecord($user);
			}

			return;
		}

		$pageRepo = $em->getRepository('\Supra\User\Entity\UserFacebookPage');
		$existingPage = $pageRepo->findOneByPageId($pageId);

		if ($existingPage instanceof UserFacebookPage) {
			// TODO: Give access to user to manage that page
			$this->log->info('Page ' . $existingPage->getPageTitle() . ' is already linked to supra');
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
			$this->setErrorOutput('Facebook saved data is not accessable');
			return;
		}

		$page->setUserData($facebookData);
		$em->flush();
		
		$availablePages = $this->getAllAvailablePages();
	
		$response->setResponseData(
			array(
				'page' => $pageData,
				'fetched_pages_count' => count($availablePages['fetched_pages']),
			)
		);
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
			$this->log->error('POST parameter "page_id" is empty');
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		$pageId = $post->get('page_id');

		$repo = $em->getRepository('Supra\User\Entity\UserFacebookPage');

		$page = $repo->findOneByPageId($pageId);

		if ( ! $page instanceof UserFacebookPage) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			$this->log->error('Invalid page id');
			return;
		}

		$tab = new UserFacebookPageTab();

		// if has tab_id will try to find it and edit, instead of creating new record
		if ($post->has('tab_id')) {
			$tabId = $post->get('tab_id');
			$repo = $em->getRepository('Supra\User\Entity\UserFacebookPageTab');
			$tab = $repo->findOneById($tabId);

			if ( ! $tab instanceof UserFacebookPageTab) {
				$this->log->error('Tab with id ' . $tabId . ' is not found');
				$response->redirect(self::PAGE_VIEW_PAGE . '?page_id=' . $pageId);
				return;
			}
		}

		if ( ! $post->has('tab-title')) {
			$this->log->error('Missing tab-title POST parameter');
			$response->redirect(self::PAGE_CREATE_TAB . '?page_id=' . $pageId);
			return;
		}

		$title = $post->get('tab-title');

		if (empty($title)) {
			$this->log->error('Tab title can not be empty');
			$response->redirect(self::PAGE_CREATE_TAB . '?page_id=' . $pageId);
			return;
		}

		if ( ! $post->has('content')) {
			$this->log->error('Missing content POST parameter');
			$response->redirect(self::PAGE_CREATE_TAB . '?page_id=' . $pageId);
			return;
		}

		$content = $post->get('content');

		if (empty($content)) {
			$this->log->error('Tab content can not be empty');
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
			$this->log->debug('User is not logged in. Redirecting to /cms');
			return;
		}

		$request = $this->getRequest();

		$pageId = $request->getParameter('page_id');
		if (empty($pageId)) {
			$this->log->error('GET parameter "page_id" is empty');
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

	/**
	 * Returns added to supra facebook pages 
	 * @return array 
	 */
	public function getAvailablePages()
	{
		//TODO Check current user access rights to particular page
		$em = ObjectRepository::getEntityManager($this);
		$query = $em->createQuery('SELECT p FROM Supra\User\Entity\UserFacebookPage p JOIN p.userData ud WHERE ud.active = :active');
		$query->setParameter('active', true);
		$databasePages = $query->getResult();

		$savedPages = array();
		foreach ($databasePages as $page) {
			/* @var $page \Supra\User\Entity\UserFacebookPage */
			$savedPages[$page->getPageId()] = $this->getPageData($page);
		}

		return $savedPages;
	}

	/**
	 * Returns already added pages to database, and pages fetched from facebook
	 * 
	 * @param Adapter $facebook
	 * @return array 
	 */
	public function getAllAvailablePages()
	{

		$pages = array();
		$facebook = new Adapter($this->getCurrentCmsUser());

		try {
			$savedPages = $this->getAvailablePages();
			$pageCollectionFromFacebook = $facebook->getUserPages();
			$pageCollectionFromFacebook = array_diff_key($pageCollectionFromFacebook, $savedPages);
			$pages['fetched_pages'] = array_values($pageCollectionFromFacebook);
			$pages['available_pages'] = array_values($savedPages);
		} catch (FacebookApiException $e) {
			// if we receive "has not authorized application" exception - then removing already stored data
			if ((strpos($e->getMessage(), 'has not authorized application') != false)
					|| $e->getCode() == FacebookApiException::CODE_PERMISSIONS_PROBLEM) {
				$this->deactivateUserDataRecord($this->getCurrentCmsUser());
				$this->setErrorOutput($e->getMessage());
			}
		}

		return $pages;
	}

	protected function getPageData(UserFacebookPage $page)
	{
		if ( ! $page instanceof UserFacebookPage) {
			$this->log->debug('Page is not instance of UserFacebookPage');
			return null;
		}

		return array(
			'id' => $page->getPageId(),
			'name' => $page->getPageTitle(),
			// title duplication for js
			'title' => $page->getPageTitle(),
			'picture' => $page->getPageIcon(),
			'link' => $page->getPageLink(),
		);
	}

	public function deleteTabAction()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			$this->log->debug('User is not logged in. Redirecting to /cms');
			return;
		}

		$tabId = $request->getParameter('tab_id');

		if (empty($tabId)) {
			$this->log->error('Tab id can not be empty');
			$response->redirect(self::PAGE_CREATE_TAB);
			return;
		}

		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository('\Supra\User\Entity\UserFacebookPageTab');
		$tab = $repo->findOneById($tabId);
		/* @var $tab UserFacebookPageTab */
		$pageId = $tab->getPage()->getPageId();

		if ($tab->isPublished()) {
			try {
				$facebook = new Adapter($user);
				$facebook->removeTabFromPage($tab);
			} catch (FacebookApiException $e) {
				// if we receive "has not authorized application" exception - then removing already stored data
				if ((strpos($e->getMessage(), 'has not authorized application') != false)
						|| $e->getCode() == FacebookApiException::CODE_PERMISSIONS_PROBLEM) {
					$this->deactivateUserDataRecord($user);
					$response->redirect(self::PAGE_SOCIAL);

					return;
				}

				$this->log->error($e->getMessage());
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
			$this->log->debug('User is not logged in. Redirecting to /cms');
			return;
		}

		$em = ObjectRepository::getEntityManager($this);
		$tabId = $request->getParameter('tab_id');
		$repo = $em->getRepository('Supra\User\Entity\UserFacebookPageTab');
		$tab = $repo->findOneById($tabId);

		if ( ! $tab instanceof UserFacebookPageTab) {
			$this->log->error('Could not find tab with id ' . $tabId);
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
		/* @var $response Response\TwigResponse */
		/* @var $request Request\HttpRequest */

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			$this->log->debug('User is not logged in. Redirecting to /cms');
			return;
		}

		$publish = $request->getParameter('publish');
		if ( ! in_array($publish, array('0', '1'))) {
			$publish = false;
			$this->log->warn('"publish" is not boolean, will set publish to false');
		}

		$publish = (bool) $publish;

		$em = ObjectRepository::getEntityManager($this);
		$tabId = $request->getParameter('tab_id');
		$repo = $em->getRepository('Supra\User\Entity\UserFacebookPageTab');
		$tab = $repo->findOneById($tabId);

		if ( ! $tab instanceof UserFacebookPageTab) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			$this->log->error('Could not find tab with id ' . $tabId);
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
		} catch (FacebookApiException $e) {
			// if we receive "has not authorized application" exception - then removing already stored data
			$this->log->error($e->getMessage());

			if ((strpos($e->getMessage(), 'has not authorized application') != false)
					|| $e->getCode() == FacebookApiException::CODE_PERMISSIONS_PROBLEM) {
				$this->deactivateUserDataRecord($user);
				$response->redirect(self::PAGE_SOCIAL);

				return;
			}

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
			$this->log->warn('Could not find tab with id ' . $tabId . '. Will show default template');
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
			$message = 'Expected POST request';
			throw new \Exception($message);
			$this->log->error($message);
		}

		$data = array();

		$post = $request->getPost();
		/* @var $post Supra\Request\RequestData */
		if ($post->has('signed_request')) {
			$data = $this->parseSignedRequest($post->get('signed_request'));
		}

		if ( ! isset($data['page'])) {
			$message = 'Page data is empty';
			throw new \Exception($message);
			$this->log->error($message);
		}

		$pageId = $data['page']['id'];

		$em = ObjectRepository::getEntityManager($this);
		$query = $em->createQuery('SELECT t.id FROM Supra\User\Entity\UserFacebookPageTab t JOIN t.page p WHERE p.pageId = :page_id AND t.published = 1');
		$query->setParameter('page_id', $pageId);

		$response = $this->getResponse();
		try {
			$tabId = $query->getSingleScalarResult();
		} catch (NoResultException $exc) {
			$this->log->error('Could not find tab for page ' . $pageId);
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

	public function postMessageAction()
	{
		$response = $this->getResponse();
		$request = $this->getRequest();

		/* @var $response Response\TwigResponse */
		/* @var $request Request\HttpRequest */
		$em = ObjectRepository::getEntityManager($this);
		$pageId = $request->getParameter('page_id');

		$repo = $em->getRepository('Supra\Controller\Pages\Entity\Page');
		$page = $repo->findOneById($pageId);

		if ( ! $page instanceof Page) {
			throw new \Exception('Wrong page_id passed');
			return;
		}

		$locale = ObjectRepository::getLocaleManager($this)->getCurrent()->getId();
		$localization = $page->getLocalization($locale);
		$pagePath = $localization->getPath();

		$fullPath = $pagePath->getFullPath();
		$systemInfo = ObjectRepository::getSystemInfo($this);
		$host = $systemInfo->getHostName(\Supra\Info::WITH_SCHEME);

		$url = $host . '/' . $locale . '/' . $fullPath;

		$title = $localization->getTitle();

		$pageId = '327221123967786';

		$fbPageRepo = $em->getRepository('Supra\User\Entity\UserFacebookPage');
		$fbPage = $fbPageRepo->findOneByPageId($pageId);
		if ( ! $fbPage instanceof UserFacebookPage) {
			throw new \Exception('Could not find page');
		}

		$userData = $fbPage->getUserData();
		if ( ! $userData instanceof UserFacebookData) {
			throw new \Exception('Could not get user data');
		}

		$user = $userData->getUser();
		if ( ! $user instanceof User) {
			throw new \Exception('Could not get user');
		}

		$facebook = new Adapter($user);
		$pageAccessToken = $facebook->getPageAccessToken($userData->getFacebookUserId(), $pageId);

		$postMessageParams = array(
			// TODO: hardcoded now
			'message' => 'Check out that page...',
			'link' => $url,
			'name' => $title,
			'access_token' => $pageAccessToken,
			'picture' => 'http://sitesupra.com/images/maintanance_logo.png',
			'caption' => 'Caption lorem ipsum dolor sit amet, consectetur adipiscing elit.',
			'description' => 'Et mollis nunc diam eget sapien. Nulla facilisi. Etiam feugiat imperdiet rhoncus. Sed suscipit bibendum enim, sed volutpat tortor malesuada non. Morbi fringilla dui non purus porttitor mattis. Suspendisse quis vulputate risus. Phasellus erat velit, sagittis sed varius volutpat, placerat nec urna. Nam eu metus vitae dolor fringilla feugiat. Nulla.',
		);

		$facebook->postMessage($postMessageParams);
	}

	public static function deactivateUserDataRecord(User $user)
	{
		$em = ObjectRepository::getEntityManager(self);
		$userDataRepo = $em->getRepository('\Supra\User\Entity\UserFacebookData');
		$userDataRecord = $userDataRepo->findOneByUser($user->getId());
		if ($userDataRecord instanceof UserFacebookData) {
			$em->persist($userDataRecord);
			$userDataRecord->setActive(false);
			$em->flush();
		}

		ObjectRepository::getLogger(self)->info('Deactivating user facebook data record');
		return true;
	}

}