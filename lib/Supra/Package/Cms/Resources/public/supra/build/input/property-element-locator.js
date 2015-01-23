/**
 * Utility to find form elements inside ifrmae
 */
YUI.add('supra.form-property-element-locator', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Folder rename plugin
	 * Saves item properties when they change
	 */
	function FormPropertyElementLocator (config) {
		FormPropertyElementLocator.superclass.constructor.apply(this, arguments);
	}
	
	FormPropertyElementLocator.NAME = 'propertyElementLocator';
	
	FormPropertyElementLocator.ATTRS = {
		
		/**
		 * Supra.Iframe instance
		 * @type {Object}
		 */
		'iframe': {
			value: null
		},
		
		/**
		 * Supra.Form instance
		 * @type {Object}
		 */
		'form': {
			value: null
		},
		
		/**
		 * Root element
		 * @type {Object}
		 */
		'rootNode': {
			value: null
		}
		
	};
	
	Y.extend(FormPropertyElementLocator, Y.Base, {
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			this.after('formChange', this._setAttrForm, this);
			
			var form = this.get('form');
			if (form) {
				this._setAttrForm({'newVal': form, 'prevVal': null});
			}
		},
		
		/**
		 * Returns window for iframe
		 * 
		 * @returns {Object} Window
		 */
		getWin: function () {
			return this.get('iframe').get('win');
		},
		
		/**
		 * Returns document for iframe
		 * 
		 * @returns {Object} Document
		 */
		getDoc: function () {
			return this.get('iframe').get('doc');
		},
		
		/**
		 * Find an element inside root node
		 *
		 * @param {String} selector CSS selector
		 * @returns {Object|Null} Node
		 */
		one: function (selector) {
			return this.get('rootNode').one(selector);
		},
		
		/**
		 * Returns iframe DOM element for input
		 *
		 * @param {Object|String} Input or input name
		 */
		getInputElement: function (input) {
			var id = this.getDOMNodeId(input),
				doc = this.get('iframe').get('doc');
			
			if (id) {
				return Y.Node(doc).one('#' + id);
			} else {
				return null;
			}
		},
		
		/**
		 * Returns iframe DOM node id for input
		 *
		 * @param {Object} input Input
		 * @returns {String} DOM node path
		 * @protected
		 */
		getDOMNodeId: function (input) {
			var root = this.get('rootNode'),
				form,
				id,
				path = [],
				obj = input;
			
			if (typeof obj === 'string') {
				path.unshift(obj);
			} else {
				while (obj && obj.isInstanceOf('input')) {
					path.unshift(obj.get('name') || obj.get('id'));
					obj = obj.get('parent');
				}
			}
			
			if (root) {
				id = this.getRootNodeId();
				if (id) {
					path.unshift(id);
				}
			}
			
			return path.join('_');
		},
		
		/**
		 * Returns root element ID
		 *
		 * @returns {String} Root node id
		 */
		getRootNodeId: function () {
			var root = this.get('rootNode'),
				id   = root ? root.getAttribute('id') : '';
			
			if (id && id.indexOf('yui_') !== 0) {
				return id;
			} else {
				return '';
			}
		},
		
		/**
		 * Returns true if there are any inline elements, otherwise false
		 *
		 * @param {Object} properties Form properties
		 * @param {Object} data Form data
		 * @returns {Boolean} True if there are inline properties with valid elements
		 */
		hasInlineInputs: function (properties, data) {
			var root = this.get('rootNode'),
				ids  = this._getDOMNodeIds(properties, data, this.getRootNodeId()),
				i    = 0,
				ii   = ids.length,
				doc  = this.get('iframe').get('doc');
			
			for (; i<ii; i++) {
				if (Y.Node(doc).one('#' + ids[i].id)) return true;
			}
			
			return false;
		},
		
		/**
		 * Returns true if there are any inline HTML elements, otherwise false
		 *
		 * @param {Object} properties Form properties
		 * @param {Object} data Form data
		 * @returns {Boolean} True if there are inline properties with valid elements
		 */
		hasHtmlInputs: function (properties, data) {
			var root = this.get('rootNode'),
				ids  = this._getDOMNodeIds(properties, data, this.getRootNodeId()),
				i    = 0,
				ii   = ids.length,
				doc  = this.get('iframe').get('doc');
			
			for (; i<ii; i++) {
				if (ids[i].property.type === 'InlineHTML' && Y.Node(doc).one('#' + ids[i].id)) {
					return true;
				}
			}
			
			return false;
		},
		

		/**
		 * Returns iframe DOM node ids of all inputs
		 * 
		 * @param {Object} properties Form properties
		 * @param {Object} data Form property data
		 * @returns {Array} List of DOM node ids
		 * @protected
		 */
		_getDOMNodeIds: function (properties, data, prefix) {
			var i = 0,
				ii = properties ? properties.length : 0,
				property,
				ids = [],
				name,
				k, kk,
				value;
			
			for (; i<ii; i++) {
				property = properties[i];
				name = (prefix ? prefix + '_' : '') + (property.name || property.id);
				value = data && data[property.id] ? data[property.id].value : null;
				
				if (property.type === 'Collection') {
					// Go through data
					property = property.properties;
					if (property && value && value.length) {
						for (k=0, kk=value.length; k<kk; k++) {
							if (property.type === 'Set') {
								// Sets inside collection are not prefixed
								ids = ids.concat(this._getDOMNodeIds(property.properties, value[k], name + '_' + k));
							} else {
								ids.push({
									'id': name + '_' + k,
									'property': property,
									'value': value[k].value
								});
							}
						}
					}
				} else if (property.type === 'Set') {
					ids = ids.concat(this._getDOMNodeIds(property.properties, value, name));
				} else {
					ids.push({
						'id': name,
						'property': property,
						'value': value
					});
				}
			}
			
			return ids;
		},
		
		
		/**
		 * Handle form attribute change
		 *
		 * @param {Object} e Event facade object
		 * @protected
		 */
		_setAttrForm: function (e) {
			if (e.newVal === e.prevVal) return;
			
			var NS = FormPropertyElementLocator.NAME;
			
			if (e.prevVal && e.prevVal[NS] === this) {
				e.prevVal.set(NS, null);
			}
			if (e.newVal) {
				e.newVal.set(NS, this);
			}
		}
		
	});
	
	
	Supra.Form.PropertyElementLocator = FormPropertyElementLocator;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['base']});
