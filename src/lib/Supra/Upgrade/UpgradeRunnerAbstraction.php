<?php

namespace Supra\Upgrade;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Writer\WriterAbstraction;
use Symfony\Component\Console\Output\OutputInterface;
use \PDO;

abstract class UpgradeRunnerAbstraction
{

	const SUPRA_UPGRADE_SUBDIRECTORY = '';
	const UPGRADE_HISTORY_TABLE = '';

	/**
	 * Database upgrade directory, relative to the SUPRA_DIR
	 * @var string
	 */
	const UPGRADE_PATH = '';

	/**
	 * Real path to the upgrade directory
	 * @var string
	 */
	protected $upgradeDir;

	/**
	 * @var WriterAbstraction
	 */
	protected $log;

	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Upgrades executed in the current object session
	 * @var array
	 */
	protected $executedUpgrades = array();

	/**
	 * @var array
	 */
	protected $pendingUpgrades = array();

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * @return OutputInterface
	 */
	public function getOutput()
	{
		return $this->output;
	}

	/**
	 * @param OutputInterface $output 
	 */
	public function setOutput(OutputInterface $output)
	{
		$this->output = $output;
	}

	/**
	 * Bind log, normalize directory names
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);

		$upgradeDir = SUPRA_PATH . static::UPGRADE_PATH;

		$this->upgradeDir = realpath($upgradeDir);

		if ($this->upgradeDir === false) {
			throw new \RuntimeException("Upgrade file subdirectory $upgradeDir doe not exist.");
		}
	}

	/**
	 * @return Connection
	 */
	public function getConnection()
	{
		if (is_null($this->connection)) {
			$entityManager = ObjectRepository::getEntityManager($this);
			$this->connection = $entityManager->getConnection();
		}

		return $this->connection;
	}

	/**
	 * @param Connection $connection
	 */
	public function setConnection(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @return FilterIterator 
	 */
	abstract protected function getFileRecursiveIterator();

	/**
	 * Lists all upgrade files
	 * @return array
	 */
	public function getAllUpgradeFiles()
	{
		$iterator = $this->getFileRecursiveIterator();
		$files = iterator_to_array($iterator);

		$files = $this->normalizePathnames($files);
		uasort($files, array($this, 'sortFiles'));

		return $files;
	}

	/**
	 * Lists upgrade file paths already executed
	 * @return array
	 */
	public function getExecutedUpgradePaths()
	{
		$executedFiles = array();
		$selectQuery = 'SELECT filename FROM ' . static::UPGRADE_HISTORY_TABLE;

		try {
			$executedFiles = $this->getConnection()->executeQuery($selectQuery)
					->fetchAll(PDO::FETCH_COLUMN);
		} catch (\PDOException $e) {
			$this->log->warn("Exception {$e->getMessage()} has been raised, assuming the table " . static::UPGRADE_HISTORY_TABLE . " is not created yet.");
		}

		if (empty($executedFiles)) {
			$executedFiles = array();
		}

		return $executedFiles;
	}

	/**
	 * Get path array of pending upgrade SQL files
	 * @return array
	 */
	public function getPendingUpgrades()
	{
		if (empty($this->pendingUpgrades)) {

			$this->pendingUpgrades = $this->getAllUpgradeFiles();
			$executedPaths = $this->getExecutedUpgradePaths();

			foreach ($this->pendingUpgrades as $path => $file) {

				// Already executed
				if (in_array($path, $executedPaths)) {
					unset($this->pendingUpgrades[$path]);
					continue;
				}

				// Check if upgrade file is valid and needed
				$allow = $this->allowUpgrade($file);
				if ( ! $allow) {
					unset($this->pendingUpgrades[$path]);
					continue;
				}
			}
		}

		return $this->pendingUpgrades;
	}

	/**
	 * Runs all upgrade files
	 */
	public function executePendingUpgrades()
	{
		$pending = $this->getPendingUpgrades();

		foreach ($pending as $file) {
			$this->executePendingUpgrade($file);
		}
	}

	/**
	 * Lists in the current session executed upgrades
	 * @return array
	 */
	public function getExecutedUpgrades()
	{
		return $this->executedUpgrades;
	}

	/**
	 * Normalizes the pathname to be similar on all machines
	 * @param array $paths
	 * @return array
	 */
	public function normalizePathnames($files)
	{
		$newFiles = array();

		foreach ($files as $path => $file) {
//			$realPath = realpath($path);
//
//			if ($realPath === false) {
//				$this->log->warn("Realpath for upgrade file $path is not found.");
//				continue;
//			}
//
//			$path = $realPath;

			//if (strpos($path, $this->upgradeDir . DIRECTORY_SEPARATOR) !== 0) {
			//	$this->log->warn("Upgrade file $path is not inside upgrade directory.");
			//	continue;
			//}

			$path = substr($path, strlen($this->upgradeDir) + 1);

			if (DIRECTORY_SEPARATOR != '/') {
				$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
			}

			$newFiles[$path] = $file;
			$file->setShortPath($path);
		}

		return $newFiles;
	}

	/**
	 * Used by sorting function to put the supra upgrades on the top
	 * @param string $path
	 * @return boolean
	 */
	private function isSupraUpgrade($path)
	{
		$isSupraUpgrade = (strpos($path, static::SUPRA_UPGRADE_SUBDIRECTORY . '/') === 0);

		return $isSupraUpgrade;
	}

	/**
	 * Sorts upgrade files
	 * @param string $path1
	 * @param string $path2
	 * @return integer
	 */
	protected function sortFiles($path1, $path2)
	{
		$sort = array(0 => array(), 1 => array());

		foreach (array($path1, $path2) as $key => $path) {

			// Supra upgrades come first
			$supra = $this->isSupraUpgrade($path);
			$sort[$key][] = $supra ? 0 : 1;

			// Sort by path afterwards
			$sort[$key][] = $path;
		}


		return $sort[0] < $sort[1] ? -1 : 1;
	}

}
