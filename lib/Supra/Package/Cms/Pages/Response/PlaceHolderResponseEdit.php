<?php

namespace Supra\Package\Cms\Pages\Response;

/**
 * Response for place holder edit mode
 */
class PlaceHolderResponseEdit extends PlaceHolderResponse
{
	public function __toString()
	{
		$name = $this->placeHolder->getName();

		return sprintf(
				'<div id="content_%1$s" class="su-content su-content-list su-content-list-%1$s">%2$s</div>',
				$name,
				parent::__toString()
		);
	}
}
