<?php

namespace Supra\Locale\Command;

use Supra\Locale;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Supra\Locale\Exception;

/**
 * Master cron command
 */
class AddLocaleCommand extends LocaleCommandAbstraction
{

	/**
	 * Configure
	 */
	protected function configure()
	{
		parent::configure();

		$this->setName('su:locale:add')
				->addArgument('id', InputArgument::REQUIRED, 'Locale ID - choose carefully! General format is something like this - "en_EN".')
				->addOption('active', null, InputOption::VALUE_REQUIRED, 'Determines whether locale can be used')
				->addOption('country', null, InputOption::VALUE_REQUIRED, 'Country of locale')
				->addOption('title', null, InputOption::VALUE_REQUIRED, 'Title for locale')
				->setDescription('Add new locale to database.');
	}

	/**
	 * Execute
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output 
	 */
	protected function doExecute()
	{
		$localeManager = $this->getLocaleManager();

		$localeId = $this->input->getArgument('id');

		if ($localeManager->exists($localeId, false)) {
			throw new Exception\RuntimeException('Locale "' . $localeId . '" already exists for context "' . $localeManager->getContext() . '". Use su:locale:update to modify existing locale.');
		}

		$locale = $localeManager->getNewLocale();

		$locale->setId($this->input->getArgument('id'));
		$locale->setCountry($this->input->getOption('country'));
		$locale->setTitle($this->input->getOption('title'));

		$locale->setActive($this->input->getOption('active'));

		$localeManager->store($locale);

		$localeManager->getEntityManager()
				->flush();

		$this->output->writeln('Locale "' . $locale->getId() . '" for context "' . $localeManager->getContext() . '" added to the database.');
	}

}
