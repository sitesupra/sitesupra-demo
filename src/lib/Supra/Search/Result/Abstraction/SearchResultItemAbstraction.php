<?php

namespace Supra\Search\Result\Abstraction;

use Supra\Search\Result\SearchResultItemInterface;
use Supra\Search\Result\Exception;

abstract class SearchResultItemAbstraction implements SearchResultItemInterface
{
	/**
	 * @var string
	 */
	protected $uniqueId;

	/**
	 *
	 * @var string
	 */
	protected $class;

    protected $localeId;
    protected $pageWebPath;
    protected $title;
    protected $text;
    protected $ancestorIds;
    protected $highlight;
    protected $breadcrumbs;
    protected $pageLocalizationId;
    protected $keywordsFromText;
	
    public function getHighlight()
    {
        return $this->highlight;
    }

    public function setHighlight($highlight)
    {
        $this->highlight = $highlight;
    }


	/**
	 * @return string
	 */
	public function getUniqueId()
	{
		if (empty($this->uniqueId)) {
			throw new Exception\RuntimeException('Item id is not set.');
		}

		return $this->uniqueId;
	}

	/**
	 * @param string $uniqueId 
	 */
	public function setUniqueId($uniqueId)
	{
		$this->uniqueId = $uniqueId;
	}

	/**
	 * @return string
	 */
	public function getClass()
	{
		if (empty($this->class)) {
			throw new Exception\RuntimeException('Item class is not set.');
		}

		return $this->class;
	}

	/**
	 * @param string $class 
	 */
	public function setClass($class)
	{
		$this->class = $class;
	}

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
	
    protected $indexedDocument;

    public function getIndexedDocument()
    {
        return $this->indexedDocument;
    }

    public function setIndexedDocument($indexedDocument)
    {
        $this->indexedDocument = $indexedDocument;
    }
}
