<?php

use Supra\Upgrade\Script\UpgradeScriptAbstraction;

class S001_UpgradeFilePath extends UpgradeScriptAbstraction
{

	public function upgrade()
	{
		$this->runCommand('su:files:regenerate_path');
	}

}
