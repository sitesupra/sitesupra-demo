<?php

namespace Supra\Package\Cms\Entity;

/**
 * Page containing an application
 * @Entity
 */
class ApplicationPage extends Page
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::APPLICATION_DISCR;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $applicationId;
	
	/**
	 * @return string
	 */
	public function getApplicationId()
	{
		return $this->applicationId;
	}

	/**
	 * @return string
	 */
	public function setApplicationId($applicationId)
	{
		$this->applicationId = $applicationId;
	}
	
}
