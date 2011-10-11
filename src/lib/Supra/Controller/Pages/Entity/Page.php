<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Set\PageSet;

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
	 * Page place holders
	 * @OneToMany(targetEntity="PagePlaceHolder", mappedBy="master", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $placeHolders;
	
	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getNestedSetRepositoryClassName()
	{
		return Abstraction\AbstractPage::CN();
	}
	
}	
