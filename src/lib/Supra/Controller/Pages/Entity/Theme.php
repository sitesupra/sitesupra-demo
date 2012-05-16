<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Database;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Layout\Theme\ThemeInterface;
use Supra\Less\SupraLessC;
use Supra\Controller\Layout\Theme\Configuration\ThemeConfiguration;
use Supra\Configuration\Parser\YamlParser;
use Supra\Controller\Layout\Theme\Configuration\ThemeConfigurationLoader;
use Supra\Controller\Pages\Entity\ThemeParameter;

/**
 * @Entity
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_idx", columns={"name"})}))
 */
class Theme extends Database\Entity implements ThemeInterface
{

	const PATH_PART_GENERATED_CSS = 'generatedCss';
	const PATH_PART_LAYOUTS = 'layouts';

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $enabled;

	/**
	 * @Column(type="string")
	 * @var string 
	 */
	protected $title;

	/**
	 * @Column(type="string");
	 * @var string
	 */
	protected $rootDir;

	/**
	 * @Column(type="string")
	 * @var string 
	 */
	protected $description;

	/**
	 * @Column(type="string")
	 * @var string 
	 */
	protected $configMd5;

	/**
	 * @OneToMany(targetEntity="ThemeLayout", mappedBy="theme", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var Arraycollection
	 */
	protected $layouts;

	/**
	 * @OneToMany(targetEntity="ThemeParameterSet", mappedBy="theme", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var ArrayCollection
	 */
	protected $parameterSets;

	/**
	 * @OneToMany(targetEntity="ThemeParameter", mappedBy="theme", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var ArrayCollection
	 */
	protected $parameters;

	/**
	 * @OneToOne(targetEntity="ThemeParameterSet")
	 * @JoinColumn(name="active_parameter_set_id", referencedColumnName="id")
	 * @var ThemeParameterSet
	 */
	protected $activeParameterSet;

	/**
	 * @var ThemeParameterSet
	 */
	protected $currentParameterSet;

	/**
	 * @Column(type="string");
	 * @var string
	 */
	protected $urlBase;

	public function __construct()
	{
		parent::__construct();

		$this->parameters = new ArrayCollection();
		$this->parameterSets = new ArrayCollection();
		$this->layouts = new ArrayCollection();
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return 'Look, master, we made some description-nama for theme "' . $this->name . '"!';
	}

	/**
	 * @param string $description 
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return string 
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title 
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @param string $rootDir 
	 */
	public function setRootDir($rootDir)
	{
		$rootDir = str_replace(SUPRA_PATH, '{SUPRA_PATH}', $rootDir);
		
		$this->rootDir = preg_replace('@/+@', '/', $rootDir);
	}

	/**
	 * @return string
	 */
	public function getRootDir()
	{
		$rootDir = str_replace('{SUPRA_PATH}', SUPRA_PATH, $this->rootDir);
		
		return $rootDir;
	}

	/**
	 * @return string
	 */
	public function getGeneratedCssDir()
	{
		return $this->getRootDir() . DIRECTORY_SEPARATOR . self::PATH_PART_GENERATED_CSS;
	}

	/**
	 * @param string $urlBase 
	 */
	public function setUrlBase($urlBase)
	{
		$this->urlBase = preg_replace('@/+@', '/', $urlBase . DIRECTORY_SEPARATOR);
	}

	/**
	 * @return string
	 */
	public function getUrlBase()
	{
		return $this->urlBase;
	}

	/**
	 * @return string
	 */
	public function getGeneratedCssUrlBase()
	{
		return $this->getUrlBase() . self::PATH_PART_GENERATED_CSS . DIRECTORY_SEPARATOR;
	}

	/**
	 * @return boolean
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * @param boolean $enabled 
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}

	/**
	 * @return string
	 */
	public function getConfigMd5()
	{
		return $this->configMd5;
	}

	/**
	 * @param string $configMd5 
	 */
	public function setConfigMd5($configMd5)
	{
		$this->configMd5 = $configMd5;
	}

	/**
	 * @return ArrayCollection
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param ThemeParameter $parameter 
	 */
	public function addParameter(ThemeParameter $parameter)
	{
		$parameter->setTheme($this);
		$this->parameters[$parameter->getName()] = $parameter;
	}

	/**
	 * @param ThemeParameter $parameter 
	 */
	public function removeParameter(ThemeParameter $parameter)
	{
		$parameter->setTheme(null);

		$this->parameters->removeElement($parameter);
	}

	/**
	 * @return array
	 */
	public function getCurrentParameterSetOutputValues()
	{
		$currentParameterSet = $this->getCurrentParameterSet();

		$outputValues = $currentParameterSet->getOutputValues();

		$outputValues['name'] = $this->getName();

		$outputValues['urlBase'] = $this->getUrlBase();

		$outputValues['generatedCssUrl'] = $this->getCurrentGeneratedCssUrl();

		$outputValues['parameterSetName'] = $currentParameterSet->getName();

		return $outputValues;
	}

	public function generateCssFiles()
	{
		foreach ($this->parameterSets as $parameterSet) {


			/* @var $parameterSet ThemeParameterSet */

			\Log::debug($parameterSet->getName());
			$this->generateCssFileFromLess($parameterSet);
		}
	}

	/**
	 * @param ThemeParameterSet $parameterSet 
	 */
	protected function generateCssFileFromLess(ThemeParameterSet $parameterSet)
	{
		$lessc = new SupraLessC($this->getRootDir() . DIRECTORY_SEPARATOR . 'theme.less');

		$lessc->setRootDir($this->getRootDir());

		$values = $parameterSet->getOutputValues();

		$cssContent = $lessc->parse(null, $values);

		$this->writeGenetratedCssToFile($parameterSet, $cssContent);
	}

	/**
	 * @param ThemeParameterSet $parameterSet
	 * @param string $content
	 * @throws Exception\RuntimeException 
	 */
	protected function writeGenetratedCssToFile(ThemeParameterSet $parameterSet, $content)
	{
		$cssFilename = $this->getGeneratedCssFilename($parameterSet);

		$result = file_put_contents($cssFilename, $content);

		if ($result === false) {
			throw new Exception\RuntimeException('Could not write theme CSS file to "' . $cssFilename . '".');
		}
	}

	/**
	 * @param string $parameterSetName
	 * @return string
	 */
	protected function getGeneratedCssBasename(ThemeParameterSet $parameterSet)
	{
		$parameterSetName = $parameterSet->getName();

		return $this->getName() . '_' . $parameterSetName . '.css';
	}

	/**
	 * @param string $parameterSetName
	 * @return string
	 */
	protected function getGeneratedCssFilename(ThemeParameterSet $parameterSet)
	{
		return $this->getGeneratedCssDir() . DIRECTORY_SEPARATOR . $this->getGeneratedCssBasename($parameterSet);
	}

	/**
	 * @param string $parameterSetName
	 * @return string
	 */
	protected function getGeneratedCssUrl(ThemeParameterSet $parameterSet)
	{
		return $this->getGeneratedCssUrlBase() . $this->getGeneratedCssBasename($parameterSet);
	}

	/**
	 * @return string
	 */
	protected function getCurrentGeneratedCssUrl()
	{
		return $this->getGeneratedCssUrl($this->getCurrentParameterSet());
	}

	/**
	 * @return ThemeParameterSet
	 */
	public function getCurrentParameterSet()
	{
		if (empty($this->currentParameterSet)) {
			$this->currentParameterSet = $this->getActiveParameterSet();
		}

		if (empty($this->currentParameterSet)) {

			$this->currentParameterSet = new ThemeParameterSet();

			foreach ($this->getParameters() as $parameter) {
				/* @var $parameter ThemeParameter */

				$value = $parameter->getThemeParameterValue();
				$this->currentParameterSet->addValue($value);
			}
			
			$this->currentParameterSet->setName('auto-current');
		}

		return $this->currentParameterSet;
	}

	/**
	 * @param ThemeParameterSet $currentParameterSet 
	 */
	public function setCurrentParameterSet(ThemeParameterSet $currentParameterSet)
	{
		$this->currentParameterSet = $currentParameterSet;
	}

	/**
	 * @return ThemeParameterSet | null
	 */
	public function getActiveParameterSet()
	{
		if (empty($this->activeParameterSet)) {

			if ($this->parameterSets->isEmpty()) {
				return null;
			} else {
				$this->activeParameterSet = $this->parameterSets->first();
			}
		}

		return $this->activeParameterSet;
	}

	/**
	 * @param ThemeParameterSet $activeParameterSet 
	 */
	public function setActiveParameterSet(ThemeParameterSet $activeParameterSet)
	{
		$this->activeParameterSet = $activeParameterSet;
	}

	/**
	 * @return string
	 */
	public function getConfigurationFilename()
	{
		return $this->getRootDir() . DIRECTORY_SEPARATOR . 'theme.yml';
	}

	/**
	 * @param string $configurationFilename 
	 */
	public function setConfigurationFilename($configurationFilename)
	{
		$this->configurationFilename = $configurationFilename;
	}

	/**
	 * @return array
	 */
	public function getParameterSets()
	{
		return $this->parameterSets;
	}

	/**
	 * @param ThemeParameterSet $parameterSet 
	 */
	public function addParameterSet(ThemeParameterSet $parameterSet)
	{
		$parameterSet->setTheme($this);

		$this->parameterSets[$parameterSet->getName()] = $parameterSet;
	}

	/**
	 * @param ThemeParameterSet $parameterSet 
	 */
	public function removeParameterSet(ThemeParameterSet $parameterSet)
	{
		$parameterSet->setTheme(null);

		$this->parameterSets->removeElement($parameterSet);
	}

	/**
	 * @return array
	 */
	public function getLayouts()
	{
		return $this->layouts;
	}

	/**
	 * @param ThemeLayout $layout 
	 */
	public function addLayout(ThemeLayout $layout)
	{
		$layout->setTheme($this);

		$this->layouts[$layout->getName()] = $layout;
	}

	/**
	 * @param ThemeLayout $layout 
	 */
	public function removeLayout(ThemeLayout $layout)
	{
		$layout->setTheme(null);

		$this->layouts->removeElement($layout);
	}

	/**
	 * @param string $layoutName
	 * @return ThemeLayout
	 */
	public function getLayout($layoutName)
	{
		return $this->layouts->get($layoutName);
	}

	/**
	 * @return ThemeConfiguration
	 */
	public function getConfiguration()
	{
		if (empty($this->configuration)) {

			$yamlParser = new YamlParser();
			$configurationLoader = new ThemeConfigurationLoader();
			$configurationLoader->setParser($yamlParser);
			$configurationLoader->setTheme($this);
			$configurationLoader->setMode(ThemeConfigurationLoader::MODE_FETCH_CONFIGURATION);
			$configurationLoader->setCacheLevel(ThemeConfigurationLoader::CACHE_LEVEL_NO_CACHE);

			$configurationLoader->loadFile($this->getRootDir() . DIRECTORY_SEPARATOR . 'theme.yml');
		}

		return $this->configuration;
	}

	/**
	 * @param ThemeConfiguration $configuration 
	 */
	public function setConfiguration(ThemeConfiguration $configuration)
	{
		$this->configuration = $configuration;
	}

}
