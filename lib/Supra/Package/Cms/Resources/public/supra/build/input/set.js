YUI.add('supra.input-set', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * List of input groups with controls to add or remove
	 * groups
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-set';
	Input.CSS_PREFIX = 'su-' + Input.NAME;
	Input.CLASS_NAME = 'su-input-set';
	
	Input.ATTRS = {
		// Image node which is edited
		"targetNode": {
			value: null
		},
		
		// Properties for each set
		'properties': {
			value: null
		},
		
		// Render widget into separate slide and add
		// button to the place where this widget should be
		'separateSlide': {
			value: true
		},
		
		// Button label to use instead of "Label"
		'labelButton': {
			value: ''
		},
		
		// Button icon to use
		'icon': {
			value: null
		},
		
		// Default value
		'defaultValue': {
			value: []
		}
	};
	
	Input.HTML_PARSER = {
		
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		
		/**
		 * Slide content node
		 * @type {Object}
		 * @private
		 */
		_slideContent: null,
		
		/**
		 * Button to open slide
		 * @type {Object}
		 * @private
		 */
		_slideButton: null,
		
		/**
		 * Slide name
		 * @type {String}
		 * @private
		 */
		_slideId: null,
		
		/**
		 * List of set inputs
		 * @type {Object}
		 * @private
		 */
		_inputs: null,
		
		
		
		/**
		 * On desctruction life cycle clean up
		 * 
		 * @private
		 */
		destructor: function () {
			this._removeInputs();
			
			if (this._slideId) {
				var slideshow = this.getSlideshow();
				slideshow.removeSlide(this._slideId);
			}
			
			this._slideContent = null;
			this._slideId = null;
			this._inputs = [];
			
			this._fireResizeEvent();
		},
		
		/**
		 * Life cycle method, render input
		 * 
		 * @private
		 */
		renderUI: function () {
			this._inputs = [];
			
			if (this.get('separateSlide')) {
				var slideshow = this.getSlideshow();
				if (!slideshow) {
					this.set('separateSlide', false);
					Y.log('Unable to create new slide for Supra.Input.Set "' + this.get('id') + '", because slideshow can\'t be detected');
				} else {
					// Don't create description, we have a button
					this.DESCRIPTION_TEMPLATE = null;
				}
			}
			
			Input.superclass.renderUI.apply(this, arguments);
			
			// Create slide or render data
			if (!this.get('separateSlide')) {
				this._renderInputs();
			} else {
				this._renderSlide();
			}
			
			// Set inital value
			var value = this.get('value');
			if (value) {
				this._applyValue(value);
			}
		},
		
		/**
		 * Life cycle method, attach event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			// When slide is opened for first time create inputs
			if (this.get('separateSlide')) {
				var slideshow = this.getSlideshow();
				var evt_handle = slideshow.on('slideChange', function (evt) {
					if (evt.newVal == this._slideId) {
						evt_handle.detach();
						this._renderInputs();
					}
				}, this);
				
				// On button click open slide
				this._slideButton.on('click', this._openSlide, this);
				
				// Disabled change
				this.on('disabledChange', function (event) {
					this._slideButton.set('disabled', event.newVal);
				}, this);
			}
			
			// Change event
			this.on('valueChange', this._afterValueChange, this);
			this.on('propertiesChange', this._afterPropertiesChange, this);
		},
		
		
		/*
		 * ------------------------- Properties ---------------------------
		 */
		
		/**
		 * Returns property by id
		 *
		 * @param {String} id Property id
		 * @param {Array} [arr] Array of properties
		 * @returns {Object|Null} Property
		 * @protected
		 */
		getProperty: function (id, arr) {
			var props = arr || this.get('properties');
			
			props = Y.Array.filter(props, function (value) {
				return value.id === id;
			});
			
			return props.length ? props[0] : null;
		},
		
		/**
		 * After properties change add/remove inputs
		 *
		 * @param {Object} e Event facade object 
		 * @protected
		 */
		_afterPropertiesChange: function (e) {
			var props = e.newVal,
				prevProps = e.prevVal,
				prop,
				i = 0,
				ii = prevProps ? prevProps.length : 0,
				
				inputs = this._inputs,
				
				id,
				container,
				form,
				data,
				
				targetNode = this.get('targetNode'),
				inputNode;
			
			for (; i<ii; i++) {
				if (!props || !this.getProperty(prevProps[i].id, props)) {
					// Remove input
					if (prevProps[i].id in inputs) {
						inputs[prevProps[i].id].destroy(true /* destroy all nodes */);
						delete(inputs[prevProps[i].id]);
					}
				}
			}
			
			// Find which inputs are still missing and create them
			ii = props ? props.length : 0;
			
			if (ii) {
				container = this.getInputContainer();
				form  = this.getForm();
				data = this.get('value');
				
				for (i=0; i<ii; i++) {
					if (!this.getProperty(props[i].id, prevProps)) {
						// Add input
						id = properties[i].id;
						
						if (properties[i].type === 'Set' || properties[i].type === 'Collection') {
							inputNode = targetNode;
						} else {
							inputNode = targetNode ? targetNode.one('#' + targetNode.getAttribute('id') + '_' + id) : null;
						}
						
						console.log('SET:', property.id, inputNode ? inputNode.getDOMNode() : null);
						
						input = form.factoryField(Supra.mix({}, props[i], {
							'id': id + '_' + Y.guid(),
							'name': id,
							'value': data[id],
							'parent': this,
							'containerNode': node,
							
							// Set target node, because child may be inline editable
							'targetNode': inputNode
						}));
						
						input.render(container);
						// @TODO Fix order
						
						//input.after('change', this._fireChangeEvent, this);
						input.after('valueChange', this._fireChangeEvent, this);
						input.on('input', this._fireInputEvent, this, id);
						
						inputs[id] = input;
					}
				}
			}
		},
		
		_renderInputs: function () {
			this._afterPropertiesChange(this.get('properties'), {});
		},
		
		getInputContainer: function () {
			if (this.get('separateSlide')) {
				return this._slideContent;
			} else {
				return this.get('contentBox');
			}
		},
		
		/**
		 * Returns widgets for set
		 * 
		 * @param {Number} index Set index
		 * @returns {Object} List of all widgets for set
		 */
		getInputs: function () {
			return this._inputs;
		},
		
		
		/*
		 * ---------------------------------------- SLIDESHOW ----------------------------------------
		 */
		
		
		/**
		 * Add slide to the slideshow
		 * 
		 * @private
		 */
		_renderSlide: function () {
			var label = this.get('label'),
				labelButton = this.get('labelButton'),
				icon = this.get('icon'),
				
				slideshow = this.getSlideshow(),
				slide_id = this.get('id') + '_' + Y.guid(),
				slide = slideshow.addSlide({
					'id': slide_id,
					'title': label || labelButton
				});
			
			this._slideContent = slide.one('.su-slide-content');
			this._slideId = slide_id;
			
			// Button
			var button = new Supra.Button({
				'style': icon ? 'icon' : 'small',
				'label': labelButton || label,
				'icon': icon
			});
			
			button.addClass('button-section');
			button.render(this.get('contentBox'));
			
			this._slideButton = button;
		},
		
		_openSlide: function () {
			var slideshow = this.getSlideshow();
			slideshow.set('slide', this._slideId);
		},
		
		/**
		 * Fire resize event
		 * 
		 * @param {Object} node Node which content changed
		 * @private
		 */
		_fireResizeEvent: function () {
			var container = null;
			
			if (this.get('separateSlide')) {
				container = this._slideContent;
			} else {
				container = this.get('contentBox');
			}
			
			if (container) {
				container = container.closest('.su-scrollable-content');
				
				if (container) {
					container.fire('contentresize');
				}
			}
		},
		
		
		/*
		 * ---------------------------------------- VALUE ----------------------------------------
		 */
		
		
		/**
		 * Trigger value change events
		 * 
		 * @private
		 */
		_fireChangeEvent: function () {
			this._silentValueUpdate = true;
			this.set('value', this.get('value'));
			this._silentValueUpdate = false;
		},
		
		_fireInputEvent: function (event, property) {
			this.fire('input', {
				'value': event.value,
				'property': property
			});
		},
		
		/**
		 * Apply value
		 * 
		 * @param {Object} value New value
		 * @private
		 */
		_applyValue: function (value) {
			// If we are updating 'value' then don't change UI
			// If inputs hasn't been rendered then we can't set value
			if (!this.get('rendered') || !this._inputs || this._silentValueUpdate) return value;
			
			var properties = this.get('properties'),
				property,
				i = 0,
				ii = properties.length,
				
				inputs = this._inputs,
				input;
			
			for (; i<ii; i++) {
				property = properties[i];
				input = inputs[property.id];
				
				if (input) {
					if (value && property.id in value) {
						input.set('value', value[property.id]);
					} else {
						input.resetValue();
					}
				}
			}
		},
		
		/**
		 * Value attribute setter
		 *
		 * @param {Object} value New attribute value
		 * @returns {Oject} Value
		 * @protected
		 */
		_setValue: function (value) {
			return value ? value : {};
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @returns {Object} Value
		 * @private
		 */
		_getValue: function (value) {
			// If inputs hasn't been rendered then we can't get values from
			// inputs which doesn't exist
			if (!this.get('rendered')) return value;
			
			var properties = this.get('properties'),
				property,
				i = 0,
				ii = properties.length,
				
				inputs = this._inputs,
				values = {};
			
			for (; i<ii; i++) {
				property = properties[i];
				
				if (property.id in inputs) {
					values[property.id] = inputs[property.id].get('saveValue');
				}
			}
			
			return values;
		},
		
		_afterValueChange: function (evt) {
			this._applyValue(evt.newVal);
			this.fire('change', {'value': evt.newVal});
		}
		
	});
	
	Supra.Input.Set = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
