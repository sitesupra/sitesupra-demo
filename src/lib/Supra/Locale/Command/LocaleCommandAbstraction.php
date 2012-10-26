<?php

namespace Supra\Locale\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Supra\Locale\Exception;
use Supra\Locale;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Supra\Locale\Entity\Locale as LocaleEntity;

/**
 * Abstraction for su:locale:* commands
 */
abstract class LocaleCommandAbstraction extends SymfonyCommand
{

	/**
	 * @var Locale\DatabaseBackedLocaleManager
	 */
	protected $localeManager;

	/**
	 * @var InputInterface
	 */
	protected $input;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * @return Locale\LocaleManager
	 */
	public function getLocaleManager()
	{
		if (empty($this->localeManager)) {

			$context = $this->input->getOption('context');

			$this->localeManager = new Locale\DatabaseBackedLocaleManager($context);
		}

		return $this->localeManager;
	}

	/**
	 * @param Locale\DatabaseBackedLocaleManager $localeManager
	 */
	public function setLocaleManager(Locale\DatabaseBackedLocaleManager $localeManager)
	{
		$this->localeManager = $localeManager;
	}

	/**
	 * @return Locale\Locale
	 */
	protected function fetchLocaleFromInput()
	{
		$localeManager = $this->getLocaleManager();

		$localeId = $this->input->getArgument('id');

		if ( ! $localeManager->exists($localeId)) {
			throw new Exception\RuntimeException('Locale "' . $localeId . '" not found.');
		}

		return $localeManager->getLocale($localeId);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->input = $input;
		$this->output = $output;

		$this->doExecute();
	}

	/**
	 * 
	 */
	protected function configure()
	{
		$this->addOption('context', null, InputOption::VALUE_REQUIRED, 'Locale manager context', LocaleEntity::DEFAULT_CONTEXT);
	}

	abstract protected function doExecute();
}
