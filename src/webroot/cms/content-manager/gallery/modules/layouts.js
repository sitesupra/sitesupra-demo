YUI.add('gallery.layouts', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	/**
	 * Layout data functions
	 */
	function SlideLayouts (config) {
		SlideLayouts.superclass.constructor.apply(this, arguments);
	}
	
	SlideLayouts.NAME = 'gallery-layouts';
	SlideLayouts.NS = 'layouts';
	
	SlideLayouts.ATTRS = {
		'layouts': {
			value: null,
			setter: '_setLayouts',
			getter: '_getLayouts'
		}
	};
	
	Y.extend(SlideLayouts, Y.Plugin.Base, {
		
		/**
		 * List of layouts indexed by id
		 * @type {Object}
		 * @private
		 */
		_layouts: null,
		
		/**
		 * List of layouts
		 * @type {Array}
		 * @private
		 */
		_layoutsArr: null,
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			this._layouts = {};
			this._layoutsArr = {};
		},
		
		/**
		 * Automatically called by Base, during destruction
		 */
		destructor: function () {
			this.resetAll();
		},
		
		/**
		 * Reset cache, clean up
		 */
		resetAll: function () {
			this._layouts = {};
			this._layoutsArr = {};
		},
		
		
		/* ---------------------------- GETTERS --------------------------- */
		
		
		/**
		 * Returns list of all layouts
		 * 
		 * @returns {Array} List of all layouts
		 */
		getAllLayouts: function () {
			return this._layoutsArr || [];
		},
		
		/**
		 * Returns layout data by id
		 * 
		 * @param {String} id Layout id
		 * @returns {Object} Layout data or null
		 */
		getLayoutById: function (id) {
			if (this._layouts[id]) {
				return this._layouts[id];
			} else {
				return null;
			}
		},
		
		/**
		 * Returns layout data for first layout in the list
		 * 
		 * @returns {Object} Layout data or null
		 */
		getDefaultLayout: function () {
			var layouts = this._layouts,
				id      = null;
			
			for (id in layouts) {
				return layouts[id];
			}
			
			return null;
		},
		
		/**
		 * Returns layout HTML
		 * 
		 * @param {String} id Layout id
		 * @returns {String} HTML for given layout
		 */
		getLayoutHtml: function (id) {
			var layout = this.getLayoutById(id) || this.getDefaultLayout(),
				template = Supra.Template.compile(layout.html, 'layout_' + id),
				model = {
					'property': {},
					'supra': {
						'cmsRequest': true
					},
					'supraBlock': {
						'property': Y.bind(function (name) {
							var data = this.get('host').options.data;
							return name in data ? data[name] : '';
						}, this)
					}
				},
				
				properties = this.get('host').options.properties,
				i = 0,
				ii = properties.length,
				property = null,
				id = null,
				value = null;
			
			for (; i<ii; i++) {
				property = properties[i];
				id = property.id;
				
				switch (property.type) {
					case 'InlineHTML':
						value = property.defaultValue || {'html': ''};
						model.property[id] = '<div class="yui3-content-inline yui3-box-reset" data-supra-item-property="' + id + '">' + (value.html || '') + '</div>';
						break;
					case 'InlineString':
					case 'InlineText':
						value = property.defaultValue || '';
						model.property[id] = '<span class="yui3-content-inline yui3-inline-reset" data-supra-item-property="' + id + '">' + value + '</span>';
						break;
					case 'BlockBackground':
						value = property.defaultValue || '';
						model.property[id] = value + '" data-supra-item-property="' + id;
						break;
					case 'Image':
						value = property.defaultValue || '';
						model.property[id] = value + 'about:blank)" data-supra-item-property="' + id + '" data-tmp="(';
						break;
					case 'InlineImage':
						value = property.defaultValue || '';
						model.property[id] = '<span class="supra-image" unselectable="on" contenteditable="false" style="width: auto; height: auto;"><img src="' + value + '" data-supra-item-property="' + id + '" alt="" /></span>';
						break;
					case 'InlineIcon':
						value = property.defaultValue || '';
						model.property[id] = '<span class="supra-icon" unselectable="on" contenteditable="false" style="width: auto; height: auto;"><img src="' + value + '" data-supra-item-property="' + id + '" alt="" /></span>';
						break;
					case 'InlineMedia':
						value = property.defaultValue || '';
						model.property[id] = '<div class="supra-media" unselectable="on" contenteditable="false" data-supra-item-property="' + id + '"></div>';
						model.property[id + 'Type'] = 'type-media';
						break;
				}
			}
			
			return template(model);
		},
		
		/**
		 * Returns layout which has property with given type
		 * 
		 * @param {String} type Property type
		 * @returns {Object} Layout data or null
		 */
		getLayoutByPropertyType: function (type, filter) {
			var property = this.get('host').settings.getPropertyByType(type, filter),
				is_inline = (type in Supra.Input && Supra.Input[type].IS_INLINE);
			
			if (!property) {
				// There are no properties with given type
				return null;
			}
			
			var layouts = this._layouts,
				id      = null,
				regex   = new RegExp('\{\{[^\}]*property\\.' + property.id + '[^a-zA-Z0-9]');
			
			if (is_inline) {
				// Go through all layouts and check if it's actually there
				for (id in layouts) {
					// Search property.NAME in html
					if (regex.test(layouts[id].html)) {
						return layouts[id];
					}
				}
			} else {
				// All layouts has same non-inline properties
				return this.getDefaultLayout();
			}
			
			return null;
		},
		
		
		/* ---------------------------- ATTRIBUTES --------------------------- */
		
		/**
		 * Layouts attribute setter
		 * 
		 * @param {Object} layouts Layouts
		 * @private
		 */
		_setLayouts: function (layouts) {
			layouts = layouts || [];
			
			var indexed = {},
				i = 0,
				ii = layouts.length;
			
			for (; i<ii; i++) {
				indexed[layouts[i].id] = layouts[i];
			}
			
			this._layouts = indexed;
			this._layoutsArr = layouts;
			
			return null;
		},
		
		/**
		 * Layouts attribute getter
		 * 
		 * @returns {Obect} Layouts
		 * @private
		 */
		_getLayouts: function () {
			return this._layouts;
		}
		
	});
	
	Supra.GalleryLayouts = SlideLayouts;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.template']});