<?php

namespace Supra\Package\Cms\Editable\Transformer;

interface ValueTransformerInterface
{
	/**
	 * @param mixed $value
	 * @return mixed
	 */
    public function transform($value);

	/**
	 * @param mixed $value
	 * @return mixed
	 */
    public function reverseTransform($value);
	
}
