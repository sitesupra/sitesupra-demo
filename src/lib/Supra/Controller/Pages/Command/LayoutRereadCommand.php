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

/**
 * Rereads known layouts and updates their information
 */
class LayoutRereadCommand extends Command
{
	/**
     * Configures the current command.
     */
    protected function configure()
    {
		$this->setName('su:layout:update')
				->setDescription("Rereads the known layouts and updates their information in database")
				->addOption('delete', null, null, 
						'Whether to remove unknown placeholders');
    }
	
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = ObjectRepository::getEntityManager($this);
		$layoutDao = $entityManager->getRepository(Layout::CN());
		$layouts = $layoutDao->findAll();
		
		//TODO: fixed processor class us used now, should be configurable somehow
		$layoutProcessor = new TwigProcessor();
		$layoutProcessor->setLayoutDir(SUPRA_TEMPLATE_PATH);
		
		$layoutUpdateTask = new LayoutProcessorTask();
		$layoutUpdateTask->setEntityManager($entityManager);
		$layoutUpdateTask->setLayoutProcessor($layoutProcessor);
		
		if ($input->getOption('delete')) {
			$layoutUpdateTask->removePlaceHolders();
		}
		
		foreach ($layouts as $layout) {
			/* @var $layout Layout */
			$file = $layout->getFile();
			$layoutUpdateTask->setLayoutId($file);
			
			try {
				$layoutUpdateTask->perform();
			} catch (LayoutException $e) {
				/* @var $e \Exception */
				$output->writeln("<error>Exception was caught: {$e->getMessage()}</error>");
			}
		}
		
		$entityManager->flush();
		
		$message = $layoutUpdateTask->getStatisticsMessage();
		
		$output->writeln("<info>{$message}</info>");
    }
}
