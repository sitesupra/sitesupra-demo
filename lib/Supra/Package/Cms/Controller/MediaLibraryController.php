<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Entity\Abstraction\File;
use Supra\Package\Cms\Entity\Folder;
use Supra\Package\Cms\Entity\Image;
use Symfony\Component\HttpFoundation\Request;

class MediaLibraryController extends Controller
{
	protected $application = 'media-library';

	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;

	public function indexAction()
	{
		return $this->renderResponse('index.html.twig');
	}

	public function listAction(Request $request)
	{
		$rootNodes = array();

		$repo = $this->container->getDoctrine()
			->getManager()->getRepository('Supra\Package\Cms\Entity\Abstraction\File');

		$output = array();

		// if parent dir is set then we set folder as rootNode
		if ($request->query->get('id')) {
			$node = $this->getFolder('id');
			$rootNodes = $node->getChildren();
		} else {
			$rootNodes = $repo->getRootNodes();
		}

		foreach ($rootNodes as $rootNode) {
			/* @var $rootNode Entity\Abstraction\File */

			if ($request->query->has('type')) {

				$itemType = $this->getEntityType($rootNode);
				$requestedType = $request->query->get('type');

				if ( ! (
					($itemType == $requestedType) ||
					($itemType == Folder::TYPE_ID)
				)) {
					continue;
				}
			}

			$item = $this->getEntityData($rootNode);

			if ($rootNode instanceof Entity\File) {

				$extension = mb_strtolower($rootNode->getExtension());

				$knownExtensions = $this->getApplicationConfigValue('knownFileExtensions', array());
				if (in_array($extension, $knownExtensions)) {
					$item['known_extension'] = $extension;
				}

				$checkExistance = $this->getApplicationConfigValue('checkFileExistence');
				if ($checkExistance == ApplicationConfiguration::CHECK_FULL) {
					$item['broken'] = ( ! $this->isAvailable($rootNode));
				}
			}

			// Get thumbnail
			if ($rootNode instanceof Entity\Image) {
				// create preview
				// TODO: hardcoded 30x30
				try {
					if ($this->fileStorage->fileExists($rootNode)) {
						$sizeName = $this->fileStorage->createResizedImage($rootNode, 30, 30, true);
						if ($rootNode->isPublic()) {
							$item['thumbnail'] = $this->fileStorage->getWebPath($rootNode, $sizeName);
						} else {
							$item['thumbnail'] = $this->getPrivateImageWebPath($rootNode, $sizeName);
						}
					}
				} catch (\Exception $e) {
					$item['broken'] = true;
				}
			}

			$output[] = $item;
		}

		return new SupraJsonResponse(array(
			'totalRecords' => count($output),
			'records' => $output,
		));
	}

	/**
	 * Get internal file entity type constant
	 * @return int
	 */
	private function getEntityType(File $entity)
	{
		$type = null;

		if ($entity instanceof Folder) {
			$type = self::TYPE_FOLDER;
		} elseif ($entity instanceof Image) {
			$type = self::TYPE_IMAGE;
		} elseif ($entity instanceof File) {
			$type = self::TYPE_FILE;
		}

		return $type;
	}
}
