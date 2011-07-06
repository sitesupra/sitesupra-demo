{
	"id": <?php $id = rand(); echo $id; ?>,
	"type": "html",
	"locked": false,
	"properties": {
		"html1": {
			"html": "Aaaaaaaaaaaaa",
			"data": {}
		},
		"html2": {
			"html": "Bbbbbbbbbbbbb",
			"data": {}
		},
		"visible": false
	},
	"html": "<div id=\"content_html_<?php echo $id; ?>_html1\" class=\"yui3-content-inline\">\n<h2>Lorem ipsum<\/h2>\n<p>Lorem ipsum<\/p>\n<\/div>\n<br \/><small>Here ends <em>html1<\/em> and starts <em>html2<\/em> editable area<\/small><br \/><br \/>\n<div id=\"content_html_<?php echo $id; ?>_html2\" class=\"yui3-content-inline\">\n<h2>Lorem ipsum<\/h2>\n<p>Lorem ipsum<\/p>\n<\/div>"
}