<?php

namespace Project\SocialMedia;

use Supra\Request;
use Supra\Response;
use Supra\Controller\SimpleController;
use SocialMedia\Facebook\Exception\FacebookApiException;

class SocialMediaController extends SimpleController
{

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
		/* @var $response Response\HttpResponse */
		$facebook = new \SocialMedia\Facebook\Adapter();
		try {
			$facebook->checkAppPermissions();
			$response->redirect('/social/edit-page');
			return;
		} catch (FacebookApiException $e) {}

		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('appId', $facebook->getAppId());

		$response->outputTemplate('index.html.twig');
	}

	public function facebookAction()
	{
		$facebook = new \SocialMedia\Facebook\Adapter();
		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */
		$response = $this->getResponse();
		/* @var $response Response\HttpResponse */

		if ( ! $request->isPost()) {
			$this->setErrorOutput('Expected POST request');
		}

		$logger = \Supra\ObjectRepository\ObjectRepository::getLogger($this);
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

		$systemInfo = \Supra\ObjectRepository\ObjectRepository::getSystemInfo($this);

		$this->output['success'] = 1;
		$this->output['redirect_url'] = $systemInfo->getHostName(\Supra\Info::WITH_SCHEME) . '/social/edit-page';

		$response->output(json_encode($this->output));
	}

	public function editPageAction()
	{
		$response = $this->getResponse();
		/* @var $response Response\HttpResponse */
		$facebook = new \SocialMedia\Facebook\Adapter();
		try {
			$facebook->checkAppPermissions();
		} catch (FacebookApiException $e) {
			$response->redirect('/social');
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

}