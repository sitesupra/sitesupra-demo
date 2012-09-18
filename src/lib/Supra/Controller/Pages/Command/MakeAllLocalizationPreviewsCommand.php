<?php

namespace Supra\Controller\Pages\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Finder;
use Symfony\Component\Console\Input\ArrayInput;
use Supra\Controller\Pages\Exception;

class MakeAllLocalizationPreviewsCommand extends Command
{

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->entityManager)) {
			$this->entityManager = ObjectRepository::getEntityManager('#cms');
		}

		return $this->entityManager;
	}

	/**
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:pages:make_all_previews')
				->setDescription('Creates new block')
				->addArgument('type', Console\Input\InputArgument::REQUIRED, 'Type (page or template)')
				->addOption('force', null, Console\Input\InputOption::VALUE_NONE, 'Force even if previews exist');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return integer
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->output = $output;

		$application = $this->getApplication();

		$application->setAutoExit(false);

		$localizationType = $input->getArgument('type');

		$force = $input->getOption('force');

		if ($localizationType == 'p') {
			$localizations = $this->findAllPageLocalizations();
		} else if ($localizationType == 't') {
			$localizations = $this->findAllTemplateLocalizations();
		} else {
			throw new Exception\RuntimeException('Bad type.');
		}

		foreach ($localizations as $localization) {
			$this->makeLocalizationPreview($localizationType, $localization->getId(), $localization->getRevisionId(), $force);
		}

		return true;
	}

	/**
	 * @return array
	 */
	protected function findAllPageLocalizations()
	{
		$em = $this->getEntityManager();

		$pageFinder = new Finder\PageFinder($em);

		$localizationFinder = new Finder\LocalizationFinder($pageFinder);

		$localizations = $localizationFinder->getResult();

		return $localizations;
	}

	/**
	 * @return array
	 */
	protected function findAllTemplateLocalizations()
	{
		$em = $this->getEntityManager();

		$templateFinder = new Finder\TemplateFinder($em);

		$localizationFinder = new Finder\LocalizationFinder($templateFinder);

		$localizations = $localizationFinder->getResult();

		return $localizations;
	}

	/**
	 * @param string $localizationType
	 * @param string $localizationId
	 * @param string $revisionId
	 */
	protected function makeLocalizationPreview($localizationType, $localizationId, $revisionId, $force = false)
	{
		$input = new ArrayInput(array(
					'su:pages:make_preview',
					'type' => $localizationType,
					'localization' => $localizationId,
					'revision' => $revisionId,
					'--force' => $force));

		$this->getApplication()->run($input, $this->output);
	}

}
