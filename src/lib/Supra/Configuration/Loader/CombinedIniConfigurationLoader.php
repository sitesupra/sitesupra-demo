<?php

namespace Supra\Configuration\Loader;

use Supra\Log\Writer\WriterAbstraction;
use Supra\Configuration\Parser\DatabaseParser;
use Supra\Configuration\Writer\DatabaseWriter;
use Supra\Configuration\Parser\AbstractParser;
use Supra\Configuration\Writer\AbstractWriter;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\FrontController;
use Supra\Configuration\WriteableIniConfigurationLoaderEventListener;
use Supra\Controller\Event\FrontControllerShutdownEventArgs;
use Supra\Configuration\Loader;

class CombinedIniConfigurationLoader extends IniConfigurationLoader
{

	/**
	 * @var array
	 */
	protected $loaders;

	/**
	 * @return array
	 */
	public function getLoaders()
	{
		return $this->loaders;
	}

	/**
	 * @param array $iniLoaders 
	 */
	public function __construct($iniLoaders)
	{
		$this->data = array();

		foreach ($iniLoaders as $iniLoader) {
			/* @var $iniLoader IniConfigurationLoader */

			$this->data = array_merge($this->data, $iniLoader->getData());
		}
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @throws Exception\RuntimeException 
	 */
	public function getFilename()
	{
		throw new Exception\RuntimeException('Combined .ini loader does not have a filename.');
	}

	/**
	 * @throws Exception\RuntimeException 
	 */
	public function getParser()
	{
		throw new Exception\RuntimeException('Combined .ini loader does not have a separate parser.');
	}

	/**
	 * @param Parser\AbstractParser $parser
	 * @throws Exception\RuntimeException 
	 */
	public function setParser(Parser\AbstractParser $parser)
	{
		throw new Exception\RuntimeException('Combined .ini loader does not need a parser.');
	}

	/**
	 * @throws Exception\RuntimeException 
	 */
	protected function parse()
	{
		throw new Exception\RuntimeException('Combined .ini loader does not do parsing.');
	}

}
