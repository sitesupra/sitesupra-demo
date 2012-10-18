<?php

namespace Supra\Translation;

use Supra\ObjectRepository\ObjectRepository;

/**
 * Description of DatabaseLoader
 */
class DatabaseLoader implements \Symfony\Component\Translation\Loader\LoaderInterface
{
	public function load($resource, $locale, $domain = 'messages')
	{
		$search = array(
			'locale' => $locale,
			'domain' => $domain,
		);

		if ( ! is_null($resource)) {
			$search['resource'] = $resource;
		}

		$em = ObjectRepository::getEntityManager($this);
		$records = $em->getRepository(Entity\Translation::CN())
				->findBy($search);

		$catalogue = new RegisteringMessageCatalogue($locale);
		
		foreach ($records as $record) {
			/* @var $record Entity\Translation */
			$id = $record->getName();
			$translation = $record->getValue();
			$catalogue->set($id, $translation, $domain);
		}

		return $catalogue;
	}

}
