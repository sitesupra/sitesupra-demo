// Block data
{
	.....
	"properties": [
		{
			"id": "design",
			"type": "SelectVisual",
			"group": "design",
			...
		},
		{
			"id": "scrollTimerEnabled",
			"type": "Checkbox",
			"group": "animation",
			...
		},
		{
			"id": "scrollTimerDelay",
			"type": "Slider",
			"group": "animation",
			...
		},
		{
			"id": "slides",
			"type": "Slideshow",
			"properties": [
				// Properties for each slide
				{
					'id': 'layout',
	          		'type': 'SelectVisual',
	      			'label': 'Layout',
			  		'defaultValue': 'bg_text_left',
			  		'separateSlide': true, // Show preview in the button, which opens another slide with all values
			  		'values': [
				  		{
		  					'id': 'bg',
		  					'title': 'Background image only',
		  					'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg.png'
		  				}, {
		  					// This is a group
							'id': 'bg_text',
							'title': 'Background image and text',
							'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text.png',
							'values': [
								// These are actual values
								{
									'id': 'bg_text_left',
									'title': 'Text on left side',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-left.png'
								}, {
									'id': 'bg_text_right',
									'title': 'Text on right side',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-right.png'
								}, {
									'id': 'bg_text_left_top',
									'title': 'Text on left and top sides',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-left-top.png'
								}, {
									'id': 'bg_text_right_top',
									'title': 'Text on right and top sides',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-right-top.png'
								}, {
									'id': 'bg_text_left_bottom',
									'title': 'Text on left and bottom sides',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-left-bottom.png'
								}, {
									'id': 'bg_text_right_top',
									'title': 'Text on right and bottom sides',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-right-bottom.png'
								}
							]
						}, {
							'id': 'bg_text_img',
							'title': 'Background image, text and graphics element',
							'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-img.png',
							'values': [
								{
									'id': 'bg_text_img_left',
									'title': 'Text on left side',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-img-left.png'
								}, {
									'id': 'bg_text_img_right',
									'title': 'Text on right side',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-img-right.png'
								}, {
									'id': 'bg_text_img_left_top',
									'title': 'Text on left and top sides',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-img-left-top.png'
								}, {
									'id': 'bg_text_img_right_top',
									'title': 'Text on right and top sides',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-img-right-top.png'
								}, {
									'id': 'bg_text_img_left_bottom',
									'title': 'Text on left and bottom sides',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-img-left-bottom.png'
								}, {
									'id': 'bg_text_img_right_top',
									'title': 'Text on right and bottom sides',
									'icon': '/components/FancyBlocks/SlideshowAdvanced/icons/layout/bg-text-img-right-bottom.png'
								}
							]
						}
					]
			  	}, {
			  		'id': 'background',
			  		'type': 'BlockBackground',
			  		'label': 'Background image'
			  	}, {
			  		'id': 'text_main',
			  		'type': 'InlineHTML',
			  		'label': 'Main text',
			  		'defaultValue': {
			  			'data': {},
			  			'html': '<h1>Lorem ipsum</h1><h2>Dolor sit amet</h2><p>Lid est laborum dolo es fugats untras. Et harums quidem rerum facilisdolores nemis omnis fugiats vitaro minimarerums unsers sadips dolores sitsers untra nemi amets.</p>'
			  		},
			  		'inline': true
			  	}, {
			  		'id': 'text_top',
			  		'type': 'InlineHTML',
			  		'label': 'Top text',
			  		'defaultValue': {
			  			'data': {},
			  			'html': '<p>Lid est laborum dolo es fugats untras. Et harums quidem rerum facilisdolores nemis omnis fugiats vitaro minimarerums unsers sadips dolores sitsers untra nemi amets.</p>'
			  		},
			  		'inline': true
			  	}, {
			  		'id': 'media',
			  		'type': 'InlineMedia', // New input type, but can be used only in slideshow manager!!!
			  		'label': 'Image or video'
			  	}, {
			  		'id': 'buttons',
			  		'type': 'Set', // New input type, can be used in all CMS!!!
			  		'label': 'Buttons',
			  		'labelAdd': 'Add more buttons',
			  		'labelRemove': 'Remove button',
			  		'labelItem': 'Button %s',
			  		'properties': [
			  			{
			  				'id': 'title',
			  				'type': 'String',
			  				'label': 'Title'
			  			},
			  			{
			  				'id': 'link',
			  				'type': 'Link',
			  				'label': 'Link'
			  			}
			  		]
			  	}
			],
			"layouts": [
				// All layouts, other blocks does not have this property, something new!
				// HTML should be taken from template files
				{
					'id': 'bg',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /></div></li>'
          		},
          		
          		{
					'id': 'bg_text_left',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div><div class="as-layer as-layer-right-large">&nbsp;</div></div></li>'
          		}, {
					'id': 'bg_text_right',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-large">&nbsp;</div><div class="as-layer as-layer-right-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div></li>'
          		}, {
          			'id': 'bg_text_left_top',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-top">{{ property.text_top }}</div><div class="as-layer as-layer-left-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div><div class="as-layer as-layer-right-large">&nbsp;</div></div></li>'
          		}, {
					'id': 'bg_text_right_top',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-top">{{ property.text_top }}</div><div class="as-layer as-layer-left-large">&nbsp;</div><div class="as-layer as-layer-right-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div></li>'
          		}, {
          			'id': 'bg_text_left_bottom',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div><div class="as-layer as-layer-right-large">&nbsp;</div><div class="as-layer as-layer-bottom">{{ property.text_top }}</div></li>'
          		}, {
					'id': 'bg_text_right_bottom',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-large">&nbsp;</div><div class="as-layer as-layer-right-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div><div class="as-layer as-layer-bottom">{{ property.text_top }}</div></li>'
          		},
          		
          		
          		{
					'id': 'bg_text_img_left',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div><div class="as-layer as-layer-right-large">{{ property.media }}</div></div></li>'
          		}, {
					'id': 'bg_text_img_right',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-large">{{ property.media }}</div><div class="as-layer as-layer-right-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div></li>'
          		}, {
          			'id': 'bg_text_img_left_top',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-top">{{ property.text_top }}</div><div class="as-layer as-layer-left-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div><div class="as-layer as-layer-right-large">{{ property.media }}</div></li>'
          		}, {
					'id': 'bg_text_img_right_top',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-top">{{ property.text_top }}</div><div class="as-layer as-layer-left-large">{{ property.media }}</div><div class="as-layer as-layer-right-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div></li>'
          		}, {
          			'id': 'bg_text_img_left_bottom',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div><div class="as-layer as-layer-right-large">{{ property.media }}</div><div class="as-layer as-layer-bottom">{{ property.text_top }}</div></li>'
          		}, {
					'id': 'bg_text_img_right_bottom',
          			'html': '<li><div class="as-wrapper"><img class="as-layer absolute fill" src="{{ property.background }}" /><div class="as-layer as-layer-left-large">{{ property.media }}</div><div class="as-layer as-layer-right-small">{{ property.text_main }}<div class="as-layer buttons" data-supra-item-property="buttons"></div></div><div class="as-layer as-layer-bottom">{{ property.text_top }}</div></li>'
          		}
			]
			...
		}
	],
	"properties_groups": [
		{
			"id": "design",
			...
		},
		{
			"id": "animation",
			...
		}
	]
}


// Block content info
{
	"design": "...",
	"animation": "top",
	"scrollTimerEnabled": true,
	"scrollTimerDelay": 5,
	"slides": [
		// ... each slide info ...
	]
}

// Slide info
{
	"layout": "bg_text_img_left",
	"background": ...,
	"text_main": {
		"data": {...},
		"html": "..."
	},
	"text_top": {
		"data": {...},
		"html": "..."
	},
	"media": {
		"type": "video",
		"resource": "source",
		"source": "<embed ....>"
	},
	"button": [
		{
			"title": "My button",
			"link": ""
		}
	]
}
