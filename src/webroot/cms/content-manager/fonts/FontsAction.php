<?php

namespace Supra\Cms\ContentManager\Fonts;

use Supra\RemoteHttp\Request\RemoteHttpRequest;
use Supra\RemoteHttp\RemoteHttpRequestService;
use Supra\ObjectRepository\ObjectRepository;

/**
 */
class FontsAction extends \Supra\Cms\ContentManager\PageManagerAction
{
	/**
	 */
	public function listAction()
	{
		$fontList = $this->getGoogleCssFontList();
				
		$this->getResponse()
				->setResponseData($fontList);
	}
}
