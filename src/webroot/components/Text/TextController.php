<?php

namespace Project\Text;

use Supra\Controller\Pages\BlockController,
		Supra\Request,
		Supra\Response;

/**
 * Simple text block
 */
class TextController extends BlockController
{
	public function execute()
	{
		$response = $this->getResponse();
		/* @var $response Response\TwigResponse */

		// DEV comment about the block
		$block = $this->getBlock();
		$comment = '';
		if ( ! empty($block)) {
			$comment .= "Block $block.\n";
			if ($block->getLocked()) {
				$comment .= "Block is locked.\n";
			}
			if ($block->getPlaceHolder()->getLocked()) {
				$comment .= "Place holder is locked.\n";
			}
			$comment .= "Master " . $block->getPlaceHolder()->getMaster()->__toString() . ".\n";
		}
		
		$response->assign('title', $comment);
		
		// Local file is used
		$response->outputTemplate('index.html.twig');
	}
	
	/**
	 * Loads property definition array
	 * @return array
	 */
	public function getPropertyDefinition()
	{
		$contents = array();
		
		$html = new \Supra\Editable\String("Title");
		$html->setDefaultValue('Paragraph default title');
		$contents['title'] = $html;
		
		$html = new \Supra\Editable\Html("Content");
		$contents['content'] = $html;
		
		
		return $contents;
	}
}
