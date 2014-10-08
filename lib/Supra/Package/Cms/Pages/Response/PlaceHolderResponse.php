<?php

namespace Supra\Package\Cms\Pages\Response;

use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;

abstract class PlaceHolderResponse extends ResponsePart
{
	/**
	 * @var PlaceHolder
	 */
	protected $placeHolder;

	public function __construct(PlaceHolder $placeHolder)
	{
		parent::__construct();
		$this->placeHolder = $placeHolder;
	}
}
