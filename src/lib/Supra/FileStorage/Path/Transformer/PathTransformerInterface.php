<?php

namespace Supra\FileStorage\Path\Transformer;

interface PathTransformerInterface
{
	/**
	 * @param string $path
	 * @return string
	 */
	public function transformWebPath($path);
	
	/**
	 * @param string $path
	 * @return string
	 */
	public function transformSystemPath($path);
}