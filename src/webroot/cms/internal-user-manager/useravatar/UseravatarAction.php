<?php

namespace Supra\Cms\InternalUserManager\Useravatar;

use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;

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
		0 =>
		array(
			'id' => 1,
			'sizes' =>
			array(
				'32x32' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/chrysanthemum_32x32.jpg',
				),
				'48x48' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/chrysanthemum_48x48.jpg',
				),
				'60x60' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/chrysanthemum_60x60.jpg',
				),
			),
		),
		1 =>
		array(
			'id' => 2,
			'sizes' =>
			array(
				'32x32' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/desert_32x32.jpg',
				),
				'48x48' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/desert_48x48.jpg',
				),
				'60x60' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/desert_60x60.jpg',
				),
			),
		),
		2 =>
		array(
			'id' => 3,
			'sizes' =>
			array(
				'32x32' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/hydrangeas_32x32.jpg',
				),
				'48x48' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/hydrangeas_48x48.jpg',
				),
				'60x60' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/hydrangeas_60x60.jpg',
				),
			),
		),
		3 =>
		array(
			'id' => 4,
			'sizes' =>
			array(
				'32x32' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/jellyfish_32x32.jpg',
				),
				'48x48' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/jellyfish_48x48.jpg',
				),
				'60x60' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/jellyfish_60x60.jpg',
				),
			),
		),
		4 =>
		array(
			'id' => 5,
			'sizes' =>
			array(
				'32x32' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/koala_32x32.jpg',
				),
				'48x48' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/koala_48x48.jpg',
				),
				'60x60' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/koala_60x60.jpg',
				),
			),
		),
		5 =>
		array(
			'id' => 6,
			'sizes' =>
			array(
				'32x32' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/lighthouse_32x32.jpg',
				),
				'48x48' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/lighthouse_48x48.jpg',
				),
				'60x60' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/lighthouse_60x60.jpg',
				),
			),
		),
		6 =>
		array(
			'id' => 7,
			'sizes' =>
			array(
				'32x32' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/penguins_32x32.jpg',
				),
				'48x48' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/penguins_48x48.jpg',
				),
				'60x60' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/penguins_60x60.jpg',
				),
			),
		),
		7 =>
		array(
			'id' => 8,
			'sizes' =>
			array(
				'32x32' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/tulips_32x32.jpg',
				),
				'48x48' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/tulips_48x48.jpg',
				),
				'60x60' =>
				array(
					'external_path' => '/cms/lib/supra/img/avatars/tulips_60x60.jpg',
				),
			),
		),
	);
	
	public static function getAvatarExternalPath($id, $size)
	{
		foreach (self::$sampleAvatars as $sampleAvatar) {
			if ($sampleAvatar['id'] == $id) {
				return $sampleAvatar['sizes'][$size]['external_path'];
			}
		}
	}

	public function useravatarAction()
	{
		$this->getResponse()->setResponseData(self::$sampleAvatars);
	}

}
