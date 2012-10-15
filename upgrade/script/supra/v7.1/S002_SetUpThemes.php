<?php

use Supra\Upgrade\Script\UpgradeScriptAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\Theme\Theme;

class S002_SetUpThemes extends UpgradeScriptAbstraction
{

	/**
	 * Check if the theme table is in the model
	 * @return boolean
	 */
	public function validate()
	{
		$em = ObjectRepository::getEntityManager($this);
		$statement = $em->getConnection()
				->prepare('SHOW TABLES LIKE \'su_Theme\'');

		$statement->execute();
		$data = $statement->fetchAll(\PDO::FETCH_ASSOC);
		$rows = count($data);

		return ($rows > 0);
	}

	public function markAsExecuted()
	{
		return true;
	}

	public function upgrade()
	{
		$this->fixThemesTable();

		$themes = array(
			'default' => SUPRA_TEMPLATE_PATH
		);

		foreach ($themes as $name => $directory) {
			$this->runCommand('su:theme:add', array('name' => $name), array('directory' => $directory));
		}

		$this->runCommand('su:theme:set_active', array('name' => 'default'));
	}

	protected function fixThemesTable()
	{
		$em = ObjectRepository::getEntityManager($this);

		$pdo = $em->getConnection();

		$pdo->exec('UPDATE su_Theme SET dtype="theme" WHERE dtype = "";');
	}

}
