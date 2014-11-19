<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;

class UnknownBlockController extends BlockController
{
	const className = __CLASS__;

	public function doExecute()
	{
		if (! $this->getRequest() instanceof PageRequestEdit) {
			// do nothing if requested not through CMS.
			return null;
		}

		$this->getResponse()->output(sprintf(
				"<p><span>Block [%s] is unknown.<br/>Maybe it was removed?</span></p>",
				$this->block->getComponentClass()
		));
	}	
}