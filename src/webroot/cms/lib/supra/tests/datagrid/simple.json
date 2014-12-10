<?php
	$titles = array(
		'Unicorns went on a rampage in city streets',
		'Zerg has taken over PX-1943 command center',
		'Double rainbow spotted in Artica',
		'Plumber from Italy rescues princess from evil turtles',
		'All G8 countries approves M&Ms as valid currency',
		'Explosion in Sector-17 opens a wormhole to alternative universe',
	);
	
	$resultsPerRequest = !empty($_GET['resultsPerRequest']) ? intval($_GET['resultsPerRequest']) : 20;
	$offset = !empty($_GET['offset']) ? intval($_GET['offset']) : 0;
?>{
	"status": 1,
	"data": {
		"results": [
			<?php $count = min($resultsPerRequest, 200 - $offset); ?>
			<?php for($i=1; $i<=$count; $i++) { ?>
			{"id": <?php echo $offset + $i; ?>, "title": "<?php echo $titles[array_rand($titles)]; ?>", "date": "<?php echo '2011-' . str_pad(rand(1, 12), 2, "0", STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, "0", STR_PAD_LEFT); ?>"}<?php if ($i != $count) echo ","; ?>
			<?php } ?>
		],
		"total": 200,
		"offset": <?php echo $offset; ?>
	}
}