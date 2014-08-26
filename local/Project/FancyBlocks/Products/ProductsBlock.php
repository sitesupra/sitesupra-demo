<?php

namespace Project\FancyBlocks\Products;

use Supra\Controller\Pages\BlockController;

class ProductsBlock extends BlockController
{
	public function doExecute()
	{
		$response = $this->getResponse();
		$response->outputTemplate('index.html.twig');
	}

}
