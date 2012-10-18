<?php

namespace Supra\Translation;

/**
 * Translator which changes the message catalogue
 */
class Translator extends \Symfony\Component\Translation\Translator
{
	private $loaders = array();
    private $resources = array();

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
}
