<?php

namespace Supra\Package\Cms\Pages\Request;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Cache\CacheGroupManager;
use Doctrine\ORM\Query;

/**
 * Page controller request object on view method
 */
class PageRequestView extends PageRequest
{
	/**
	 * {@inheritdoc}
	 * @param Query $query
	 */
	protected function prepareQueryResultCache(Query $query)
	{
		// @FIXME
		return null;

 		$cacheGroupManager = new CacheGroupManager();
		$cacheGroupManager->configureQueryResultCache($query, PageController::CACHE_GROUP_NAME);
	}
	
	/**
	 * Overriden with page detection from URL
	 * @return Entity\Abstraction\Localization
	 */
	public function getPageLocalization()
	{
		$data = parent::getPageLocalization();
		
		if (empty($data)) {
			$data = $this->detectRequestPageLocalization();
			
			$this->setPageLocalization($data);
		}
	
		return $data;
	}
	
	/**
	 * @return PageLocalization
	 * @throws ResourceNotFoundException if page not found or is inactive
	 */
	protected function detectRequestPageLocalization()
	{
		$pathString = $this->attributes->get('path');

		$entityManager = $this->getEntityManager();

		$queryString = sprintf('SELECT l FROM %s l JOIN l.path p WHERE p.path = :path
			AND p.active = true AND p.locale = :locale',
				PageLocalization::CN()
		);
		
		$query = $entityManager->createQuery($queryString)
				->setParameters(array(
					'locale' => $this->getLocale(),
					'path' => $pathString
				));

		$this->prepareQueryResultCache($query);
		
		$pageLocalization = $query->getOneOrNullResult();
		/* @var $pageData PageLocalization */

		if ($pageLocalization === null) {
			throw new ResourceNotFoundException(sprintf('
					No page found by path [%s] in pages controller.',
					$pathString
			));
		}
		
		if (! $pageLocalization->isActive()) {
			throw new ResourceNotFoundException(sprintf(
					'Page found by path [%s] in pages controller is inactive.'
			));
		}

		$localeManager = $this->container->getLocaleManager();

		if (! $localeManager->isActive($pageLocalization->getLocaleId())) {
			throw new ResourceNotFoundException(sprintf(
					'Page found by path [%s] in pages controller belongs to inactive locale [%s].',
					$pathString,
					$this->getLocale()
			));
		}

		return $pageLocalization;
	}
}
