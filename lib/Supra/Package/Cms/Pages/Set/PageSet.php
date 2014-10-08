<?php

namespace Supra\Package\Cms\Pages\Set;

use Supra\Package\Cms\Entity\Abstraction\AbstractPage;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\Theme\ThemeLayout;

class PageSet extends AbstractSet
{
	/**
	 * Gets root template (first element in the set).
	 * 
	 * @return Template
	 */
	public function getRootTemplate()
	{
		return $this->getFirstElement();
	}
	
	/**
	 * Gets final page (last element in the set).
	 *
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
		throw new \Exception('Do not use me.');

		$layout = null;
		$trace = array();
		
		foreach ($this as $abstractPage) {
			if ($abstractPage instanceof Template) {
				if ($abstractPage->hasLayout($media)) {
					$layout = $abstractPage->getLayout($media);
				}
			}

			$trace[] = (string) $abstractPage;
		}
		
		return $layout;
	}

	/**
	 * Get theme layout for the page hierarchy (last one in the stack).
	 * 
	 * @param string $media
	 * @return string
	 */
	public function getLayoutName($media)
	{
		$layout = null;
		$trace = array();

		foreach ($this as $abstractPage) {
			if ($abstractPage instanceof Template) {
				if ($abstractPage->hasLayout($media)) {
					$layout = $abstractPage->getLayoutName($media);
				}
			}

			$trace[] = (string) $abstractPage;
		}

		return $layout;
	}
}
