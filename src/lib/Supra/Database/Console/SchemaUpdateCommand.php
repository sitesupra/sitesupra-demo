<?php

namespace Supra\Database\Console;

use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Events;
use Supra\Controller\Pages\Listener\VersionedAnnotationListener;

/**
 * Schema update command
 *
 */
class SchemaUpdateCommand extends SchemaAbstractCommand
{
	
	/**
	 * Configure
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:schema:update')
				->setDescription('Updates ORM schema.')
				->setHelp('Updates ORM schema.')
				->setDefinition(array(
					new InputOption(
						'force', null, InputOption::VALUE_NONE,
						'Causes the generated SQL statements to be physically executed against your database.'
					),
				));
	}

	/**
	 * Execute command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output 
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<comment>ATTENTION</comment>: This operation should not be executed in a production environment.');
		$output->writeln('');
		
        $force = (true === $input->getOption('force'));
		
		if ($force) {
			$output->writeln('Updating database schema...');

			foreach ($this->entityManagers as $em) {
				
				if ($em->_mode == 'History') {
					$listeners = $em->getEventManager()->getListeners(Events::loadClassMetadata);
					foreach ($listeners as $listener) {
						if ($listener instanceof VersionedAnnotationListener) {
							$listeners = $em->getEventManager()->removeEventListener(Events::loadClassMetadata, $listener);
						}
					}
					$listener = new VersionedAnnotationListener();
					$listener->setAsCreateCall();
					$em->getEventManager()->addEventListener(array(Events::loadClassMetadata), $listener);
				}
				
				$output->write($em->_mode);
				$metadatas = $em->getMetadataFactory()->getAllMetadata();
				$schemaTool = new SchemaTool($em);
				$sqls = $schemaTool->getUpdateSchemaSql($metadatas, true);
				if (! empty($sqls)) {
					$schemaTool->updateSchema($metadatas, true);
					$output->writeln("\t - " . count($sqls) . ' queries');
				} else {
					$output->writeln("\t - nothing to update");
				}
				
				if ($em->_mode == 'History') {
					$listeners = $em->getEventManager()->getListeners(Events::loadClassMetadata);
					foreach ($listeners as $listener) {
						if ($listener instanceof VersionedAnnotationListener && $listener->isOnCreateMode()) {
							$listeners = $em->getEventManager()->removeEventListener(Events::loadClassMetadata, $listener);
						}
					}
				}
				
			}

			$output->writeln('Database schema updated successfully!');
		} else {
            $output->writeln('Please run the operation by passing one of the following options:');
            $output->writeln(sprintf('    <info>%s --force</info> to execute the command', $this->getName()));
		}
	}
	
}
