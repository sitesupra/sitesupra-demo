<?php

namespace Supra\Search\Mysql;

use Supra\Search\Result\Abstraction\SearchResultItemAbstraction;
use Supra\ObjectRepository\ObjectRepository;

class PageLocalizationSearchResultItem extends SearchResultItemAbstraction {

	/**
	 * @param $row array Mysql result
	 */
	function __construct($row = array(), $searchQuery = '') {
		if (!empty($row)) {
			// Highlight the text
			$row['pageContent'] = $this->highlightText($row['pageContent'], $searchQuery);

			$this->setUniqueId($row['uniqueId']);
			$this->setClass($row['entityClass']);

			$this->setLocaleId($row['localeId']);
			$this->setPageWebPath($row['pageWebPath']);
			$this->setTitle($row['pageTitle']);
			$this->setText($row['pageContent']);
			$this->setHighlight($row['pageContent']);

			$ancestorIds = NULL;
			try {
				$ancestorIds = unserialize($row['ancestorId']);
			} catch (Exception\RuntimeException $e) {
				\Log::error($e->getMessage());
			}
			if (!is_array($ancestorIds)) {
				$ancestorIds = NULL;
			}

			$this->setAncestorIds($ancestorIds);
			$this->setPageLocalizationId($row['localizationId']);

			$this->setIndexedDocument($this);
		}
	}

	/**
	 * Highlight search query, cut the line with it
	 * 
	 * @param string $text
	 * @param string $query
	 * @return string
	 */
	public function highlightText($text, $query) {
		$searchWords = explode(' ', $query);
		$searchWords = array_map('preg_quote', $searchWords);

		$posQuery = intval(mb_stripos($text, $query));

		$lenQuery = mb_strlen($query);
		$lenText = mb_strlen($text);

		$posFrom = ($posQuery - SEARCH_SERVICE_FUULTEXT_HIGHLIGHT_LENGTH);
		if ($posFrom < 0) {
			$posFrom = 0;
		}

		$posTo = ($posQuery + $lenQuery + SEARCH_SERVICE_FUULTEXT_HIGHLIGHT_LENGTH);

		// Cut text
		$text = mb_substr($text, $posFrom, $posTo);

		// Make highlight
		$text = preg_replace("#(" . implode('|', $searchWords) . ")#ius", "<b>$1</b>", $text);

		return $text;
	}

}
