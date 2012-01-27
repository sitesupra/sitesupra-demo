<?php

namespace Supra\Cms\InternalUserManager\Useravatar;

use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Supra\Validator;
use Supra\FileStorage\FileStorageException;
use Supra\FileStorage\ImageProcessor\ImageResizer;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;

/**
 * UseravatarAction
 */
class UseravatarAction extends InternalUserManagerAbstractAction
{

	/**
	 * @TODO hardcoded list for now
	 * @var array
	 */
	public static $sampleAvatars = array(
		array(
			'id' => 1,
			'sizes' => array(
				'32x32' => array('external_path' => '/cms/lib/supra/img/avatars/chrysanthemum_32x32.jpg'),
				'48x48' => array('external_path' => '/cms/lib/supra/img/avatars/chrysanthemum_48x48.jpg'),
				'60x60' => array('external_path' => '/cms/lib/supra/img/avatars/chrysanthemum_60x60.jpg'),
			),
		),
		array(
			'id' => 2,
			'sizes' => array(
				'32x32' => array('external_path' => '/cms/lib/supra/img/avatars/desert_32x32.jpg'),
				'48x48' => array('external_path' => '/cms/lib/supra/img/avatars/desert_48x48.jpg'),
				'60x60' => array('external_path' => '/cms/lib/supra/img/avatars/desert_60x60.jpg'),
			),
		),
		array(
			'id' => 3,
			'sizes' => array(
				'32x32' => array('external_path' => '/cms/lib/supra/img/avatars/hydrangeas_32x32.jpg'),
				'48x48' => array('external_path' => '/cms/lib/supra/img/avatars/hydrangeas_48x48.jpg'),
				'60x60' => array('external_path' => '/cms/lib/supra/img/avatars/hydrangeas_60x60.jpg'),
			),
		),
		array(
			'id' => 4,
			'sizes' => array(
				'32x32' => array('external_path' => '/cms/lib/supra/img/avatars/jellyfish_32x32.jpg'),
				'48x48' => array('external_path' => '/cms/lib/supra/img/avatars/jellyfish_48x48.jpg'),
				'60x60' => array('external_path' => '/cms/lib/supra/img/avatars/jellyfish_60x60.jpg'),
			),
		),
		array(
			'id' => 5,
			'sizes' => array(
				'32x32' => array('external_path' => '/cms/lib/supra/img/avatars/koala_32x32.jpg'),
				'48x48' => array('external_path' => '/cms/lib/supra/img/avatars/koala_48x48.jpg'),
				'60x60' => array('external_path' => '/cms/lib/supra/img/avatars/koala_60x60.jpg'),
			),
		),
		array(
			'id' => 6,
			'sizes' => array(
				'32x32' => array('external_path' => '/cms/lib/supra/img/avatars/lighthouse_32x32.jpg'),
				'48x48' => array('external_path' => '/cms/lib/supra/img/avatars/lighthouse_48x48.jpg'),
				'60x60' => array('external_path' => '/cms/lib/supra/img/avatars/lighthouse_60x60.jpg'),
			),
		),
		array(
			'id' => 7,
			'sizes' => array(
				'32x32' => array('external_path' => '/cms/lib/supra/img/avatars/penguins_32x32.jpg'),
				'48x48' => array('external_path' => '/cms/lib/supra/img/avatars/penguins_48x48.jpg'),
				'60x60' => array('external_path' => '/cms/lib/supra/img/avatars/penguins_60x60.jpg'),
			),
		),
		array(
			'id' => 8,
			'sizes' => array(
				'32x32' => array('external_path' => '/cms/lib/supra/img/avatars/tulips_32x32.jpg'),
				'48x48' => array('external_path' => '/cms/lib/supra/img/avatars/tulips_48x48.jpg'),
				'60x60' => array('external_path' => '/cms/lib/supra/img/avatars/tulips_60x60.jpg'),
			),
		),
	);

	/**
	 * Avatar sizes
	 * @var array 
	 */
	public static $avatarSizes = array(
		'32x32' => array(
			'height' => 32,
			'width' => 32,
		),
		'48x48' => array(
			'height' => 48,
			'width' => 48,
		),
		'60x60' => array(
			'height' => 60,
			'width' => 60,
		),
	);
	
	/**
	 * Overriden so PHP <= 5.3.2 doesn't treat useravatarAction() as a constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * @param string $id
	 * @param string $size
	 * @param $user
	 * @return string 
	 */
	public static function getAvatarExternalPath($user, $size)
	{
		if ( ! $user instanceof User) {
			return null;
		}
		
		if ( ! $user->hasPersonalAvatar()) {
			foreach (self::$sampleAvatars as $sampleAvatar) {
				if ($sampleAvatar['id'] == $user->getAvatar()) {
					return $sampleAvatar['sizes'][$size]['external_path'];
				}
			}
		} else {
			return self::getAvatarsPath() . $user->getId() . '_' . $size;
		}
	}

	public function useravatarAction()
	{
		$this->getResponse()->setResponseData(self::$sampleAvatars);
	}

	public function uploadAction()
	{

		$request = $this->request;
		/* @var $request \Supra\Request\HttpRequest */

		if ( ! $request->isPost()) {
			throw new CmsException(null, 'Post request expected');
		}

		// getting File data
		try {
			$files = $request->getPostFiles();
			$file = $files->get('file');
		} catch (Validator\Exception\RuntimeException $e) {
			throw new CmsException(null, 'Empty file');
		}

		//$em = ObjectRepository::getEntityManager($this);

		// find user
		try {
			$userId = $request->getPostValue('user_id');
			//$userRepo = $em->getRepository('Supra\User\Entity\User');

			$user = $this->userProvider
					->findUserById($userId);
			
			if ( ! $user instanceof User) {
				throw new CmsException(null, 'Could not find a user');
			}
		} catch (Validator\Exception\RuntimeException $e) {
			throw new CmsException(null, 'Empty user');
		}

		$fileStorage = ObjectRepository::getFileStorage($this);

		// checking mime-type
		if ( ! $fileStorage->isMimeTypeImage($file['type'])) {
			throw new CmsException(null, 'Wrong mime type');
		}

		$originalFilePath = $file['tmp_name'];
		$fullPath = self::getAvatarsPath(false);

		if ( ! is_dir($fullPath)) {
			if ( ! mkdir($fullPath, $fileStorage->getFolderAccessMode(), true)) {
				throw new FileStorageException('Could not create avatars folder');
			}
		}
		
		$response = array(
			'sizes' => array(),
		);

		// resizing images
		foreach (self::$avatarSizes as $sizeId => $size) {
			$resizer = new ImageResizer();
			$resizer->setSourceFile($originalFilePath)
					->setOutputQuality(80)
					->setTargetWidth($size['width'])
					->setTargetHeight($size['height'])
					->setCropMode(true);

			$path = $fullPath . $userId . '_' . $sizeId;
			$resizer->setOutputFile($path);
			$resizer->process();
			
			$response['sizes'][$sizeId]['external_path'] = self::getAvatarsPath() . $userId . '_' . $sizeId;
			
		}

		$originalsPath = $fullPath . 'originals' . DIRECTORY_SEPARATOR;

		if ( ! is_dir($originalsPath)) {
			if ( ! mkdir($originalsPath, $fileStorage->getFolderAccessMode(), true)) {
				throw new FileStorageException('Could not create original avatars folder');
			}
		}

		if ( ! move_uploaded_file($originalFilePath, $originalsPath . $userId)) {
			throw new FileStorageException('Could not save original avatar to file system');
		}

		//$em->persist($user);
		$user->setPersonalAvatar(true);
		
		$this->userProvider
				->getAuthAdapter()
				->credentialChange($user);
		
		$this->getResponse()->setResponseData($response);
	}

	/**
	 * Returns array of predefined avatar ids
	 * @return array 
	 */
	public static function getPredefinedAvatarIds()
	{
		$ids = array();
		foreach (self::$sampleAvatars as $avatar) {
			$ids[] = $avatar['id'];
		}

		return $ids;
	}

	private static function getAvatarsPath($forWeb = true)
	{
		$fileStorage = ObjectRepository::getFileStorage(self);
		$externalPath = $fileStorage->getExternalPath();

		if ($forWeb) {
			$externalPath = '/' . str_replace(SUPRA_WEBROOT_PATH, '', $externalPath);
		}

		$fullPath = $externalPath . '_avatars' . DIRECTORY_SEPARATOR;
		return $fullPath;
	}

}
