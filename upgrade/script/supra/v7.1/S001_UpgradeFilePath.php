<?php

use Supra\Upgrade\Script\UpgradeScriptAbstraction;
use Supra\Upgrade\Plugin\DependencyValidationPlugin;
use Supra\Upgrade\Exception\RuntimeException;

class S001_UpgradeFilePath extends UpgradeScriptAbstraction
{

	public function upgrade()
	{
		$this->runCommand('su:files:regenerate_path');
	}

	public function validate()
	{
		$dependencies = array(
			'Supra\FileStorage\Entity\FilePath'
		);

		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);

		$validator = new DependencyValidationPlugin($em, $dependencies);

		try {
			$validator->execute();
		} catch (RuntimeException $e) {
			\Log::warn($e->getMessage());
			
			return false;
		}

		return true;
	}

}
