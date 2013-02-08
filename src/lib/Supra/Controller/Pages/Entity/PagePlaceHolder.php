<?php

namespace Supra\Controller\Pages\Entity;

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
