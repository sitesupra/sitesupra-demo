YUI.add('supra.input-collection', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * List of input groups with controls to add or remove
	 * groups
	 */
	function Collection (config) {
		Collection.superclass.constructor.apply(this, arguments);
	}
	
	// Collection is not inline
	Collection.IS_INLINE = false;
	
	// Collection is inside form
	Collection.IS_CONTAINED = true;
	
	Collection.NAME = 'input-collection';
	Collection.CSS_PREFIX = 'su-' + Collection.NAME;
	Collection.CLASS_NAME = 'su-input-collection';
	
	Collection.ATTRS = {
		// Image node which is edited
		"targetNode": {
			value: null
		},
		
		// Prefix for target node Id
		"targetNodeIdPrefix": {
			value: null
		},
		
		// Properties for each item
		// Although name is plural, this attribute contains configuration
		// for single property
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
		
		// Minimal item count
		'minCount': {
			value: 0
		},
		
		// Maximal item count
		'maxCount': {
			value: 0
		},
		
		// Default value
		'defaultValue': {
			value: []
		}
	};
	
	Y.extend(Collection, Supra.Input.Proto, {
		
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		
		/**
		 * Item count
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
		 * List of item nodes
		 * @type {Object}
		 * @private
		 */
		_nodes: null,
		
		/**
		 * List of item widgets and nodes
		 * @type {Array}
		 * @private
		 */
		_widgets: null,
		
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
		_focusedItemIndex: 0,
		
		
		
		/**
		 * On desctruction life cycle clean up
		 * 
		 * @private
		 */
		destructor: function () {
			var count = this._count,
				i = count-1;
			
			for (; i >= 0; i--) {
				this._removeItem(i);
			}
			
			if (this._slideId) {
				var slideshow = this.getSlideshow();
				slideshow.removeSlide(this._slideId);
			}
			
			this._slideContent = null;
			this._slideId = null;
			this._widgets = [];
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
			this._widgets = [];
			
			// Create items?
			if (this.get('separateSlide')) {
				var slideshow = this.getSlideshow();
				if (!slideshow) {
					this.set('separateSlide', false);
					Y.log('Unable to create new slide for Supra.Input.Collection "' + this.get('id') + '", because slideshow can\'t be detected');
				} else {
					// Don't create description, we have a button
					this.DESCRIPTION_TEMPLATE = null;
				}
			}
			
			Collection.superclass.renderUI.apply(this, arguments);
			
			// New item button
			var button = this._addButton = new Supra.Button({
				'label': this.get('labelAdd'),
				'style': 'small-gray'
			});
			button.addClass(button.getClassName('fill'));
			
			// Create slide or render data
			if (!this.get('separateSlide')) {
				this._createAllItems();
				button.render(this.get('contentBox'));
			} else {
				this._renderSlide();
				button.render(this._slideContent);
			}
			
			// Set inital value
			var value = this.get('value');
			if (value && value.length) {
				this._setValue('value', value);
			}
		},
		
		/**
		 * Life cycle method, attach event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			Collection.superclass.bindUI.apply(this, arguments);
			
			// When slide is opened for first time create inputs
			if (this.get('separateSlide')) {
				var slideshow = this.getSlideshow();
				var evt_handle = slideshow.on('slideChange', function (evt) {
					if (evt.newVal == this._slideId) {
						evt_handle.detach();
						this._createAllItems();
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
			this._addButton.on('click', this.addItem, this);
			
			// Change event
			this.on('valueChange', this._afterValueChange, this);
		},
		
		
		/*
		 * ------------------------- Items --------------------------------
		 */
		
		
		/**
		 * Recreate items from data
		 * 
		 * @private
		 */
		_createAllItems: function () {
			var data = this.get('value'),
				i = 0,
				ii = data.length;
			
			for (; i<ii; i++) {
				this._addItem(data[i]);
			}
			
			this._valuesRendered = true;
			this._fireResizeEvent();
			
			this.fire('itemRender');
		},
		
		/**
		 * Add new item
		 * 
		 * @param {Object} data Item default input values
		 * @param {Boolean} animate Animate UI
		 * @private
		 */
		_addItem: function (data, index, animate) {
			var property = this.get('properties'),
				
				form  = this.getForm(),
				node = Y.Node.create('<div class="' + this.getClassName('group') + '"></div>'),
				index = this._count,
				
				widgets = {'input': null, 'nodeHeading': null, 'buttonRemove': null},
				input = null,
				
				heading = null,
				button = null,
				
				container = null,
				targetNode = this.get('targetNode'),
				idPrefix   = this.get('targetNodeIdPrefix'),
				inputNode;
			
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
			heading = widgets.nodeHeading = Y.Node.create('<h3>' + this.get('labelItem').replace('%s', index + 1) + '</h3>');
			node.append(heading);
			
			// Create inputs
			if (property) {
				if (property.type === 'Set' || property.type === 'Collection') {
					inputNode = targetNode;
				} else {
					//console.log(targetNode ? targetNode.getDOMNode() : 'No dom node');
					inputNode = targetNode ? targetNode.one('#' + targetNode.getAttribute('id') + '_' + property.id + '_' + index) : null;
				}
				
				//console.log('COLLECTION:', property.id, inputNode ? inputNode.getDOMNode() : null, 'value:', data);
				
				input = form.factoryField(Supra.mix({}, property, {
					'id': property.id + '_' + Y.guid(),
					'name': property.id,
					'value': data,
					'parent': this,
					'containerNode': node,
					
					// For 'Set' input
					'separateSlide': false,
					
					// Set target node, because child may be inline editable
					'targetNode': inputNode
				}));
				
				input.render(node);
				
				input.after('valueChange', this._fireChangeEvent, this);
				input.on('focus', this._onInputFocus, this);
				input.on('input', this._fireInputEvent, this, property.id);
				
				widgets.input = input;
			}
			
			// "Remove" button
			button = widgets.buttonRemove = new Supra.Button({
				'label': this.get('labelRemove'),
				'style': 'small-red'
			});
			button.addClass(button.getClassName('fill'));
			button.render(node);
			button.on('click', this._removeTargetItem, this, node);
			
			this._count++;
			this._nodes.push(node);
			this._widgets.push(widgets);
			
			if (animate) {
				this._animateIn(node);
			}
			
			if (this._valuesRendered) {
				this.fire('itemRender', {'data': data});
			}
		},
		
		/**
		 * Remove item
		 * 
		 * @param {Number} index Item index
		 * @param {Boolean} animate Animate UI
		 * @private
		 */
		_removeItem: function (index, animate) {
			var nodes = this._nodes,
				widgets = this._widgets,
				count = this._count,
				
				input = null,
				key = null,
				
				node = null;
			
			if (index >=0 && index < count) {
				node = nodes[index];
				
				if (animate) {
					this._animateOut(node, widgets);
				} else {
					// Destroy inputs
					if (widgets.input) {
						widgets.input.destroy(true);
					}
					if (widgets.buttonRemove) {
						widgets.buttonRemove.destroy(true);
					}
					
					node.remove(true);
				}
				
				widgets.splice(index, 1);
				nodes.splice(index, 1);
				this._count--;
				
				// Update all other item headings
				var i = index,
					ii = count - 1;
				
				for (; i<ii; i++) {
					widgets[i].nodeHeading.set('innerHTML', this.get('labelItem').replace('%s', i + 1));
				}
			}
		},
		
		/**
		 * Remove item in which "Remove" button was clicked
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} node Item node which needs to be removed
		 * @private
		 */
		_removeTargetItem: function (event, node) {
			var index = this._getItemIndex(node);
			this.removeItem(index);
		},
		
		addItem: function () {
			var data;
			
			this._addItem(data, true);
			this._fireResizeEvent();
			this._fireChangeEvent();
			this.fire('add', data);
		},
		
		removeItem: function (index) {
			this.fire('remove', index);
			this._removeItem(index, true);
			this._fireResizeEvent();
			this._fireChangeEvent();
		},
		
		/**
		 * Returns item count
		 * 
		 * @returns {Number} Item count
		 */
		size: function () {
			return this._count;
		},
		
		/**
		 * Returns item index by node
		 * 
		 * @param {Object} node Item container node
		 * @returns {Number} Item index
		 * @private
		 */
		_getItemIndex: function (node) {
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
		 * On input focus save item index
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_onInputFocus: function (event) {
			this._focusedItemIndex = this._getItemIndex(event.target.get('srcNode'));
		},
		
		/**
		 * Returns widgets for item
		 * 
		 * @param {Number} index Item index
		 * @returns {Array} List of all widgets for item
		 */
		getItemWidgets: function (index) {
			var property = this.get('properties'),
				widgets  = this._widgets,
				obj      = {};
			
			if (property && this._widgets[index]) {
				obj[property.id] = this._widgets[index].input;
			}
			
			return obj;
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
		
		_animateOut: function (node, widgets) {
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
				if (widgets.input) {
					widgets.input.destroy(true);
				}
				if (widgets.buttonRemove) {
					widgets.buttonRemove.destroy(true);
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
			var index = this._focusedItemIndex;
			
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
				this._removeItem(i);
			}
			
			// Add new values
			count = value.length;
			i = 0;
			
			for (; i<count; i++) {
				this._addItem(value[i]);
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
			if (!this.get('rendered') || !this._valuesRendered) {
				return value;
			}
			
			var data = [],
				i = 0,
				ii = this._count,
				widgets = this._widgets;
			
			if (this.get('properties')) {
				for (; i<ii; i++) {
					data.push(widgets[i].input.get('value'));
				}
			}
			
			return data;
		},
		
		_getSaveValue: function () {
			// If inputs hasn't been rendered then we can't get values from
			// inputs which doesn't exist
			if (!this.get('rendered') || !this._valuesRendered) {
				return this.get('value');
			}
			
			var data = [],
				i = 0,
				ii = this._count,
				widgets = this._widgets;
			
			if (this.get('properties')) {
				for (; i<ii; i++) {
					data.push(widgets[i].input.get('saveValue'));
				}
			}
			
			return data;
		},
		
		_afterValueChange: function (evt) {
			this.fire('change', {'value': evt.newVal});
		}
		
	});
	
	Supra.Input.Collection = Collection;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
