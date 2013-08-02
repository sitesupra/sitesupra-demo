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
			
			if (id in layouts) {
				return this._mixed[id] = Supra.mix({}, layouts[id], id in values ? values[id] : null);
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
			var mixed   = this._mixed,
				layouts = this._layouts,
				values  = this._values,
				id      = null;
			
			for (id in mixed) {
				return mixed[id];
			}
			for (id in layouts) {
				return mixed[id] = Supra.mix({}, layouts[id], id in values ? values[id] : null);
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
				value = null,
				
				html = '';
			
			for (; i<ii; i++) {
				property = properties[i];
				id = property.id;
				
				switch (property.type) {
					case 'InlineHTML':
						value = property.defaultValue || {'html': ''};
						model.property[id] = '<div class="yui3-content yui3-box-reset" data-supra-item-property="' + id + '">' + (value.html || '') + '</div>';
						break;
					case 'InlineString':
					case 'InlineText':
						value = property.defaultValue || '';
						model.property[id] = '<div class="yui3-box-reset" data-supra-item-property="' + id + '">' + value + '</div>';
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
						model.property[id] = '<span class="supra-image" unselectable="on" contenteditable="false" style="width: auto; height: auto;"><img class="as-layer" src="' + value + '" data-supra-item-property="' + id + '" alt="" /></span>';
						break;
					case 'InlineIcon':
						value = property.defaultValue || '';
						model.property[id] = '<span class="supra-icon" unselectable="on" contenteditable="false" style="width: auto; height: auto;"><img class="as-layer" src="' + value + '" data-supra-item-property="' + id + '" alt="" /></span>';
						break;
					case 'InlineMedia':
						value = property.defaultValue || '';
						model.property[id] = '<div class="supra-media" unselectable="on" contenteditable="false" data-supra-item-property="' + id + '"></div>';
						model.property[id + 'Type'] = 'type-media';
						break;
					case 'SelectVisual':
						// Classname
						value = property.defaultValue || '';
						model.property[id] = value + '" data-supra-item-property="' + id;
						break;
					case 'Color':
						// Background color
						value = property.defaultValue || 'transparent';
						model.property[id] = value + ';" data-supra-item-property="' + id;
						break;
				}
				
				// Mask
				switch (id) {
					case 'mask_image':
						// Background image
						value = property.defaultValue || 'about:blank';
						model.property[id] =  value + ')" data-supra-item-property="mask_image" data-tmp="(';
						break;
				}
			}
			
			html = template(model);
			
			// Merge multiple data-supra-item-property attributes into one for single attribute
			html = html.replace(/(<[^>]+data-supra-item-property[^>]*?)(\/?>)/g, function (all, tag, end) {
				var regex   = /data-supra-item-property="([^"]*)"/g,
					item    = null,
					html    = tag,
					out     = [];
				
				while (item = regex.exec(tag)) {
					html = html.replace(item[0], '');
					if (item[1]) {
						out.push(item[1]);
					}
				}
				
				return html + ' data-supra-item-property="' + out.join(' ') + '"' + end;
			});
			
			return html;
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