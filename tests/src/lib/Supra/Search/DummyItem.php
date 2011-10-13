<?php

namespace Supra\Tests\Search;

class DummyItem
{

	public $id;
	public $revision;

	function __construct($id, $revision)
	{
		$this->id = $id;
		$this->revision = $revision;
	}

}
