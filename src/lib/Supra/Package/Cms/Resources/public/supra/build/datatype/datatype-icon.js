/*
 * Add color parsing and formatting
 */
YUI.add('supra.datatype-icon', function(Y) {
	//Invoke strict mode
	"use strict";
	
	var Icon = Y.namespace("DataType.Icon");
	
	/**
	 * Icon data object
	 * 
	 * @param {Object} data Icon data or icon instance which should be cloned
	 */
	Y.DataType.Icon = Icon = function (data) {
		if (data instanceof Icon) {
			this.set(data.toJSON());
		} else if (data) {
			this.set(data);
		}
	};
	
	Icon.prototype = {
		
		/**
		 * Icon id
		 * @type {String}
		 */
		id: null,
		
		/**
		 * Type, should be used only when same property name is used for image and icon
		 * @type {String}
		 */
		type: null,
		
		/**
		 * Icon width
		 * @type {Number}
		 */
		width: 64,
		
		/**
		 * Icon height
		 * @type {Number}
		 */
		height: 64,
		
		/**
		 * Icon color
		 * @type {String}
		 */
		color: '',
		
		/**
		 * Icon align position
		 * @type {String}
		 */
		align: '',
		
		
		// Data properties
		
		/**
		 * Icon SVG source
		 * @type {String}
		 */
		svg: '',
		
		/**
		 * Icon SVG source path
		 * @type {String}
		 */
		svg_path: '',
		
		/**
		 * Icon Image path
		 * @type {String}
		 */
		icon_path: '',
		
		/**
		 * Icon title
		 * @type {String}
		 */
		title: '',
		
		/**
		 * Icon keywords for search
		 * @type {String}
		 */
		keywords: '',
		
		/**
		 * Icon category
		 * @type {String}
		 */
		category: '',
		
		
		// Private
		
		/**
		 * SVG icon DOM element
		 * @type {Object}
		 * @private
		 */
		_domNode: null,
		
		/**
		 * Promise object
		 * @type {Object}
		 * @private
		 */
		_promise: null,
		
		
		/**
		 * Load icon SVG data
		 * Returns deferred object, to which when resolved is passed SVG data
		 * 
		 * @returns {Object} Deferred object
		 */
		load: function () {
			if (!this._promise) {
				var deferred = new Supra.Deferred(),
					promise = this._promise = deferred.promise();
				
				if (this.svg) {
					deferred.resolveWith(this, [this.svg]);
				} else if (this.svg_path) {
					Supra.io(this.svg_path, {
						'type': 'html'
					})
						.done(function (svg) {
							this.svg = svg;
							deferred.resolveWith(this, [svg]);
						}, this)
						.fail(function () {
							deferred.rejectWith(this, [null]);
						});
				} else {
					deferred.rejectWith(this, [null]);
				}
			}
			
			return this._promise;
		},
		
		/**
		 * Returns true if all icon data is set
		 * 
		 * @returns {Boolean} True if all icon data is set, otherwise false
		 */
		isDataComplete: function () {
			return !!this.svg;
		},
		
		/**
		 * Update icon properties
		 * 
		 * @param {Object} data Icon data
		 * @private
		 */
		set: function (key, value) {
			if (key && typeof key === 'object') {
				
				// Don't copy functions if passed in object with them
				for (var k in key) {
					if (typeof key[k] !== 'function') {
						this[k] = key[k];
					}
				}
				
				if ('svg' in key) {
					// SVG changed, DOM node is not valid representation of it anymore
					this._domNode = null;
				}
			} else if (typeof key === 'string') {
				this[key] = value;
				
				if (key === 'svg') {
					// SVG changed, DOM node is not valid representation of it anymore
					this._domNode = null;
				}
			}
		},
		
		/**
		 * Render icon into DOM
		 * 
		 * @param {Object} node Container node into which to render or SVG node which to replace
		 * @returns {Object} Promise object, on resolve SVG element element is passed as argument
		 */
		render: function (node) {
			var deferred = new Supra.Deferred(),
				promise  = deferred.promise();
			
			if (!node) {
				deferred.rejectWith(this, []);
				return promise;
			}
			
			if (node.tagName) {
				node = Y.Node(node);
			}
			if (node.test) {
				var svg = this.getDOMNode(),
					ysvg = null;
				
				if (!svg) {
					// Load SVG data and then call render again
					this.load()
						.done(function (svg) {
							this.render(node)
								.done(function (svg) {
									deferred.resolveWith(this, [svg]);
								}, this)
								.fail(function () {
									deferred.rejectWith(this, []);
								});
						}, this)
						.fail(function () {
							deferred.rejectWith(this, []);
						});
					
				} else {
					// We have SVG element, render
					if (node.get('tagName').toUpperCase() === 'SVG') {
						node.empty();
						
						// append <g /> element
						this._renderAppend(svg.childNodes, node, true);
						 
						node.setAttribute('version', '1.1');
						node.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
						node.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
						node.setAttribute('x', '0px');
						node.setAttribute('y', '0px');
						node.setAttribute('viewBox', '0 0 512 512');
						node.setAttribute('enable-background', 'new 0 0 512 512');
						node.setAttribute('xml:space', 'preserve');
						svg = node.getDOMNode();
					} else {
						svg = svg.cloneNode();
						this._renderAppend([svg], node, false);
					}
					
					// Style
					svg.setAttribute('width', (this.width ? this.width + 'px' : ''));
					svg.setAttribute('height', (this.height ? this.height + 'px' : ''));
					svg.style.fill = (this.color ? this.color : '');
					
					// ClassName
					ysvg = Y.Node(svg);
					ysvg.removeClass('align-left')
						.removeClass('align-right')
						.removeClass('align-middle');
					
					if (this.align) {
						ysvg.addClass('align-' + this.align);
					}
					
					deferred.resolveWith(this, [svg]);
				}
			} else {
				deferred.rejectWith(this, []);
			}
			
			return promise;
		},
		
		/**
		 * Returns SVG HTML
		 * 
		 * @param {Object} attr Additional attributes
		 * @param {Boolean} force Returns empty SVG even if there is not SVG data
		 * @returns {String} HTML
		 */
		toHTML: function (attr, force) {
			if (!this.svg && !force) return '';
			attr = attr || {};
			
			var svg = this.svg || '',
				attrs_str = '',
				html = '',
				key = null;
			
			attr.width = attr.width || this.width;
			attr.height = attr.height || this.height;
			attr.style = (attr.style || '') + (this.color ? ' fill: ' + this.color + ';' : '');
			attr.classname = (attr.classname || '') + (this.align ? ' align-' + this.align : '');
			
			for (key in attr) {
				attrs_str += key + '="' + attr[key] + '" ';
			}
			
			return '<svg ' + attrs_str + 'version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve">' + svg + '</svg>';
		},
		
		/**
		 * @private
		 */
		_renderAppend: function (nodes, target, clone_children) {
			var i = 0,
				ii = nodes.length,
				cloned = null;
			
			for (; i<ii; i++) {
				if (nodes[i].cloneNode) {
					if (clone_children !== false) {
						cloned = nodes[i].cloneNode();
					} else {
						cloned = nodes[i];
					}
					
					if (target.append) {
						// Y.Node
						target.append(cloned);
					} else {
						// HTMLElement
						target.appendChild(cloned);
					}
					
					if (nodes[i].childNodes && nodes[i].childNodes.length) {
						this._renderAppend(nodes[i].childNodes, cloned, true);
					}
				}
			}
		},
		
		/**
		 * Returns SVG DOM node
		 */
		getDOMNode: function () {
			if (this._domNode) return this._domNode;
			if (!this.svg) return null;
			
			var div = document.createElement('div');
			div.innerHTML = this.toHTML();
			
			this._domNode = div.firstChild; // SVG element
			return this._domNode;
		},
		
		/**
		 * Returns only data which should be encoded§
		 * 
		 * @returns {Object} All properties which should be encoded
		 */
		toURIComponent: function () {
			// Icon is not set, then send empty string
			var obj = '';
			
			if (this.id) {
				obj = {
					'id': this.id,
					'width': this.width,
					'height': this.height,
					'color': this.color,
					'align': this.align
				};
				if (this.type) {
					obj['type'] = this.type;
				}
			}
			
			return obj;
		},
		
		/**
		 * Returns JSON object
		 * 
		 * @returns {Object} All properties which should be JSON encoded
		 */
		toJSON: function () {
			return {
				'id': this.id,
				'type': this.type,
				
				'width': this.width,
				'height': this.height,
				'color': this.color,
				'align': this.align,
				
				'svg': this.svg,
				'title': this.title,
				'keywords': this.keywords,
				'category': this.category,
				
				'svg_path': this.svg_path,
				'icon_path': this.icon_path
			};
		}
		
	};
	
	Icon.parse = function (value) {
		return new Icon(value);
	};
	
	Icon.format = function (value) {
		return new Icon(value);
	};
	
}, YUI.version);