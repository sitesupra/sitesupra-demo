<?php

namespace Supra\Search;
use Supra\ObjectRepository\ObjectRepository;

abstract class SearchServiceAdapter {
	
	/**
	 * @var WriterAbstraction
	 */
	protected $log;
	
	/**
	 * System ID to be used for this project.
	 * @var string
	 */
	protected $systemId;
	
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * @return string
	 */
	public function getSystemId()
	{
		if (is_null($this->systemId)) {
			$info = ObjectRepository::getSystemInfo($this);
			$this->systemId = $info->name;
		}
		
		return $this->systemId;
	}
	
	abstract public function processRequest(\Supra\Search\Request\SearchRequestInterface $request);
	
	abstract public function configure();
	
	abstract public function doSearch($text, $maxRows, $startRow);
}