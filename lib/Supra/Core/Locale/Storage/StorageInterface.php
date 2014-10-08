<?php

namespace Supra\Core\Locale\Storage;
use Supra\Core\Locale\LocaleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Interface for storages for current locale
 */
interface StorageInterface
{
	/**
	 * Store the detected locale
	 * @param Request $request
	 * @param Response $response
	 * @param string $localeIdentifier
	 * @return
	 */
	public function store(Request $request, Response $response, $localeIdentifier);

	/**
	 * Sets locale data provider
	 * @param LocaleInterface $locale
	 */
	public function setLocale(LocaleInterface $locale);
}