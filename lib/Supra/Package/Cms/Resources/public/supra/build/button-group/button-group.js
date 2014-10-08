YUI.add('supra.button-group', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function ButtonGroup (config) {
		ButtonGroup.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	ButtonGroup.NAME = 'group';
	ButtonGroup.CSS_PREFIX = 'su-' + ButtonGroup.NAME;
	ButtonGroup.CLASS_NAME = Y.ClassNameManager.getClassName(ButtonGroup.NAME);
	
	ButtonGroup.ATTRS = {
		//Button behaviour
		'type': {
			'value': '',			//valid values are '', 'checkbox', 'radio', 'radio-checkbox'
			'setter': '_setType'
		},
		//Force specific style for all buttons
		'style': {
			'value': '',
			'setter': '_setStyle'
		},
		//Predefined buttons
		'buttons': {
			'value': null,
			'writeOnce': 'initOnly'
		},
		//Automatically search for button when rendered
		'autoDiscoverButtons': {
			'value': true
		},
		//Disable/enable all buttons 
		'disabled': {
			'value': false,
			'setter': '_setDisabled'
		},
		//Selection
		'selection': {
			'value': [],
			'setter': '_setSelection'
		}
	};
	
	ButtonGroup.HTML_PARSER = {
		'type': function (srcNode) {
			var type = srcNode.getAttribute('data-type');
			return type || '';
		},
		'style': function (srcNode) {
			var style = srcNode.getAttribute('data-style');
			return style|| '';
		}
	};
	
	Y.extend(ButtonGroup, Y.Widget, {
		
		/**
		 * List of buttons
		 * @type {Array}
		 * @private
		 */
		'buttons': [],
		
		/**
		 * Button list by index
		 * @type {Object}
		 * @private
		 */
		'index': {},
		
		
		
		/**
		 * Set buttons on initializer
		 * 
		 * @private
		 */
		'initializer': function () {
			this.index = {};
			this.buttons = [];
			this.add(this.get('buttons'));
		},
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		'renderUI': function () {
			if (this.get('autoDiscoverButtons')) {
				this._createButtonsFromDOM();
			}
			
			//Render buttons
			var container = this.get('contentBox'),
				buttons = this.buttons,
				ii = buttons.length,
				i = 0,
				type = this.get('type'),
				selection = this.get('selection'),
				uid = null;
			
			for (; i<ii; i++) {
				if (buttons[i].get('rendered')) {
					container.append(buttons[i].get('boundingBox'));
				} else {
					buttons[i].render(container);
				}
				
				uid = buttons[i].get('buttonId');
				if ((type === 'radio' || type === 'checkbox' || type === 'radio-checkbox') && Y.Array.indexOf(selection, uid) !== -1) {
					buttons[i].set('down', true);
				}
				
				//Attach event listener
				buttons[i].on('click', this._onButtonClick, this);
			}
		},
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		/**
		 * Find and create buttons inside content
		 * 
		 * @private
		 */
		'_createButtonsFromDOM': function () {
			var content = this.get('boundingBox'),
				buttons = content.all('button, input[type="button"], input[type="submit"]');
			
			buttons.each(this.add, this);
		},
		
		/**
		 * On button click do something
		 * 
		 * @private
		 */
		'_onButtonClick': function (e) {
			var type	= this.get('type'),
				button	= e.target,
				buttons	= this.buttons,
				i		= 0,
				ii		= buttons.length,
				changed	= false;
			
			if (type === 'radio' || type === 'radio-checkbox') {
				
				if (!button.get('down')) {
					if (type !== 'radio-checkbox') {
						//One radio button must be selected
						button.set('down', true);
					}
					changed = true;
				} else {
					for(; i<ii; i++) {
						if (buttons[i] !== button) {
							if (buttons[i].get('down')) {
								buttons[i].set('down', false);
								changed = true;
							}
						}
					}
					
					if (type === 'radio-checkbox') {
						//Radio checkbox button can have none selected
						changed = true;
					}
				}
				
			} else if (type === 'checkbox') {
				changed = true;
			}
			
			this.fire('buttonClick', {
				'button': button
			});
			
			if (changed) {
				this.set('selection', this.getSelection());
			}
		},
		
		/**
		 * Returns hopefully unique ID for button
		 * Searches 'id', 'data-id' and 'data-button-id' and 'class' node attributes and buttonId Supra.Button attribute
		 * 
		 * @param {Object} button
		 */
		'getUID': function (button, create) {
			if (typeof button === 'string') return button;
			
			var id = button.get('buttonId'),
				node = null;
			
			if (!id) {
				//id attribute, whether it is yui_ or user specified
				id = button.get('id');
				
				if (!id || id.indexOf('yui_') === 0) {
					//check node for buttonId
					node = Y.Lang.isWidget(button, 'Button') ? button.get('nodeButton') : button;
					id = node.get('buttonId');
					
					if (!id) {
						id = node.getAttribute('data-button-id') || node.getAttribute('data-id');
						
						if (!id) {
							var classnames	= node.getAttribute('class').split(/\s+/g),
								c			= '',
								i			= 0,
								ii			= classnames.length,
								style		= button.get('style');
							
							id = null;
							
							for(; i<ii; i++) {
								c = classnames[i];
								if (c && c.indexOf('su-') === -1 && c.indexOf('yui3-') === -1 && c !== style) {
									id = c; break;
								}
							}
							
						}
					}
				}
				
				id = id || (create ? Y.guid() : null);
				if (id) {
					button.set('buttonId', id);
				}
			}
			
			return id;
		},
		
		/**
		 * Update button "first" and "last" classnames
		 * 
		 * @private
		 */
		'updateClassNames': function () {
			if (this.buttons.length) {
				var classname_first	= Y.ClassNameManager.getClassName(Supra.Button.CSS_PREFIX, 'first', true),
					classname_last 	= Y.ClassNameManager.getClassName(Supra.Button.CSS_PREFIX, 'last', true),
					buttons			= this.buttons,
					length			= buttons.length;
				
				buttons[0].addClass(classname_first);
				buttons[length - 1].addClass(classname_last);
				
				if (length > 1) {
					buttons[1].removeClass(classname_first);
					buttons[length - 2].removeClass(classname_last);
				}
			}
		},
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
		/**
		 * Returns button by index or id
		 * 
		 * @param {String} id Button id or button index
		 * @return Button instance or null if button is not found
		 * @type {Object}
		 */
		'item': function (id) {
			if (typeof id === 'string') {
				return this.index[id] || null;
			} else if (typeof id === 'number') {
				return this.buttons[id] || null;
			} else if (Y.Lang.isWidget(id, 'Button')) {
				return this.index[this.getUID(id)] || null;
			} else {
				return null;
			}
		},
		
		/**
		 * Returns all buttons
		 * 
		 * @return All buttons
		 * @type {Array}
		 */
		'all': function () {
			return this.buttons;
		},
		
		/**
		 * Returns true if button is in this group, otherwise false
		 * 
		 * @param {Object} button Button or button ID
		 * @return True if button is in this group
		 * @type {Boolean}
		 */
		'has': function (button) {
			return !!this.item(button);
		},
		
		/**
		 * Add button to the group
		 * 
		 * @param {Object} button Button or button definition
		 * @param {Number} index Index where to insert button
		 * @return ButtonGroup for call chaining
		 * @type {Object}
		 */
		'add': function (button, index) {
			var config = null,
				type   = this.get('type'),
				btype  = (type === 'checkbox' || type === 'radio' || type === 'radio-checkbox' ? 'toggle' : 'push');
			
			if (Y.Lang.isArray(button)) {
				var i	= 0,
					ii	= button.length;
				
				for(; i<ii; i++) {
					this.add(button[i], index + i);
				}
				
				return this;
			}
			
			if (Y.Lang.isObject(button)) {
				var rendered = this.get('rendered');
				
				if (Y.Lang.isWidget(button)) {
					if (button.isInstanceOf('Node')) {
						config = {'srcNode': button};
						
						if (!button.getAttribute('su-button-type')) {
							config.type = btype;
						}
						
						button = new Supra.Button(config);
						
						if (rendered) {
							button.render(this.get('contentBox'));
						}
					} else if (!button.isInstanceOf('Button')) {
						return this;
					}
				} else {
					//Clone to avoid overwriting properties
					button = config = Supra.mix({}, button);
					
					//Set type
					if (!button.type) {
						button.type = btype;
					}
					
					//Tre not to set ID on button node, will use buttonId instead 
					if (button.id && !button.buttonId) {
						button.buttonId = button.id;
						delete(button.id);
					}
					
					button = new Supra.Button(button);
					if (rendered) {
						button.render(this.get('contentBox'));
					}
				}
				
				//Insert button into DOM
				var insert_before	= null,
					this_buttons	= this.buttons,
					this_index		= this.index;
				
				if (rendered) {
					if (Y.Lang.isWidget(index)) {
						//Supra.Button was passed
						insert_before = index.get('boundingBox')
					} else if (typeof index === 'number') {
						//by index
						if (index >= 0 && index < this_buttons.length) {
							insert_before = this_buttons[index].get('boundingBox');
						}
					} else if (typeof index === 'string' && this_index[index]) {
						//by ID
						insert_before = this_index[index].get('boundingBox');
					}
					
					if (insert_before) {
						insert_before.insert(button.get('boundingBox'), 'before');
					} else {
						this.get('contentBox').append(button.get('boundingBox'));
					}
				}
				
				//Add to data
				var uid = this.getUID(button, true),
					insert_index = -1;
				
				if (this_index[uid]) {
					//Already in index
					index = Y.Array.indexOf(this_index, uid);
					if (index != -1) {
						//Remove button
						this_buttons.splice(index, 1);
					} else {
						//Something is wrong with index, button was not in the array
					}
				}
				
				insert_index = insert_before ? this.indexOf(insert_before) : -1;
				
				if (insert_index !== -1) {
					this_buttons.splice(insert_index, 0, button);
				} else {
					this_buttons.push(button);
				}
				
				//Update .su-button-first and .su-button-last classnames
				this.updateClassNames();
				
				//Button type and style
				var type	= this.get('type'),
					btype	= (type === 'checkbox' || type === 'radio' || type === 'radio-checkbox' ? 'toggle' : 'push'),
					style	= this.get('style');
				
				if (type && (!config || !config.type)) {
					//If group type is set and button type was not set
					button.set('type', btype);
				}
				
				if (style) {
					button.set('style', style);
				}
				
				if (rendered) {
					button.on('click', this._onButtonClick, this);
					
					if ((type === 'radio' || type === 'checkbox' || type === 'radio-checkbox') && Y.Array.indexOf(this.get('selection'), uid) !== -1) {
						button.set('down', true);
					}
				}
				
				this_index[uid] = button;
			}
			
			return this;
		},
		
		/**
		 * Returns button count in the group
		 * 
		 * @return Button count
		 * @type {Number}
		 */
		'size': function () {
			return this.buttons.length;
		},
		
		/**
		 * Remove button from the group
		 * 
		 * @param {Object} button Button or button ID
		 * @param {Boolean} keep Don't destroy button after it's removed
		 * @return ButtonGroup for call chaining
		 * @type {Object}
		 */
		'remove': function (button, keep) {
			var id		= this.getUID(button),
				index	= -1;
			
			if (id && this.index[id]) {
				button = this.index[id];
				
				index = this.indexOf(id);
				if (index != -1) {
					this.buttons.splice(index, 1);
					this.updateClassNames();
				}
				delete(this.index[id]);
				
				button.detach('click', this._onButtonClick, this);
				
				if (!keep) button.destroy();
			}
		},
		
		/**
		 * Returns selected button ids
		 * 
		 * @return Selected button ids
		 * @type {Array}
		 */
		'getSelection': function () {
			var buttons		= this.buttons,
				i			= 0,
				ii			= buttons.length,
				selection	= [];
			
			for(; i<ii; i++) {
				if (buttons[i].get('down')) selection.push({'id': buttons[i].get('buttonId'), 'button': buttons[i]});
			}
			
			return selection;
		},
		
		/* -- Array functions -- */
		
		/**
		 * Returns buttons index or -1 if button is not in the group
		 * 
		 * @param {Object} button Button or button ID
		 * @return Index of the button in the group or -1 if button is not the group
		 * @type {Number}
		 */
		'indexOf': function (button) {
			var id = this.getUID(button);
			
			if (id) {
				var buttons	= this.buttons,
					i		= 0,
					ii		= buttons.length;
				
				for(; i<ii; i++) {
					if (buttons[i].get('buttonId') === id) return i;
				}
			}
			
			return -1;
		},
		
		/**
		 * Executes the supplied function on each button and returns a new array
		 * containing all the values returned by the supplied function.
		 * 
		 * @param {Function} fn The function to execute on each item
		 * @param {Object} context Optional context object
		 * @return Array with returned values
		 * @type {Array}
		 */
		'map': function (fn, context) {
			return Y.Array.map(this.buttons, fn, context);
		},
		
		
		/**
		 * Executes the supplied function on each button and returns a new array
		 * containing the items for which the supplied function returned a truthy value.
		 * 
		 * @param {Function} fn The function to execute on each item
		 * @param {Object} context Optional context object
		 * @return Array with buttons
		 * @type {Array}
		 */
		'filter': function (fn, context) {
			return Y.Array.filter(this.buttons, fn, context);
		},
		
		/**
		 * Executes the supplied function on each button, searching for the first button
		 * that matches the supplied function.
		 * 
		 * @param {Function} fn The function to execute on each item
		 * @param {Object} context Optional context object
		 * @return Button for which function returned true
		 * @type {Object}
		 */
		'find': function (fn, context) {
			return Y.Array.find(this.buttons, fn, context);
		},
		
		/**
		 * Executes the supplied function on each button
		 * 
		 * @param {Function} fn The function to execute on each item
		 * @param {Object} context Optional context object
		 */
		'each': function (fn, context) {
			Y.Array.each(this.buttons, fn, context);
			return this;
		},
		
		
		
		/**
		 * ------------------------------ ATTRIBUTES ------------------------------
		 */
		
		
		/**
		 * Style attribute setter
		 * Update style attribute for all buttons
		 * 
		 * @param {String} style New style value
		 * @return Style attribute value
		 * @type {String}
		 * @private
		 */
		'_setStyle': function (style) {
			if (this.get('rendered') && this.get('style') != style) {
				
				Y.Array.each(this.buttons, function (button) {
					button.set('style', style || 'small');
				});
				
			}
			
			return style;
		},
		
		/**
		 * Disabled attribute setter
		 * Disable or enable all buttons
		 * 
		 * @param {Boolean} disabled Disabled attribute value
		 * @return Disabled attribute value
		 * @type {Boolean}
		 * @private
		 */
		'_setDisabled': function (disabled) {
			if (this.get('rendered')) {
				
				if (!!this.get('disabled') !== !!disabled) {
					Y.Array.each(this.buttons, function (button) {
						button.set('disabled', disabled);
					});
				}
				
			}
			
			return !!disabled;
		},
		
		/**
		 * Selection attribute setter
		 * 
		 * @param {Array} selection Selection attribute value
		 * @return Selection attribute value
		 * @type {Array}
		 * @private
		 */
		'_setSelection': function (selection) {
			if (!Y.Lang.isArray(selection)) selection = [];
			var type = this.get('type');
			
			if ((type === 'radio' || type === 'radio-checkbox') && selection.length > 1) {
				selection = [selection[0]];
			}
			
			if (this.get('rendered') && (type === 'radio' || type === 'checkbox' || type === 'radio-checkbox')) {
				var buttons = this.buttons,
					ii = buttons.length,
					i = 0,
					id = null;
				
				for (; i<ii; i++) {
					id = buttons[i].get('buttonId');
					if (Y.Array.indexOf(selection, id) !== -1) {
						buttons[i].set('down', true);
					}
				}
			}
			
			return selection;
		},
		
		/**
		 * Type attribute setter
		 * Change button behaviour type
		 * 
		 * @param {String} type Type attribute value
		 * @return Type attribute value
		 * @type {String}
		 * @private
		 */
		'_setType': function (type) {
			var otype	= this.get('type'),
				btype	= (type === 'checkbox' || type === 'radio' || type === 'radio-checkbox' ? 'toggle' : 'push'),
				changed	= false;
			
			if (otype != type && ((type && !otype) || (!type && otype))) {
				
				Y.Array.each(this.buttons, function (button) {
					button.set('type', btype);
					
					if (type === 'push' && button.get('down')) {
						button.set('down');
						changed = true;
					}
				});
				
				var selection = this.getSelection();
				
				if (type !== 'checkbox' && type !== 'radio' && type !== 'radio-checkbox' && selection.lengh) {
					var i		= 0,
						ii		= selection.length,
						button	= null;
					
					for(; i<ii; i++) {
						button = selection[i].button;
						if (button.get('down')) {
							button.set('down', false);
							changed = true;
						}
					}
				}
				
				if (changed) {
					this.set('selection', this.getSelection());
				}
			}
			
			return type;
		}
		
	});
	
	Supra.ButtonGroup = ButtonGroup;
	
	
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
	
}, YUI.version, {'requires': ['widget', 'widget-child']});