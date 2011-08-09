//Invoke strict mode
"use strict";

YUI.add('supra.tooltip', function (Y) {
	
	/**
	 * Panel class
	 * 
	 * @extends Supra.Panel
	 * @param {Object} config Attribute values
	 */
	function Tooltip (config) {
		Tooltip.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Tooltip.NAME = 'tooltip';
	Tooltip.CLASS_NAME = Y.ClassNameManager.getClassName(Tooltip.NAME);
	
	/*
	 * Tooltip attributes, default attribute values
	 */
	Tooltip.ATTRS = {
		/**
		 * Arrow visibility
		 */
		arrowVisible: {
			value: true,
			setter: '_setArrowVisible'
		},
		
		/**
		 * Text message
		 */
		textMessage: {
			value: null,
			setter: '_setTextMessage'
		},
		
		/**
		 * HTML message
		 */
		htmlMessage: {
			value: null,
			setter: '_setHTMLMessage'
		}
	};
	
	Y.extend(Tooltip, Supra.Panel, {
		/**
		 * Escape and set tooltip message
		 * 
		 * @param {String} Tooltip mesasge
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setTextMessage: function (message) {
			this.set('htmlMessage', Y.Lang.escapeHTML(message));
			return message;
		},
		
		/**
		 * Set tooltip message
		 * 
		 * @param {String} Tooltip mesasge
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setHTMLMessage: function (message) {
			if (message) {
				var node = this.get('boundingBox').one('P');
				if (!node) {
					node = Y.Node.create('<p></p>');
					this.get('boundingBox').append(node);
				}
				node.set('innerHTML', message);
			}
			return message;
		},
		
		renderUI: function () {
			Tooltip.superclass.renderUI.apply(this, arguments);
			
			if (this.get('alignPosition')) {
				this._setAlignPosition(this.get('alignPosition'));
			}
			
			if (this.get('textMessage')) {
				this._setTextMessage(this.get('textMessage'));
			}
			
			if (this.get('htmlMessage')) {
				this._setHTMLMessage(this.get('htmlMessage'));
			}
		}
	});
	
	Supra.Tooltip = Tooltip;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.panel']});