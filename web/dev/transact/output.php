<?php

function getOutputFrame($content)
{
	ob_start();
	?>
	<h1>A stub for Transact Pro payment provider</h1>
	<h3>It does not do much.</h3>

	<hr />

	<?php echo($content); ?>

	<hr />

	Session:
	<pre><?php var_dump($_SESSION); ?></pre> 

	<hr />

	<?php
	return ob_get_clean();
}

function getDefaultOuptut($message = null, $extra = null)
{
	ob_start();
	?>

	<?php if ($message): ?>
		<h2><?php echo($message); ?></h2>
	<?php endif ?>

	<?php if ($extra): ?>
		<pre><?php echo($extra); ?></pre>
		<hr />
	<?php endif ?>

	<?php if( ! empty($_SESSION['last_request'])): ?>

	<p> 
		<a href="?do=success">Return with SUCCESS</a> <br />
		<a href="?do=failure">Return with FAILURE</a> <br />
		<a href="?do=success-notify">Send SUCCESS notification</a>
		<a href="?do=failed-notify">Send FAILURE notification</a>
	</p>

	<hr />

	Last request:
	<pre><?php var_dump($_SESSION['last_request']); ?></pre>

	<?php endif ?>

	<?php
	return getOutputFrame(ob_get_clean());
}

function getErrorOutput($message, $extra = null)
{
	ob_start();
	?>
	<h2>WE HAD AN ERROR</h2>

	<p>
		<?php echo($message); ?>
	</p>

	<?php if ($extra): ?>
		<pre><?php echo($extra); ?></pre>
	<?php endif ?>

	<?php
	return getOutputFrame(ob_get_clean());
}
