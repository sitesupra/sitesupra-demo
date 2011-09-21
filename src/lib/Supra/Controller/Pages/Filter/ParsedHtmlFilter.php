<?php

namespace Supra\Controller\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Controller\Pages\Entity;

/**
 * Parses supra markup tags inside the HTML content
 */
class ParsedHtmlFilter implements FilterInterface
{
	/**
	 * @var BlockProperty
	 */
	public $property;
	
	/**
	 * @var WriterAbstraction
	 */
	private $log;
	
	/**
	 * Create log instance
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * Parse supra.link
	 * @param string $content
	 * @param array $data
	 * @return string
	 */
	private function parseSupraLink($content, $data)
	{
		$url = null;
		$localeManager = ObjectRepository::getLocaleManager($this);
		$localeId = $localeManager->getCurrent()->getId();

		switch ($data['resource']) {
			case 'page':
				$pageId = $data['page_id'];

				$em = ObjectRepository::getEntityManager($this);

				$pageDataEntity = Entity\PageData::__CLASSNAME__();

				$query = $em->createQuery("SELECT d FROM $pageDataEntity d
						WHERE d.locale = ?0 AND d.master = ?1");

				$params = array(
					0 => $localeId,
					1 => $pageId,
				);

				$query->execute($params);

				try {
					/* @var $page Entity\PageData */
					$pageData = $query->getSingleResult();
					$url = '/' . $pageData->getPath();
				} catch (\Doctrine\ORM\NoResultException $noResults) {
					//ignore
				}

				break;
			case 'file':

				$fileId = $data['file_id'];
				$fs = ObjectRepository::getFileStorage($this);
				$em = $fs->getDoctrineEntityManager();
				$file = $em->find('Supra\FileStorage\Entity\File', $fileId);

				if ($file instanceof \Supra\FileStorage\Entity\File) {
					$url = $fs->getWebPath($file);
				}

				break;
			case 'link':
				$url = $data['href'];
				break;

			default:
				$this->log->warn("Unrecognized resource for supra html markup link tag, data: ", $data);
		}

		$target = $data['target'];
		$title = $data['title'];

		$attributes = array(
			'target' => $target,
			'title' => $title,
			'href' => $url
		);

		$text = '<a ';

		foreach ($attributes as $attributeName => $attributeValue) {
			if ($attributeValue != '') {
				$text .= ' ' . $attributeName . '="' . htmlspecialchars($attributeValue) . '"';
			}
		}

		$text .= '>' . $content . '</a>';
		
		return $text;
	}
	
	/**
	 * Parse supra.image
	 * @param string $content
	 * @param array $data
	 * @return string
	 */
	private function parseSupraImage($content, $data)
	{
		$text = null;
		$imageId = $data['image'];
		$fs = ObjectRepository::getFileStorage($this);
		$em = $fs->getDoctrineEntityManager();
		$image = $em->find('Supra\FileStorage\Entity\Image', $imageId);

		if (empty($image)) {
			$this->log->warn("Image #{$imageId} has not been found");
		} else {
			//TODO: add other attributes as align, size, etc
			$sizeName = null;
			if ( ! empty($data['size_name'])) {
				$sizeName = $data['size_name'];
			}
			$src = $fs->getWebPath($image, $sizeName);
			$text = '<img src="' . htmlspecialchars($src) . '"';
			
			$classNames = array();
			
			if ( ! empty($data['align'])) {
				$classNames[] = 'align-' . $data['align'];
			}
			
			if ( ! empty($data['style'])) {
				$classNames[] = $data['style'];
			}

			if ( ! empty($data['size_width']) 
				&& is_numeric($data['size_width'])
			) {
				$text .= ' width="' . $data['size_width'] . '"';
			}

			if ( ! empty($data['size_height'])
				&& is_numeric($data['size_height'])
			) {
				$text .= ' height="' . $data['size_height'] . '"';
			}

			if ( ! empty($data['title'])) {
				$text .= ' title="' . $data['title'] . '"';
				$text .= ' alt="' . $data['title'] . '"';
			}
			
			if ( ! empty($classNames)) {
				$classValue = implode(' ', $classNames);
				$text .= ' class="' . htmlspecialchars($classValue) . '"';
			}
			
			$text .= ' />';
		}
		
		return $text;
	}
	
	/**
	 * Replace image/link supra tags with real elements
	 * @param string $value
	 * @param array $valueData
	 * @return string 
	 */
	protected function parseSupraMarkup($value, &$valueData)
	{
		//TODO: dummy replace for links, images only for now, must move to some filters, suppose like template engine extensions
		$matches = array();
		preg_match_all('/\{supra\.([^\s]+) id="(.*?)"\}((.*?)(\{\/supra\.\1\}))?/', $value, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

		$offset = 0;
		$result = '';

		foreach ($matches as $match) {

			$offsetInit = $match[0][1];
			$offsetEnd = $match[0][1] + strlen($match[0][0]);

			$class = $match[1][0];
			$id = $match[2][0];
			$content = $match[4][0];
			
			$content = $this->parseSupraMarkup($content, $valueData);

			$data = $valueData[$id];
			$text = '';

			switch ($class) {
				case 'link':
					$text = $this->parseSupraLink($content, $data);
					break;
				case 'image':
					$text = $this->parseSupraImage($content, $data);
					break;
				default:
					$this->log->warn("Unrecognized supra html markup tag $class with data ", $data);
			}

			$result .= substr($value, $offset, $offsetInit - $offset);
			$result .= $text;

			$offset = $offsetEnd;
		}

		$result .= substr($value, $offset);

		return $result;
	}
	
	/**
	 * @param string $content
	 * @return string
	 */
	public function filter($content)
	{
		$value = $this->property->getValue();
		$valueData = $this->property->getValueData();
		
		$filteredValue = $this->parseSupraMarkup($value, $valueData);
		
		return $filteredValue;
	}
}
