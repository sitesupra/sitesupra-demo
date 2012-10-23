<?php

namespace Supra\Translation;

use Symfony\Component\Translation\Dumper\DumperInterface;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Description of DatabaseDumper
 */
class DatabaseDumper implements DumperInterface
{
	public function dump(MessageCatalogue $messages, $options = array())
	{
		$resource = null;
		if (isset($options['resource'])) {
			$resource = $options['resource'];
		}

		$em = ObjectRepository::getEntityManager($this);
		$locale = $messages->getLocale();

		foreach ($messages->getDomains() as $domain) {
			$array = $messages->all($domain);

			foreach ($array as $name => $value) {
				$record = $em->getRepository(Entity\Translation::CN())
					->findOneBy(array(
						'locale' => $locale,
						'domain' => $domain,
						'name' => $name,
					));

				if (empty($record)) {
					$record = new Entity\Translation();
					$record->setStatus(Entity\Translation::STATUS_IMPORT)
							->setLocale($locale)
							->setDomain($domain)
							->setName($name);

					$em->persist($record);
				}

				if ( ! is_null($resource)) {
					$record->setResource($resource);
				}
				
				switch ($record->getStatus()) {
					case Entity\Translation::STATUS_MANUAL:
						$record->setStatus(Entity\Translation::STATUS_CHANGED);
						break;
					case Entity\Translation::STATUS_FOUND:
						$record->setStatus(Entity\Translation::STATUS_IMPORT);
						break;
				}

				// Can change value
				if ($record->getStatus() == Entity\Translation::STATUS_IMPORT) {
					$record->setValue($value);
				}

				$em->flush($record);
			}
		}
	}
}
