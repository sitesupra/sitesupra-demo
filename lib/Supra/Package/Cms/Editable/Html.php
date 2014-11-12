<?php

namespace Supra\Package\Cms\Editable;

/**
 * Html editable content, extends string so the string could be extended to HTML content
 */
class Html extends String
{
	const EDITOR_TYPE = 'InlineHTML';
//
//	/**
//	 * @var array
//	 */
//	protected $fonts = array();
//
//	/**
//	 * @param mixed $content
//	 */
//	public function setContentFromEdit($content)
//	{
//		if (is_array($content)) {
//			if ( ! isset($content['html'])) {
//				throw new \InvalidArgumentException("Incorrect array content {$content} received for Html editable");
//			}
//
//			$this->content = $content['html'];
//		} else {
//			// plain strings are treated as HTML
//			$this->content = $content;
//		}
//
//		$this->fonts = isset($content['fonts']) ? $content['fonts'] : array();
//		$this->contentMetadata = isset($content['data']) ? $content['data'] : array();
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getContent()
//	{
//		return serialize(array(
//			'html' => $this->content,
//			'fonts' => $this->fonts,
//		));
//	}
//
//	/**
//	 * @param string $content
//	 */
//	public function setContent($content)
//	{
//		$unserialized = unserialize($content);
//
//		if ($unserialized !== false) {
//
//			$this->content = $unserialized['html'];
//			$this->fonts = $unserialized['fonts'];
//
//		} else {
//			// old values can be the strings
//			$this->content = $content;
//		}
//	}
//
//	/**
//	 * @return array
//	 */
//	public function getContentForEdit()
//	{
//		$metaArray = array();
//
//		foreach ($this->contentMetadata as $key => $metaItem) {
//
//			if ($metaItem instanceof \Supra\Controller\Pages\Entity\BlockPropertyMetadata) {
//
//				$element = $metaItem->getReferencedElement();
//				$metaArray[$key] = $this->convertReferencedElementToArray($element);
//			} else {
//				$metaArray[$key] = $metaItem;
//			}
//		}
//
//		return array(
//			'html' => $this->content,
//			'data' => $metaArray,
//		);
//	}
}
