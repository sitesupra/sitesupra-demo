<?php

namespace Supra\Search;

use \Solarium_Document_ReadWrite;

class IndexedDocument extends Solarium_Document_ReadWrite
{
	/**
	 * Object's local ID
	 * @var string
	 */
	private $localId;
	
	/**
	 * Must provide document's class and local ID
	 * @param string $class
	 * @param string $localId
	 */
	public function __construct($class, $localId)
	{
		$this->setField('class', $class);
		$this->localId = $localId;
	}
	
	/**
	 * @return string
	 */
	public function getLocalId()
	{
		return $this->localId;
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
	 * @param string $content
	 * @return string 
	 */
	public function formatText($content)
	{
//		$content = str_replace('„', '"', $content);
//		$content = str_replace('”', '"', $content);
//		
		// Inline tags
		$content = preg_replace('/<\/?(i|b|em|strong|span)( [^>]*)?>/i', '', $content);
		
		// Block tags
		$content = preg_replace('/<[^>]*>/', ' ', $content);
		
		return $content;
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
