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
				Supra.mix(this, key);
				
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
		 * @returns {Object} SVG element or null if nothing was rendered
		 */
		render: function (node) {
			if (!node) return null;
			if (node.tagName) {
				node = Y.Node(node);
			}
			if (node.test) {
				var svg = this.getDOMNode(),
					ysvg = null;
				
				if (!svg) return null;
				
				if (node.get('tagName') === 'SVG') {
					node.empty();
					node.append(svg.firstChild); // append <g /> element
					svg = node;
				} else {
					svg = svg.cloneNode();
					node.append(svg);
				}
				
				// Style
				svg.style.width = (this.width ? this.width + 'px' : '');
				svg.style.height = (this.height ? this.height + 'px' : '');
				svg.style.fill = (this.color ? this.color : '');
				
				// ClassName
				ysvg = Y.Node(svg);
				ysvg.removeClass('align-left')
					.removeClass('align-right')
					.removeClass('align-middle');
				
				if (this.align) {
					ysvg.addClass('align-' + this.align);
				}
				
				return svg;
			}
			
			return null;
		},
		
		/**
		 * Returns SVG DOM node
		 */
		getDOMNode: function () {
			if (this._domNode) return this._domNode;
			if (!this.svg) return null;
			
			var div = document.createElement('div');
			div.innerHTML = this.svg;
			
			this._domNode = div.firstChild; // SVG element
			return this._domNode;
		},
		
		/**
		 * Returns only data which should be encoded§
		 * 
		 * @returns {Object} All properties which should be encoded
		 */
		toURIComponent: function () {
			if (!this.id) {
				// Icon is not set, send empty string
				return '';
			} else {
				return {
					'id': this.id,
					'width': this.width,
					'height': this.height,
					'color': this.color,
					'align': this.align
				};
			}
		},
		
		/**
		 * Returns JSON object
		 * 
		 * @returns {Object} All properties which should be JSON encoded
		 */
		toJSON: function () {
			return {
				'id': this.id,
				'width': this.width,
				'height': this.height,
				'color': this.color,
				'align': this.align,
				
				'svg': this.svg,
				'title': this.title,
				'keywords': this.keywords,
				'category': this.category
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