<?php

namespace Project\SocialMedia;

use Supra\Request;
use Supra\Response;
use Supra\Controller\SimpleController;
use SocialMedia\Facebook\Exception\FacebookApiException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;
use SocialMedia\Facebook\Adapter;

class SocialMediaController extends SimpleController
{
	const PAGE_SOCIAL = '/social';
	const PAGE_SELECT_PAGE = '/social/select-page';
	const PAGE_EDIT_PAGE = '/social/edit-page';

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

		if ($request instanceof Request\CliRequest) {
			return new Response\CliResponse();
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

		if ( ! is_null($user->getFacebookId())) {
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		}

		/* @var $response Response\HttpResponse */
		$facebook = new Adapter($user);
		try {
			$facebook->checkAppPermissions();
			$response->redirect(self::PAGE_SELECT_PAGE);
			return;
		} catch (FacebookApiException $e) {
			
		}

		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('appId', $facebook->getAppId());

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
		$em->persist($user);
		$user->setFacebookAccessToken($accessToken);
		$user->setFacebookId($facebook->getUserId());
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
		$facebook = new Adapter($user);
		try {
			$facebook->checkAppPermissions();
		} catch (FacebookApiException $e) {
			$response->redirect(self::PAGE_SOCIAL);
			return;
		}

		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('pages', $facebook->getUserPages());

		$response->outputTemplate('select-page.html.twig');
	}

	public function editPageAction()
	{
		$response = $this->getResponse();

		$user = $this->getCurrentCmsUser();

		if (is_null($user)) {
			$response->redirect('/cms');
			return;
		}

		/* @var $response Response\HttpResponse */
		$facebook = new Adapter($user);

		try {
			$facebook->checkAppPermissions();
		} catch (FacebookApiException $e) {
			$response->redirect(self::PAGE_SOCIAL);
			return;
		}

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

		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('pages', $facebook->getUserPages());

		$response->outputTemplate('edit-page.html.twig');
	}

	private function setErrorOutput($message)
	{
		$response = $this->getResponse();
		$this->output['errorMessage'] = $message;
		$response->output(json_encode($this->output));
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

}