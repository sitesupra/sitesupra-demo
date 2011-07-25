{
	"status": 1,
	"data": {
		"id": <?php echo rand(); ?>,
		"icon": "<?php echo $_POST['icon']; ?>",
		"title": "<?php echo $_POST['title']; ?>",
		"template": "<?php echo $_POST['template']; ?>",
		"parent": <?php echo $_POST['parent']; ?>,
		"path": "<?php echo $_POST['path']; ?>",
		"published": false,
		"scheduled": false,
		"preview": "/cms/supra/img/sitemap/preview/blank.jpg"
	}
}