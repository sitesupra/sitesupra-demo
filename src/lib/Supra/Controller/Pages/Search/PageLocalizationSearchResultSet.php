<?php

namespace Supra\Controller\Pages\Search;

use Supra\Search\Result\Abstraction\SearchResultSetAbstraction;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\GroupPage;

class PageLocalizationSearchResultSet extends SearchResultSetAbstraction
{

	public function gatherBreadcrumbs(EntityManager $em)
	{
		$pr = $em->getRepository(PageLocalization::CN());

		foreach ($this->items as $item) {
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

