<?php

namespace Supra\Controller\Pages\Markup\Abstraction;

abstract class SupraMarkupBlockStart extends SupraMarkupElement
{

	/**
	 * @var SupraMarkupBlockEnd
	 */
	protected $end;

	/**
	 * @return SupraMarkupBlockEnd
	 */
	public function getEnd()
	{
		return $this->end;
	}

	/**
	 * @param SupraMarkupBlockEnd $end 
	 */
	public function setEnd($end)
	{
		$this->end = $end;
	}

}

