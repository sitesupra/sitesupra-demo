<?php

namespace Supra\Controller\Pages\Set;

use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Entity\Template;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\Controller\Pages\Entity\ThemeLayout;

/**
 * Set containing 
 */
class PageSet extends AbstractSet
{
	/**
	 * The root template is the first element in the set
	 * @return Template
	 */
	public function getRootTemplate()
	{
		return $this->getFirstElement();
	}
	
	/**
	 * @return AbstractPage
	 */
	public function getFinalPage()
	{
		return $this->getLastElement();
	}
	
	/**
	 * Get layout for the page hierarchy (last one in the stack)
	 * @param string $media
	 * @return ThemeLayout
	 */
	public function getLayout($media)
	{
		$layout = null;
		$trace = array();
		
		foreach ($this as $abstractPage) {
			if ($abstractPage instanceof Template) {
				if ($abstractPage->hasLayout($media)) {
					$layout = $abstractPage->getLayout($media);
				}
			}
			
			$trace[] = $abstractPage->__toString();
		}
		
		return $layout;
	}
}
