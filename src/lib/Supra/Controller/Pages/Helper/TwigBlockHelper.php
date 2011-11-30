<?php

namespace Supra\Controller\Pages\Helper;

use Supra\Controller\Pages\BlockController;
use Supra\Response\TwigResponse;
use Twig_Markup;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;

/**
 * Supra page controller twig helper
 */
class TwigBlockHelper
{
	/**
	 * @var BlockController
	 */
	protected $blockController;
	
	/**
	 * @param BlockController $blockController
	 */
	public function __construct(BlockController $blockController)
	{
		$this->blockController = $blockController;
	}
	
	/**
	 * Outputs block property
	 * @param string $name
	 * @return string
	 */
	public function property($name)
	{
		$value = $this->blockController->getPropertyValue($name);
		
		// Marks content safe
		$valueObject = new Twig_Markup($value);

		return $valueObject;
	}
}
