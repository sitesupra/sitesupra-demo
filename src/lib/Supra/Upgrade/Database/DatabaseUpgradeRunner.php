<?php

namespace Supra\Upgrade\Database;

use Supra\Upgrade\UpgradeRunnerAbstraction;

/**
 * Executes database upgrades from files automatically.
 * SQL files must contain @upgrade annotation as line
 * -- @supra:upgrade
 * You can filter out executed upgrades by specifying table the upgrade creates:
 * -- @supra:createsTable `tablename`
 * Also can skip upgrade by arbitraty query. It will be skipped if query doesn't fail:
 * -- @supra:runUnless SELECT newField FROM existingTable LIMIT 1
 */
class DatabaseUpgradeRunner extends UpgradeRunnerAbstraction
{

	const SUPRA_UPGRADE_SUBDIRECTORY = 'supra';
	const UPGRADE_HISTORY_TABLE = 'database_upgrade_history';
	const UPGRADE_PATH = '../upgrade/database';

	/**
	 * Whether to run the upgrade
	 * @param SqlUpgradeFile $file
	 * @return boolean
	 */
	protected function allowUpgrade(SqlUpgradeFile $file)
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
	protected function executePendingUpgrade(SqlUpgradeFile $file)
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

			$connection->insert(static::UPGRADE_HISTORY_TABLE, $insert);
		} catch (\PDOException $e) {
			$connection->rollback();
			$this->log->error("Could not perform upgrade for $path: {$e->getMessage()}");

			throw $e;
		}

		$connection->commit();

		$this->executedUpgrades[] = $file;
	}

	protected function getFileRecursiveIterator()
	{
		return new SqlFileRecursiveIterator($this->upgradeDir);
	}

}
