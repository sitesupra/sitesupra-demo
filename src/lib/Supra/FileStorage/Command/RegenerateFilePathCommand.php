<?php

namespace Supra\FileStorage\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\Abstraction\File;
use Supra\FileStorage\Entity\Folder;
use Supra\FileStorage\Entity\FilePath;
use Symfony\Component\Console\Input\InputOption;

/**
 * Regenerates paths for all the files
 */
class RegenerateFilePathCommand extends Command
{
	const CHANGED_PATH_ACTION_NONE = 'none';
	const CHANGED_PATH_ACTION_MOVE_FILE = 'move';
	const CHANGED_PATH_ACTION_COPY_FILE = 'copy';
	
	/**
	 * @var \Supra\FileStorage\FileStorage
	 */
	protected $fileStorage;
	
	protected function configure()
	{
		$this->setName('su:files:regenerate_path')
				->addOption(
						'changed-path-file-action', 
						'A', 
						InputOption::VALUE_OPTIONAL, 
						'Defines what to do with files if path has been changed',
						self::CHANGED_PATH_ACTION_NONE
				)->setDescription("Regenerates the path for all files");
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = ObjectRepository::getEntityManager($this);

		$changedPathAction = $input->getOption('changed-path-file-action');
		
		$originFileEntities = array();
		
		if ($changedPathAction !== self::CHANGED_PATH_ACTION_NONE) {
			
			$fileRepo = $em->getRepository(File::CN());
			
			foreach ($fileRepo->findAll() as $file) {
				/* @var $file File */
				
				$originFile = clone $file;
				$em->detach($file);
				
				$originFileEntities[$originFile->getId()] = $originFile;
			}
		}

		$em->beginTransaction();
		
		// cleanup file <-> path entity relation
		$em->createQuery('UPDATE ' . File::CN() . ' f SET f.path = NULL')
				->execute();
		
		// clear paths table
		$em->createQuery('DELETE FROM ' . FilePath::CN())
				->execute();
		
		// all files ordered by nesting level
		$files = $em->createQuery('SELECT f FROM ' . File::CN() . ' f ORDER BY f.left ASC')
				->getResult();
		
		$this->fileStorage = ObjectRepository::getFileStorage($this);
		
		$pathGenerator = $this->fileStorage->getFilePathGenerator();

		$i = 0;
		
		$newSystemPaths = array();
		
		foreach ($files as $file) {
			/* @var $file File */

			$pathGenerator->generateFilePath($file);
			
			$pathEntity = $file->getPathEntity();
			
			$newSystemPaths[$pathEntity->getId()] = $pathEntity->getSystemPath();

			if (++$i % 10 == 0) {
				$output->writeln("Processed $i files");
			}
		}
		
		$output->writeln("Done generating. Flushing to database...");
		
		try {
			$em->flush();
			
			$output->writeln("Successfully flushed. Processing changed file paths...");
			
			$this->handleChangedPaths($originFileEntities, $files, $changedPathAction);
			
		} catch (\Exception $e) {
			$em->rollback();
			throw $e;
		}
			
		$em->commit();

		$output->writeln("Done. Processed $i files");
	}
	
	private function handleChangedPaths(array $originFiles, array $newFiles, $action)
	{
		switch ($action) {
			case self::CHANGED_PATH_ACTION_NONE:
				// nothing to do
				return null;
				
			case self::CHANGED_PATH_ACTION_MOVE_FILE:
				// not implemented
				throw new \RuntimeException('Move is not implemented.');
				
			case self::CHANGED_PATH_ACTION_COPY_FILE:
				foreach ($newFiles as $file) {
					
					$id = $file->getId();
					
					if (empty($originFiles[$id])) {
						// something has gone wrong
						continue;
					}
					
					$originFile = $originFiles[$id];
					
					$currentPath = $originFiles[$id]->getPathEntity()
							->getSystemPath();
					
					$newPath = $file->getPathEntity()
							->getSystemPath();
										
					if ($currentPath == $newPath) {
						continue;
					}
					
					$originFilename = $this->fileStorage->getFilesystemPath($originFile);
					$newFilename = $this->fileStorage->getFilesystemPath($file);
					
					// if there is no source file - cannot copy
					// if target already exists, it's safer to skip
					if (! file_exists($originFilename) || file_exists($newFilename)) {
						continue;
					}
					
					if ($file instanceof Folder) {
						$this->fileStorage->createFolder($file);
					} else {
						
						copy($originFilename, $newFilename);
						@chmod($newFilename, SITESUPRA_FILE_PERMISSION_MODE);
						
						if ($file instanceof \Supra\FileStorage\Entity\Image) {
							
							$fileDirectory = $this->fileStorage->getFilesystemDir($file);

							foreach ($originFile->getImageSizeCollection() as $size) {
								
								$sizeName = $size->getName();
								
								$originResizeName = $this->fileStorage->getImagePath($originFile, $sizeName);
								$newResizeName = $this->fileStorage->getImagePath($file, $sizeName);
								
								$resizedFileDir =  $fileDirectory 
										. \Supra\FileStorage\FileStorage::RESERVED_DIR_SIZE 
										. DIRECTORY_SEPARATOR
										. $size->getFolderName()
										. DIRECTORY_SEPARATOR;
								
								if (! file_exists($resizedFileDir)) {
									mkdir($resizedFileDir, SITESUPRA_FOLDER_PERMISSION_MODE, true);
								}
								
								if (file_exists($originResizeName) && !file_exists($newResizeName)) {
									copy($originResizeName, $newResizeName);
									@chmod($newFilename, SITESUPRA_FILE_PERMISSION_MODE);
								}
							}
						}
					}
				}
				
				break;
			
			default:
				throw new \InvalidArgumentException("Unknown action {$action}");
		}
	}

}
