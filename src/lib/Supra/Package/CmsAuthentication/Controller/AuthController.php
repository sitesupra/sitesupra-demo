<?php

namespace Supra\Package\CmsAuthentication\Controller;

use Supra\Core\Controller\Controller;

class AuthController extends Controller
{
	/**
	 * @var string
	 */
	protected $application = 'cms_authentication';

	public function loginAction()
	{
		return $this->renderResponse('auth/login.html.twig', array());
	}
}