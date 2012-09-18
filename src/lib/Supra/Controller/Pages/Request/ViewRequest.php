<?php

namespace Supra\Controller\Pages\Request;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Finder;
use Supra\Controller\Pages\Exception;

class ViewRequest extends PageRequestView
{

	/**
	 * @var string
	 */
	protected $localizationId;

	/**
	 * @var string
	 */
	protected $revision;

	/**
	 * @return \Supra\Controller\Pages\Entity\Abstraction\Localization
	 * @throws \Supra\Controller\Exception\RuntimeException
	 */
	protected function detectRequestPageLocalization()
	{
		list($type, $this->localizationId, $this->revision) = $this->getPath()->getPathList();

		if ($type == 'p') {
			$localization = $this->findPageLocalization();
		} else
		if ($type == 't') {
			$localization = $this->findTemplateLocalization();
		} else {
			throw Exception\RuntimeException('Bad type.');
		}

		return $localization;
	}

	/**
	 * @return \Supra\Controller\Pages\Entity\PageLocalization
	 */
	protected function findPageLocalization()
	{
		$pageFinder = new Finder\PageFinder($this->getDoctrineEntityManager());

		$localizationFinder = new Finder\LocalizationFinder($pageFinder);

		$localizationFinder->addCustomCondition("l.id = '$this->localizationId'");
		$localizationFinder->addCustomCondition("l.revision = '$this->revision'");

		list($localization, ) = $localizationFinder->getResult();

		return $localization;
	}

	/**
	 * @return \Supra\Controller\Pages\Entity\TemplateLocalization
	 */
	protected function findTemplateLocalization()
	{
		$templateFinder = new Finder\TemplateFinder($this->getDoctrineEntityManager());

		$localizationFinder = new Finder\LocalizationFinder($templateFinder);

		$localizationFinder->isActive(null);
		$localizationFinder->isPublic(null);
		
		$localizationFinder->addCustomCondition("l.id = '$this->localizationId'");
		$localizationFinder->addCustomCondition("l.revision = '$this->revision'");

		$queryBuilder = $localizationFinder->getQueryBuilder();

		list($localization, ) = $localizationFinder->getResult();

		return $localization;
	}

}
