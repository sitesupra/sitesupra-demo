{
	"id": 111,
	"title": "<?php
		$titles = Array('Catalogue', 'News', 'Services', 'Products');
		shuffle($titles);
		echo $titles[0];
	?>",
	
	"path": "catalogue",
	"path_prefix": "/sample/",
	
	"keywords": "web development, web design, nearshore development, e-commerce, visualization, 3D, web 2.0, PHP, LAMP, SiteSupra Platform, CMS, content management, web application, Web systems, IT solutions, usability improvements, system design, FMS, SFS, design conception, design solutions, intranet systems development, extranet systems development, flash development, hitask",
	"description": "",
	
	"template": {
		"id": "template_3",
		"title": "Simple",
		"img": "/cms/supra/img/templates/template-3.png"
	},
	
	"scheduled_date": "18.08.2011",
	"scheduled_time": "08:00",
	
	"version": {
		"id": 222,
		"title": "Draft (auto-saved)",
		"author": "Admin",
		"date": "21.05.2011"
	},
	
	"active": true,
	
	"internal_url": "/cms/content-manager-2/sample-acme-page.html",
	
	"contents": [
		{
			"id": "inner",
			"type": "list",
			"allow": ["html", "string", "sample"],
			"contents": [
				{
					"id": 111,
					"type": "html",
					"value": "<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>"
				},
				{
					"id": 222,
					"type": "html",
					"value": "<h2>Header Level 2</h2><ol><li>Lorem ipsum</li></ol>"
				}
			]
		},
		{
			"id": "sidebar",
			"type": "list",
			"allow": ["string"],
			"contents": [
				{
					"id": 333,
					"type": "html",
					"value": "<ul><li><a href=\"javascript://\">Lorem ipsum dolor sit amet</a></li><li><a href=\"javascript://\">Consectetuer adipiscing elit.</a></li><li><a href=\"javascript://\">Aliquam tincidunt mauris eu risus.</a></li><li><a href=\"javascript://\">Vestibulum auctor dapibus neque.</a></li></ul>"
				}
			]
		}
	]
}