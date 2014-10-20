<?php

namespace Supra\Package\Cms\Pages\Request;

use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;

/**
 * Request object for edit mode requests
 */
class PageRequestEdit extends PageRequest
{
	public function getPlaceHolderSet()
	{
		if ($this->placeHolderSet) {
			return $this->placeHolderSet;
		}

		parent::getPlaceHolderSet();

		$this->createMissingPlaceHolders();

		return $this->placeHolderSet;
	}

	/**
	 * @inheritDoc
	 */
	protected function getEntityManager()
	{
		return $this->container['doctrine.entity_managers.cms'];
	}

	/**
	 * @return void
	 * @throws \LogicException
	 */
	private function createMissingPlaceHolders()
	{
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();

		if (empty($layoutPlaceHolderNames)) {
			return null;
		}

		if ($this->placeHolderSet === null) {
			throw new \LogicException('Expecting place holder set to be created already.');
		}

		$entityManager = $this->getEntityManager();
		$localization = $this->getLocalization();

		$finalPlaceHolders = $this->placeHolderSet->getFinalPlaceHolders();
		$parentPlaceHolders = $this->placeHolderSet->getParentPlaceHolders();

		$isDirty = false;

		foreach ($layoutPlaceHolderNames as $name) {

			if ($finalPlaceHolders->offsetExists($name)) {
				continue;
			}

			$placeHolder = null;
			$parentPlaceHolder = null;

			// Check if page doesn't have it already set locally
			$knownPlaceHolders = $localization->getPlaceHolders();

			if ($knownPlaceHolders->offsetExists($name)) {
				$placeHolder = $knownPlaceHolders->offsetGet($name);
			} else {
				
				$parentPlaceHolder = $parentPlaceHolders->getLastByName($name);

				// Creates with unlocked blocks copy
				$placeHolder = PlaceHolder::factory($localization, $name, $parentPlaceHolder);
				$placeHolder->setMaster($localization);

				$entityManager->persist($placeHolder);

				$isDirty = true;
			}

			$this->placeHolderSet->append($placeHolder);
		}

		if ($isDirty) {
			$this->getEntityManager()->flush();
		}
	}
}
