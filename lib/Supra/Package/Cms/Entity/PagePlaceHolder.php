<?php

namespace Supra\Package\Cms\Entity;

/**
 * Page Place Holder
 * @Entity
 */
class PagePlaceHolder extends Abstraction\PlaceHolder
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::PAGE_DISCR;
	
	/**
	 * @var integer
	 */
	protected $type = 1;
}
