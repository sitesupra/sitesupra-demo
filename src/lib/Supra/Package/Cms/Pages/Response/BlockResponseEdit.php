<?php

namespace Supra\Package\Cms\Pages\Response;

class BlockResponseEdit extends BlockResponse
{
	public function __toString()
	{
		return sprintf(
				'<div id="content_%1$s" class="yui3-content yui3-content-%2$s yui3-content-%2$s-%1$s">%3$s</div>',
				$this->block->getId(),
				$this->block->getComponentName(),
				parent::__toString()
		);
	}
}
