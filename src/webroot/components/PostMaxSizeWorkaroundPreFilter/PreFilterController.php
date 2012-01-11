<?php

namespace Project\PostMaxSizeWorkaroundPreFilter;

use Supra\Controller;
use Supra\Controller\Exception;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;

class PreFilterController extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
{
	/**
	 * Main method
	 */
	public function execute()
	{
		$request = $this->getRequest();

		if ($request->isPost()) {
			/* @var $request HttpRequest */
			$post = $request->getPost();
			if ($post->count() == 0) {
			
				$phpInput = fopen('php://input', 'rb');
				
				$inputContent = '';
				while ( ! feof($phpInput)) {
					$line = stream_get_line($phpInput, 1024, "\n");
					
					if (strpos($line, 'Content-Type:') !== false) {
						break;
					}
					
					$inputContent .= $line;
				}
				
				$postArray = $this->_parseRawPost($inputContent);
				$request->setPost($postArray);
			}
		}
	}
	
	// TODO: avoid usage of regular expression, if possible
	private function _parseRawPost($rawPost) 
	{
		$request = $this->getRequest();
		$contentType = $request->getServerValue('CONTENT_TYPE');
		
		preg_match('/boundary=(.*)$/', $contentType, $matches);
		if ( ! isset($matches[1])) {
			return array();
		}
		
		$boundary = $matches[1];
		$rawPostArray = explode($boundary, $rawPost);
		
		$post = array();
		foreach($rawPostArray as $postItem) {
			$postItem = trim($postItem, '-');
			if (preg_match('/ name="([^\".]+)\"[^;](.*)/', $postItem, $keyMatches) == 1 && count($keyMatches) > 2) {
				$post[$keyMatches[1]] = trim($keyMatches[2]);
			}
		}
		
		return $post;
	}

}