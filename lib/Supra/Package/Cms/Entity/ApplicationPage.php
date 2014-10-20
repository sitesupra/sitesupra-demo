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
	 * @param string $applicationId
	 */
	public function __construct($applicationId)
	{
		parent::__construct();
		$this->applicationId = $applicationId;
	}

	/**
	 * @return string
	 */
	public function getApplicationId()
	{
		return $this->applicationId;
	}	
}
