<?php

namespace Supra\Package\Cms\Entity;

/**
 * @Entity
 * @method ApplicationPage getMaster()
 */
class ApplicationLocalization extends PageLocalization
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::APPLICATION_DISCR;
	
//	/**
//     * @OneToMany(
//	 *		targetEntity="Supra\Package\Cms\Entity\ApplicationLocalizationParameter", 
//	 *		mappedBy="localization", 
//	 *		cascade={"persist", "remove"}, 
//	 *		indexBy="name",
//	 *		fetch="LAZY"
//	 * )
//	 * 
//     * @var \Doctrine\Common\Collections\Collection
//     */
//	protected $parameters;
	
	/**
	 * {@inheritdoc}
	 */
	public function __construct($locale) 
	{
		parent::__construct($locale);	
//		$this->parameters = new Collections\ArrayCollection();
	}
	
//	/**
//	 * @return \Doctrine\Common\Collections\Collection
//	 */
//	public function getParameterCollection()
//	{
//		return $this->parameters;
//	}
	
//	/**
//	 * @param \Supra\Controller\Pages\Entity\ApplicationLocalizationParameter $parameter
//	 */
//	public function addParameterToCollection(ApplicationLocalizationParameter $parameter)
//	{
//		$this->parameters->set($parameter->getName(), $parameter);
//	}
	
//	/**
//	 * @param string $name
//	 * @return \Supra\Controller\Pages\Entity\ApplicationLocalizationParameter | null
//	 */
//	public function getParameter($name)
//	{
//		if ($this->parameters->offsetExists($name)) {
//			return $this->parameters
//					->offsetGet($name);
//		}
//		
//		return null;
//	}
	
//	/**
//	 * @param string $name
//	 * @return \Supra\Controller\Pages\Entity\ApplicationLocalizationParameter
//	 */
//	public function getOrCreateParameter($name)
//	{
//		$parameter = $this->getParameter($name);
//		
//		if ($parameter === null) {	
//			$parameter = new ApplicationLocalizationParameter($name);
//			$parameter->setApplicationLocalization($this);
//		}
//		
//		return $parameter;
//	}
	
//	/**
//	 * @param string $name
//	 * @param mixed $default
//	 * @return mixed
//	 */
//	public function getParameterValue($name, $default = null)
//	{
//		if ($this->parameters->offsetExists($name)) {
//			return $this->parameters
//					->offsetGet($name)
//					->getValue();
//		}
//		
//		return $default;
//	}
}
