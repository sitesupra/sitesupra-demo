<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Page containing an application
 * @Entity
 */
class ApplicationPage extends Page
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = 'application';
	
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
