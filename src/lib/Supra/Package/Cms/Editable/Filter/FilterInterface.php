<?php

namespace Supra\Package\Cms\Editable\Filter;

/**
 * Editable content filter interface
 */
interface FilterInterface
{
	/**
	 * Filters the editable content's data
	 * @params string $content
	 * @return string
	 */
	public function filter($content);
}
