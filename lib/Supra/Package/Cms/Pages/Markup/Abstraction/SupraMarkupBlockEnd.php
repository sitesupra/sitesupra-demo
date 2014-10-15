<?php

namespace Supra\Package\Cms\Pages\Markup\Abstraction;

abstract class SupraMarkupBlockEnd extends SupraMarkupElement
{

	/**
	 * @var SupraMarkupBlockStart
	 */
	protected $start;

	/**
	 * @return SupraMarkupBlockStart
	 */
	public function getStart()
	{
		return $this->start;
	}

	/**
	 * @param SupraMarkupBlockStart $start 
	 */
	public function setStart($start)
	{
		$this->start = $start;
	}

}

