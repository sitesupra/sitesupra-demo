<?php

namespace Project\FancyBlocks\Testimonials;

use Supra\Controller\Pages\BlockController;

class TestimonialsBlock extends BlockController
{
	public function doExecute()
	{
		$response = $this->getResponse();
		$response->outputTemplate('index.html.twig');
	}
}
