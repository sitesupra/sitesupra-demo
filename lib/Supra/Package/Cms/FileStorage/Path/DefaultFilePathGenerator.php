<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\FileStorage\Path;

use Supra\Package\Cms\Entity\Abstraction\File as FileAbstraction;
use Supra\Package\Cms\Entity\Folder;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\FileStorage\Path\Transformer\PathTransformerInterface;

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
	
	public function __construct(PathTransformerInterface $transformer, FileStorage $fileStorage)
	{
		$this->pathTransformer = $transformer;
		$this->fileStorage = $fileStorage;
	}
	
	public function generateFilePath(FileAbstraction $file)
	{
		$filePath = $file->getPathEntity();

		$filePath->setSystemPath($this->generateSystemPath($file));
		$filePath->setWebPath($this->generateWebPath($file));

		$file->setPathEntity($filePath);
		$filePath->setId($file->getId());
	}
	
	/**
	 * @param FileAbstraction $file
	 * @return string
	 */
	protected function generateWebPath(FileAbstraction $file)
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
				/* @var $pathNode Folder */
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
	 * @param FileAbstraction $file
	 * @return string
	 */
	protected function generateSystemPath(FileAbstraction $file)
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