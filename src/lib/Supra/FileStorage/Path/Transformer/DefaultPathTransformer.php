<?php

namespace Supra\FileStorage\Path\Transformer;

/**
 * Returns paths without modifying them
 */
class DefaultPathTransformer implements PathTransformerInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function transformSystemPath($path)
	{
		return $path;
	}

	/**
	 * {@inheritDoc}
	 */
	public function transformWebPath($path)
	{
		return $path;
	}	
}