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
		
		// Properties for each set
		'properties': {
			value: null
		},
		
		// Render widget into separate slide and add
		// button to the place where this widget should be
		'separateSlide': {
			value: true
		},
		
		// Add more button label
		'labelAdd': {
			value: 'Add more'
		},
		
		// Remove button label
		'labelRemove': {
			value: 'Remove'
		},
		
		// Item number label
		'labelItem': {
			value: '#%s'
		},
		
		// Button label to use instead of "Label"
		'labelButton': {
			value: ''
		},
		
		// Button icon to use
		'icon': {
			value: null
		},
		
		// Minimal set count
		'minCount': {
			value: 0
		},
		
		// Maximal set count
		'maxCount': {
			value: 0
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
		 * Set count
		 * @type {Number}
		 * @private
		 */
		_count: 0,
		
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
		 * "Add more" button
		 * @type {Object}
		 * @private
		 */
		_addButton: null,
		
		/**
		 * List of set nodes
		 * @type {Object}
		 * @private
		 */
		_nodes: null,
		
		/**
		 * List of set inputs, array of objects
		 * @type {Array}
		 * @private
		 */
		_inputs: null,
		
		/**
		 * Value is beeing updated by setter
		 * Don't trigger event
		 * @type {Boolean}
		 * @private
		 */
		_silentValueUpdate: false,
		
		/**
		 * Values has been rendered
		 * @type {Boolean}
		 * @private
		 */
		_valuesRendered: false,
		
		/**
		 * Focused list index
		 * @type {Number}
		 * @private
		 */
		_focusedSetIndex: 0,
		
		
		
		/**
		 * On desctruction life cycle clean up
		 * 
		 * @private
		 */
		destructor: function () {
			var count = this._count,
				i = count-1;
			
			for (; i >= 0; i--) {
				this._removeSet(i);
			}
			
			if (this._slideId) {
				var slideshow = this.getSlideshow();
				slideshow.removeSlide(this._slideId);
			}
			
			this._slideContent = null;
			this._slideId = null;
			this._inputs = [];
			this._nodes = [];
			this._count = 0;
			
			this._fireResizeEvent();
		},
		
		/**
		 * Life cycle method, render input
		 * 
		 * @private
		 */
		renderUI: function () {
			this._count = 0;
			this._nodes = [];
			this._inputs = [];
			
			// Create sets?
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
			
			// New item button
			var button = this._addButton = new Supra.Button({
				'label': this.get('labelAdd'),
				'style': 'small-gray'
			});
			button.addClass(button.getClassName('fill'));
			
			// Create slide or render data
			if (!this.get('separateSlide')) {
				this._createAllSets();
				button.render(this.get('contentBox'));
			} else {
				this._createSlide();
				button.render(this._slideContent);
			}
			
			// Set inital value
			var value = this.get('value');
			if (value && value.length) {
				this.set('value', value);
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
						this._createAllSets();
					}
				}, this);
				
				// On button click open slide
				this._slideButton.on('click', this._openSlide, this);
				
				// Disabled change
				this.on('disabledChange', function (event) {
					this._slideButton.set('disabled', event.newVal);
				}, this);
			}
			
			// Add new item on "Add more" click
			this._addButton.on('click', this.addSet, this);
			
			// Change event
			this.on('valueChange', this._afterValueChange, this);
		},
		
		
		/*
		 * ---------------------------------------- SETS ----------------------------------------
		 */
		
		
		/**
		 * Recreate sets from data
		 * 
		 * @private
		 */
		_createAllSets: function () {
			var data = this.get('value'),
				i = 0,
				ii = data.length;
			
			for (; i<ii; i++) {
				this._addSet(data[i]);
			}
			
			this._valuesRendered = true;
			this._fireResizeEvent();
			
			this.fire('setRender');
		},
		
		/**
		 * Add new set
		 * 
		 * @param {Object} data Set default input values
		 * @param {Boolean} animate Animate UI
		 * @private
		 */
		_addSet: function (data, animate) {
			var properties = this.get('properties'),
				i = 0,
				count = properties.length,
				form  = this.getForm(),
				node = Y.Node.create('<div class="' + this.getClassName('group') + '"></div>'),
				index = this._count,
				
				id = null,
				input = null,
				inputs = {},
				
				heading = null,
				button = null,
				
				container = null;
			
			data = data || {};
			
			// Create container node
			if (this.get('separateSlide')) {
				container = this._slideContent;
			} else {
				container = this.get('contentBox');
			}
			
			container.append(node);
			
			if (this._addButton) {
				container.append(this._addButton.get('boundingBox'));
			}
			
			// Create heading
			heading = inputs.nodeHeading = Y.Node.create('<h3>' + this.get('labelItem').replace('%s', index + 1) + '</h3>');
			node.append(heading);
			
			// Create inputs
			for (; i<count; i++) {
				id = properties[i].id;
				
				input = form.factoryField(Supra.mix({}, properties[i], {
					'id': id + '_' + Y.guid(),
					'name': id,
					'value': data[id],
					'parent': this
				}));
				
				input.render(node);
				
				//input.after('change', this._fireChangeEvent, this);
				input.after('valueChange', this._fireChangeEvent, this);
				input.on('focus', this._onInputFocus, this);
				input.on('input', this._fireInputEvent, this, id);
				
				inputs[id] = input;
			}
			
			// "Remove" button
			button = inputs.buttonRemove = new Supra.Button({
				'label': this.get('labelRemove'),
				'style': 'small-red'
			});
			button.addClass(button.getClassName('fill'));
			button.render(node);
			button.on('click', this._removeTargetSet, this, node);
			
			this._count++;
			this._nodes.push(node);
			this._inputs.push(inputs);
			
			if (animate) {
				this._animateIn(node);
			}
			
			if (this._valuesRendered) {
				this.fire('setRender', {'data': data});
			}
		},
		
		/**
		 * Remove set
		 * 
		 * @param {Number} index Set index
		 * @param {Boolean} animate Animate UI
		 * @private
		 */
		_removeSet: function (index, animate) {
			var nodes = this._nodes,
				inputs = this._inputs,
				count = this._count,
				
				tmp = null,
				key = null,
				
				node = null;
			
			if (index >=0 && index < count) {
				tmp = inputs[index];
				node = nodes[index];
				
				if (animate) {
					this._animateOut(node, tmp);
				} else {
					// Destroy inputs
					for (key in tmp) {
						if (tmp[key] && tmp[key].destroy) {
							tmp[key].destroy(true);
						}
					}
					
					node.remove(true);
				}
				
				inputs.splice(index, 1);
				nodes.splice(index, 1);
				this._count--;
				
				// Update all other set headings
				var i = index,
					ii = count - 1;
				
				for (; i<ii; i++) {
					inputs[i].nodeHeading.set('innerHTML', this.get('labelItem').replace('%s', i + 1));
				}
			}
		},
		
		/**
		 * Remove set in which "Remove" button was clicked
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} node Set node which needs to be removed
		 * @private
		 */
		_removeTargetSet: function (event, node) {
			var index = this._getSetIndex(node);
			this.removeSet(index);
		},
		
		addSet: function (data) {
			this._addSet(data, true);
			this._fireResizeEvent();
			this._fireChangeEvent();
			this.fire('add', data);
		},
		
		removeSet: function (index) {
			this.fire('remove', index);
			this._removeSet(index, true);
			this._fireResizeEvent();
			this._fireChangeEvent();
		},
		
		/**
		 * Returns set count
		 * 
		 * @returns {Number} Set count
		 */
		size: function () {
			return this._count;
		},
		
		/**
		 * Returns set index by node
		 * 
		 * @param {Object} node Set container node
		 * @returns {Number} Set index
		 * @private
		 */
		_getSetIndex: function (node) {
			var index = 0,
				selector = '.' + this.getClassName('group');
			
			node = node.closest(selector);
			
			while (node) {
				node = node.previous(selector);
				if (node) {
					index++;
				}
			}
			
			return index;
		},
		
		/**
		 * On input focus save set index
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_onInputFocus: function (event) {
			this._focusedSetIndex = this._getSetIndex(event.target.get('srcNode'));
		},
		
		/**
		 * Returns widgets for set
		 * 
		 * @param {Number} index Set index
		 * @returns {Object} List of all widgets for set
		 */
		getSetWidgets: function (index) {
			if (index >= 0 && index < this._count) {
				return this._inputs[index];
			}
		},
		
		
		/*
		 * ---------------------------------------- ANIMATIONS ----------------------------------------
		 */
		
		
		/**
		 * Fade and slide in node
		 * 
		 * @param {Object} node Slide node which will be animated
		 * @private
		 */
		_animateIn: function (node) {
			var height = node.get('offsetHeight');
			
			node.setStyles({
				'overflow': 'hidden',
				'height': '0px',
				'opacity': 0
			});
			node.transition({
				'height': height + 'px',
				'opacity': 1,
				'duration': 0.35
			}, function () {
				node.removeAttribute('style');
			});
		},
		
		_animateOut: function (node, inputs) {
			node.setStyles({
				'overflow': 'hidden',
				'margin': 0,
				'padding': 0
			});
			node.transition({
				'height': '0px',
				'opacity': 0,
				'duration': 0.35
			}, function () {
				//Destroy inputs
				if (inputs) {
					for (var key in inputs) {
						if (inputs[key] && inputs[key].destroy) {
							inputs[key].destroy(true);
						}
					}
				}
				// Remove node
				node.remove(true);
			});
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
					container.fire('contentResize');
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
			var index = this._focusedSetIndex;
			
			this.fire('input', {
				'value': event.value,
				'index': index,
				'property': property
			});
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value New value
		 * @returns {Object} New value
		 * @private
		 */
		_setValue: function (value) {
			value = value || [];
			
			// If we are updating 'value' then don't change UI
			// If inputs hasn't been rendered then we can't set value
			if (!this.get('rendered') || this._silentValueUpdate || !this._valuesRendered) return value;
			
			// Remove old values
			var count = this._count,
				i = count-1;
			
			for (; i >= 0; i--) {
				this._removeSet(i);
			}
			
			// Add new values
			count = value.length;
			i = 0;
			
			for (; i<count; i++) {
				this._addSet(value[i]);
			}
			
			if (value) {
				// Value is missing when resetting form, not need to
				// fire resize event in that case
				this._fireResizeEvent();
			}
			
			return value;
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
			if (!this.get('rendered') || !this._valuesRendered) return value;
			
			var i = 0,
				ii = this._count,
				data = [],
				item = null,
				properties = this.get('properties'),
				id = null,
				k = 0,
				kk = properties.length,
				inputs = this._inputs;
			
			for (; i<ii; i++) {
				item = {};
				
				for (k=0; k<kk; k++) {
					id = properties[k].id;
					item[id] = inputs[i][id].get('saveValue');
				}
				
				data.push(item);
			}
			
			return data;
		},
		
		_afterValueChange: function (evt) {
			this.fire('change', {'value': evt.newVal});
		}
		
	});
	
	Supra.Input.Set = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
