YUI.add("supra.input-checkbox", function (Y) {
	//Invoke strict mode
	"use strict";
	
	
	var TEMPLATE = '<div class="yui3-input-checkbox-bg" tabindex="0">\
							<span class="label-a"></span>\
							<span class="label-b"></span>\
							<span class="pin"></span>\
					  </div>';
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = "input-checkbox";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.HTML_PARSER = {
		'backgroundNode': function (srcNode) {
			var node = (srcNode.test('input') ? srcNode.previous() : srcNode.one('.yui3-input-checkbox-bg'));
			return (node && node.hasClass('yui3-input-checkbox-bg') ? node : null);
		},
		'labelNodeA': function (srcNode) {
			var background_node = this.get('backgroundNode');
			return background_node ? background_node.one('.label-a') : null;
		},
		'labelNodeB': function (srcNode) {
			var background_node = this.get('backgroundNode');
			return background_node ? background_node.one('.label-b') : null;
		},
		'pinNode': function (srcNode) {
			var background_node = this.get('backgroundNode');
			return background_node ? background_node.one('.pin') : null;
		},
		
		'labels': function (srcNode) {
			var a = srcNode.getAttribute('data-label-a'),
				b = srcNode.getAttribute('data-label-b');
			
			if (a && b) {
				return [a, b];
			} else {
				return;
			}
		}
	};
	Input.ATTRS = {
		/**
		 * Value/option list
		 */
		'labels': {
			value: ['{#buttons.yes#}', '{#buttons.no#}'],
			setter: '_setLabels'
		},
		
		/**
		 * Background node
		 */
		'backgroundNode': {
			value: null
		},
		
		/**
		 * First label 
		 */
		'labelNodeA': {
			value: null
		},
		
		/**
		 * Second label
		 */
		'labelNodeB': {
			value: null
		},
		
		/**
		 * Pin icon node
		 */
		'pinNode': {
			value: null
		},
		
		/**
		 * Default value
		 */
		'defaultValue': {
			value: true
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			//Add missing nodes
			var node = this.get('backgroundNode');
			if (!node) {
				node = Y.Node.create(TEMPLATE);
				this.set('backgroundNode', node);
				
				var label = node.one('.label-a');
				if (label) this.set('labelNodeA', label);
				
				var label = node.one('.label-b');
				if (label) this.set('labelNodeB', label);
				
				var pin = node.one('.pin');
				if (pin) this.set('pinNode', pin);
				
				node.toggleClass('active', this.get('value'));
			}
			
			this.get('labelNodeA').on('click', this._animateValueOn, this);
			this.get('labelNodeB').on('click', this._animateValueOff, this);
			this.get('pinNode').on('click', this._animateValueToggle, this);
			
			this.set('labels', this.get('labels'));
			
			//Fix overflow
			this.get('contentBox').addClass('clearfix');
			
			//On key press change selected value
			this.get('backgroundNode').on('click', this._animateValueToggle, this);
			this.get('backgroundNode').on('keyup', this._onKeyUp, this);
			
			//Hide INPUT or SELECT element
			this.get('inputNode').insert(this.get('backgroundNode'), 'before');
			this.get('inputNode').addClass('hidden');
			
			this.on('valueChange', this._afterValueChange, this);
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var lbl = this.get('labelNode');
			if (lbl) {
				lbl.on('click', this.toggle, this);
			}
		},
		
		/**
		 * Toggle value 
		 */
		toggle: function (e) {
			this._animateValueToggle();
			
			if (e) {
				e.preventDefault();
			}
		},
		
		/**
		 * On label change update values
		 * 
		 * @param {Array} labels Array of labels
		 * @return New labels attribute value
		 * @private
		 */
		_setLabels: function (labels) {
			var node = null;
			
			if (labels.length == 2) {
				node = this.get('labelNodeA');
				if (node) node.set('text', Supra.Intl.replace(labels[0] || ''));
				
				node = this.get('labelNodeB');
				if (node) node.set('text', Supra.Intl.replace(labels[1] || ''));
			}
			return labels;
		},
		
		/**
		 * Used when rendering buttons
		 * 
		 * @private
		 */
		_getInternalValue: function () {
			return this.get('value') ? '1' : '0';
		},
		
		/**
		 * Value getter.
		 * Returns value as boolean
		 * 
		 * @return Value
		 * @type {Boolean}
		 */
		_getValue: function () {
			return this.get('inputNode').get('value') == '1';
		},
		
		/**
		 * Value setter.
		 * 
		 * @param {Boolean} value Value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setValue: function (value) {
			if (typeof value === 'string') {
				value = value === 'true' || value === '1' ? true : false;
			}
			
			//Check
			this.get('inputNode').set('value', value ? '1' : '0');
			
			//Update style
			var node = this.get('backgroundNode');
			if (node) node.toggleClass('active', value);
			
			return value;
		},
		
		_animate: function (from, to) {
			if (!this.pin_anim) {
				this.pin_anim = new Y.Anim({
					'node': this.get('pinNode'),
					'duration': 0.1
				});
				this.pin_anim.on('end', function () {
					this.get('pinNode').setStyle('left', null);
				}, this);
				this.get('pinNode').setStyle('left', from + 'px');
			}
			
			this.pin_anim.stop();
			this.get('pinNode').setStyle('left', from + 'px');
			this.pin_anim.set('from', {'left': from})
						 .set('to', {'left': to})
						 .run();
		},
		_animateValueToggle: function (evt) {
			if (this.get('disabled')) return;
			
			if (this.get('value')) {
				this._animateValueOff(evt);
			} else {
				this._animateValueOn(evt);
			}
		},
		_animateValueOn: function (evt) {
			if (this.get('disabled')) return;
			
			if (!this.get('value')) {
				this._animate(28, 0);
				this.set('value', true);
			}
			if (evt && evt.type == 'click') {
				evt.halt();
			}
		},
		_animateValueOff: function (evt) {
			if (this.get('disabled')) return;
			
			if (this.get('value')) {
				this._animate(0, 28);
				this.set('value', false);
			}
			if (evt && evt.type == 'click') {
				evt.halt();
			}
		},
		
		_onKeyUp: function (event) {
			if (this.get('disabled')) return;
			
			var key = event.keyCode;
			
			if (key == 32 || key == 13) {	//Space or return key
				this._animateValueToggle();
			} else if (key == 37) {			//Left arrow
				this._animateValueOn();
			} else if (key == 39) {			//Right arrow
				this._animateValueOff();
			}
		},
		
		/**
		 * After value change trigger event
		 */
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal === true || evt.newVal == '1' ? true : false});
			}
		}
		
	});
	
	Supra.Input.Checkbox = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "anim"]});