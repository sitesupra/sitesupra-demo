<?php

namespace Project\SocialMedia;

use Supra\Request;
use Supra\Response;
use Supra\Controller\SimpleController;
use SocialMedia\Facebook\Exception\FacebookApiException;

class SocialMediaController extends SimpleController
{

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

		$facebook = new \SocialMedia\Facebook\Adapter();
		
		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('appId', $facebook->getAppId());
		
		$response->outputTemplate('index.html.twig');
	}
}