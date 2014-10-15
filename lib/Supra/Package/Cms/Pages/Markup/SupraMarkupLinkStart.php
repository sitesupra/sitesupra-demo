<?php

namespace Supra\Package\Cms\Pages\Markup;

class SupraMarkupLinkStart extends Abstraction\SupraMarkupBlockStart
{

	public function parseSource()
	{
		$this->id = $this->extractValueFromSource('id');
	}

	/**
	 * @param string $id 
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

}
