<?php

namespace Supra\Search\Mysql;

use Supra\Search\Result\Abstraction\SearchResultItemAbstraction;

class PageLocalizationSearchResultItem extends SearchResultItemAbstraction
{

	const HIGHLIGHT_LENGHT = 150;


	public function __construct($row = array(), $searchQuery = '')
	{
		if ( ! empty($row)) {

			// Highlight the text
			$higlightedText = $this->highlightText($row['content'], $searchQuery);

			// Highlight the title
			$higlightedTitle = $this->highlightText($row['title'], $searchQuery);

			$this->setUniqueId($row['uniqueId']);
			$this->setClass($row['entityClass']);

			$this->setLocaleId($row['locale']);
			$this->setPageWebPath($row['path']);
			$this->setTitle($higlightedTitle);
			$this->setText($row['content']);
			$this->setHighlight($higlightedText);

			$ancestorIds = NULL;
			try {
				$ancestorIds = unserialize($row['ancestorIds']);
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
	public function highlightText($text, $query)
	{
		$searchWords = explode(' ', $query);

		if (preg_match('#(' . implode('|', $searchWords) . ')#ius', $text)) {

			$textParts = array();

			foreach ($searchWords as $index => $searchWord) {

				$wordLen = mb_strlen($searchWord);

				if ($wordLen < MysqlSearcher::MIN_WORD_LENGTH) {
					unset($searchWords[$index]);
					continue;
				}

				$searchText = $text;

				$repeats = 0;

				while ($pos = mb_stripos($searchText, $searchWord)) {

					$before = $pos > self::HIGHLIGHT_LENGHT ? ($pos - self::HIGHLIGHT_LENGHT) : 0;

					$after = $wordLen + self::HIGHLIGHT_LENGHT + ($pos - $before);

					if (false !== ($breakpoint = mb_strpos($searchText, ' ', $before + $after))) {
						$after = $breakpoint - $before;
					}

					$textParts[] = mb_substr($searchText, $before, $after);
					$searchText = mb_substr($searchText, $before + $after);

					$repeats++;

					if ($repeats > 4) {
						break;
					}
				}
			}

			$text = implode(' (...) ', $textParts);
		} else {
			$text = mb_substr($text, 0, self::HIGHLIGHT_LENGHT);
		}

		// Make highlight
		$text = preg_replace("#(" . implode('|', $searchWords) . ")#ius", "<strong>$1</strong>", $text);

		return trim($text);
	}

}
