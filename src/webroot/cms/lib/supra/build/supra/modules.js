(function () {
	
	var Y = Supra.Y;
	
	/**
	 * Add module to module definition list
	 * 
	 * @param {String} id Module id
	 * @param {Object} definition Module definition
	 */
	Supra.addModule = function (id, definition) {
		if (Y.Lang.isString(id) && Y.Lang.isObject(definition)) {
			var groupId = id.indexOf('website.') == 0 ? 'website' : 'supra';
			Supra.YUI_BASE.groups[groupId].modules[id] = definition;
		}
	};
	
	/**
	 * Set path to modules with 'website' prefix
	 * 
	 * @param {String} path
	 */
	Supra.setWebsiteModulePath = function (path) {
		var config = Supra.YUI_BASE.groups.website;
		
		//Add trailing slash
		path = path.replace(/\/$/, '') + '/';
		config.root = path;
		config.base = path;
	};
	
	/**
	 * Add module to automatically included module list
	 * 
	 * @param {String} module Name of the module, which will be automatically loaded
	 */
	Supra.autoLoadModule = function (module) {
		Supra.useModules.push(module);
	};
	
	
})();




/**
 * List of modules, which are added to use() automatically when using Supra()
 * @type {Array}
 */
Supra.useModules = [
	'base', 'controller',
	'node', 'node-focusmanager',
	'widget', 'widget-child',
	'event',
	'querystring',
	'escape',
	
	'supra.event',
	'supra.intl',
	'supra.lang',
	'substitute',
	'supra.datatype-date-reformat',	// + supra.datatype-date-parse
	'supra.base',					// + base, node
	'supra.panel',					// + supra.button, widget, overlay
	'supra.permission',
	'supra.io',						// + io, json
	'supra.io-session',
	'supra.io-css',
	'supra.dom',
	'supra.template'
];


/**
 * Supra module definitions
 * @type {Object}
 */
Supra.YUI_BASE.groups.supra.modules = {
	/**
	 * Supra.Debug module
	 */
	'supra.debug': {
		path: 'debug/debug.js'
	},
	
	/**
	 * Y.Base extension
	 */
	'supra.base': {
		path: 'base/base.js'
	},
	
	/**
	 * Supra.Intl
	 */
	'supra.intl': {
		path: 'intl/intl.js',
		requires: ['intl', 'supra.io']
	},
	
	/**
	 * Y.Lang extension
	 */
	'supra.lang': {
		path: 'lang/lang.js'
	},
	
	/**
	 * Y.DOM extension
	 */
	'supra.dom': {
		path: 'dom/dom.js'
	},
	
	'supra.io': {
		path: 'io/io.js',
		requires: ['io', 'json']
	},
	'supra.io-session': {
		path: 'io/session.js',
		requires: [
			'io'
		]
	},
	'supra.io-css': {
		path: 'io/css.js',
		requires: [
			'io'
		]
	},
	'supra.io-upload': {
		path: 'io/upload.js',
		requires: [
			'supra.io-upload-legacy'
		]
	},
	'supra.io-upload-legacy': {
		path: 'io/upload-legacy.js',
		requires: [
			'base',
			'json'
		]
	},
	
	/**
	 * File upload helper
	 */
	'supra.uploader': {
		path: 'uploader/uploader.js',
		requires: [
			'supra.io-upload'
		]
	},
	
	/**
	 * Event 'exist' plugin
	 */
	'supra.event': {
		path: 'event/event.js'
	},
	
	/**
	 * Layout plugin
	 */
	'supra.plugin-layout': {
		path: 'layout/layout.js',
		requires: ['widget', 'plugin']
	},
	
	/**
	 * Button widget
	 */
	'supra.button': {
		path: 'button/button.js',
		requires: ['node-focusmanager', 'widget', 'widget-child'],
		skinnable: true
	},
	
	/**
	 * Media Library widget
	 */
	'supra.medialibrary': {
		path: 'medialibrary/medialibrary.js',
		requires: [
			'supra.medialibrary-list'
		],
		skinnable: true
	},
	
	'supra.medialibrary-data': {
		path: 'medialibrary/data.js',
		requires: [
			'attribute',
			'array-extras'
		]
	},
	
	'supra.medialibrary-list': {
		path: 'medialibrary/medialist.js',
		requires: [
			'widget',
			'supra.slideshow',
			'supra.medialibrary-data'
		]
	},
	
	'supra.medialibrary-list-extended': {
		path: 'medialibrary/medialist-extended.js',
		requires: [
			'supra.input',
			'supra.medialibrary',
			'supra.slideshow-multiview',
			'supra.medialibrary-list-edit',
			'supra.medialibrary-image-editor'
		]
	},
	
	'supra.medialibrary-list-dd': {
		path: 'medialibrary/medialist-dd.js',
		requires: [
			'plugin',
			'supra.medialibrary'
		]
	},
	
	'supra.medialibrary-list-edit': {
		path: 'medialibrary/medialist-edit.js',
		requires: [
			'plugin'
		]
	},
	
	'supra.medialibrary-upload': {
		path: 'medialibrary/upload.js',
		requires: [
			'supra.io-upload',
			'plugin'
		]
	},
	
	'supra.medialibrary-image-editor': {
		path: 'medialibrary-image-editor/medialibrary-image-editor.js',
		requires: [
			'plugin',
			'transition'
		],
		skinnable: true
	},
	
	/**
	 * Editor widget
	 */
	'supra.htmleditor': {
		path: 'htmleditor/htmleditor.js',
		requires: [
			'supra.htmleditor-base',
			'supra.htmleditor-parser',
			'supra.htmleditor-selection',
			'supra.htmleditor-traverse',
			'supra.htmleditor-editable',
			'supra.htmleditor-commands',
			'supra.htmleditor-plugins',
			'supra.htmleditor-data',
			'supra.htmleditor-toolbar',
			
			'supra.htmleditor-plugin-image',
			'supra.htmleditor-plugin-image-resize',
			'supra.htmleditor-plugin-gallery',
			'supra.htmleditor-plugin-link',
			'supra.htmleditor-plugin-table',
			'supra.htmleditor-plugin-formats',
			'supra.htmleditor-plugin-lists',
			'supra.htmleditor-plugin-textstyle',
			'supra.htmleditor-plugin-styles',
			'supra.htmleditor-plugin-paste',
			'supra.htmleditor-plugin-paragraph',
			'supra.htmleditor-plugin-paragraph-string',
			'supra.htmleditor-plugin-source'
		],
		skinnable: true
	},
		'supra.htmleditor-base': {
			path: 'htmleditor/htmleditor-base.js'
		},
		'supra.htmleditor-parser': {
			path: 'htmleditor/htmleditor-parser.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-selection': {
			path: 'htmleditor/htmleditor-selection.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-traverse': {
			path: 'htmleditor/htmleditor-traverse.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-editable': {
			path: 'htmleditor/htmleditor-editable.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-commands': {
			path: 'htmleditor/htmleditor-commands.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugins': {
			path: 'htmleditor/htmleditor-plugins.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-data': {
			path: 'htmleditor/htmleditor-data.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-toolbar': {
			path: 'htmleditor/toolbar.js',
			requires: ['supra.panel', 'supra.button']
		},
		
		/* Plugins */
		'supra.htmleditor-plugin-link': {
			path: 'htmleditor/plugins/plugin-link.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-gallery': {
			path: 'htmleditor/plugins/plugin-gallery.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-image': {
			path: 'htmleditor/plugins/plugin-image.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-image-resize': {
			path: 'htmleditor/plugins/plugin-image-resize.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-table': {
			path: 'htmleditor/plugins/plugin-table.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-textstyle': {
			path: 'htmleditor/plugins/plugin-textstyle.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-formats': {
			path: 'htmleditor/plugins/plugin-formats.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-lists': {
			path: 'htmleditor/plugins/plugin-lists.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-styles': {
			path: 'htmleditor/plugins/plugin-styles.js',
			requires: ['supra.htmleditor-base', 'supra.template']
		},
		'supra.htmleditor-plugin-paste': {
			path: 'htmleditor/plugins/plugin-paste.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-paragraph': {
			path: 'htmleditor/plugins/plugin-paragraph.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-paragraph-string': {
			path: 'htmleditor/plugins/plugin-paragraph-string.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-source': {
			path: 'htmleditor/plugins/plugin-source.js',
			requires: ['supra.htmleditor-base']
		},
	
	/**
	 * Header widget
	 */
	'supra.header': {
		path: 'header/header.js',
		requires: ['supra.header.appdock'],
		skinnable: true
	},
	'supra.header.appdock': {
		path: 'header/appdock.js',
		requires: ['supra.tooltip']
	},
	
	/**
	 * DataTable
	 */
	'supra.datatable': {
		path: 'datatable/datatable.js',
		requires: ['widget', 'datasource', 'dataschema', 'datatype', 'querystring', 'supra.datatable-row', 'supra.datatable-checkboxes'],
		skinnable: true
	},
	'supra.datatable-row': {
		path: 'datatable/datatable-row.js'
	},
	'supra.datatable-checkboxes': {
		path: 'datatable/plugin-checkboxes.js',
		requires: ['supra.datatable']
	},
	
	/**
	 * Panel
	 */
	'supra.panel': {
		path: 'panel/panel.js',
		requires: ['overlay', 'supra.button'],
		skinnable: true
	},
	'supra.tooltip': {
		path: 'panel/tooltip.js',
		requires: ['supra.panel']
	},
	
	/**
	 * Slideshow widget
	 */
	'supra.slideshow': {
		path: 'slideshow/slideshow.js',
		requires: ['widget', 'anim', 'supra.slideshow-input-button', 'supra.scrollable'],
		skinnable: true
	},
	'supra.slideshow-multiview': {
		path: 'slideshow/slideshow-multiview.js',
		requires: ['supra.slideshow']
	},
	'supra.slideshow-input-button': {
		path: 'slideshow/slideshow-input-button.js',
		requires: ['supra.input-proto']
	},
	
	/**
	 * Scrollable widget
	 */
	'supra.scrollable': {
		path: 'scrollable/scrollable.js',
		requires: ['widget'],
		skinnable: true
	},
	
	/**
	 * Footer
	 */
	'supra.footer': {
		path: 'footer/footer.js',
		skinnable: true
	},
	
	/**
	 * Tree widget
	 */
	'supra.tree': {
		path: 'tree/tree.js',
		requires: ['supra.tree-node', 'supra.tree-plugin-expand-history', 'widget', 'widget-parent'],
		skinnable: true
	},
	'supra.tree-dragable': {
		path: 'tree/tree-dragable.js',
		requires: ['supra.tree', 'supra.tree-node-dragable']
	},
	'supra.tree-node': {
		path: 'tree/tree-node.js',
		requires: ['widget', 'widget-child']
	},
	'supra.tree-node-dragable': {
		path: 'tree/tree-node-dragable.js',
		requires: ['dd', 'supra.tree-node']
	},
	'supra.tree-plugin-expand-history': {
		path: 'tree/plugin-expand-history.js',
		requires: ['plugin', 'cookie', 'supra.tree']
	},
	
	/**
	 * Input widgets
	 */
	'supra.input-proto': {
		path: 'input/proto.js',
		requires: ['widget']
	},
	'supra.input-hidden': {
		path: 'input/hidden.js',
		requires: ['supra.input-proto']
	},
	'supra.input-string': {
		path: 'input/string.js',
		requires: ['supra.input-proto']
	},
	'supra.input-text': {
		path: 'input/text.js',
		requires: ['supra.input-string']
	},
	'supra.input-number': {
		path: 'input/number.js',
		requires: ['supra.input-string']
	},
	'supra.input-path': {
		path: 'input/path.js',
		requires: ['supra.input-string']
	},
	'supra.input-checkbox': {
		path: 'input/checkbox.js',
		requires: ['supra.input-proto', 'anim']
	},
	'supra.input-file-upload': {
		path: 'input/fileupload.js',
		requires: ['supra.input-proto', 'supra.uploader', 'supra.tooltip']
	},
	'supra.input-select': {
		path: 'input/select.js',
		requires: ['supra.input-string', 'anim', 'supra.scrollable']
	},
	'supra.input-select-list': {
		path: 'input/select-list.js',
		requires: ['supra.input-proto', 'supra.button']
	},
	'supra.input-slider': {
		path: 'input/slider.js',
		requires: ['supra.input-proto', 'slider']
	},
	'supra.input-link': {
		path: 'input/link.js',
		requires: ['supra.input-proto']
	},
	'supra.input-image': {
		path: 'input/image.js',
		requires: ['supra.input-proto']
	},
	'supra.input-map': {
		path: 'input/map.js',
		requires: ['supra.input-proto']
	},
	
	'supra.form': {
		path: 'input/form.js',
		requires: [
			'widget',
			'supra.input-proto',
			'supra.input-hidden',
			'supra.input-string',
			'supra.input-text',
			'supra.input-html',
			'supra.input-number',
			'supra.input-path',
			'supra.input-checkbox',
			'supra.input-file-upload',
			'supra.input-select',
			'supra.input-select-list',
			'supra.input-slider',
			'supra.input-link',
			'supra.input-image',
			'supra.input-map'
		]
	},
	'supra.input': {
		path: 'input/input.js',
		requires: ['supra.form'],
		skinnable: true
	},
	
	//HTML editor
	'supra.input-html': {
		path: 'input/html.js',
		requires: ['supra.input-proto', 'supra.htmleditor']
	},
	
	//In-line HTML editor
	'supra.input-inline-html': {
		path: 'input/html-inline.js',
		requires: ['supra.input-proto']
	},
	
	//In-line string editor
	'supra.input-inline-string': {
		path: 'input/string-inline.js',
		requires: ['supra.input-inline-html']
	},
	
	/**
	 * Calendar widget
	 */
	'supra.datatype-date-parse': {
		path: 'datatype/datatype-date-parse.js',
		requires: ['datatype-date']
	},
	
	'supra.datatype-date-reformat': {
		path: 'datatype/datatype-date-reformat.js',
		requires: ['supra.datatype-date-parse']
	},
	
	'supra.calendar': {
		path: 'calendar/calendar.js',
		requires: ['widget', 'anim', 'datatype-date'],
		skinnable: true
	},
	
	/**
	 * Tabs
	 */
	'supra.tabs': {
		path: 'tabs/tabs.js',
		requires: ['widget'],
		skinnable: true
	},
	
	/**
	 * Language bar
	 */
	'supra.languagebar': {
		path: 'languagebar/languagebar.js',
		requires: ['supra.tooltip'],
		skinnable: true
	},
	
	/**
	 * Permission
	 */
	'supra.permission': {
		path: 'permission/permission.js'
	},
	
	/**
	 * Template
	 */
	'supra.template': {
		path: 'template/template.js',
		requires: [
			'supra.template-compiler'
		]
	},
	'supra.template-compiler': {
		path: 'template/template-compiler.js'
	},
	
	/**
	 * Manager
	 */
	'supra.manager': {
		path: 'manager/manager.js',
		requires: [
			'supra.permission',
			'supra.manager-base',
			'supra.manager-loader',
			'supra.manager-loader-actions',
			'supra.manager-action',
			'supra.manager-action-base',
			'supra.manager-action-plugin-manager',
			'supra.manager-action-plugin-base',
			'supra.manager-action-plugin-panel',
			'supra.manager-action-plugin-form',
			'supra.manager-action-plugin-footer',
			'supra.manager-action-plugin-container',
			'supra.manager-action-plugin-maincontent'
		]
	},
	'supra.manager-base': {
		path: 'manager/base.js'
	},
	'supra.manager-loader': {
		path: 'manager/loader.js',
		requires: ['supra.manager-base']
	},
	'supra.manager-loader-actions': {
		path: 'manager/loader-common-actions.js',
		requires: ['supra.manager-loader']
	},
	'supra.manager-action': {
		path: 'manager/action.js',
		requires: ['supra.manager-base']
	},
	'supra.manager-action-base': {
		path: 'manager/action/base.js',
		requires: ['supra.manager-action']
	},
	'supra.manager-action-plugin-manager': {
		path: 'manager/action/plugin-manager.js',
		requires: ['supra.manager-base']
	},
	'supra.manager-action-plugin-base': {
		path: 'manager/action/plugin-base.js',
		requires: ['supra.manager-base']
	},
	'supra.manager-action-plugin-panel': {
		path: 'manager/action/plugin-panel.js',
		requires: ['supra.manager-action-plugin-base', 'supra.panel']
	},
	'supra.manager-action-plugin-form': {
		path: 'manager/action/plugin-form.js',
		requires: ['supra.manager-action-plugin-base', 'supra.input']
	},
	'supra.manager-action-plugin-footer': {
		path: 'manager/action/plugin-footer.js',
		requires: ['supra.manager-action-plugin-base', 'supra.footer']
	},
	'supra.manager-action-plugin-container': {
		path: 'manager/action/plugin-container.js',
		requires: ['supra.manager-action-plugin-base']
	},
	'supra.manager-action-plugin-maincontent': {
		path: 'manager/action/plugin-maincontent.js',
		requires: ['supra.manager-action-plugin-base']
	},
	'supra.manager-action-plugin-layout-sidebar': {
		path: 'manager/action/plugin-layout-sidebar.js',
		requires: ['supra.manager-action-plugin-base', 'supra.input', 'supra.scrollable']
	}
	
};