<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Set\PageSet;

/**
 * Page controller page object
 * @Entity(repositoryClass="Supra\Controller\Pages\Repository\PageRepository")
 * @Table(name="su_page")
 * @method PageData getData(string $locale)
 */
class Page extends Abstraction\Page
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = 'page';
	
	/**
	 * Page place holders
	 * @OneToMany(targetEntity="PagePlaceHolder", mappedBy="master", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $placeHolders;
	
}
