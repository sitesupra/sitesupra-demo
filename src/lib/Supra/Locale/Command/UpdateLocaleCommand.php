<?php

namespace Supra\Locale\Command;

use Supra\Locale;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Supra\Locale\Exception;

/**
 * Master cron command
 */
class UpdateLocaleCommand extends LocaleCommandAbstraction
{

	/**
	 * @var Locale\Entity\Locale
	 */
	protected $locale;

	/**
	 * @return Locale\LocaleInterface
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	/**
	 * Configure
	 */
	protected function configure()
	{
		parent::configure();

		$this->setName('su:locale:update')
				->addArgument('id', InputArgument::REQUIRED, 'Locale ID')
				->addOption('active', null, InputOption::VALUE_REQUIRED)
				->addOption('default', null)
				->addOption('country', null, InputOption::VALUE_REQUIRED)
				->addOption('title', null, InputOption::VALUE_REQUIRED)
				->setDescription('Updates locale.');
	}

	/**
	 */
	protected function doExecute()
	{
		$localeManager = $this->getLocaleManager();

		$this->locale = $this->fetchLocaleFromInput();

		$active = $this->input->getOption('active');
		if ($active !== null) {
			$this->locale->setActive($active);
		}

		$default = $this->input->getOption('default');
		if ($default) {
			$localeManager->setDefaultLocale($this->locale);
		}

		$country = $this->input->getOption('country');
		if ($country !== null) {
			$this->locale->setCountry($country);
		}

		$title = $this->input->getOption('title');
		if ($title !== null) {
			$this->locale->setTitle($title);
		}

		$localeManager->store($this->locale);

		$localeManager->getEntityManager()
				->flush();

		$this->output->writeln('Locale "' . $this->locale->getId() . '" for context "' . $this->locale->getContext() . '" updated.');
	}

}
