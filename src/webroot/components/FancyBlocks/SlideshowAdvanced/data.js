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
					"id": "layout",
					"type": "SelectVisual",
					"separateSlide": true, // Show preview in the button, which opens another slide with all values
					"values": [
						{
							// This is a group
							"icon": "...",
							"id": "group_background_text",
							"title": "Background image and text",
							"values": [
								// These are values
								...
							]
						},
						{
							// This is a group
							"icon": "...",
							"id": "group_background_text_graphics",
							"title": "Background image, text and graphics element or a video",
							"values": [
								// These are values
								{
									"icon": "...",
									"id": "text_graphics",
									"title": "..."
								}
							]
						},
						{
							// This is a value, it can be on same level as groups
							"icon": "...",
							"id": "...",
							"title": "Background image only"
						},
						...
					]
				},
				{
					"id": "background",
					"type": "Image",
					...
				},
				{
					"id": "text_main",
					"type": "InlineHTML",
					...
				},
				{
					"id": "text_top",
					"type": "InlineHTML",
					...
				},
				{
					"id": "image",
					"type": "InlineImage",
					...
				},
				{
					"id": "video",
					"type": "Video",
					...
				}
			],
			"layouts": [
				// All layouts, other blocks does not have this property, something new!
				{
					"id": "text_graphics",
					"html": '<div class="as-wrapper">\
								<div class="text">\
									...something...\
								</div>\
								<div class="video">\
									...something...\
								</div>\
							</div>'
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

// Slide ifno
{
	"layout": "text_graphics",
	"background": ...,
	"text_main": ...,
	"text-_top": ...
}
