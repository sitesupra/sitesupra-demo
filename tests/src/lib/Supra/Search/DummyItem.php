<?php

namespace Supra\Tests\Search;

class DummyItem
{

	public $id;
	public $revision;
	public $text;

	function __construct($id, $revision, $text)
	{
		$this->id = $id;
		$this->revision = $revision;
		$this->text = $text;
	}
	
}
