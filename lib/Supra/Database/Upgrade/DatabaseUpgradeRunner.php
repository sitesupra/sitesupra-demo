<?php

namespace Supra\Database\Upgrade;

use SplFileInfo;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Writer\WriterAbstraction;
use PDO;
use Doctrine\DBAL\Connection;

/**
 * Executes database upgrades from files automatically.
 * SQL files must contain @upgrade annotation as line
 * -- @supra:upgrade
 * You can filter out executed upgrades by specifying table the upgrade creates:
 * -- @supra:createsTable `tablename`
 * Also can skip upgrade by arbitraty query. It will be skipped if query doesn't fail:
 * -- @supra:runUnless SELECT newField FROM existingTable LIMIT 1
 */
class DatabaseUpgradeRunner
{
	const SUPRA_UPGRADE_SUBDIRECTORY = 'supra';

	const UPGRADE_HISTORY_TABLE = 'database_upgrade_history';
	
	/**
	 * Database upgrade directory, relative to the SUPRA_DIR
	 * @var string
	 */
	const UPGRADE_PATH = '../database';

	/**
	 * Real path to the upgrade directory
	 * @var string
	 */
	private $upgradeDir;

	/**
	 * @var WriterAbstraction
	 */
	private $log;

	/**
	 * @var Connection
	 */
	private $connection;
	
	/**
	 * Upgrades executed in the current object session
	 * @var array
	 */
	private $executedUpgrades = array();

	/**
	 * Bind log, normalize directory names
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);

		$upgradeDir = SUPRA_PATH . self::UPGRADE_PATH;
		$this->upgradeDir = realpath($upgradeDir);

		if ($this->upgradeDir === false) {
			throw new \RuntimeException("Database upgrade subdirectory $upgradeDir doesn't exist");
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
	 * Lists all upgrade files
	 * @return array
	 */
	public function getAllUpgradeFiles()
	{
		$iterator = new SqlFileRecursiveIterator($this->upgradeDir);
		$files = iterator_to_array($iterator);

		$files = $this->normalizePathnames($files);
		usort($files, array($this, 'sortFiles'));
		
		return $files;
	}

	/**
	 * Lists upgrade file paths already executed
	 * @return array
	 */
	public function getExecutedUpgradePaths()
	{
		$executedFiles = array();
		$selectQuery = 'SELECT filename FROM ' . self::UPGRADE_HISTORY_TABLE;

		try {
			$executedFiles = $this->getConnection()->executeQuery($selectQuery)
					->fetchAll(PDO::FETCH_COLUMN);
		} catch (\PDOException $e) {
			$this->log->warn("Exception {$e->getMessage()} has been raised, assuming the table " . self::UPGRADE_HISTORY_TABLE . " is not created yet.");
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
		$foundFiles = $this->getAllUpgradeFiles();
		$executedPaths = $this->getExecutedUpgradePaths();

		foreach ($foundFiles as $path => $file) {
			
			// Already executed
			if (in_array($path, $executedPaths)) {
				unset($foundFiles[$path]);
				continue;
			}
			
			// Check if upgrade file is valid and needed
			$allow = $this->allowUpgrade($file);
			if ( ! $allow) {
				unset($foundFiles[$path]);
				continue;
			}
		}
		
		return $foundFiles;
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
	 * Whether to run the upgrade
	 * @param SqlUpgradeFile $file
	 * @return boolean
	 */
	private function allowUpgrade(SqlUpgradeFile $file)
	{
		$connection = $this->getConnection();
		$path = $file->getShortPath();
		$annotations = $file->getAnnotations();

		if ( ! isset($annotations['upgrade'])) {
			$this->log->debug("Skipping $path, no @upgrade annotation");
			return false;
		}

		$runUnless = null;

		if ( ! empty($annotations['createstable'])) {
			$runUnless = 'SELECT true FROM ' . $annotations['createstable'] . ' LIMIT 1';
		} elseif ( ! empty($annotations['rununless'])) {
			$runUnless = $annotations['rununless'];
		}

		if ( ! empty($runUnless)) {
			$connection->beginTransaction();
			try {
				$connection->fetchAll($runUnless);
				$connection->rollback();
				$this->log->debug("Skipping $path, SQL '$runUnless' succeeded");

				return false;
			} catch (\PDOException $expected) {
				$connection->rollback();
				// Exception was expected
			}
		}
		
		return true;
	}

	/**
	 * Runs the upgrade if it's allowed
	 * @param SqlUpgradeFile $file
	 */
	private function executePendingUpgrade(SqlUpgradeFile $file)
	{
		$connection = $this->getConnection();
		$connection->beginTransaction();
		$path = $file->getShortPath();

		try {
			$statement = $file->getContents();
			
			// Can't fetch database output notices yet
			$output = $connection->exec($statement);

			$insert = array(
				'filename' => $path,
				'md5sum' => md5($statement),
				'output' => $output,
			);

			$connection->insert(self::UPGRADE_HISTORY_TABLE, $insert);
		} catch (\PDOException $e) {
			$connection->rollback();
			$this->log->error("Could not perform upgrade for $path: {$e->getMessage()}");

			throw $e;
		}

		$connection->commit();
		
		$this->executedUpgrades[] = $file;
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
			$realPath = realpath($path);

			if ($realPath === false) {
				$this->log->warn("SQL upgrade file $path realpath not found");
				continue;
			}
			
			$path = $realPath;

			if (strpos($path, $this->upgradeDir . DIRECTORY_SEPARATOR) !== 0) {
				$this->log->warn("File $path is not inside upgrade directory");
				continue;
			}

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
		$isSupraUpgrade = (strpos($path, self::SUPRA_UPGRADE_SUBDIRECTORY . '/') === 0);

		return $isSupraUpgrade;
	}

	/**
	 * Sorts upgrade files
	 * @param string $path1
	 * @param string $path2
	 * @return integer
	 */
	private function sortFiles($path1, $path2)
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
