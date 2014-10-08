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
			var groupId = id.match(/^[a-z0-9\_\-]+/);
			if (!groupId || !Supra.YUI_BASE.groups[groupId]) {
				groupId = 'supra';
			}
			Supra.YUI_BASE.groups[groupId].modules[id] = definition;
			
			//Reset configuration state
			Supra.yui_base_set = false;
		}
	};
	
	/**
	 * Add list of modules to the definitions list
	 * 
	 * @param {Object} definitions Module definitions
	 */
	Supra.addModules = function (definitions) {
		if (Y.Lang.isObject(definitions)) {
			for (var key in definitions) {
				
				Supra.addModule(key, definitions[key]);
				
			}
		}
	};
	
	/**
	 * Set path to modules with group prefix
	 * 
	 * @param {String} group Module group
	 * @param {String} path Path to modules
	 */
	Supra.setModuleGroupPath = function (group, path) {
		var config = Supra.YUI_BASE.groups[group];
		
		//Set default configuration
		if (!config) {
			config = Supra.YUI_BASE.groups[group] = {
				//Website specific modules
				combine: true,
				root: "/cms/",
				base: "/cms/",
				//Use YUI file combo
				comboBase: window.comboBase,
				filter: "raw",
				modules: {}
			};
		}

		//Add trailing slash
		path = path.replace(/\/$/, '') + '/';
		config.root = path;
		config.base = path;

		//Reset configuration state
		Supra.yui_base_set = false;
	};

	/**
	 * Returns path to modules with group prefix
	 *
	 * @param {String} group Module group
	 * @returns {String} Path to modules
	 */
	Supra.getModuleGroupPath = function (group) {
		var config = Supra.YUI_BASE.groups[group];
		if (config) {
			return config.root;
		} else {
			return null;
		}
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
	'base',
	'node', 'node-focusmanager',
	'widget', 'widget-child',
	'event',
	'querystring',
	'escape',
	'cookie',
	'transition',
	'router',

	'supra.url',
	'supra.timer',
	'supra.dd-ddm',
	'supra.event',
	'supra.deferred',
	'supra.intl',
	'supra.lang',
	'substitute',
	'supra.datatype-date-reformat',	// + supra.datatype-date-parse
	'supra.base',					// + base, node
	'supra.panel',					// + supra.button, widget, overlay
	'supra.permission',
	'supra.io',						// + io, json, jsonp
	'supra.io-session',
	'supra.io-css',
	'supra.dom',
	'supra.template',
	'supra.input',
	'supra.manager',

	'supra.header',
	'supra.plugin-layout',
	'supra.languagebar'
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
	 * Timer functions
	 */
	'supra.timer': {
		path: 'timer/timer.js'
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
	 * Supra.Lipsum
	 */
	'supra.lipsum': {
		path: 'lipsum/lipsum.js'
	},

	/**
	 * Y.DOM extension
	 */
	'supra.dom': {
		path: 'dom/dom.js'
	},

	'supra.io': {
		path: 'io/io.js',
		requires: ['io', 'json', 'jsonp', 'jsonp-url']
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
	 * Y.DD.DDM extension to allow shims for multiple documents
	 */
	'supra.dd-ddm': {
		path: 'dd-ddm/dd-ddm.js',
		requires: [
			'dd-ddm'
		]
	},
	'supra.dd-drop-target': {
		path: 'dd-ddm/dd-drop-target.js',
		requires: [
			'attribute',
			'node'
		]
	},

	/**
	 * Event 'exist' plugin
	 */
	'supra.event': {
		path: 'event/event.js'
	},

	/**
	 * Deferred object
	 */
	'supra.deferred': {
		path: 'event/deferred.js'
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

	'supra.button-plugin-input': {
		path: 'button/plugin-input.js',
		requires: ['plugin', 'supra.button']
	},

	/**
	 * Button widget
	 */
	'supra.button-group': {
		path: 'button-group/button-group.js',
		requires: ['widget', 'widget-child']
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

	'supra.medialibrary-data-object': {
		path: 'medialibrary/dataobject.js',
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
			'supra.medialibrary-data-object'
		]
	},

	'supra.medialibrary-list-extended': {
		path: 'medialibrary/medialist-extended.js',
		requires: [
			'supra.input',
			'supra.medialibrary',
			'supra.slideshow-multiview',
			'supra.medialibrary-list-edit',
			'supra.medialibrary-image-editor',
			'supra.medialibrary-list-folder-dd'
		]
	},

	'supra.medialibrary-list-dd': {
		path: 'medialibrary/medialist-dd.js',
		requires: [
			'plugin',
			'supra.medialibrary'
		]
	},

	'supra.medialibrary-list-folder-dd': {
		path: 'medialibrary/medialist-extended-dd.js',
		requires: [
			'plugin',
			'dd',
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
			'supra.htmleditor-dom',
			'supra.htmleditor-traverse',
			'supra.htmleditor-editable',
			'supra.htmleditor-commands',
			'supra.htmleditor-plugins',
			'supra.htmleditor-data',
			'supra.htmleditor-toolbar',

			'supra.htmleditor-plugin-image',
			'supra.htmleditor-plugin-icon',
			'supra.htmleditor-plugin-gallery',
			'supra.htmleditor-plugin-link',
			'supra.htmleditor-plugin-video',
			'supra.htmleditor-plugin-table',
			'supra.htmleditor-plugin-itemlist',
			'supra.htmleditor-plugin-table-mobile',
			'supra.htmleditor-plugin-fullscreen',
			'supra.htmleditor-plugin-formats',
			'supra.htmleditor-plugin-lists',
			'supra.htmleditor-plugin-textstyle',
			'supra.htmleditor-plugin-shortcuts',
			'supra.htmleditor-plugin-styles',
			'supra.htmleditor-plugin-paste',
			'supra.htmleditor-plugin-paragraph',
			'supra.htmleditor-plugin-paragraph-string',
			'supra.htmleditor-plugin-paragraph-text',
			'supra.htmleditor-plugin-source',
			'supra.htmleditor-plugin-fonts',
			'supra.htmleditor-plugin-align',
			'supra.htmleditor-plugin-insert',
			'supra.htmleditor-plugin-maxlength'
		],
		skinnable: true
	},
		'supra.htmleditor-base': {
			path: 'htmleditor/htmleditor-base.js',
			requires: ['supra.iframe-stylesheet-parser']
		},
		'supra.htmleditor-parser': {
			path: 'htmleditor/htmleditor-parser.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-selection': {
			path: 'htmleditor/htmleditor-selection.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-dom': {
			path: 'htmleditor/htmleditor-dom.js',
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
		'supra.htmleditor-plugin-video': {
			path: 'htmleditor/plugins/plugin-video',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-gallery': {
			path: 'htmleditor/plugins/plugin-gallery.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-insert': {
			path: 'htmleditor/plugins/plugin-insert.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-image': {
			path: 'htmleditor/plugins/plugin-image.js',
			requires: ['supra.htmleditor-base', 'supra.imageresizer', 'supra.manager']
		},
		'supra.htmleditor-plugin-icon': {
			path: 'htmleditor/plugins/plugin-icon.js',
			requires: ['supra.htmleditor-base', 'supra.imageresizer', 'supra.manager']
		},
		'supra.htmleditor-plugin-table': {
			path: 'htmleditor/plugins/plugin-table.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-table-mobile': {
			path: 'htmleditor/plugins/plugin-table-mobile.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-itemlist': {
			path: 'htmleditor/plugins/plugin-itemlist.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-fullscreen': {
			path: 'htmleditor/plugins/plugin-fullscreen.js',
			requires: ['supra.manager', 'supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-textstyle': {
			path: 'htmleditor/plugins/plugin-textstyle.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-shortcuts': {
			path: 'htmleditor/plugins/plugin-shortcuts.js',
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
		'supra.htmleditor-plugin-paragraph-text': {
			path: 'htmleditor/plugins/plugin-paragraph-text.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-source': {
			path: 'htmleditor/plugins/plugin-source.js',
			requires: ['supra.manager', 'supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-fonts': {
			path: 'htmleditor/plugins/plugin-fonts.js',
			requires: ['supra.manager', 'supra.htmleditor-base', 'supra.input-fonts', 'supra.google-fonts']
		},
		'supra.htmleditor-plugin-align': {
			path: 'htmleditor/plugins/plugin-align.js',
			requires: ['supra.manager', 'supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-maxlength': {
			path: 'htmleditor/plugins/plugin-maxlength.js',
			requires: ['supra.htmleditor-base']
		},

	/**
	 * Iframe + stylesheet parser
	 */
	'supra.iframe-stylesheet-parser': {
		path: 'iframe/stylesheet-parser.js',
		requires: ['base']
	},
	'supra.iframe': {
		path: 'iframe/iframe.js',
		requires: ['widget', 'supra.iframe-stylesheet-parser', 'supra.google-fonts'],
		skinnable: true
	},

	/**
	 * Google fonts
	 */
	'supra.google-fonts': {
		path: 'google-fonts/google-fonts.js',
		requires: ['base']
	},

	/**
	 * Image resize
	 */
	'supra.imageresizer': {
		path: 'imageresizer/imageresizer.js',
		requires: ['supra.panel', 'supra.slider', 'dd-plugin', 'supra.datatype-image', 'supra.datatype-icon'],
		skinnable: true
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
	 * DataGrid
	 */
	'supra.datagrid': {
		path: 'datagrid/datagrid.js',
		requires: ['widget', 'datasource', 'dataschema', 'datatype', 'querystring', 'supra.datagrid-row', 'supra.scrollable'],
		skinnable: true
	},
	'supra.datagrid-loader': {
		path: 'datagrid/datagrid-loader.js',
		requires: ['plugin', 'supra.datagrid']
	},
	'supra.datagrid-sortable': {
		path: 'datagrid/datagrid-sortable.js',
		requires: ['plugin', 'supra.datagrid']
	},
	'supra.datagrid-draggable': {
		path: 'datagrid/datagrid-draggable.js',
		requires: ['plugin', 'dd-delegate', 'dd-drag', 'dd-proxy', 'dd-drop', 'supra.datagrid']
	},
	'supra.datagrid-row': {
		path: 'datagrid/datagrid-row.js',
		requires: ['widget']
	},
	'supra.datagrid-new-item': {
		path: 'datagrid-new-item/datagrid-new-item.js',
		requires: ['widget', 'dd-drag'],
		skinnable: true
	},

	/**
	 * List
	 */
	'supra.list': {
		path: 'list/list.js',
		requires: [
			'supra.list-new-item'
		],
		skinnable: true
	},
	'supra.list-new-item': {
		path: 'list/new-item.js',
		requires: ['widget', 'dd-drag']
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
	 * Slider widget
	 */
	'supra.slider': {
		path: 'slider/slider.js',
		requires: ['slider'],
		skinnable: true
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
		requires: ['widget', 'anim'],
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
	'supra.tree-draggable': {
		path: 'tree/tree-draggable.js',
		requires: ['supra.tree', 'supra.tree-node-draggable']
	},
	'supra.tree-node': {
		path: 'tree/tree-node.js',
		requires: ['widget', 'widget-child']
	},
	'supra.tree-node-draggable': {
		path: 'tree/tree-node-draggable.js',
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
		requires: ['widget', 'supra.lipsum']
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
	'supra.input-select-visual': {
		path: 'input/select-visual.js',
		requires: ['supra.input-select-list']
	},
	'supra.input-slider': {
		path: 'input/slider.js',
		requires: ['supra.input-proto', 'supra.slider']
	},
	'supra.input-link': {
		path: 'input/link.js',
		requires: ['supra.input-proto']
	},
	'supra.input-tree': {
		path: 'input/tree.js',
		requires: ['supra.input-link']
	},
	'supra.input-image': {
		path: 'input/image.js',
		requires: ['supra.input-proto', 'supra.dd-drop-target']
	},
	'supra.input-file': {
		path: 'input/file.js',
		requires: ['supra.input-proto']
	},
	'supra.input-map': {
		path: 'input/map.js',
		requires: ['supra.input-proto']
	},
	'supra.input-map-inline': {
		path: 'input/map-inline.js',
		requires: ['supra.input-proto']
	},
	'supra.input-color': {
		path: 'input/color.js',
		requires: ['supra.input-proto', 'dd', 'supra.datatype-color']
	},
	'supra.input-fonts': {
		path: 'input/fonts.js',
		requires: ['supra.input-select-visual']
	},
	'supra.input-date': {
		path: 'input/date.js',
		requires: ['supra.input-proto', 'supra.calendar']
	},
	'supra.input-block-background': {
		path: 'input/block-background.js',
		requires: ['supra.input-proto', 'supra.datatype-image']
	},
	'supra.input-image-inline': {
		path: 'input/image-inline.js',
		requires: ['supra.input-block-background']
	},
	'supra.input-icon-inline': {
		path: 'input/icon-inline.js',
		requires: ['supra.input-proto']
	},
	'supra.input-video': {
		path: 'input/video.js',
		requires: ['supra.input-hidden']
	},
	'supra.input-keywords': {
		path: 'input/keywords.js',
		requires: ['supra.input-proto', 'supra.io']
	},
	'supra.input-set': {
		path: 'input/set.js',
		requires: ['supra.input-hidden']
	},
	'supra.input-group': {
		path: 'input/group.js',
		requires: ['supra.input-hidden']
	},
	'supra.input-media-inline': {
		path: 'input/media-inline.js',
		requires: ['supra.input-proto', 'supra.uploader', 'supra.datatype-image']
	},
	'supra.input-string-clear': {
		path: 'input/string.js',
		requires: ['supra.input-string', 'plugin']
	},
	
	'supra.form': {
		path: 'input/form.js',
		requires: [
			'querystring',
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
			'supra.input-select-visual',
			'supra.input-slider',
			'supra.input-link',
			'supra.input-tree',
			'supra.input-image',
			'supra.input-file',
			'supra.input-map',
			'supra.input-map-inline',
			'supra.input-color',
			'supra.input-date',
			'supra.input-block-background',
			'supra.input-image-inline',
			'supra.input-icon-inline',
			'supra.input-inline-html',
			'supra.input-inline-string',
			'supra.input-inline-text',
			'supra.input-video',
			'supra.input-keywords',
			'supra.input-set',
			'supra.input-group',
			'supra.input-media-inline',
			
			'supra.button-plugin-input',
			'supra.input-string-clear'
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
	
	//In-line text editor
	'supra.input-inline-text': {
		path: 'input/text-inline.js',
		requires: ['supra.input-inline-string']
	},
	
	//Image list, standalone
	'supra.input-image-list': {
		path: 'input/image-list.js',
		requires: ['supra.input-image', 'supra.datatype-image']
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
	 * Color
	 */
	'supra.datatype-color': {
		path: 'datatype/datatype-color.js'
	},
	
	/**
	 * Image
	 */
	'supra.datatype-image': {
		path: 'datatype/datatype-image.js'
	},
	
	/**
	 * Icon
	 */
	'supra.datatype-icon': {
		path: 'datatype/datatype-icon.js'
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
	 * Help
	 */
	'supra.help': {
		path: 'help/help.js',
		skinnable: true,
		requires: [
			'supra.help-tip'
		]
	},
	
	'supra.help-tip': {
		path: 'help/tip.js',
		requires: [
			'widget'
		]
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
			'supra.manager-action-plugin-maincontent',
			'supra.manager-action-plugin-layout-sidebar'
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