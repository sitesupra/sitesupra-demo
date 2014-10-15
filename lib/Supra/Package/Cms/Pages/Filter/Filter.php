<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;

abstract class Filter implements FilterInterface
{
	/**
	 * @var BlockProperty
	 */
	protected $blockProperty;

	/**
	 * @param \Supra\Package\Cms\Entity\BlockProperty $blockProperty
	 */
	public function __construct(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}
}