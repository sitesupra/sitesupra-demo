<?php

use Supra\Upgrade\Script\UpgradeScriptAbstraction;
use \PDO;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Supra\ObjectRepository\ObjectRepository;

class S000_UpgradeLinks extends UpgradeScriptAbstraction
{

	public function validate()
	{
		return true;
	}

	public function upgrade()
	{
		$output = $this->getOutput();

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

		$output->writeln('Done. Updated rows by scheme: ');

		foreach ($statistics as $scheme => $rowCount) {
			$output->writeln(sprintf(' %-7s - %3d rows', $scheme, $rowCount));
		}
	}

	public function rollback()
	{
		
	}

}
