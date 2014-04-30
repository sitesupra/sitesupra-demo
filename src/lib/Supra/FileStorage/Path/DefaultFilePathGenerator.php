<?php

namespace Supra\FileStorage\Path;

use Supra\FileStorage\Entity\Abstraction\File;
use Supra\FileStorage\FileStorage;

class DefaultFilePathGenerator implements FilePathGeneratorInterface
{
	/**
	 * @var Transformer\PathTransformerInterface
	 */
	private $pathTransformer;
	
	/**
	 * @var FileStorage
	 */
	private $fileStorage;
	
	public function __construct(Transformer\PathTransformerInterface $transformer, FileStorage $fileStorage)
	{
		$this->pathTransformer = $transformer;
		$this->fileStorage = $fileStorage;
	}
	
	public function generateFilePath(File $file)
	{
		$filePath = $file->getPathEntity();

		$filePath->setSystemPath($this->generateSystemPath($file));
		$filePath->setWebPath($this->generateWebPath($file));

		$file->setPathEntity($filePath);
		$filePath->setId($file->getId());
	}
	
	/**
	 * @param File $file
	 * @return string
	 */
	protected function generateWebPath(File $file)
	{
		if ($file->isPublic()) {

			$externalUrlBase = $this->fileStorage->getExternalUrlBase();

			if ( ! empty($externalUrlBase)) {
				$path = $externalUrlBase . DIRECTORY_SEPARATOR;
			} else {
				$path = '/' . str_replace(SUPRA_WEBROOT_PATH, '', $this->fileStorage->getExternalPath());
			}

			// Fix backslash on Windows
			$path = str_replace(array('//', "\\"), '/', $path);

			// get file dir
			$pathNodes = $file->getAncestors(0, false);
			$pathNodes = array_reverse($pathNodes);

			foreach ($pathNodes as $pathNode) {
				/* @var $pathNode Entity\Folder */
				$path .= $pathNode->getFileName() . '/';
			}

			// Encode the filename URL part
			$path .= $file->getFileName();

			$transformedPath = $this->pathTransformer
					->transformWebPath($path);
			
			$pathParts = explode('/', $transformedPath);
			
			$pathParts = array_map('rawurlencode', $pathParts);
			
			return implode('/', $pathParts);
		}
	}

	/**
	 * @param File $file
	 * @return string
	 */
	protected function generateSystemPath(File $file)
	{
		$pathNodes = $file->getAncestors(0, true);
		
		$items = array();
		
		foreach ($pathNodes as $node) {
			array_unshift($items, $node->__toString());
		}
		
		$path = implode(DIRECTORY_SEPARATOR, $items);
		
		return $this->pathTransformer
				->transformSystemPath($path);
	}
}