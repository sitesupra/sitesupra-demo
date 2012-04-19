<?php

namespace Supra\Controller\Pages\Markup;

class DefaultTokenizer extends TokenizerAbstraction
{

	function __construct($source)
	{
		parent::__construct($source);

		$this->markupElements = array(
				SupraMarkupImage::SIGNATURE => SupraMarkupImage::CN(),
				SupraMarkupLinkConstructor::SIGNATURE => SupraMarkupLinkConstructor::CN()
		);
	}

}
