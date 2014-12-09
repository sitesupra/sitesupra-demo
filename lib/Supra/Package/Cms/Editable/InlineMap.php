<?php

namespace Supra\Package\Cms\Editable;

class InlineMap extends Editable
{
    const EDITOR_TYPE = 'InlineMap';

	protected $defaultValue = array(
		'latitude' =>	56,
		'longitude'	=>	32,
		'zoom'		=>	5
	);

	/**
	 * {@inheritDoc}
	 */
    public function getEditorType()
    {
        return static::EDITOR_TYPE;
    }
}
