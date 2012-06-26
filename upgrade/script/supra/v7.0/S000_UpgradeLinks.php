<?php

use Supra\Upgrade\Script\UpgradeScriptAbstraction;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Upgrade\Script\SkippableOnError;
use Supra\Upgrade\Plugin\DependencyValidationPlugin;

class S000_UpgradeLinks extends UpgradeScriptAbstraction
{
	
	public function validate()
	{
		$dependencies = array(
			LinkReferencedElement::CN()
		);

		$em = ObjectRepository::getEntityManager($this);

		$validator = new DependencyValidationPlugin($em, $dependencies);
		$result = $validator->execute();
		
		return $result;
	}
	
	public function upgrade()
	{
		$output = $this->getOutput();

		$schemas = array(
			PageController::SCHEMA_PUBLIC,
			PageController::SCHEMA_DRAFT,
			PageController::SCHEMA_AUDIT
		);

		$statistics = array_fill_keys($schemas, 0);

		$linkEntity = LinkReferencedElement::CN();
		$localizationEntity = Localization::CN();

		foreach ($schemas as $schema) {

			$entityManager = ObjectRepository::getEntityManager($schema);

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

				$statistics[$schema] += $updateStatement->rowCount();
			}
		}

		$output->writeln('Updated rows by schema: ');

		foreach ($statistics as $schema => $rowCount) {
			$output->writeln(sprintf(' %-7s - %3d rows', $schema, $rowCount));
		}
	}

}
