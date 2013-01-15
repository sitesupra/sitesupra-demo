<?php

namespace Supra\Controller\Layout\Processor;

use Supra\Response\ResponseInterface;
use Supra\Controller\Layout\Exception;
use Supra\Request\RequestInterface;
use Supra\Controller\Pages\Response\PlaceHoldersContainer\PlaceHoldersContainerResponse;

/**
 * Simple layout processor
 */
class HtmlProcessor implements ProcessorInterface
{
	/**
	 * Place holder function name
	 */
	const PLACE_HOLDER = 'placeHolder';
	
	/**
	 * Place holder container function name
	 */
	const PLACE_HOLDER_SET = 'placeHolderSet';

	/**
	 * Maximum layout file size
	 */
	const FILE_SIZE_LIMIT = 1000000;

	/**
	 * Allowed macro functions
	 * @var array
	 */
	static protected $macroFunctions = array(
		self::PLACE_HOLDER,
		self::PLACE_HOLDER_SET,
	);

	/**Pa
	 * Layout root dir
	 * @var string
	 */
	protected $layoutDir;
	
	public $layout;

	/**
	 * @var string
	 */
	protected $startDelimiter = '<!--';

	/**
	 * @var string
	 */
	protected $endDelimiter = '-->';

	/**
	 * @var RequestInterface
	 */
	protected $request;

	/**
	 * @var ResponseInterface
	 */
	protected $response;

	/**
	 * @param RequestInterface $request
	 */
	public function setRequest(RequestInterface $request)
	{
		$this->request = $request;
	}

	/**
	 * @param ResponseInterface $response 
	 */
	public function setResponse(ResponseInterface $response)
	{
		$this->response = $response;
	}

	/**
	 * Process the layout
	 * @param ResponseInterface $response
	 * @param array $placeResponses
	 * @param string $layout
	 */
	public function process(ResponseInterface $response, array $placeResponses, $layoutSrc)
	{

		// Output CDATA
		$cdataCallback = function($cdata) use ($response) {
					$response->output($cdata);
		};

		$self = $this;
		
		// Flush place holder responses into master response
		$macroCallback = function($func, array $args, $self) use (&$response, &$placeResponses, $self) {
					if ($func == HtmlProcessor::PLACE_HOLDER) {
						if ( ! array_key_exists(0, $args) || $args[0] == '') {
							throw new Exception\RuntimeException("No placeholder name defined in the placeHolder macro in template ");
						}

						$place = $args[0];

						if (isset($placeResponses[$place])) {
							/* @var $placeResponse ResponseInterface */
							$placeResponse = $placeResponses[$place];
							$placeResponse->flushToResponse($response);
						}
					}

					if ($func == HtmlProcessor::PLACE_HOLDER_SET) {

						foreach($placeResponses as $placeResponse) {
							if ($placeResponse instanceof PlaceHoldersContainerResponse) {
								if ($placeResponse->getContainer() == $args[0]) {
									$currentContainerResponses = $placeResponse->getPlaceHolderResponses();
									$containerResponse = $placeResponse;
									break;
								}
							}
						}
						
						if ( ! empty($currentContainerResponses)) {
							$groupName = $containerResponse->getGroup();
							$layoutGroups = $self->layout->getTheme()->getPlaceholderSets();
							
							if ( ! $layoutGroups->isEmpty()) {
														
								// @FIXME: replace with default value assigned on configuration processing							
								if ( empty($groupName)) {
									$groupName = $layoutGroups->first()
											->getName();
								}

								/* @var $layoutGroups \Doctrine\ORM\PersistentCollection */

								if ($layoutGroups->offsetExists($groupName)) {
									$group = $layoutGroups->get($groupName);
									/* @var $group Supra\Controller\Pages\Entity\Theme\ThemeLayoutPlaceholderGroup */
									$self->process($containerResponse, $currentContainerResponses, $group->getLayoutFilename());
									
									$containerResponse->flushToResponse($response);
								} else {
									\Log::warn("No configuration found for {$groupName}, output for this placeholders group is skipped");
								}
							} else {
								\Log::warn('Layout group array is empty');
							}
						}
					}
				};

		$this->walk($layoutSrc, $cdataCallback, $macroCallback);
	}
	
	public function setLayout($layout)
	{
		$this->layout = $layout;
	}

	/**
	 * Return list of place names inside the layout
	 * @param string $layoutSrc
	 * @return array
	 */
	public function getPlaces($layoutSrc)
	{
		$places = array();

		// Ignore CDATA
		$cdataCallback = function($cdata) {};

		// Collect place holders
		$macroCallback = function($func, array $args) use (&$places, $layoutSrc) {
					if ($func == HtmlProcessor::PLACE_HOLDER) {
						if ( ! array_key_exists(0, $args) || $args[0] == '') {
							throw new Exception\RuntimeException("No placeholder name defined in the placeHolder macro in file {$layoutSrc}");
						}

						// Normalize placeholder ID for case insensitive MySQL varchar field
						$places[] = mb_strtolower($args[0]);
					}
				};

		$this->walk($layoutSrc, $cdataCallback, $macroCallback);

		return $places;
	}
	
	public function getPlaceContainers($layoutSrc)
	{
		$groups = array();
		
		// Ignore CDATA
		$cdataCallback = function($cdata) {};

		// Collect place holders
		$macroCallback = function($func, array $args) use (&$groups, $layoutSrc) {
					if ($func == HtmlProcessor::PLACE_HOLDER_SET) {
						if ( ! array_key_exists(0, $args) || $args[0] == '') {
							throw new Exception\RuntimeException("No placeholder container name defined in the placeHolderContainer macro in file {$layoutSrc}");
						}

						// Normalize placeholder ID for case insensitive MySQL varchar field
						$groups[] = mb_strtolower($args[0]);
					}
				};

		$this->walk($layoutSrc, $cdataCallback, $macroCallback);
		
		return $groups;
	}

	/**
	 * Generates absolute filename
	 * @param string $layoutSrc
	 * @return string
	 * @throws Exception\RuntimeException when file or security issue is raised
	 */
	protected function getFileName($layoutSrc)
	{
		$filename = $this->getLayoutDir() . \DIRECTORY_SEPARATOR . $layoutSrc;
		if ( ! is_file($filename)) {
			throw new Exception\LayoutNotFoundException("File '$layoutSrc' was not found");
		}
		if ( ! is_readable($filename)) {
			throw new Exception\RuntimeException("File '$layoutSrc' is not readable");
		}

		// security stuff
		$this->securityCheck($filename);

		return $filename;
	}

	/**
	 * @param string $layoutSrc
	 * @return string
	 * @throws Exception\RuntimeException when file or security issue is raised
	 */
	protected function getContent($layoutSrc)
	{
		$filename = $this->getFileName($layoutSrc);

		return file_get_contents($filename);
	}

	/**
	 * @param string $filename
	 * @throws Exception\RuntimeException if security issue is found
	 */
	protected function securityCheck($filename)
	{
		if (preg_match('!(^|/|\\\\)\.\.($|/|\\\\)!', $filename)) {
			throw new Exception\RuntimeException("Security error for '$filename': Layout filename cannot contain '..' part");
		}
		if (\filesize($filename) > self::FILE_SIZE_LIMIT) {
			throw new Exception\RuntimeException("Security error for '$filename': Layout file size cannot exceed " . self::FILE_SIZE_LIMIT . ' bytes');
		}
	}

	protected function macroExists($name)
	{
		$exists = in_array($name, static::$macroFunctions);
		return $exists;
	}

	protected function walk($layoutSrc, \Closure $cdataCallback, \Closure $macroCallback)
	{
		$layoutContent = $this->getContent($layoutSrc);

		$startDelimiter = $this->getStartDelimiter();
		$startLength = strlen($startDelimiter);
		$endDelimiter = $this->getEndDelimiter();
		$endLength = strlen($endDelimiter);
		$pos = null;

		do {
			$pos = strpos($layoutContent, $startDelimiter);
			if ($pos !== false) {
				$cdataCallback(substr($layoutContent, 0, $pos));
				$layoutContent = substr($layoutContent, $pos);
				$pos = strpos($layoutContent, $endDelimiter);
				if ($pos === false) {
					break;
				}

				$macroString = substr($layoutContent, $startLength, $pos - $startLength);
				$macro = trim($macroString);
				if ( ! preg_match('!^(.*)\((.*)\)$!', $macro, $macroInfo)) {
					$cdataCallback(substr($layoutContent, 0, $startLength));
					$layoutContent = substr($layoutContent, $startLength);
					continue;
				}

				$macroFunction = trim($macroInfo[1]);
				$macroArguments = explode(',', $macroInfo[2]);
				$macroArguments = array_map('trim', $macroArguments);

				if ( ! $this->macroExists($macroFunction)) {
					$cdataCallback(substr($layoutContent, 0, $startLength));
					$layoutContent = substr($layoutContent, $startLength);
					continue;
				}

				$macroCallback($macroFunction, $macroArguments, null);

				// remove the used data
				$layoutContent = substr($layoutContent, $pos + $endLength);
			}
		} while ($pos !== false);

		$cdataCallback($layoutContent);
	}

	/**
	 * Set layout root dir
	 * @param string $layoutDir
	 */
	public function setLayoutDir($layoutDir)
	{
		$this->layoutDir = $layoutDir;
	}

	/**
	 * Get layout root dir
	 * @return string
	 */
	public function getLayoutDir()
	{
		return $this->layoutDir;
	}

	/**
	 * @return string
	 */
	public function getStartDelimiter()
	{
		return $this->startDelimiter;
	}

	/**
	 * @param string $startDelimiter
	 */
	public function setStartDelimiter($startDelimiter)
	{
		$this->startDelimiter = $startDelimiter;
	}

	/**
	 * @return string
	 */
	public function getEndDelimiter()
	{
		return $this->endDelimiter;
	}

	/**
	 * @param string $endDelimiter
	 */
	public function setEndDelimiter($endDelimiter)
	{
		$this->endDelimiter = $endDelimiter;
	}

}
