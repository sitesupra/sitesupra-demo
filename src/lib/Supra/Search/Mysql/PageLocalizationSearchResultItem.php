<?php

namespace Supra\Search\Mysql;

use Supra\Search\Result\Abstraction\SearchResultItemAbstraction;

class PageLocalizationSearchResultItem extends SearchResultItemAbstraction
{
    /**
     * @param $row array Mysql result
     */
    function __construct($row = array())
    {
		if ( ! empty( $row ) )
		{
			$this->setUniqueId($row['uniqueId']);
			$this->setClass($row['entityClass']);

			$this->setLocaleId($row['localeId']);
			$this->setPageWebPath($row['pageWebPath']);
			$this->setTitle($row['pageTitle']);
			$this->setText($row['pageContent']);
			$this->setHighlight($row['pageContent']);
			$ancestorIds = @unserialize($row['ancestorId']);
			if ( ! is_array($ancestorIds) )
			{
				$ancestorIds = NULL;
			}
			$this->setAncestorIds($ancestorIds);
			$this->setPageLocalizationId($row['localizationId']);
			/** @TODO fix */
			$this->setIndexedDocument($this);
		}
    }

}
