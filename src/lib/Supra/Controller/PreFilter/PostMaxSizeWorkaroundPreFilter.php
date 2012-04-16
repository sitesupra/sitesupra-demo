<?php

namespace Supra\Controller\PreFilter;

use Supra\Controller;
use Supra\Controller\Exception;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Component tries to read raw post data from "php://input"
 * in cases when $_POST array is empty caused by post body size
 * exceeding "post_max_size" directive defined in php.ini
 * 
 */
class PostMaxSizeWorkaroundPreFilter extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
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
						$inputContent .= $line;
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
		foreach ($rawPostArray as $postItem) {
			$postItem = trim($postItem, '-');
			if (preg_match('/ name="([^\".]+)\"[^;](.*)/', $postItem, $keyMatches) == 1 && count($keyMatches) > 2) {
				$post[$keyMatches[1]] = trim($keyMatches[2]);
			}

			if (strpos($postItem, 'filename="') !== false) {
				$matches = $output = array();
				preg_match_all('/\S+=\"\S+\"/', $postItem, $matches);
				if ( ! empty($matches[0])) {
					$matches = $matches[0];
					$query = join('&', $matches);
					parse_str($query, $output);

					if ( ! empty($output['name']) && ! empty($output['filename'])) {
						$post[trim($output['name'], '"')] = trim($output['filename'], '"');
					}
				}
			}
		}

		return $post;
	}

}