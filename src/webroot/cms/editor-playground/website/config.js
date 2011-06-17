/*
 * Define website specific modules
 */
Supra.addModule('website.htmleditor-plugin-css', {
	path: 'htmleditor-plugin-css.js',
	requires: ['supra.htmleditor-base', 'website.htmleditor-plugin-css-stylesheet', 'supra.input-string']
});
Supra.addModule('website.htmleditor-plugin-css-stylesheet', {
	path: 'htmleditor-plugin-css.css',
	type: 'css'
});


Supra.data.mix('supra.htmleditor', {
	/*
	 * Plugin configuration
	 */
	plugins: {
		'css': {
			elements: ['p', 'h1']
		},
		'table': {
			elements: ['table']
		}
	},
	
	/*
	 * Add plugin to HTML editor
	 */
	'requires': [
		'website.htmleditor-plugin-css'
	]
});