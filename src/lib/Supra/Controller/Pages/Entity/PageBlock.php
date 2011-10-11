<?php

namespace Supra\Controller\Pages\Entity;

/**
 * @Entity
 */
class PageBlock extends Abstraction\Block
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::PAGE_DISCR;
}