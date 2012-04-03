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

class WriteableIniConfigurationLoader extends IniConfigurationLoader
{

	/**
	 * @var WriterAbstraction
	 */
	protected $writer;

	/**
	 * @var boolean
	 */
	protected $dirty = false;

	/**
	 * @param string $filename
	 * @param string $directory 
	 */
	public function __construct($filename, $directory = SUPRA_CONF_PATH)
	{
		parent::__construct($filename, '[Database]');

		$eventManager = ObjectRepository::getEventManager();
		$listener = new WriteableIniConfigurationLoaderEventListener($this);
		$eventManager->listen(FrontControllerShutdownEventArgs::FRONTCONTROLLER_SHUTDOWN, $listener);
	}

	/**
	 *
	 * @return AbstractParser
	 */
	public function getParser()
	{
		if (empty($this->parser)) {

			$this->parser = new DatabaseParser();
		}

		return $this->parser;
	}

	/**
	 * @return AbstractWriter
	 */
	public function getWriter()
	{
		if (empty($this->writer)) {

			$this->writer = new DatabaseWriter();
			$this->writer->setParser($this->getParser());
		}

		return $this->writer;
	}

	/**
	 * @param AbstractWriter $writer 
	 */
	public function setWriter(AbstractWriter $writer)
	{
		$this->writer = $writer;
	}

	/**
	 * @return boolean
	 */
	public function isDirty()
	{
		return $this->dirty;
	}

	/**
	 * @param boolean $dirty 
	 */
	protected function setDirty($dirty)
	{
		$this->dirty = $dirty;
	}

	/**
	 * @param string $sectionName
	 * @param string $name
	 * @param string $value 
	 */
	public function setValue($sectionName, $name, $value)
	{
		$this->getData();

		$data = &$this->data;

		if (empty($data[$sectionName])) {
			$data[$sectionName] = array();
		}

		$data[$sectionName][$name] = $value;

		$this->setDirty(true);
	}

	/**
	 * @param string $sectionName
	 * @param array $section 
	 */
	public function setSection($sectionName, $section)
	{
		$this->getData();

		$this->data[$sectionName] = $section;

		$this->setDirty(true);
	}

	public function write()
	{
		if ($this->isDirty()) {

			$writer = $this->getWriter();
			
			$writer->writeData($this->filename, $this->data);

			$this->setDirty(false);
		}
	}

}
