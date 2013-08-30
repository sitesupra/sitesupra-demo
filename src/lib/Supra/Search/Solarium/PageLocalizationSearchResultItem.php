<?php

namespace Supra\Search\Solarium;

use Supra\Search\Result\Abstraction\SearchResultItemAbstraction;
use \Solarium_Document_ReadOnly;
use \Solarium_Result_Select_Highlighting;

class PageLocalizationSearchResultItem extends SearchResultItemAbstraction
{
    /**
     * @param Solarium_Document_ReadOnly $document
     * @param Solarium_Result_Select_Highlighting $highlighting 
     */
    function __construct(Solarium_Document_ReadOnly $document, Solarium_Result_Select_Highlighting $highlighting = null)
    {
        $this->setUniqueId($document->uniqueId);
        $this->setClass($document->class);

        $this->setLocaleId($document->localeId);
        $this->setPageWebPath($document->pageWebPath);
        $this->setTitle($document->title_general);
        $this->setText($document->text_general);
        $this->setAncestorIds($document->ancestorIds);
        $this->setPageLocalizationId($document->pageLocalizationId);

        $this->setIndexedDocument($document);

        if ( ! empty($highlighting)) {

            $highlightedResultItem = $highlighting->getResult($document->uniqueId);

            if ($highlightedResultItem) {

                foreach ($highlightedResultItem as $highlight) {

                    $highlight = implode(' (...) ', $highlight);
                    $this->setHighlight($highlight);
                }
            }
        }
    }

}
