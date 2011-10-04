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
	
	/**
	 * Overriden to read page ID from the history URL
	 * @return Entity\Abstraction\Localization
	 */
	protected function getInitialPageLocalization()
	{
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
}
