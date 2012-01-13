<?php

namespace Supra\Controller\Pages\Command;

use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Controller\Pages\PageController;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use PDO;
use Supra\Controller\Pages\Entity\Abstraction\Localization;

/**
 * Upgrades link referenced elements. Previosuly page localization ID was stored,
 * not master ID should be stored to avoid issues on page cloning to another locale.
 */
class UpgradeLinkReferencedElements extends Command
{
	/**
     * Configures the current command.
     */
    protected function configure()
    {
		$this->setName('su:pages:upgrade_links')
				->setDescription("Upgrades link referenced elements to the new format.");
    }
	
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
    {
		$schemes = array(
			PageController::SCHEMA_PUBLIC,
			PageController::SCHEMA_DRAFT,
			PageController::SCHEMA_AUDIT
		);
		
		$statistics = array_fill_keys($schemes, 0);
		
		$linkEntity = LinkReferencedElement::CN();
		$localizationEntity = Localization::CN();
		
		foreach ($schemes as $scheme) {
			
			$entityManager = ObjectRepository::getEntityManager($scheme);
			
			$linkMetadata = $entityManager->getClassMetadata($linkEntity);
			$linkTableName = $linkMetadata->getTableName();
			
			$localizationMetadata = $entityManager->getClassMetadata($localizationEntity);
			$localizationTableName = $localizationMetadata->getTableName();
			
			$selectSql = "SELECT DISTINCT l.id, l.master_id FROM $linkTableName link 
					JOIN $localizationTableName l ON l.id = link.pageId";
			
			$conn = $entityManager->getConnection();
			
			$selectStatement = $conn->prepare($selectSql);
			$selectStatement->execute();
			
			while ($row = $selectStatement->fetch(PDO::FETCH_ASSOC)) {
				$updateSql = "UPDATE $linkTableName SET pageId = :master_id WHERE pageId = :id";
				$updateStatement = $conn->prepare($updateSql);
				$updateStatement->execute($row);
				
				$statistics[$scheme] += $updateStatement->rowCount();
			}
		}
		
		$output->writeln("Done. Updated rows by scheme:");
		
		foreach ($statistics as $scheme => $rowCount) {
			$output->writeln(sprintf(' %-7s - %3d rows', $scheme, $rowCount));
		}
	}
}
