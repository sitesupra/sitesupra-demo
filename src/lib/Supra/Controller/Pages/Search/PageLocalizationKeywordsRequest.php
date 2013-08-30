<?php

namespace Supra\Controller\Pages\Search;

use \Solarium_Query_Select;
use \Solarium_Result_Select;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\Request\Abstraction\SearchRequestAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use \Supra\Search\Result\DefaultSearchResultSet;
use \Supra\Search\Solarium;
use Supra\Search\Solarium\PageLocalizationSearchResultItem;

class PageLocalizationKeywordsRequest extends SearchRequestAbstraction
{

    protected $schemaName = null;
    protected $localeId = null;
    protected $pageLocalizationId = null;
    protected $revisionId = null;

    public function setSchemaName($schemaName)
    {
        $this->schemaName = $schemaName;
    }

    public function setLocaleId($localeId)
    {
        $this->localeId = $localeId;
    }

    public function setPageLocalizationId($pageLocalizationId)
    {
        $this->pageLocalizationId = $pageLocalizationId;
    }

    public function setRevisionId($revisionId)
    {
        $this->revisionId = $revisionId;
    }

    public function applyParametersToSelectQuery(Solarium_Query_Select $selectQuery)
    {

        $query = array();

        if ( ! empty($this->schemaName)) {
            $query[] = 'schemaName:"' . $this->schemaName . '"';
        }

        if ( ! empty($this->pageLocalizationId)) {
            $query[] = 'pageLocalizationId:' . $this->pageLocalizationId;
        }

        if ( ! empty($this->revisionId)) {
            $query[] = 'revisionId:' . $this->revisionId;
        }

        $selectQuery->setQuery(join(' AND ', $query));

        $tvComponent = Solarium\TermVectorComponent::CN();
        $tvComponentRequestBuilder = Solarium\TermVectorComponentRequestBuilder::CN();
        $tvComponentResponseParser = Solarium\TermVectorComponentResponseParser::CN();
        $selectQuery->registerComponentType(Solarium\TermVectorComponent::TYPE, $tvComponent, $tvComponentRequestBuilder, $tvComponentResponseParser);

        $selectQuery->getComponent(Solarium\TermVectorComponent::TYPE, true);

        $selectQuery->setFields('pageLocalizationId,uniqueId,class');

        parent::applyParametersToSelectQuery($selectQuery);
    }

    public function processResults(Solarium_Result_Select $selectResults)
    {
        //$facetSet = $selectResults->getFacetSet();
        //$facet = $facetSet->getFacet('text_lv');

        $resultSet = new DefaultSearchResultSet();

        $termVectorResults = $selectResults->getComponent(Solarium\TermVectorComponent::TYPE);

        $lm = ObjectRepository::getLocaleManager($this);
        $locale = $lm->getLocale($this->localeId);
        $localeLanguage = $locale->getProperty('language');

        $termVectorFieldname = 'text_' . $localeLanguage;

        foreach ($selectResults as $resultDocument) {

            $item = new PageLocalizationSearchResultItem($resultDocument);

            $termVector = $termVectorResults->getResult($resultDocument->uniqueId);

            $item->setKeywordsFromText($termVector[$termVectorFieldname]);

            $resultSet->add($item);
        }

        $resultSet->setTotalResultCount($selectResults->getNumFound());

        return $resultSet;
    }

}
