<?php

namespace Supra\Controller\Pages\Set;

use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Entity\Template;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;

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
}
