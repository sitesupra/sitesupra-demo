<?php

namespace Supra\Cms\Settings\Sitesettings;

use Supra\Cms\CmsAction;
use Supra\Cms\Exception\CmsException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Validator\Type\AbstractType;
use Supra\Validator\FilteredInput;


class SitesettingsAction extends CmsAction
{

	/**
	 * @var Supra\Configuration\Loader\WriteableIniConfigurationLoader
	 */
	protected $iniLoaded;
	
	/**
	 * @var array
	 */
	protected $settingCollection = array(
		'name' => array(array('name', 'string')),
		'domain' => array(array('domain', 'string')),
		'email' => array(array('email', AbstractType::EMAIL)),
		'analytics' => array(
			array('key', 'string'),
			array('source', 'string'),
		),
	);
	
	
	public function __construct()
	{
		parent::__construct();
		$this->iniLoader = ObjectRepository::getIniConfigurationLoader($this);
	}
	
	public function loadAction()
	{
		$settings = $this->iniLoader->getData();
		
		$response = array();
		foreach ($this->settingCollection as $section => $names) {
			if (isset($settings[$section])) {
				foreach($names as $definition) {
					
					list($name, $type) = $definition;
					
					if (isset($settings[$section][$name])) {
						$response[$section][$name] = $settings[$section][$name];
					}
				}
			}
		}
		
		$this->getResponse()
				->setResponseData($response);
	}
	
	public function saveAction()
	{
		$this->isPostRequest();
		
		foreach ($this->settingCollection as $section => $names) {
			if ($this->hasRequestParameter($section)) {
	
				$values = $this->getRequestParameter($section);
				foreach($names as $definition) {
					
					list($name, $type) = $definition;
					
					if (isset($values[$name])) {
						
						$value = $this->getValid($values[$name], $type);
						
						if ( ! empty($value)) {
							$this->iniLoader->setValue($section, $name, $value);
						}
					}
				}
			}
		}
		
		$this->getResponse()
				->setResponseData(true);
	}
	
	public function deleteAction()
	{
		// not implemented
		throw new CmsException(null, 'You cannot delete your site right now');
	}
	
	protected function getValid($value, $type)
	{
		if ($type != 'string') {
			$value = FilteredInput::validate($value, $type);
		}
		
		return $value;
	}
	
}
