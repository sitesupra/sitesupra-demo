<?php

namespace Supra\Controller\Pages\Command;

use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\Layout;
use Supra\Controller\Layout\Processor\TwigProcessor;
use Supra\Controller\Pages\Task\LayoutProcessorTask;
use Supra\Controller\Layout\Exception\LayoutException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input;

/**
 * Adds new layout to database
 */
class LayoutAddCommand extends Command
{

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setName('su:layout:add')
				->setDescription("Adds new layout to database")
				->addArgument('layout', Input\InputArgument::REQUIRED, 'Layout name');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// checking if file exists
		$fileName = $input->getArgument('layout');
		$filePath = SUPRA_TEMPLATE_PATH . $fileName;

		if ( ! file_exists($filePath)) {
			$output->writeln("<error>File does not exist in {$filePath}</error>");
			return;
		}

		// check if layout has been already added
		$em = ObjectRepository::getEntityManager($this);
		$layoutRepo = $em->getRepository(Layout::CN());
		$layout = $layoutRepo->findOneBy(array('file' => $fileName));

		if ($layout instanceof Layout) {
				$output->writeln("<error>Layout {$fileName} already exist in database. Use --force parameter to overwrite layout</error>");
				return;
		}
		// Creating layout
		$twigProcessor = new TwigProcessor();
		$twigProcessor->setLayoutDir(SUPRA_TEMPLATE_PATH);

		$layoutTask = new LayoutProcessorTask();
		$layoutTask->setLayoutId($fileName);
		$layoutTask->setEntityManager($em);
		$layoutTask->setLayoutProcessor($twigProcessor);

		$layoutTask->perform();
		$em->flush();

		$output->writeln("<info>Added {$fileName} layout</info>");
	}

}
