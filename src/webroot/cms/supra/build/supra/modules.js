/**
 * List of modules, which are added to use() automatically when using Supra()
 * @type {Array}
 */
Supra.useModules = [
	'event',
	'event-delegate',
	'supra.event',
	'supra.lang',
	'substitute',
	'supra.datatype-date-parse',
	'supra.base',					// + base, node
	'supra.panel',					// + supra.button, widget, overlay
	'supra.io',						// + io, json
	'supra.dom',
	
	'supra.debug'
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
	
	/**
	 * Event 'exist' plugin
	 */
	'supra.event': {
		path: 'event/event.js'
	},
	
	/**
	 * Button widget
	 */
	'supra.button': {
		path: 'button/button.js',
		requires: ['supra.button-css']
	},
	'supra.button-css': {
		path: 'button/button.css',
		type: 'css'
	},
	
	/**
	 * Media Library widget
	 */
	'supra.medialibrary': {
		path: 'medialibrary/medialibrary.js',
		requires: [
			'supra.medialibrary-base',
			'supra.medialibrary-list'
		]
	},
	
	'supra.medialibrary-base': {
		path: 'medialibrary/base.js',
		requires: [
			'widget'
		]
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
			'supra.medialibrary-data',
			'supra.medialibrary-list-css'
		]
	},
	
	'supra.medialibrary-list-extended': {
		path: 'medialibrary/medialist-extended.js',
		requires: [
			'slider',
			'supra.form',
			'supra.medialibrary-list',
			'supra.medialibrary-slideshow'
		]
	},
	
	'supra.medialibrary-list-css' :{
		path: 'medialibrary/medialist.css',
		type: 'css'
	},
	
	'supra.medialibrary-list-dd': {
		path: 'medialibrary/medialist-dd.js',
		requires: [
			'plugin',
			'supra.medialibrary-list'
		]
	},
	
	'supra.medialibrary-slideshow': {
		path: 'medialibrary/slideshow.js',
		requires: [
			'widget',
			'anim',
			'supra.medialibrary-slideshow-css'
		]
	},
	'supra.medialibrary-slideshow-css' :{
		path: 'medialibrary/slideshow.css',
		type: 'css'
	},
	
	/**
	 * Editor widget
	 */
	'supra.editor': {
		path: 'editor/editor.js',
		requires: ['supra.editor-toolbar']
	},
	'supra.editor-toolbar': {
		path: 'editor/toolbar.js',
		requires: ['widget', 'supra.panel', 'supra.button', 'supra.tabs', 'supra.editor-toolbar-css']
	},
	'supra.editor-toolbar-css': {
		path: 'editor/toolbar.css',
		type: 'css'
	},
	
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
			
			'supra.htmleditor-plugin-insertlink',
			'supra.htmleditor-plugin-gallery',
			'supra.htmleditor-plugin-image',
			'supra.htmleditor-plugin-table',
			'supra.htmleditor-plugin-formats',
			'supra.htmleditor-plugin-lists',
			'supra.htmleditor-plugin-textstyle',
			'supra.htmleditor-plugin-style',
			'supra.htmleditor-plugin-paste',
			'supra.htmleditor-plugin-paragraph'
		]
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
			requires: ['supra.panel', 'supra.button', 'supra.htmleditor-toolbar-css']
		},
		'supra.htmleditor-toolbar-css': {
			path: 'htmleditor/toolbar.css',
			type: 'css'
		},
		
		/* Plugins */
		'supra.htmleditor-plugin-insertlink': {
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
		'supra.htmleditor-plugin-style': {
			path: 'htmleditor/plugins/plugin-style.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-paste': {
			path: 'htmleditor/plugins/plugin-paste.js',
			requires: ['supra.htmleditor-base']
		},
		'supra.htmleditor-plugin-paragraph': {
			path: 'htmleditor/plugins/plugin-paragraph.js',
			requires: ['supra.htmleditor-base']
		},
	
	/**
	 * Header widget
	 */
	'supra.header': {
		path: 'header/header.js',
		requires: ['widget', 'supra.header-item', 'supra.header-css']
	},
	'supra.header-item': {
		path: 'header/item.js',
		requires: ['widget']
	},
	'supra.header-css': {
		path: 'header/header.css',
		type: 'css'
	},
	
	/**
	 * DataTable
	 */
	'supra.datatable': {
		path: 'datatable/datatable.js',
		requires: ['widget', 'datasource', 'dataschema', 'supra.datatable-css', 'datatype', 'querystring', 'supra.datatable-row', 'supra.datatable-checkboxes']
	},
	'supra.datatable-row': {
		path: 'datatable/datatable-row.js'
	},
	'supra.datatable-checkboxes': {
		path: 'datatable/plugin-checkboxes.js',
		requires: ['supra.datatable']
	},
	'supra.datatable-css': {
		path: 'datatable/datatable.css',
		type: 'css'
	},
	
	/**
	 * Panel
	 */
	'supra.panel': {
		path: 'panel/panel.js',
		requires: ['overlay', 'supra.button', 'supra.panel-css']
	},
	'supra.panel-css': {
		path: 'panel/panel.css',
		type: 'css'
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
		requires: ['widget', 'anim', 'supra.slideshow-css']
	},
	'supra.slideshow-css': {
		path: 'slideshow/slideshow.css',
		type: 'css'
	},
	
	/**
	 * Footer
	 */
	'supra.footer': {
		path: 'footer/footer.js',
		requires: ['supra.footer-css']
	},
	'supra.footer-css': {
		path: 'footer/footer.css',
		type: 'css'
	},
	
	/**
	 * Tree widget
	 */
	'supra.tree': {
		path: 'tree/tree.js',
		requires: ['supra.tree-css', 'supra.tree-node', 'supra.tree-plugin-expand-history', 'widget', 'widget-parent']
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
	'supra.tree-css': {
		path: 'tree/tree.css',
		type: 'css'
	},
	
	/**
	 * Input widgets
	 */
	'supra.input-proto': {
		path: 'input/proto.js',
		requires: ['widget', 'supra.input-css']
	},
	'supra.input-hidden': {
		path: 'input/hidden.js',
		requires: ['supra.input-proto']
	},
	'supra.input-string': {
		path: 'input/string.js',
		requires: ['supra.input-proto']
	},
	'supra.input-path': {
		path: 'input/path.js',
		requires: ['supra.input-string']
	},
	'supra.input-checkbox': {
		path: 'input/checkbox.js',
		requires: ['supra.input-proto']
	},
	'supra.input-file-upload': {
		path: 'input/fileupload.js',
		requires: ['supra.input-proto', 'uploader']
	},
	'supra.input-select-list': {
		path: 'input/select-list.js',
		requires: ['supra.input-proto', 'supra.button']
	},
	'supra.form': {
		path: 'input/form.js',
		requires: ['supra.input-proto', 'supra.input-hidden', 'supra.input-string', 'supra.input-path', 'supra.input-checkbox', 'supra.input-file-upload', 'supra.input-select-list']
	},
	'supra.input-css': {
		path: 'input/input.css',
		type: 'css'
	},
	
	//In-line HTML editor
	'supra.input-inline-html': {
		path: 'input/html-inline.js',
		requires: ['supra.input-proto']
	},
	
	/**
	 * Calendar widget
	 */
	'supra.datatype-date-parse': {
		path: 'datatype/datatype-date-parse.js',
		requires: ['datatype-date']
	},
	
	'supra.calendar': {
		path: 'calendar/calendar.js',
		requires: ['widget', 'anim', 'datatype-date', 'supra.calendar-css']
	},
	'supra.calendar-css': {
		path: 'calendar/calendar.css',
		type: 'css'
	},
	
	/**
	 * Tabs
	 */
	'supra.tabs': {
		path: 'tabs/tabs.js',
		requires: ['widget', 'supra.tabs-css']
	},
	'supra.tabs-css': {
		path: 'tabs/tabs.css',
		type: 'css'
	},
	
	/**
	 * Authorization
	 */
	'supra.authorization': {
		path: 'authorization/authorization.js'
	},
	
	/**
	 * Manager
	 */
	'supra.manager': {
		path: 'manager/manager.js',
		requires: ['supra.authorization', 'supra.manager-base', 'supra.manager-loader', 'supra.manager-loader-actions', 'supra.manager-action', 'supra.manager-action-base', 'supra.manager-action-plugin-manager', 'supra.manager-action-plugin-base', 'supra.manager-action-plugin-panel', 'supra.manager-action-plugin-form', 'supra.manager-action-plugin-footer', 'supra.manager-action-plugin-container']
	},
	'supra.manager-base': {
		path: 'manager/base.js',
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
		requires: ['supra.manager-action-plugin-base', 'supra.form']
	},
	'supra.manager-action-plugin-footer': {
		path: 'manager/action/plugin-footer.js',
		requires: ['supra.manager-action-plugin-base', 'supra.footer']
	},
	'supra.manager-action-plugin-container': {
		path: 'manager/action/plugin-container.js',
		requires: ['supra.manager-action-plugin-base']
	}
	
};