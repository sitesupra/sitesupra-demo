<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Database;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Entity\ThemeLayout;
use Supra\Configuration\Parser\YamlParser;
use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;

/**
 * @Entity
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_idx", columns={"name"})}))
 */
class Theme extends Database\Entity
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
	 * @JoinColumn(name="current_parameter_set_id", referencedColumnName="id")
	 * @var ThemeParameterSet
	 */
	protected $activeParameterSet;

	/**
	 * @var ThemeParameterSet
	 */
	protected $currentParameterSet;

	/**
	 * @var string
	 */
	protected $providerRootDir;

	/**
	 * @var string
	 */
	protected $providerUrlBase;

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
	 * @param string $themeDir 
	 */
	public function setProviderRootDir($providerRootDir)
	{
		$this->providerRootDir = $providerRootDir;
	}

	/**
	 * @return string
	 */
	public function getRootDir()
	{
		return $this->providerRootDir . DIRECTORY_SEPARATOR . $this->getName();
	}

	/**
	 * @return string
	 */
	public function getLayoutDir()
	{
		return $this->getRootDir() . DIRECTORY_SEPARATOR . self::PATH_PART_LAYOUTS;
	}

	/**
	 * @return string
	 */
	public function getGeneratedCssDir()
	{
		return $this->getRootDir() . DIRECTORY_SEPARATOR . self::PATH_PART_GENERATED_CSS;
	}

	/**
	 * @param string $providerUrlBase 
	 */
	public function setProviderUrlBase($providerUrlBase)
	{
		$this->providerUrlBase = $providerUrlBase;
	}

	/**
	 * @return string
	 */
	protected function getUrlBase()
	{
		return $this->providerUrlBase . DIRECTORY_SEPARATOR . $this->getName() . DIRECTORY_SEPARATOR;
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

		$parameterOutputValues = $currentParameterSet->getOutputValues();

		$parameterOutputValues['cssUrl'] = $this->getCurrentCssUrl();

		$parameterOutputValues['name'] = $this->getName();

		return $parameterOutputValues;
	}

	public function generateCssFiles()
	{
		foreach ($this->parameterSets as $parameterSet) {
			/* @var $parameterSet ThemeParameterSet */
			$this->generateCssFileFromLess($parameterSet);
		}
	}

	/**
	 * @param ThemeParameterSet $parameterSet 
	 */
	protected function generateCssFileFromLess(ThemeParameterSet $parameterSet)
	{
		$parameterSet = $this->getParameters($parameterSetName);

		$lessc = new SupraLessC($this->getLayoutRoot() . DIRECTORY_SEPARATOR . 'theme.less');

		$lessc->setRootDir($this->setRootDir());

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
	 * @throws Execption\RuntimeException 
	 */
	protected function generateCssFileFromTemplate($parameterSetName)
	{
		$parameters = $this->getParameters($parameterSetName);

		$cssContent = $this->getCssContent($parameters);

		$this->writeToCssFile($parameterSetName, $cssContent);
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
		return $this->getCssUrl($this->getCurrentParameterSet());
	}

	/**
	 * @return ThemeParameterSet
	 */
	public function getCurrentParameterSet()
	{
		if (emtpy($this->currentParameterSet)) {
			$this->currentParameterSet = $this->activeParameterSet;
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
	 * @return ThemeParameterSet
	 */
	public function getActiveParameterSet()
	{
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

}
