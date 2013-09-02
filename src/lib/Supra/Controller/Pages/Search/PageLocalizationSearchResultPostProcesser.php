<?php

namespace Supra\Controller\Pages\Search;

use Supra\Search\Result\Abstraction\SearchResultSetAbstraction;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\GroupPage;
use Supra\Search\Result\SearchResultPostprocesserInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Result\SearchResultSetInterface;
use Supra\Search\Solarium\PageLocalizationSearchResultItem;

class PageLocalizationSearchResultPostProcesser implements SearchResultPostprocesserInterface
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->em)) {
			$this->em = ObjectRepository::getEntityManager($this);
		}

		return $this->em;
	}

	/**
	 * @param EntityManager $em 
	 */
	public function setEntityManager($em)
	{
		$this->em = $em;
	}

	/**
	 * @return array
	 */
	public function getClasses()
	{
		return array(PageLocalization::CN());
	}

	public function postprocessResultSet(SearchResultSetInterface $resultSet)
	{
		$em = $this->getEntityManager();
		$pr = $em->getRepository(PageLocalization::CN());
		
		$items = $resultSet->getItems();
		foreach ($items as $item) {

			/* @var $item PageLocalizationSearchResultItem */

			$ancestorIds = array_reverse($item->getAncestorIds());

			$breadcrumbs = array();

			foreach ($ancestorIds as $ancestorId) {

				$p = $pr->find($ancestorId);

				if ($p instanceof Page) {

					$localeId = $item->getLocaleId();
					$pl = $p->getLocalization($localeId);
					$breadcrumbs[] = $pl->getTitle();
				} else if ($p instanceof PageLocalization) {

					$breadcrumbs[] = $p->getTitle();
				} elseif ($p instanceof GroupPage) {

					$breadcrumbs[] = $p->getTitle();
				}

				$item->setBreadcrumbs($breadcrumbs);
			}
		}
	}

}

