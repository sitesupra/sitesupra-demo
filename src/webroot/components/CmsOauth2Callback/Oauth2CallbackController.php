<?php

namespace Project\CmsOauth2Callback;

use Supra\Statistics\GoogleAnalytics\Authentication\OAuth2Authentication;


class Oauth2CallbackController extends \Supra\Controller\SimpleController
{
	/**
	 * 
	 */
	public function execute()
	{
		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */
		$code = $request->getParameter('code');
		
		if ( ! empty($code)) {
		
			$userProvider = \Supra\ObjectRepository\ObjectRepository::getUserProvider($this);
			$currentUser = $userProvider->getSignedInUser(false);

			$authSuccess = false;
			if ($currentUser instanceof \Supra\User\Entity\User) {
	
				$provider = new \Supra\Statistics\GoogleAnalytics\GoogleAnalyticsDataProvider();
				$oAuthAdapter = $provider->getAuthAdapter();

				if ( ! $oAuthAdapter instanceof OAuth2Authentication) {
					\Log::error('OAuth2Authentication object is required to process OAuth2 callbacks');
					throw new \Supra\Controller\Exception\StopRequestException;
				}

				$authSuccess = $oAuthAdapter->authenticate($code);
			}
		}
		
		$this->getResponse()
				->assign('authSuccess', $authSuccess)
				->outputTemplate('index.html.twig');
	}
	
	/**
	 * 
	 */
	public function createResponse(\Supra\Request\RequestInterface $request)
	{
		return new \Supra\Response\TwigResponse($this);
	}
}
