<?php

namespace Supra\Locale\Command;

use Supra\Locale;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * 
 */
class SetLocalePropertyCommand extends LocaleCommandAbstraction
{

	/**
	 * @var Locale\Entity\Locale
	 */
	protected $locale;

	/**
	 * Configure
	 */
	protected function configure()
	{
		parent::configure();

		$this->setName('su:locale:set_property')
				->addArgument('id', InputArgument::REQUIRED, 'Locale ID')
				->addOption('name', null, InputOption::VALUE_REQUIRED, 'Property name')
				->addOption('value', null, InputOption::VALUE_REQUIRED, 'Property value')
				->setDescription('Set locale property value.');
	}

	/**
	 * Execute
	 */
	protected function doExecute()
	{
		$this->locale = $this->fetchLocaleFromInput();

		$propertyName = $this->input->getOption('name');
		$propertyValue = $this->input->getOption('value');

		$this->locale->addProperty($propertyName, $propertyValue);

		$this->output->writeln('Set property "' . $propertyName . '" to value "' . $propertyValue . '" for locale "' . $this->locale->getId() . '" for context "' . $this->locale->getContext() . '".');

		$localeManager = $this->getLocaleManager();
		$localeManager->store($this->locale);
		$localeManager->getEntityManager()->flush();
	}

}
