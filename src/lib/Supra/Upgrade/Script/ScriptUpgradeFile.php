<?php

namespace Supra\Upgrade\Script;

use Supra\Upgrade\UpgradeFileAbstraction;
use Supra\Upgrade\Script\UpgradeScriptAbstraction;

/**
 * Database SQL upgrade file metadata
 */
class ScriptUpgradeFile extends UpgradeFileAbstraction
{

	const CN = __CLASS__;

	/**
	 * @var UpgradeScriptAbstraction
	 */
	protected $upgradeScriptInstance;

	/**
	 * @var string
	 */
	protected $upgradeScriptClassName;

	/**
	 * 
	 */
	public function loadUpgradeScriptClass()
	{
		require_once($this->getRealPath());

		$this->upgradeScriptClassName = $this->getBasename('.php');
	}

	/**
	 * @return UpgradeScriptAbstraction
	 */
	public function getUpgradeScriptInstance()
	{
		if (empty($this->upgradeScriptInstance)) {

			$this->loadUpgradeScriptClass();

			$this->upgradeScriptInstance = new $this->upgradeScriptClassName();
		}

		return $this->upgradeScriptInstance;
	}

}
