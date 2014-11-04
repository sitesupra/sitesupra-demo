<?php

namespace Supra\Package\Cms\Entity;

/**
 * This class represent synthetic root folder and is used only 
 * for authorization. Do not use this for anything else!
 */
final class SlashFolder extends Folder
{
	const DUMMY_ROOT_ID = 'slash';
	const DUMMY_ROOT_NAME = '-=SLASH=-';
	
	function __construct() 
	{
		parent::__construct();
		$this->id = self::DUMMY_ROOT_ID;
		$this->fileName = self::DUMMY_ROOT_NAME;
	}
	
	public function getAuthorizationAncestors() {
		
		return array();
	}
}
