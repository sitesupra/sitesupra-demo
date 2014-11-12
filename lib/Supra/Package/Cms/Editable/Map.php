<?php

namespace Supra\Package\Cms\Editable;

// @FIXME: if needed

//use Supra\Validator\FilteredInput;
//use Supra\Validator\Type\AbstractType;
//
///**
// * Map
// */
//class Map extends EditableAbstraction
//{
//	/**
//	 * @var float
//	 */
//	private $latitude;
//
//	/**
//	 * @var float
//	 */
//	private $longitude;
//
//	/**
//	 * Default value (Latvia/Riga)
//	 * @var mixed
//	 */
//	protected $defaultValue = array('56.95', '24.1');
//
//	const EDITOR_TYPE = 'Map';
//	const EDITOR_INLINE_EDITABLE = false;
//
//	/**
//	 * Return editor type
//	 * @return string
//	 */
//	public function getEditorType()
//	{
//		return self::EDITOR_TYPE;
//	}
//
//	/**
//	 * {@inheritdoc}
//	 * @return boolean
//	 */
//	public function isInlineEditable()
//	{
//		return self::EDITOR_INLINE_EDITABLE;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getLatitude()
//	{
//		return $this->latitude;
//	}
//
//	/**
//	 * @param string $latitude
//	 */
//	public function setLatitude($latitude)
//	{
//		$this->latitude = $latitude;
//	}
//
//	/**
//	 * @return string
//	 */
//	public function getLongitude()
//	{
//		return $this->longitude;
//	}
//
//	/**
//	 * @param string $longitude
//	 */
//	public function setLongitude($longitude)
//	{
//		$this->longitude = $longitude;
//	}
//
//	/**
//	 * Validates and sanitizes the content
//	 * @param mixed $content
//	 */
//	public function setContent($content)
//	{
//		if (empty($content)) {
//			return;
//		}
//
//		// normalize
//		if (is_string($content)) {
//			$content = explode(';', $content, 2);
//		}
//
//		list($this->latitude, $this->longitude) = $content;
//
//		parent::setContent($content);
//	}
//
//	/**
//	 * Converts to string
//	 * @return string
//	 */
//	public function getContent()
//	{
//		$content = $this->latitude . ';' . $this->longitude;
//
//		return $content;
//	}
//
//	public function getContentForEdit()
//	{
//		return array(
//			$this->latitude,
//			$this->longitude,
//		);
//	}
//
//	/**
//	 * Not used now!
//	 * @param array $content
//	 */
//	public function setContentFromEdit($content)
//	{
//		$this->latitude = $content[0];
//		$this->longitude = $content[1];
//	}
//
////	public function getFilteredValue()
////	{
////		$content = parent::getFilteredValue();
////
////		return explode(';', $content, 2);
////	}
//}
