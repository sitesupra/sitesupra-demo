<?php

namespace Supra\Controller\Pages\Entity;

/**
 * @Entity
 * @method ApplicationPage getMaster()
 */
class ApplicationLocalization extends PageLocalization
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = 'application';
}
