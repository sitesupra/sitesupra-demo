<?php

namespace Supra\Package\Cms\Entity\ReferencedElement;

/**
 * @Entity
 */
class IconReferencedElement extends ReferencedElementAbstract
{

	const TYPE_ID = 'icon';

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $iconId;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $width;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $height;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $color;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $align;
	
	/**
	 * @var string
	 */
	protected $svgContent;

	/**
	 * @return string
	 */
	public function getIconId()
	{
		return $this->iconId;
	}
	
	/**
	 * @param string $id
	 */
	public function setIconId($id)
	{
		$this->iconId = $id;
	}
	
	/**
	 * @return string
	 */
	public function getAlign()
	{
		return $this->align;
	}

	/**
	 * @param string $align
	 */
	public function setAlign($align)
	{
		$this->align = $align;
	}

	/**
	 * @return string
	 */
	public function getColor()
	{
		return $this->color;
	}

	/**
	 * @param string $color
	 */
	public function setColor($color)
	{
		if ( ! empty($color) && ! preg_match('/^#[a-f0-9]{6}$/i', $color)) {
			throw new \InvalidArgumentException("{$color} is not valid HEX color value");
		}
		
		$this->color = $color;
	}

	/**
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @param integer $width
	 */
	public function setWidth($width)
	{
		$width = (int) $width;

		if ($width < 0) {
			throw new \InvalidArgumentException("Negative width '$width' received");
		} elseif ($width == 0) {
			$width = null;
		}

		$this->width = $width;
	}

	/**
	 * @return integer
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * @param integer $height
	 */
	public function setHeight($height)
	{
		$height = (int) $height;

		if ($height < 0) {
			throw new \InvalidArgumentException("Negative height '$height' received");
		} elseif ($height == 0) {
			$height = null;
		}

		$this->height = $height;
	}

	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'type' => self::TYPE_ID,
			'id' => $this->iconId,
			'color' => $this->color,
			'align' => $this->align,
			'width' => $this->width,
			'height' => $this->height,
//			'svg' => $this->getIconSvgContent(),
		);
	}

	/**
	 * {@inheritdoc}
	 * @param array $array
	 */
	public function fillArray(array $array)
	{
		$array = $array + array(
			'align' => null,
			'color' => null,
			'width' => null,
			'height' => null,
		);
		
		$this->iconId = $array['id'];
		$this->align = $array['align'];
		$this->setColor($array['color']);
		$this->width = $array['width'];
		$this->height = $array['height'];
	
		$this->svgContent = null;
	}
	
	public function getIconSvgContent()
	{
		if ($this->svgContent === null) {
			
			$this->svgContent = false;
			
			$themeConfiguration = \Supra\ObjectRepository\ObjectRepository::getThemeProvider($this)
					->getCurrentTheme()
					->getConfiguration();
			
			$iconConfiguration = $themeConfiguration->getIconConfiguration();
			if ($iconConfiguration instanceof \Supra\Controller\Layout\Theme\Configuration\ThemeIconSetConfiguration) {
				$this->svgContent = $iconConfiguration->getIconSvgContent($this->iconId);
			}
		}
		
		return $this->svgContent;
	}

}
