<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Package\Cms\Editable\Filter\FilterInterface as BaseFilterInterface;

/**
 * Does escaping, nl2br to the content
 */
class TextareaFilter implements BaseFilterInterface
{
	public function filter($content)
	{
		return new \Twig_Markup(nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')), 'UTF-8');
	}
}
