<?php

namespace Supra\Search;

use \Solarium_Document_ReadWrite;

class IndexedDocument extends Solarium_Document_ReadWrite
{
	function __construct()
	{
		//$this->__set('uniqueId', sha1(uniqid()));
	}

	/**
	 * Returns date/time as string formatted for sending to Solr.
	 * @param integer$timestamp
	 * @return string
	 */
	public function formatDate($timestamp)
	{
		return gmDate("Y-m-d\TH:i:s\Z", $timestamp);
	}

	/**
	 * Returns a text stripped from HTML and other insanities.
	 * @param string $text
	 * @return string 
	 */
	public function formatText($text)
	{
		//return str_replace("\xA0", ' ', html_entity_decode(strip_tags($text)));
		return strip_tags($text);
	}

	/**
	 * Validates document to have uniqueId. systemId and class set. Override on extend as needed.
	 */
	public function validate()
	{
		$class = $this->__get('class');
		if (empty($class)) {
			throw new Exception\IndexedDocumentException('Document class not set');
		}

		$uniqueId = $this->__get('uniqueId');
		if (empty($uniqueId)) {
			throw new Exception\IndexedDocumentException('Unique id for document not set');
		}

		$systemId = $this->__get('systemId');
		if (empty($systemId)) {
			throw new Exception\IndexedDocumentException('System id for document not set');
		}
	}

}
