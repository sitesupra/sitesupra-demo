<?php

namespace Supra\Cms\ContentManager\H;

use Supra\Cms\ContentManager\Root\RootAction;
use Supra\Uri\Path;
use Supra\Controller\Pages\Entity;

/**
 * History action, executes root action
 */
class HAction extends RootAction
{
	protected $notFoundAction = 'index';
	
	protected $skipInitialPageLoading = false;
	
	/**
	 * Overriden to read page ID from the history URL
	 * @return Entity\Abstraction\Localization
	 */
	protected function getInitialPageLocalization()
	{
		if ($this->skipInitialPageLoading) {
			return;
		}
		
		$request = $this->getRequest();
		$path = $request->getPath();
		
		$startPath = new Path('page');
		
		if ($path->startsWith($startPath)) {
			$pathList = $path->getPathList();
			$pageLocalizationId = $pathList[1];
			
			$this->setInitialPageId($pageLocalizationId);
		}
		
		return parent::getInitialPageLocalization();
	}
	
	/**
	 * When sitemap is opened, we don't need any initial page
	 */
	public function sitemapAction()
	{
		$this->skipInitialPageLoading = true;
		
		$this->indexAction();
	}
}
