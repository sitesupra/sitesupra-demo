<?php

namespace Supra\Configuration\Loader;

use Supra\Log\Writer\WriterAbstraction;
use Supra\Configuration\Writer\DatabaseWriter;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\FrontController;
use Supra\Session\WriteableIniConfigurationLoaderEventListener;

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
		parent::__construct($filename, $directory);

		$eventManager = ObjectRepository::getEventManager();

		$listener = new WriteableIniConfigurationLoaderEventListener($this);

		$eventManager->listen(FrontController::EVENT_FRONTCONTROLLER_SHUTDOWN, $listener);
	}

	/**
	 * @return WriterAbstraction
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
	 * @param WriterAbstraction $writer 
	 */
	public function setWriter(WriterAbstraction $writer)
	{
		$this->writer = $writer;
	}

	public function isDirty()
	{
		return $this->dirty;
	}

	public function setDirty($dirty)
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

			$this->writer->write();

			$this->setDirty(false);
		}
	}

}
