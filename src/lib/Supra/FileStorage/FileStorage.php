<?php

/**
 * File storage
 *
 */
class FileStorage
{

	/**
	 * FileStorage
	 */

	//  checkWebSafe() using Upload Filters
	//  deleteFile($fileObj)
	//  deleteFolder($fileObj) only empty folders
	//  storeUploadedFile
	//  LIST (children by folder id)
	// getDoctrineRepository()

	//  setPrivate (File $file)
	//  setPublic (File $file)
	// getFile($fileId)
	// getFolder($fileId)

	// getFileContents(File $file)
	// getFileHandle(File $file)

	// setDbConnection

	/**
	 *
	 * @param \Supra\FileStorage\Entity\File $file
	 * @param <type> $source 
	 * @autowired
	 */
	private static $log; // Logger

	function storeFileData(\Supra\FileStorage\Entity\File $file, $source)
	{
//		SupraDatabase::getConnection(__CLASS__);
//		$log = Logger::getLogger(__CLASS__);
//		Repository::getInstance('Logger', __CLASS__);

		\Log::debug();

		$file->setSize();

		$dest = $file->getPath();

		copy($source, $dest);
	}

}