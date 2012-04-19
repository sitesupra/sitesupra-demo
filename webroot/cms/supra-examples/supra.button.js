/*
 * --- Create from existing markup ---
 */

var button = new Supra.Button({
	//Button element, optional
	'srcNode': node,
	//Button style, default is "mid", optional
	'style': 'mid-blue'
});

//Render button
btn.render();

//Add event listener
btn.on('click', this.onSlideScheduleApply, this);



/*
 * --- Dynamicaly create button ---
 */
var button = new Supra.Button({
	//Button label
	'label': 'Click me',
	//Button style, default is "mid", optional
	'style': 'mid-blue'
});

//Render button inside node
button.render(node);