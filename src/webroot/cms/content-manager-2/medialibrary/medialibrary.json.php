<?php header('Content-type: application/json'); ?>
<?php $folder = isset($_GET['folder']) ? $_GET['folder'] : 0; ?>
<?php if ($folder == 0) { ?>

[
	{"id": 1, "title": "Illustrations", "type": 1, "thumbnail": "/cms/supra/img/media/folder-1.jpg"},
	{"id": 2, "title": "Photos", "type": 1, "thumbnail": "/cms/supra/img/media/folder-2.jpg"}
]

<?php } else if ($folder == 1) { ?>

[
	{
		"id": 3,
		"title": "Flowers",
		"type": 2,
		"filename": "flower.jpg",
		"description": "Short description",
		"sizes": [
			{"id": "60x60",    "width": 60,  "height": 60,  "external_path": "/cms/supra/img/media/picture-1-thumb.jpg"},
			{"id": "200x200",  "width": 200, "height": 150, "external_path": "/cms/supra/img/media/picture-1.jpg"},
			{"id": "original", "width": 600, "height": 450, "external_path": "/cms/supra/img/media/picture-1-original.jpg"}
		]
		
	},
	{
		"id": 4,
		"title": "Tulips",
		"type": 2,
		"filename": "tulips.jpg",
		"description": "Short description",
		"sizes": [
			{"id": "60x60",    "width": 60,  "height": 60,  "external_path": "/cms/supra/img/media/picture-2-thumb.jpg"},
			{"id": "200x200",  "width": 200, "height": 150, "external_path": "/cms/supra/img/media/picture-2.jpg"},
			{"id": "original", "width": 600, "height": 450, "external_path": "/cms/supra/img/media/picture-2-original.jpg"}
		]
	},
	{
		"id": 5,
		"title": "Koala",
		"type": 2,
		"filename": "koala.jpg",
		"description": "Koala sitting on the tree",
		"sizes": [
			{"id": "60x60",    "width": 60,  "height": 60,  "external_path": "/cms/supra/img/media/picture-3-thumb.jpg"},
			{"id": "200x200",  "width": 200, "height": 150, "external_path": "/cms/supra/img/media/picture-3.jpg"},
			{"id": "original", "width": 600, "height": 450, "external_path": "/cms/supra/img/media/picture-3-original.jpg"}
		]
	},
	{
		"id": 6,
		"title": "Penguins",
		"type": 2,
		"filename": "penguins.jpg",
		"description": "Penguins daily meeting",
		"sizes": [
			{"id": "60x60",    "width": 60,  "height": 60,  "external_path": "/cms/supra/img/media/picture-4-thumb.jpg"},
			{"id": "200x200",  "width": 200, "height": 150, "external_path": "/cms/supra/img/media/picture-4.jpg"},
			{"id": "original", "width": 600, "height": 450, "external_path": "/cms/supra/img/media/picture-4-original.jpg"}
		]
	}
]

<?php } else if ($folder == 2) { ?>

[
]

<?php } ?>