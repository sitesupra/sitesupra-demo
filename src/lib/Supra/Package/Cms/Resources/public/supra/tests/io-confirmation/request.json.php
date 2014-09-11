<?php
if (isset($_POST['_confirmation']['redirect'])) {
	if ($_POST['_confirmation']['redirect'] == '1'):
	?>
	{
		"status": 1,
		"data": {
			"text": "Data for redirected page..."
		}
	}
	<?php
	else:
	?>
	{
			"status": 1,
			"data": {
				"text": "Data for not redirected page..."
			}
		}
	<?php	
	endif;
} else {
	echo '{
	"status": 1,
	"data": null,
	"confirmation": {
		"id": "redirect",
		"question": "Do you want to redirect?"
	}
}';
}
