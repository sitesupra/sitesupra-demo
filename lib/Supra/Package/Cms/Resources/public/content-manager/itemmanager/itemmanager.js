//Add module group
Supra.setModuleGroupPath('itemmanager', Supra.Manager.Loader.getActionFolder('ItemManager') + 'modules/');

//Add module definitions
Supra.addModules({
	'itemmanager.itemlist': {
		path: 'itemlist.js',
		requires: ['supra.iframe', 'plugin', 'itemmanager.itemlist-order']
	},
	'itemmanager.itemlist-order': {
		path: 'itemlist-order.js',
		requires: ['plugin', 'dd-delegate']
	},
	'itemmanager.renderer': {
		path: 'renderer.js',
		requires: ['supra.template', 'supra.google-fonts', 'plugin']
	},
	'itemmanager.collection': {
		path: 'collection.js',
		requires: ['supra.input-collection']
	}
});

Supra(
	'dd-delegate', 'dd-drop-plugin', 'dd-constrain', 'dd-proxy',
	'itemmanager.itemlist', 'itemmanager.renderer', 'itemmanager.collection',
function (Y) {

	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Add as child, when EditorToolbar will be hidden ItemManager will be hidden also (page editing is closed)
	Manager.getAction('EditorToolbar').addChildAction('ItemManager');
	
	// Defaults
	var SLIDESHOW_MAIN_SLIDE = 'propertySlideMain';
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'ItemManager',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		/**
		 * Item manager options
		 * @type {Object|Null}
		 */
		options: null,
		
		/**
		 * Widgets
		 * @type {Object}
		 * @private
		 */
		widgets: {},
		
		/**
		 * When Item Manager will be closed restore editor toolbar
		 * @type {Boolean}
		 * @private
		 */
		restoreEditorToolbar: false,
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
			//On visibility change update container class and disable/enable toolbar
			this.on('visibleChange', this.afterAttrVisibleChange, this);
			
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Add buttons to toolbar
			Manager.getAction('PageToolbar').addActionButtons(this.NAME + 'Settings', []);
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			
			//Add side buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME + 'Settings', []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': this.close
			}]);
		},
		
		setup: function () {
			var options = this.options;
			
			if (!this.renderer) {
				this.plug(Supra.ItemManagerRenderer, {
					'contentElement': options.contentElement,
					'itemTemplate': options.itemTemplate,
					'wrapperTemplate': options.wrapperTemplate,
					'properties': options.properties
				});
			} else {
				this.renderer.setAttrs({
					'contentElement': options.contentElement,
					'itemTemplate': options.itemTemplate,
					'wrapperTemplate': options.wrapperTemplate,
					'properties': options.properties
				});
			}
			
			this.setupIframe();
		},
		
		cleanup: function () {
			this.resetAll();
			
			// Restore sidebar
			var propertiesHandler = this.getBlockProperties();
			
			if (propertiesHandler) {
				propertiesHandler.showPropertiesForm();
			}
		},
		
		/**
		 * Create iframe
		 */
		setupIframe: function () {
			var content = this.one('.su-item-manager-content'),
				iframe = this.widgets.iframe;
			
			if (!iframe) {
				iframe = this.widgets.iframe = new Supra.Iframe({
					'preventNavigation': true,
					'preventExternalNavigation': true,
					'loading': true
				});
				
				iframe.render(content);
			}
			
			iframe.once('ready', this.onIframeContentReady, this);
			iframe.set('html', this.renderer.getCompleteHTML(this.options.data));
			
			return iframe;
		},
		
		onIframeContentReady: function () {
			var options = this.options,
				
				content = this.one('.su-item-manager-content'),
				iframe  = this.widgets.iframe,
				form    = this.setupForm(),
				
				rootNode = iframe.one('[data-wrapper]'),
				locator = this.widgets.locator = new Supra.Form.PropertyElementLocator({
					'form': form,
					'iframe': iframe,
					'rootNode': rootNode,
				}),
				
				propertiesHandler = this.getBlockProperties();
			
			content.removeClass('loading');
			iframe.set('loading', false);
			
			// Hide sidebar
			if (propertiesHandler) {
				propertiesHandler.hidePropertiesForm({
					// Keep buttons because form will be restored when
					// item manager will be closed
					'keepToolbarButtons': true
				});
			}
			
			// Item view
			if (!this.itemlist) {
				this.plug(Supra.ItemManagerItemList, {
					'contentElement': rootNode,
					'form': form,
					'iframe': iframe,
					'properties': options.properties
				});
			} else {
				this.itemlist.reinitialize({
					'contentElement': rootNode,
					'properties': options.properties
				});
			}
		},
		
		
		/*
		 * ----------------------- Properties form  -----------------------
		 */
		
		
		/**
		 * Hide sidebar properties form
		 */
		hidePropertiesForm: function () {
			Supra.Manager.PageContentSettings.hide();
		},
		
		/**
		 * Create form
		 */
		setupForm: function () {
			// Get form placeholder
			var form = this.widgets.form,
				slideshow,
				button,
				locator,
				properties;
			
			properties = {
				'id': 'set',
				'name': 'set',
				'type': 'Set',
				'properties': this.processProperties(this.options.properties).concat([{
					'id': '__suid',
					'name': '__suid',
					'type': 'hidden'
				}])
			};
			
			if (!form) {
				form = this.widgets.form = new Supra.Form({
					'inputs': [], // will set properties later
					'style': 'vertical',
					'autoDiscoverInputs': false,
					'slideshow': true
				});
				
				// Add collection input
				form.addInput({
					'id': 'collection',
					'name': 'collection',
					'type': 'ItemManagerCollection',
					'separateSlide': false,
					'properties': properties
				}, {});
			} else {
				form.getInput('collection').set('properties', properties);
			}
			
			return form;
		},
		
		resetAll: function () {
			var form = this.widgets.form,
				iframe = this.widgets.iframe;
			
			this.itemlist.resetAll();
			
			if (form) {
				form.getInput('collection').set('value', []);
			}
			if (iframe) {
				iframe.set('html', '');
			}
		},
		
		/**
		 * Go through properties and set up additional options where needed
		 *
		 * @param {Array} properties List of properties
		 * @returns {Array} Properties
		 * @protected
		 */
		processProperties: function (_properties) {
			var properties = Supra.mix([], _properties, true), // deep clone
				i = 0,
				ii = properties.length;
			
			for (; i<ii; i++) {
				if (properties[i].type === 'InlineImage') {
					// Image should take all available horizontal space
					properties[i].fixedCropWidth = true;
					
					// When changing crop automatically change zoom and
					// image size
					properties[i].allowCropZooming = true;
					
					// Item list will call stopEditing manually
					properties[i].autoClose = false;
				}
			}
			
			return properties;
		},
		
		
		/*
		 * ---------------------- Show / hide content ------------------------
		 */
		
		
		afterAttrVisibleChange: function (evt) {
			if (evt.newVal) {
				this.one().removeClass('hidden');
				this.setup();
			} else {
				this.one().addClass('hidden');
				this.cleanup();
			}
			
			Manager.getAction('EditorToolbar').set('disabled', evt.newVal);
		},
		
		show: function () {
			if (!this.get('visible')) {
				this.set('visible', true);
				this.animateIn();
				Supra.Manager.PageHeader.back_button.hide();
			}
		},
		
		hide: function () {
			if (this.get('visible')) {
				// Hide settings form
				if (this.settings_form && this.settings_form.get('visible')) {
					Manager.PageContentSettings.hide();
				}
				
				this.animateOut();
				Supra.Manager.PageHeader.back_button.show();
			}
		},
		
		animateIn: function () {
			var node = this.one(),
				width = Y.DOM.viewportRegion().width;
			
			// Use CSS transforms + transition
			node.addClass('hidden');
			node.setStyle('transform', 'translate(' + width + 'px, 0px)');
			
			Y.later(1, this, function () {
				// Only now remove hidden to prevent unneeded animation
				node.removeClass('hidden');
			});
			
			// Use CSS
			Y.later(32, this, function () {
				// Animate
				node.setStyle('transform', 'translate(0px, 0px)');
				
				Y.later(500, this, function () {
					//this.itemlist.set('visible', true);
					// @TODO
				});
			});
		},
		
		animateOut: function () {
			var node = this.one(),
				width = Y.DOM.viewportRegion().width;
			
			// Use CSS transforms + transition
			node.setStyle('transform', 'translate(' + width + 'px, 0px)');
			Y.later(350, this, function () {
				this.set('visible', false);
			});
		},
		
		
		/**
		 * Returns block
		 *
		 * @returns {Object|Null} Block
		 */
		getBlock: function () {
			var host = this.options.host,
				block;
			
			if (host) {
				return host.get('root');
			}
		},
		
		/**
		 * Returns block properties handler instance
		 *
		 * @returns {Object|Null} Block properties
		 */
		getBlockProperties: function () {
			var host = this.options.host,
				propertiesHandler;
			
			if (host && host.getParentWidget) {
				return host.getParentWidget('page-content-properties');
			}
		},
		
		/**
		 * Returns data
		 *
		 * @returns {Array} Data
		 */
		getData: function () {
			var data = this.widgets.form.getValues().collection,
				i = 0,
				ii = data ? data.length : 0,
				order = this.itemlist.order.getOrder();
			
			data.sort(function (a, b) {
				return (order[a.__suid] > order[b.__suid] ? 1 : -1);
			});
			
			for (; i<ii; i++) {
				delete(data[i].__suid);
			}
			
			return data;
		},
		
		
		/*
		 * ------------------------ Open / Close ----------------------------
		 */
		
		
		/**
		 * Apply changes, call callback with new data
		 * 
		 * @private
		 */
		close: function () {
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			// Open editor toolbar if it was visible when Item manager was
			// opened
			if (this.restoreEditorToolbar) {
				Manager.EditorToolbar.execute();
			}
			
			this.hide();
			
			if (this.options.callback) {
				this.options.callback(this.getData(), true);
			}
		},
		
		/**
		 * Apply options
		 */
		setOptions: function (_options) {
			var options = Supra.mix({
				'data': [],
				'host': null,
				'callback': null,
				'contentElement': null,
				
				'itemTemplate': '',
				'wrapperTemplate': '',
				'properties': [],
				'imageUploadFolder': 0
			}, _options);
			
			if (!options.contentElement) {
				// There is nothing we can do without an element, show error
				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['itemmanager', 'error_template']),
					'buttons': [{'id': 'error'}]
				});
				
				return false;
			}
			
			this.options = options;
			return true;
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} options Gallery options: data, callback, block
		 */
		execute: function (options) {
            // If already visible, then stop, because this manager was called twice
            if (this.get('visible')) return;
			if (!this.setOptions(options)) return;
			
			// Hide properties form if it's already opened
			this.hidePropertiesForm();
			
			if (Manager.EditorToolbar.get('visible')) {
				Manager.EditorToolbar.hide();
				this.restoreEditorToolbar = true;
			} else {
				this.restoreEditorToolbar = false;
			}
			
			if (!Manager.getAction('PageToolbar').inHistory(this.NAME)) {
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
			}
			
			this.show();
		}
		
	});
	
});
