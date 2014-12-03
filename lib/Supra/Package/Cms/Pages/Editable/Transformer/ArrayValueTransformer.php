<?php

namespace Supra\Package\Cms\Pages\Editable\Transformer;

use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;

class ArrayValueTransformer implements ValueTransformerInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function reverseTransform($value)
	{
		if (! empty($value)) {
			return serialize($value);
		}
		
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function transform($value)
	{
		if (! empty($value)) {
			return unserialize($value);
		}

		return null;
	}
}