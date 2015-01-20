<?php

namespace Supra\Package\Cms\Pages\Response;

class BlockResponseEdit extends BlockResponse
{
	public function __toString()
	{
		return sprintf(
				'<div id="content_%1$s" class="su-content su-content-%2$s su-content-%2$s-%1$s">%3$s</div>',
				$this->block->getId(),
				$this->block->getComponentName(),
				parent::__toString()
		);
	}
}
