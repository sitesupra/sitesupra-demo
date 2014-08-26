<?php

namespace Project\FancyBlocks\SocialShare;

use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;

class SocialShareBlock extends BlockController
{
	
	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		$properties = array();

		return $properties;
	}

	protected function doExecute()
	{        
        $localization = $this->getRequest()->getPageLocalization();
        /* @var $request \Supra\Controller\Pages\Request\PageRequestView */
        $pathPart = $localization->getPathPart();
        
        $sysInfo = ObjectRepository::getSystemInfo($this);
        $hostName = $sysInfo->getHostName();
        
        $pagePath = 'http://' . $hostName . '/' . $pathPart;

		$this->getResponse()
                ->assign('pagePath', $pagePath)
                ->outputTemplate('index.html.twig');
	}
	
}
