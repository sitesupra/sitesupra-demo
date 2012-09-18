<?php

use Supra\Upgrade\Script\UpgradeScriptAbstraction;
use Supra\ObjectRepository\ObjectRepository;

class S002_SetUpThemes extends UpgradeScriptAbstraction
{

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
