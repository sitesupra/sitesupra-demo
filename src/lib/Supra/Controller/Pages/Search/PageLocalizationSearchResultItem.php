<?php

namespace Supra\Controller\Pages\Search;

use Supra\Search\Result\Abstraction\SearchResultItemAbstraction;
use \Solarium_Document_ReadOnly;
use \Solarium_Result_Select_Highlighting;

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

	/**
	 * @var Solarium_Document_ReadOnly
	 */
	protected $indexedDocument;

	public function getIndexedDocument()
	{
		return $this->indexedDocument;
	}

	public function setIndexedDocument($indexedDocument)
	{
		$this->indexedDocument = $indexedDocument;
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
