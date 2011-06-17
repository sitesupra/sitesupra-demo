<?php

$file = (!empty($_GET['file']) ? $_GET['file'] : null);

if ($file) {
	?>
	<!doctype html>
	<html>
	<head>
		<link rel="stylesheet" type="text/css" href=".highlight/SyntaxHighlighter.css" />
		<link rel="stylesheet" type="text/css" href=".highlight/index.css" />
	</head>
	<body>
		<?php
			$dir = (!empty($_GET['dir']) ? $_GET['dir'] : '');
			$path = rtrim($dir, '/');
			$path = ($path == '.' ? '' : $path);
			echo '<h1><a href="?dir=' . $path . '">' . ($path ? '/' . $dir : 'Back') . '</a></h1>';
		?>
		<pre name="code" class="JScript"><?php echo htmlspecialchars(file_get_contents($file)); ?></pre>
	
		<script type="text/javascript" src=".highlight/shCore.js"></script>
		<script type="text/javascript" src=".highlight/shBrushJScript.js"></script>
		<script type="text/javascript">dp.SyntaxHighlighter.HighlightAll('code');</script>
	</body>
	</html>
	
	<?php
} else {
	?>
	<!doctype html>
	<html>
	<head>
		<link rel="stylesheet" type="text/css" href=".highlight/index.css" />
	</head>
	<body>
		<?php
			$dir = (!empty($_GET['dir']) ? $_GET['dir'] . '/' : '');
			$path = dirname($dir);
			$path = ($path == '.' ? '' : $path);
			echo $dir ? '<h1><a href="?dir=' . $path . '">/' . $dir . '</a></h1>' : '';
		?>
		<div class="wrapper">
	<?php
	
	$files = scandir(dirname(__FILE__) . '/' . $dir);
	$self = basename(__FILE__);
	$run = 'run.php';
	
	$dir_html = '';
	$file_html = '';
	
	foreach($files as $file) {
		if (strpos($file, '.') !== 0 && $file != $self && $file != $run) {
			if (is_file($dir . $file)) {
				$file_html .= '<a href="?file=' . $dir . $file . '&dir=' . $dir . '">' . $file . '</a><br />';
			} else {
				$dir_html .= '<a href="?dir=' . $dir . $file . '">' . $file . '</a><br />';
			}
		}
	}
	
	echo $dir_html ? '<div class="hr"></div><h2>Folders</h2><div>' . $dir_html . '</div>' : '';
	echo $file_html ? '<div class="hr"></div><h2>Files</h2><div>' . $file_html . '</div>' : '';
	
	?>
		</div>
	</body>
	</html>
	<?php
}
