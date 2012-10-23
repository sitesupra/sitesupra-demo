<?php

namespace Supra\Translation;

use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Imports translations from file sources into database
 */
class TranslationImportCommand extends Command
{
	protected function configure()
	{
		$this->setName('su:translation:import')
				->setDescription('Make new translations manageable in CMS')
				->setHelp('Takes the known translations from the files and inserts records in the database so it can be managed in the CMS');
	}

	/**
	 * Execute
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$translator = ObjectRepository::getObject($this, 'Symfony\Component\Translation\Translator');

		if ( ! $translator instanceof Translator) {
			$output->writeln("<error>Only Supra translator instance can be used to import translations</error>");
			return;
		}

		$resources = $translator->getResources();

		$writer = new \Symfony\Component\Translation\Writer\TranslationWriter();
		$writer->addDumper('db', new DatabaseDumper());

		foreach ($resources as $locale => $resourceForLocale) {
			foreach ($resourceForLocale as $resourceData) {

				list($format, $resource, $domain) = $resourceData;

				$loader = $translator->getLoader($format);

				// Skip
				if ($loader instanceof DatabaseLoader) {
					continue;
				}

				$catalogue = $loader->load($resource, $locale, $domain);

				$baseResource = basename($resource);
				$options = array(
					'resource' => $baseResource
				);

				$writer->writeTranslations($catalogue, 'db', $options);
			}
		}

		$output->writeln('<info>Success!</info>');
	}
}
