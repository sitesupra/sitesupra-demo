<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
	<title>YUI 3 + Supra</title>
	
	<!-- Load all base (YUI, Loader, Supra) JS files at once -->
	<script src="/cms/yui.3.3.0/combo/combo.php?/cms/yui.3.3.0/build/yui/yui-min.js&/cms/yui.3.3.0/build/loader/loader-min.js&/cms/supra/build/supra/supra.js&/cms/supra/build/supra/modules.js"></script>
	
	<!-- Load all base CSS files at once -->
	<link rel="stylesheet" type="text/css" href="/cms/yui.3.3.0/combo/combo.php?/cms/yui.3.3.0/build/cssreset/reset.css&/cms/supra/build/supra/reset.css&/cms/supra/build/supra/common.css" />
</head>
<body>
	
	<!-- Action container node -->
	<div id="cmsContent"></div>
	
	<?php
		$file = (!empty($_GET['file']) ? $_GET['file'] : null);
	?>
	
	<script type="text/javascript" src="<?php echo $file; ?>"></script>
	
</body>
</html>