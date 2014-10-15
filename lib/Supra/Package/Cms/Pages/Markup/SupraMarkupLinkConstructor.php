<?php

namespace Supra\Package\Cms\Pages\Markup;

class SupraMarkupLinkConstructor extends Abstraction\SupraMarkupBlockConstructor
{
	const SIGNATURE = 'supra.link';

	function __construct()
	{
		parent::__construct(
				self::SIGNATURE, SupraMarkupLinkStart::CN(), SupraMarkupLinkEnd::CN()
		);
	}

}
