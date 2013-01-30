YUI.add('slideshowmanager.layouts', function (Y) {
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
	
	SlideLayouts.NAME = 'slideshowmanager-layouts';
	SlideLayouts.NS = 'layouts';
	
	SlideLayouts.ATTRS = {
		'layouts': {
			value: null,
			setter: '_setLayouts'
		},
		'properties': {
			value: null,
			setter: '_setProperties' 
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
		 * List of 'layout' property values
		 * @type {Object}
		 * @private
		 */
		_values: null,
		
		/**
		 * Full layout info, a mix of property value and layout
		 * @type {Object}
		 * @private
		 */
		_mixed: null,
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			this._mixed = {};
			this._values = {};
			this._layouts = {};
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
			this._mixed = {};
			this._values = {};
			this._layouts = {};
		},
		
		
		/* ---------------------------- GETTERS --------------------------- */
		
		
		/**
		 * Returns layout data by id
		 * 
		 * @param {String} id Layout id
		 * @returns {Object} Layout data or null
		 */
		getLayoutById: function (id) {
			if (this._mixed[id]) {
				return this._mixed[id];
			}
			
			var layouts = this._layouts,
				values = this._values,
				mixed = null;
			
			if (id in layouts && id in values) {
				return this._mixed[id] = Supra.mix({}, layouts[id], values[id]);
			} else {
				return null;
			}
		},
		
		/**
		 * Returns layout HTML
		 * 
		 * @param {String} id Layout id
		 * @returns {String} HTML for given layout
		 */
		getLayoutHtml: function (id) {
			var layout = this.getLayoutById(id),
				template = Supra.Template.compile(layout.html, 'layout_' + id),
				model = {'property': {}},
				
				properties = this.get('host').options.properties,
				i = 0,
				ii = properties.length,
				property = null,
				id = null,
				value = null;
			
			for (; i<ii; i++) {
				property = properties[i];
				id = property.id;
				
				if (property.inline) {
					if (property.type == 'InlineHTML') {
						value = property.defaultValue || {'html': ''};
						model.property[id] = '<div class="yui3-content yui3-box-reset" data-supra-item-property="' + id + '">' + (value.html || '') + '</div>';
					} else if (property.type == 'InlineString') {
						value = property.defaultValue || '';
						model.property[id] = '<span class="yui3-inline-reset" data-supra-item-property="' + id + '">' + value + '</span>';
					} else if (property.type == '...') {
						// @TODO Implement image/video selection input
					}
				}
				
				if (property.type == 'InlineImage' || property.type == 'BlockBackground') {
					value = property.defaultValue || '';
					model.property[id] = value + '" data-supra-item-property="' + id;
				}
			}
			
			return template(model);
		},
		
		/**
		 * Returns default layout data or first layout
		 * if defaultValue for 'layout' input is not set
		 * 
		 * @returns {Object} Layout data
		 * @deprecated Instead should be used SlideData method 'getNewSlideData'
		 */
		/*
		getDefaultLayout: function () {
			var layouts = this._layouts,
				id = this._defaultLayoutId,
				layout = null;
			
			if (id) {
				layout = this.getLayoutById(id);
				if (layout) {
					return layout;
				}
			}
			
			for (id in layouts) {
				layout = this.getLayoutById(id);
				if (layout) {
					return layout;
				}
			}
			
			return null;
		},
		*/
		
		
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
			return null;
		},
		
		/**
		 * Properties attribute setter
		 * 
		 * @param {Object} properties Properties
		 * @private
		 */
		_setProperties: function (properties) {
			properties = properties || [];
			
			var i = 0,
				ii = properties.length,
				output = {},
				values = null,
				j = 0,
				jj = 0,
				subvalues = null,
				k = 0,
				kk = 0;
			
			// Search for layout property and extract values
			for (; i<ii; i++) {
				if (properties[i].id === 'layout') {
					values = properties[i].values;
					j = 0;
					jj = values.length;
					
					for (; j<jj; j++) {
						
						subvalues = values[j].values;
						if (subvalues) {
							k = 0;
							kk = subvalues.length;
							
							for (; k<kk; k++) {
								output[subvalues[k].id] = subvalues[k];
							}
							
						} else {
							output[values[j].id] = values[j];
						}
						
					}
					
					break;
				}
			}
			
			this._values = output;
			return null;
		}
		
	});
	
	Supra.SlideshowManagerLayouts = SlideLayouts;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.template']});