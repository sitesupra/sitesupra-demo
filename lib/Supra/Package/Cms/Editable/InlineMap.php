<?php

namespace Supra\Package\Cms\Editable;

class InlineMap extends Editable
{
    const EDITOR_TYPE = 'InlineMap';
	
	/**
	 * @var float
	 */
	protected $latitude = 0;
	
	/**
	 * @var float
	 */
	protected $longitude = 0;

	/**
	 * @var integer
	 */
	protected $zoom = 0;
	
	/**
	 * @var integer
	 */
	protected $height = null;

	/**
	 * {@inheritDoc}
	 */
    public function getEditorType()
    {
        return static::EDITOR_TYPE;
    }

	/**
	 * @param mixed $content
	 * @return mixed
	 */
	public function setContent($content)
	{
		if (empty($content)) {
			return;
		}
		
		// normalize
		if (is_string($content)) {
			$content = explode(';', $content, 4);
		}
		
		list($this->latitude, $this->longitude, $this->zoom) = $content;
		
		// old values can be without the "height" parameter
		if (count($content) > 3) {
			$this->height = $content[3];
		}
		
		parent::setContent($content);
	}
	
	/**
	 * Converts to string
	 * @return string
	 */
	public function getContent()
	{
		return implode(';', array($this->latitude, $this->longitude, $this->zoom, $this->height));
	}
	
	/**
	 * @return array
	 */
	public function getContentForEdit()
	{
		return array(
			'latitude' => $this->latitude,
			'longitude' => $this->longitude,
			'zoom' => $this->zoom,
			'height' => $this->height,
		);
	}
	
	/**
	 * @param array $content
	 */
	public function setContentFromEdit($content)
	{
		if ( ! (isset($content['latitude'])
				&& isset($content['longitude'])
				&& isset($content['zoom'])
				&& isset($content['height']))
		){
			throw new \InvalidArgumentException('Received content is not valid map data array');
		}
		
		$zoom = (int) $content['zoom'];
		
		if ($zoom < 0) {
			throw new \InvalidArgumentException("Zoom level {$zoom} is out of range");
		}
		
		$this->latitude = (float) $content['latitude'];
		$this->longitude = (float) $content['longitude'];
		$this->zoom = $zoom;
		$this->height = ( ! empty($content['height']) ? (int) $content['height'] : '');
	}
	
	/**
	 * Filtered value consists from the array of 
	 * latitude, longitude and zoom
	 * 
	 * @return array
	 */
	public function getFilteredValue()
	{
		return array(
			'latitude' => $this->latitude,
			'longitude' => $this->longitude,
			'zoom' => $this->zoom,
			'height' => $this->height,
		);
	}
    
}
