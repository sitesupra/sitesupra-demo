<?php

namespace Supra\Controller\Pages\Entity;

/**
 * @Entity
 * @method ApplicationPage getMaster()
 */
class ApplicationLocalization extends PageLocalization
{
	/**
	 * For Twig templates, I hope temporary solution
	 * @var boolean
	 */
	public $isApplication = true;
	
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::APPLICATION_DISCR;
}
