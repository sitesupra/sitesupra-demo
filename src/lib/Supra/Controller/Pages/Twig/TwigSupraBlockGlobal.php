<?php

namespace Supra\Controller\Pages\Twig;

use Supra\Controller\Pages\BlockController;
use Supra\Response\TwigResponse;
use Twig_Markup;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;

/**
 * Supra page controller twig helper
 */
class TwigSupraBlockGlobal
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
		
		return $value;
	}
}
