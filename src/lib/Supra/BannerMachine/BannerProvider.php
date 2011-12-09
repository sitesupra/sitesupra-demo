<?php

namespace Supra\BannerMachine;

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\BannerMachine\Entity\Banner;
use Doctrine\Common\Collections\ArrayCollection;

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
	protected $sizeTypes;

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
	public function getSizeTypes()
	{
		return $this->sizeTypes;
	}

	public function setSizeTypes($sizeTypes)
	{
		$this->sizeTypes = $sizeTypes;
	}

	/**
	 * @param string SizeType
	 * @return ArrayCollection
	 */
	public function getBanners($sizeType)
	{
		if (empty($this->sizeTypes[$sizeType->getId()])) {
			throw new Exception\RuntimeException('Unknown banner size type "' . $sizeType->getId() . '".');
		}

		$criteria = array(
				'sizeTypeId' => $sizeType->getId()
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
			throw new Exception\RuntimeException('Banner not found for id "' . $bannerId . '"');
		}

		return $banner;
	}

	/**
	 * @param string $sizeTypeId
	 * @return SizeType
	 */
	public function getSizeType($sizeTypeId)
	{
		if (empty($this->sizeTypes[$sizeTypeId])) {
			throw new Exception\RuntimeException('Unknown banner size type "' . $sizeTypeId . '".');
		}

		return $this->sizeTypes[$sizeTypeId];
	}

}
