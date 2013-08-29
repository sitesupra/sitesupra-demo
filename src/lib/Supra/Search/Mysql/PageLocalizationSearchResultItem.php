<?php

namespace Supra\Search\Mysql;

use Supra\Search\Result\Abstraction\SearchResultItemAbstraction;

class PageLocalizationSearchResultItem extends SearchResultItemAbstraction
{
    protected $localeId;
    protected $pageWebPath;
    protected $title;
    protected $text;
    protected $ancestorIds;
    protected $highlight;
    protected $breadcrumbs;
    protected $pageLocalizationId;
    protected $keywordsFromText;

    public function getPageLocalizationId()
    {
        return $this->pageLocalizationId;
    }

    public function setPageLocalizationId($pageLocalizationId)
    {
        $this->pageLocalizationId = $pageLocalizationId;
    }

    public function getLocaleId()
    {
        return $this->localeId;
    }

    public function setLocaleId($localeId)
    {
        $this->localeId = $localeId;
    }

    public function getPageWebPath()
    {
        return $this->pageWebPath;
    }

    public function setPageWebPath($pageWebPath)
    {
        $this->pageWebPath = $pageWebPath;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getAncestorIds()
    {
        if (empty($this->ancestorIds)) {
            $this->setAncestorIds(array());
        }

        return $this->ancestorIds;
    }

    public function setAncestorIds($ancestorIds)
    {
        $this->ancestorIds = $ancestorIds;
    }

    public function getHighlight()
    {
        return $this->highlight;
    }

    public function setHighlight($highlight)
    {
        $this->highlight = $highlight;
    }

    public function getBreadcrumbs()
    {
        return $this->breadcrumbs;
    }

    public function setBreadcrumbs($breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    public function getKeywordsFromText()
    {
        return $this->keywordsFromText;
    }

    public function setKeywordsFromText($keywordsFromText)
    {
        $this->keywordsFromText = $keywordsFromText;
    }

    /**
     * @param Solarium_Document_ReadOnly $document
     * @param Solarium_Result_Select_Highlighting $highlighting 
     */
    function __construct($row)
    {
        $this->setUniqueId($row['uniqueId']);
        $this->setClass($row['entityClass']);

        $this->setLocaleId($row['localeId']);
        $this->setPageWebPath($row['pageWebPath']);
        $this->setTitle($row['pageTitle']);
        $this->setText($row['pageContent']);
		$this->setHighlight($row['pageContent']);
        $this->setAncestorIds(NULL);//$document->ancestorIds);
        $this->setPageLocalizationId($row['localizationId']);
        //$this->setIndexedDocument($document);
    }

}
