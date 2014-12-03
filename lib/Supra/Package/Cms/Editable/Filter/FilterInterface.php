<?php

namespace Supra\Package\Cms\Editable\Filter;

/**
 * Editable content filter interface
 */
interface FilterInterface
{
	/**
	 * @param mixed $content Content to filter
	 * @param array $options Additional options (optional)
	 * @return mixed
	 */
	public function filter($content, array $options = array());
}
