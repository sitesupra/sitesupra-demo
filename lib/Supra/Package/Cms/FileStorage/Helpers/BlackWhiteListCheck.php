<?php

namespace Supra\Package\Cms\FileStorage\Helpers;

class BlackWhiteListCheck
{
	
	const MODE_WHITELIST = 1;
	
	const MODE_BLACKLIST = 2;
	
	/**
	 * Current mode WL/BL
	 * @var type $mode
	 */
	private $mode = self::MODE_WHITELIST;
	
	/**
	 * List of allowed/denied properties
	 * @var type array
	 */
	private $list = array();
	
	/**
	 * Sets current mode to WL/BL
	 * @param type $mode 
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
	}
	
	/**
	 * Adds item to list of allowed/denied properties
	 * @param type $item 
	 */
	public function addItem($item)
	{
		$this->list[] = strtolower($item);
	}
	
	/**
	 * Adds items to list of allowed/denied properties
	 * @param array $item 
	 */
	public function addItems(array $items)
	{
		foreach ($items as $item) {
			$this->list[] = strtolower($item);
		}
	}
	
	/**
	 * Checks if item is in list
	 * @param type $item
	 * @return boolean $allow
	 */
	protected function checkList($item)
	{
		$inList = in_array($item, $this->list);
		
		$allow = null;
		
		if ($this->mode == self::MODE_BLACKLIST) {
			$allow = ! $inList;
		} else {
			$allow = $inList;
		}
		
		return $allow;
	}
}