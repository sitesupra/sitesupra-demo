<?php

namespace Supra\Controller\Pages\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Configuration\Loader\IniConfigurationLoader;
use Supra\Info;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Entity\Abstraction\Localization;

class MakeLocalizationPreviewCommand extends Command
{

	/**
	 *
	 * @var IniConfigurationLoader
	 */
	protected $iniConfiguration;

	/**
	 *
	 * @var Info
	 */
	protected $systemInformation;

	/**
	 * @return IniConfigurationLoader
	 */
	public function getIniConfiguration()
	{
		if (empty($this->iniConfiguration)) {
			$this->iniConfiguration = ObjectRepository::getIniConfigurationLoader($this);
		}

		return $this->iniConfiguration;
	}

	/**
	 * @return Info
	 */
	public function getSystemInformation()
	{
		if (empty($this->systemInformation)) {
			$this->systemInformation = ObjectRepository::getSystemInfo($this);
		}

		return $this->systemInformation;
	}

	/**
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:pages:make_preview')
				->setDescription('Creates new block')
				->addArgument('type', Console\Input\InputArgument::REQUIRED, 'Type (page or template)')
				->addArgument('localization', Console\Input\InputArgument::REQUIRED, 'Localization Id')
				->addArgument('revision', Console\Input\InputArgument::REQUIRED, 'Revisino Id')
				->addOption('force', null, Console\Input\InputOption::VALUE_NONE, 'Force even if preview exists')
				->addOption('geometry', null, Console\Input\InputOption::VALUE_REQUIRED, 'Geometry for preview (defaults to 80x80)');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return integer
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$localizationType = $input->getArgument('type');
		$localizationId = $input->getArgument('localization');
		$revisionId = $input->getArgument('revision');

		$force = $input->getOption('force');

		if ($force) {

			$previewFilename = $this->makeLocalizationPreview($localizationType, $localizationId, $revisionId);
		} else {

			$previewFilename = Localization::getPreviewFilenameForTypeAndLocalizationAndRevision($localizationType, $localizationId, $revisionId);

			if (file_exists($previewFilename) && ! $force) {
				return $previewFilename;
			} else {
				$previewFilename = $this->makeLocalizationPreview($localizationType, $localizationId, $revisionId);
			}
		}

		$output->writeln($localizationType . ' ' . $localizationId . ' ' . $revisionId . ': ' . $previewFilename . ' ' . filesize($previewFilename));

		return true;
	}

	/**
	 * @param string $localizationType
	 * @param string $localizationId
	 * @param string $revisionId
	 */
	public function makeLocalizationPreview($localizationType, $localizationId, $revisionId, $geometry = '80x80')
	{
		clearstatcache();
		
		$iniConfiguration = $this->getIniConfiguration();
		$systemInfo = $this->getSystemInformation();

		$previewFilename = Localization::getPreviewFilenameForTypeAndLocalizationAndRevision($localizationType, $localizationId, $revisionId);

		$wkhtmltoimagePath = $iniConfiguration->getValue('system', 'wkhtmltoimage_path');
		$gmPath = $iniConfiguration->getValue('system', 'gm_path');

		$siteHost = $systemInfo->getWebserverHostAndPort();

		$sourcePath = join('/', array('__view__', $localizationType, $localizationId, $revisionId));
		$sourceUrl = http_build_url(false, array('host' => $siteHost, 'path' => $sourcePath));

		$temporaryFilename = tempnam(sys_get_temp_dir(), 'preview-' . basename($previewFilename));

		$command = array(
			$wkhtmltoimagePath . ' --format jpg --width 1280 --height 1280 --crop-h 1280 ' . escapeshellarg($sourceUrl) . ' ' . escapeshellarg($temporaryFilename),
			'; ',
			$gmPath . ' mogrify -resize ' . escapeshellarg($geometry) . ' ' . escapeshellarg($temporaryFilename),
		);

		\Log::debug('FINAL FILENAME: ', $previewFilename);
		\Log::debug('COMMAND: ', $command);

		$output = array();
		$exitCode = 0;
		
		exec(join('', $command), $output, $exitCode);

		if ( ! file_exists($temporaryFilename)) {
			\Log::error('COMMAND: ', $command);
			\Log::error('OUTPUT: ', $output);
			throw new Exception\RuntimeException('Intermediate file not generated.');
		}

		if ( ! ($previewImageSize = getimagesize($temporaryFilename))) {
			\Log::error('COMMAND: ', $command);
			\Log::error('OUTPUT: ', $output);
			throw new Exception\RuntimeException('Intermediate file not image.');
		}

		if ( ! rename($temporaryFilename, $previewFilename)) {
			\Log::error('COMMAND: ', $command);
			\Log::error('OUTPUT: ', $output);
			throw new Exception\RuntimeException('Could not move intermediate ' . $temporaryFilename . ' to assets ' . $previewFilename . '.');
		}

		//\Log::debug('PREVIEW IMAGE FILENAME: ', $previewFilename);
		//\Log::debug('PREVIEW IMAGE SIZE: ', $previewImageSize);

		return $previewFilename;
	}

}
