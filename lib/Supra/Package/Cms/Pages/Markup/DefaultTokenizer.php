<?php

namespace Supra\Package\Cms\Pages\Markup;

class DefaultTokenizer extends TokenizerAbstraction
{

	function __construct($source)
	{
		parent::__construct($source);

		$this->markupElements = array(
				SupraMarkupImage::SIGNATURE => SupraMarkupImage::CN(),
				SupraMarkupVideo::SIGNATURE => SupraMarkupVideo::CN(),
				SupraMarkupIcon::SIGNATURE => SupraMarkupIcon::CN(),
				SupraMarkupLinkConstructor::SIGNATURE => SupraMarkupLinkConstructor::CN(),
		);
	}

}
