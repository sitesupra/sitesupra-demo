<?php

namespace Supra\Translation;

use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Catalogue which saves all unknown translation requests
 */
class RegisteringMessageCatalogue extends MessageCatalogue
{
	public function get($id, $domain = 'messages')
	{
		if ( ! $this->has($id, $domain)) {
			$em = ObjectRepository::getEntityManager($this);
			$translation = new Entity\Translation();
			$translation->setDomain($domain)
					->setLocale($this->getLocale())
					->setName($id)
					->setStatus(Entity\Translation::STATUS_FOUND)
					->setValue($id);

			$uri = \Supra\Request\HttpRequest::guessPathInfo($_SERVER);
			$configuration = ObjectRepository::getComponentConfiguration($this);

			$comment = "Found by address '$uri'";

			if ( ! empty($configuration)) {
				$comment .= ", block '{$configuration->title}'";;
			}

			$translation->setComment($comment);

			$em->persist($translation);
			$em->flush($translation);

			$this->set($id, $id);
		}
		
		return parent::get($id, $domain);
	}

}
