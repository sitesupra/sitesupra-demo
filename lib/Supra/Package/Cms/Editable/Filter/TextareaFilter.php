<?php

namespace Supra\Package\Cms\Editable\Filter;

/**
 * Does escaping, nl2br to the content
 */
class TextareaFilter implements FilterInterface
{
	public function filter($content)
	{
		return new \Twig_Markup(nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')), 'UTF-8');
	}
}
