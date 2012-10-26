<?php

namespace Supra\Locale;

use Supra\Locale\Entity\Locale as LocaleEntity;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class DatabaseBackedLocaleManager extends LocaleManager
{

	/**
	 * @var string
	 */
	protected $context;

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var EntityRepository
	 */
	protected $repository;

	/**
	 * @var LocaleEntity
	 */
	protected $defaultLocale;

	/**
	 * @param string $context
	 */
	public function __construct($context)
	{
		$this->context = $context;
	}

	/**
	 * @return string
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @return EntityManager
	 * @throws Exception\RuntimeException
	 */
	public function getEntityManager()
	{
		if (empty($this->entityManager)) {
			$this->entityManager = ObjectRepository::getEntityManager($this);
		}

		return $this->entityManager;
	}

	/**
	 * @param EntityManager $entityManager
	 */
	public function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @return EntityRepository
	 */
	public function getRepository()
	{
		if (empty($this->repository)) {

			$em = $this->getEntityManager();

			$this->repository = $em->getRepository(LocaleEntity::CN());
		}

		return $this->repository;
	}

	/**
	 * @param EntityRepository $repository
	 */
	public function setRepository(EntityRepository $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * 
	 */
	public function loadAll()
	{
		$findAllCriteria = array('context' => $this->getContext());

		$locales = $this->getRepository()->findBy($findAllCriteria);

		foreach ($locales as $locale) {

			/* @var $locale LocaleEntity */

			parent::add($locale);

			if ($locale->isDefault()) {
				$this->defaultLocale = $locale;
			}
		}
	}

	/**
	 * @param Locale $locale
	 */
	public function add(Locale $locale)
	{
		throw new Exception\RuntimeException('Locale may not be added with add() for database backed locale manager.');
	}

	/**
	 * @param LocaleEntity $locale
	 */
	public function store(LocaleEntity $locale)
	{
		if ($locale->getContext() != $this->getContext()) {
			throw new Exception\RuntimeException('Locale and locale manager contexts do not match. What are You doing?');
		}

		parent::add($locale);

		$em = $this->getEntityManager();

		$em->persist($locale);
	}

	/**
	 * @return LocaleEntity
	 */
	public function getDefaultLocale()
	{
		if (empty($this->locales)) {
			$this->loadAll();
		}

		return $this->defaultLocale;
	}

	/**
	 * @param LocaleEntity $locale
	 */
	public function setDefaultLocale(LocaleEntity $locale)
	{
		$currentDefaultLocale = $this->getDefaultLocale();

		if ( ! empty($currentDefaultLocale)) {
			$currentDefaultLocale->setDefault(false);
		}

		parent::add($locale);
		
		$locale->setDefault(true);

		$this->defaultLocale = $locale;

		$this->store($locale);

		if ( ! empty($currentDefaultLocale)) {
			$this->store($currentDefaultLocale);
		}
	}

	/**
	 * @param LocaleEntity $locale
	 */
	public function activateLocale(LocaleEntity $locale)
	{
		$locale->setActive(true);
	}

	/**
	 * @param LocaleEntity $locale
	 * @throws Exception\RuntimeException
	 */
	public function deactivateLocale(LocaleEntity $locale)
	{
		if ($locale->isDefault()) {
			throw new Exception\RuntimeException('Can not deactivate default locale, set another locale to be default first.');
		}

		$locale->setActive(false);
	}

	/**
	 * @return LocaleEntity
	 */
	public function getNewLocale()
	{
		return new LocaleEntity($this->getContext());
	}

	/**
	 * @return array
	 */
	public function getLocales()
	{
		if (empty($this->locales)) {
			$this->loadAll();
		}

		return parent::getLocales();
	}

}
