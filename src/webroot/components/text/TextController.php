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
		if ( ! ($response instanceof Response\Http)) {
			\Log::sdebug('Response is not an instance of Http response in block controller ' . __CLASS__);
			return;
		}

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

		/* @var $response Response\Http */
		$response->output('<div title="' . htmlspecialchars($comment) . '">');
//		$response->output($this->getPropertyValue('html', 'defaultText'));
		
		$this->outputProperty('html', 'defaultText');
		
		$response->output('</div>');
	}
	
	/**
	 * Loads property definition array
	 * @return array
	 */
	protected function getPropertyDefinition()
	{
		$contents = array();
		
		$html = new \Supra\Editable\Html("The Main Content");
		$contents['html'] = $html;
		
		return $contents;
	}
}
