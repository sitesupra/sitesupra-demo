<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\Controller\Pages\Entity\BlockProperty;

/**
 * Does escaping, nl2br to the content
 */
class EditableTextarea implements FilterInterface
{
	/**
	 * @var BlockProperty
	 */
	public $property;

	public function filter($content)
	{
		$html = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
		$html = strtr($html, array("\r\n" => '<br />', "\n" => '<br />'));

		$markup = new \Twig_Markup($html, 'UTF-8');
		
		return $markup;
	}

}
