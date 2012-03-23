<?php

namespace Project\Blocks\Text;

use Supra\Controller\Pages\BlockController;
use Supra\Response;

/**
 * Simple text block
 */
class TextController extends BlockController
{
	public function doExecute()
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
		
		$response->assign('comment', $comment);
		
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
		
		$usePageTitle = new \Supra\Editable\Checkbox("Use page title");
		$usePageTitle->setDefaultValue(true);
		$contents['usePageTitle'] = $usePageTitle;
		
		$html = new \Supra\Editable\InlineString("Title");
		$html->setDefaultValue('Paragraph default title');
		$contents['title'] = $html;
		
		$html = new \Supra\Editable\Html("Content");
		$contents['content'] = $html;
		
		$contents['image'] = new \Supra\Editable\Image('Picture');
		
		return $contents;
	}
}
