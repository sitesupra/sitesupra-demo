<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Page controller page object
 * @Entity(repositoryClass="Supra\Controller\Pages\Repository\PageRepository")
 * @method PageLocalization getLocalization(string $locale)
 */
class Page extends Abstraction\AbstractPage
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::PAGE_DISCR;

	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getNestedSetRepositoryClassName()
	{
		return Abstraction\AbstractPage::CN();
	}
	
}	
