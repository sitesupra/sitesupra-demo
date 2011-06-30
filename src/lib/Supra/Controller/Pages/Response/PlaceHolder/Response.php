<?php

namespace Supra\Controller\Pages\Response\PlaceHolder;

use Supra\Response\Http,
		Supra\Controller\Pages\Entity\BlockProperty,
		Supra\Editable\EditableAbstraction,
		Supra\Controller\Pages\Entity\Abstraction\PlaceHolder;

/**
 * Response for place holder
 */
abstract class Response extends Http
{
	/**
	 * @var PlaceHolder
	 */
	private $placeHolder;
	
	/**
	 * @return PlaceHolder
	 */
	public function getPlaceHolder()
	{
		return $this->placeHolder;
	}

	/**
	 * @param PlaceHolder $placeHolder
	 */
	public function setPlaceHolder(PlaceHolder $placeHolder)
	{
		$this->placeHolder = $placeHolder;
	}

}
