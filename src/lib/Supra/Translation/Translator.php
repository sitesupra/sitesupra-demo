<?php

namespace Supra\Translation;

/**
 * Translator which changes the message catalogue
 */
class Translator extends \Symfony\Component\Translation\Translator
{
	private $loaders = array();
    private $resources = array();

	/**
	 * @var string
	 */
	private $currentLocale = null;
	
	
	public function trans($id, array $parameters = array(), $domain = 'messages', $locale = null)
	{
		$locale = (is_null($locale) ? $this->getCurrentLocale() : $locale);
		
		if ($id instanceof TranslatedString) {
			return (string) $id;
		}

		return parent::trans($id, $parameters, $domain, $locale);
	}

	public function transChoice($id, $number, array $parameters = array(), $domain = 'messages', $locale = null)
	{
		$locale = (is_null($locale) ? $this->getCurrentLocale() : $locale);
		
		// Skip catalogues if is already translated
		if ($id instanceof TranslatedString) {
			if ( ! isset($locale)) {
				$locale = $this->getLocale();
			}

			return strtr($this->selector->choose((string) $id, (int) $number, $locale), $parameters);
		}

		return parent::transChoice($id, $number, $parameters, $domain, $locale);
	}


	public function addLoader($format, \Symfony\Component\Translation\Loader\LoaderInterface $loader)
	{
		$this->loaders[$format] = $loader;
		parent::addLoader($format, $loader);
	}

	public function addResource($format, $resource, $locale, $domain = 'messages')
	{
		$this->resources[$locale][] = array($format, $resource, $domain);
		parent::addResource($format, $resource, $locale, $domain);
	}

	public function getLoader($format)
	{
		if ( ! isset($this->loaders[$format])) {
			throw new \RuntimeException(sprintf('The "%s" translation loader is not registered.', $format));
		}

		return $this->loaders[$format];
	}

	public function getResources()
	{
		return $this->resources;
	}

	protected function loadCatalogue($locale)
	{
		parent::loadCatalogue($locale);

		if ( ! $this->catalogues[$locale] instanceof RegisteringMessageCatalogue) {
			$registeringCatalogue = new RegisteringMessageCatalogue($locale);
			$registeringCatalogue->addCatalogue($this->catalogues[$locale]);
			$this->catalogues[$locale] = $registeringCatalogue;
		}
	}
	
	/**
	 * 
	 * @return string
	 */
	protected function getCurrentLocale()
	{
		if (is_null($this->currentLocale)) {
			$localeManager = \Supra\ObjectRepository\ObjectRepository::getLocaleManager($this);
			$this->currentLocale = $localeManager->getCurrent()
					->getId();
		}
		
		return $this->currentLocale;
	}
}
