<?php

namespace Supra\Package\Cms\Entity\ReferencedElement;

use Doctrine\ORM\EntityManager;
use Supra\Core\Locale\LocaleInterface;
use Supra\Package\Cms\Uri\Path;
use Supra\Package\Cms\Entity\PageLocalization;

class ReferencedElementUtils
{
	public static function getLinkReferencedElementTitle(
			LinkReferencedElement $element,
			EntityManager $entityManager,
			LocaleInterface $currenctLocale
	) {
		// if has title set explicitly, will return it.
		$title = $element->getTitle();
		if (! empty($title)) {
			return $title;
		}

		// if no title, try to fetch it from referenced element.
		switch ($element->getResource()) {
			case LinkReferencedElement::RESOURCE_LINK:
				// title can't be obtained.
				return null;
			case LinkReferencedElement::RESOURCE_PAGE:

				$localization = $entityManager->getRepository(PageLocalization::CN())
						->findOneBy(array(
							'master' => $element->getPageId(),
							'locale' => $currenctLocale->getId(),
				));
				/* @var $localization PageLocalization */

				if ($localization !== null) {
					return $localization->getTitle();
				}

				break;
			case LinkReferencedElement::RESOURCE_FILE:
				// @FIXME: get the filestorage somehow, or use File entity?
				throw new \Exception('Implement me, I have no file storage here.');
				break;
			default:
				throw new \UnexpectedValueException(
						"Unrecognized resource type [{$element->getResource()}]."
				);
		}

		return null;
		// @FIXME: 'email' resource type handling?
	}

	public static function getLinkReferencedElementUrl(
			LinkReferencedElement $element,
			EntityManager $entityManager,
			LocaleInterface $currenctLocale
	) {
		
		switch ($element->getResource()) {
			case LinkReferencedElement::RESOURCE_LINK:
				return $element->getHref();
			case LinkReferencedElement::RESOURCE_PAGE:

				$localization = $entityManager->getRepository(PageLocalization::CN())
						->findOneBy(array(
							'master' => $element->getPageId(),
							'locale' => $currenctLocale->getId(),
				));
				/* @var $localization PageLocalization */

				if ($localization !== null) {
					// @TODO: allow to affect the path somehow?
					return $localization->getFullPath(Path::FORMAT_LEFT_DELIMITER);
				}

				break;
			case LinkReferencedElement::RESOURCE_FILE:
				// @FIXME: get the filestorage somehow, or use File entity?
				throw new \Exception('Implement me, I have no file storage here.');
				break;
			default:
				throw new \UnexpectedValueException(
						"Unrecognized resource type [{$element->getResource()}]."
				);
		}

		return null;
		// @TODO: 'email' resource type handling?
	}
}