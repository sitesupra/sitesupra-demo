YUI.add('supra.input-group', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * List of input groups with controls to add or remove
	 * groups
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'group';
	Input.CSS_PREFIX = 'su-' + Input.NAME;
	Input.CLASS_NAME = 'su-input-group';
	
	Input.ATTRS = {
		
		// Properties for each set
		'properties': {
			value: null
		},
		
		// Only valid as separate slide
		'separateSlide': {
			value: true,
			readOnly: true
		},
		
		// Slide button style
		'buttonStyle': {
			value: 'small'
		},
		
		// Slide button label
		'labelButton': {
			value: ''
		},
		
		// Slide button icon
		'icon': {
			value: ''
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
		 * List of inputs
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
			if (this._slideId) {
				var slideshow = this.getSlideshow();
				slideshow.removeSlide(this._slideId);
			}
			
			this._slideContent = null;
			this._slideId = null;
		},
		
		/**
		 * Life cycle method, render input
		 * 
		 * @private
		 */
		renderUI: function () {
			// Create sets?
			if (this.get('separateSlide')) {
				var slideshow = this.getSlideshow();
				if (!slideshow) {
					this.set('separateSlide', false);
					Y.log('Unable to create new slide for Supra.Input.Group, because slideshow can\'t be detected');
				} else {
					// Don't create description, we have a button
					this.DESCRIPTION_TEMPLATE = null;
				}
			}
			
			Input.superclass.renderUI.apply(this, arguments);
			
			this._inputs = {};
			this._createSlide();
			this._createInputs();
			this._createButton();
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
				
				// On button click open slide
				if (this._slideButton) {
					this._slideButton.on('click', this._openSlide, this);
					
					// Disabled change
					this.on('disabledChange', function (event) {
						this._slideButton.set('disabled', event.newVal);
					}, this);
				}
			}
		},
		
		/**
		 * Returns all inputs in group
		 * 
		 * @returns {Object} Inputs in group
		 */
		getInputs: function () {
			return this._inputs || {};
		},
	

		/*
		 * ---------------------------------------- PROPERTIES ----------------------------------------
		 */
		
		
		_createInputs: function () {
			var properties = this.get('properties');
			if (!properties) return;
			
			var i = 0,
				count = properties.length,
				form  = this.getForm(),
				index = this._count,
				
				id = null,
				input = null,
				inputs = this._inputs = this._inputs || {},
				definition = null,
				
				heading = null,
				button = null,
				
				container = this._slideContent;
			
			// Create inputs
			for (; i<count; i++) {
				id = properties[i].id;
				
				definition = Supra.mix({}, properties[i], {
					'id': id,
					'name': id,
					'parent': this
				});
				
				input = form.factoryField(definition);
				input.render(container);
				
				form.addInput(input, definition);
				input.set('parent', this);
				
				inputs[id] = input;
			}
		},
		
		
		/*
		 * ---------------------------------------- SLIDESHOW ----------------------------------------
		 */
		
		
		/**
		 * Add slide to the slideshow
		 * 
		 * @private
		 */
		_createSlide: function () {
			var label = this.get('label'),
				labelButton = this.get('labelButton'),
				
				slideshow = this.getSlideshow(),
				slide_id = this.get('id') + '_' + Y.guid(),
				slide = slideshow.addSlide({'id': slide_id, 'title': label || labelButton});
			
			this._slideContent = slide.one('.su-slide-content');
			this._slideId = slide_id;
		},
		
		/**
		 * Add button to the main slide
		 * 
		 * @private
		 */
		_createButton: function () {
			var label = this.get('label'),
				labelButton = this.get('labelButton'),
				icon = this.get('icon'),
				style = this.get('buttonStyle');
			
			// Button
			var button = new Supra.Button({
				'style': style,
				'label': labelButton || label,
				'icon': icon ? icon : null
			});
			
			if (style == 'small' || style == 'small-gray') {
				button.addClass('button-section');
			} else {
				button.addClass('su-button-fill');
			}
			
			button.render(this.get('contentBox'));
			
			this._slideButton = button;
		},
		
		_openSlide: function () {
			var slideshow = this.getSlideshow();
			slideshow.set('slide', this._slideId);
		},
	
		
		/*
		 * ---------------------------------------- ATTRIBUTES ----------------------------------------
		 */
		
		
		/**
		 * Value attribute getter
		 * 
		 * @returns {Undefined} Undefined, group doesn't have its own value
		 * @private
		 */
		_getValue: function () {
		},
		
		/**
		 * Value attribute setter
		 */
		_setValue: function () {
		}
		
	});
	
	Supra.Input.Group = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
