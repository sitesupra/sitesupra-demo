<?php

namespace Supra\BannerMachine;

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\BannerMachine\Entity\Banner;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\BannerMachine\BannerType\BannerTypeAbstraction;
use Supra\BannerMachine\Exception\BannerNotFoundException;
use DateTime;
use Supra\Locale\LocaleInterface;
use Supra\FileStorage\Entity\Abstraction\File;

class BannerProvider
{

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var EntityRepository
	 */
	protected $er;

	/**
	 * @var array
	 */
	protected $types;

	/**
	 * @var string
	 */
	protected $redirectorPath;

	public function getEntityManager()
	{
		if (empty($this->em)) {
			$this->em = ObjectRepository::getEntityManager($this);
		}

		return $this->em;
	}

	public function getEntityRepository()
	{
		if (empty($this->er)) {
			$this->er = $this->getEntityManager()->getRepository(Banner::CN());
		}

		return $this->er;
	}

	/**
	 * @return array
	 */
	public function getTypes()
	{
		return $this->types;
	}

	public function setTypes($types)
	{
		$this->types = $types;
	}

	/**
	 * @param BannerTypeAbstraction $type
	 * @return ArrayCollection
	 */
	public function getBanners($type, $localeId)
	{
		if (empty($this->types[$type->getId()])) {
			throw new Exception\RuntimeException('Unknown banner type "' . $type->getId() . '".');
		}

		$criteria = array(
			'typeId' => $type->getId(),
			'localeId' => $localeId
		);

		$banners = $this->getEntityRepository()->findBy($criteria);

		return $banners;
	}

	/**
	 * @param Banner $banner 
	 */
	public function store(Banner $banner)
	{
		$this->getEntityManager()->persist($banner);
		$this->getEntityManager()->flush();
	}

	/**
	 * @param Banner $banner 
	 */
	public function remove(Banner $banner)
	{
		$this->getEntityManager()->remove($banner);
		$this->getEntityManager()->flush();
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $id 
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @param string $bannerId
	 * @return Banner
	 */
	public function getBanner($bannerId)
	{
		$banner = $this->getEntityRepository()->find($bannerId);

		if (empty($banner)) {
			throw new Exception\BannerNotFoundException('Banner Id: "' . $bannerId . '"');
		}

		return $banner;
	}

	/**
	 * @param string $typeId
	 * @return boolean
	 */
	public function hasType($typeId)
	{
		$has = ( ! empty($this->types[$typeId]));

		return $has;
	}

	/**
	 * @param string $typeId
	 * @return BannerTypeAbstraction
	 */
	public function getType($typeId)
	{
		if (empty($this->types[$typeId])) {
			throw new Exception\RuntimeException('Unknown banner type "' . $typeId . '".');
		}

		return $this->types[$typeId];
	}

	function intervals($items)
	{

		$weightSum = 0;

		foreach ($items as $n => $weight) {
			$weightSum += $weight;
		}

		$r = rand(1, $weightSum);

		$runningWeight = 0;
		foreach ($items as $n => $weight) {

			$runningWeight += $weight;

			if ($r <= $runningWeight) {
				return $n;
			}
		}
	}

	public function getRandomBanner(BannerTypeAbstraction $bannerType, LocaleInterface $locale)
	{
		if (empty($this->types[$bannerType->getId()])) {
			throw new Exception\RuntimeException('Unknown banner type "' . $bannerType->getId() . '".');
		}

		$now = new DateTime('now');

		$q = $this->getEntityManager()->createQuery();
		$q->setDQL('SELECT b.id, b.priority + 1 AS priority FROM ' . Banner::CN() .
				' b WHERE b.localeId = :localeId 
					AND ( (b.scheduledFrom IS NULL AND b.scheduledTill IS NULL) 
						OR (:now BETWEEN b.scheduledFrom AND b.scheduledTill) ) 
					AND b.typeId = :typeId 
					AND b.status = :status 
					ORDER BY b.priority');
		$q->setParameter('typeId', $bannerType->getId());
		$q->setParameter('status', Banner::STATUS_ACTIVE);
		$q->setParameter('localeId', $locale->getId());
		$q->setParameter('now', $now);
		$banners = $q->getArrayResult();

		$priorityWeight = 0;
		foreach ($banners as $bannerData) {
			$priorityWeight += $bannerData['priority'];
		}

		$r = rand(1, $priorityWeight);

		$bannerId = null;

		$runningWeight = 0;
		foreach ($banners as $bannerData) {

			$runningWeight += $bannerData['priority'];

			if ($r <= $runningWeight) {
				$bannerId = $bannerData['id'];
				break;
			}
		}

		if (empty($bannerId)) {
			throw new BannerNotFoundException();
		}

		$chosenBanner = $this->getBanner($bannerId);

		return $chosenBanner;
	}

	public function increaseBannerExposureCounter(Banner $banner)
	{
		$updateExposureCountQuery = $this->getEntityManager()->createQuery();

		$updateExposureCountQuery->setDQL('UPDATE ' . Banner::CN() . ' b SET b.exposureCount = b.exposureCount + 1 WHERE b.id = :bannerId');
		$updateExposureCountQuery->setParameter('bannerId', $banner->getId());

		$updateExposureCountQuery->execute();
	}

	public function increaseBannerClickCounter(Banner $banner)
	{
		$updateExposureCountQuery = $this->getEntityManager()->createQuery();

		$updateExposureCountQuery->setDQL('UPDATE ' . Banner::CN() . ' b SET b.clickCount = b.clickCount + 1 WHERE b.id = :bannerId');
		$updateExposureCountQuery->setParameter('bannerId', $banner->getId());

		$updateExposureCountQuery->execute();
	}

	public function setRedirectorPath($redirectorPath)
	{
		$this->redirectorPath = $redirectorPath;
	}

	public function getRedirectorPath()
	{
		return $this->redirectorPath;
	}

	public function getBannersByFile(File $file)
	{
		$criteria = array('file' => $file->getId());

		$imageBannerEr = $this->getEntityManager()->getRepository(Entity\ImageBanner::CN());
		$imageBanners = $imageBannerEr->findBy($criteria);

		$flashBannerEr = $this->getEntityManager()->getRepository(Entity\FlashBanner::CN());
		$flashBanners = $flashBannerEr->findBy($criteria);

		return $imageBanners + $flashBanners;
	}

}
