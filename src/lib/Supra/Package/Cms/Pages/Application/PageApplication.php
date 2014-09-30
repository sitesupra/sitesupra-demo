<?php

namespace Supra\Package\Cms\Pages\Application;

use Doctrine\ORM\EntityManager;
use Supra\Package\Cms\Entity\ApplicationLocalization;

abstract class PageApplication implements PageApplicationInterface
{
	protected $id;

	protected $title;

	protected $icon;

	protected $allowChildren = true;

	protected $newChildInsertPolicy = self::INSERT_POLICY_PREPEND;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $entityManager;

	/**
	 * @var ApplicationLocalization
	 */
	protected $applicationLocalization;

	
	public function getId()
	{
		return $this->id;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getIcon()
	{
		return $this->icon;
	}

	public function getAllowChildren()
	{
		return $this->allowChildren === true;
	}

	public function getNewChildInsertPolicy()
	{
		return $this->newChildInsertPolicy;
	}

	public function setEntityManager(EntityManager $em)
	{
		$this->entityManager = $em;
	}

	public function setApplicationLocalization(ApplicationLocalization $applicationLocalization)
	{
		$this->applicationLocalization = $applicationLocalization;
	}
}
