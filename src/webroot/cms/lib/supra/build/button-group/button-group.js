//Invoke strict mode
"use strict";

YUI.add('supra.button-group', function (Y) {
	
	function ButtonGroup (config) {
		ButtonGroup.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	ButtonGroup.NAME = 'button-group';
	ButtonGroup.CSS_PREFIX = 'su-' + ButtonGroup.NAME;
	ButtonGroup.CLASS_NAME = Y.ClassNameManager.getClassName(ButtonGroup.NAME);
	
	ButtonGroup.ATTRS = {
		//Button behaviour
		'type': {
			'value': null,			//valid values are '', 'checkbox', 'radio'
			'setter': '_setType'
		},
		//Force specific style for all buttons
		'style': {
			'value': null,
			'setter': '_setStyle'
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
			'value': []
		}
	};
	
	ButtonGroup.HTML_PARSER = {
		'type': function (srcNode) {
			var type = srcNode.getAttribute('suType');
			return type || '';
		},
		'style': function (srcNode) {
			var style = srcNode.getAttribute('suStyle');
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
		 * Render UI
		 * 
		 * @private
		 */
		'renderUI': function () {
			this.index = {};
			this.buttons = [];
			
			if (this.get('autoDiscoverButtons')) {
				this._createButtonsFromDOM();
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
			
			if (type == 'radio') {
				
				if (!button.get('down')) {
					button.set('down', true);
					changed = true;
					e.halt();
				} else {
					for(; i<ii; i++) {
						if (buttons[i] !== button) {
							if (buttons[i].get('down')) {
								buttons[i].set('down', false);
								changed = true;
							}
						}
					}
				}
				
			}
			
			this.fire('click', {
				'target': button,
				'type': 'click'
			});
			
			if (changed) {
				this.set('selection', this.getSelection());
			}
		},
		
		/**
		 * Returns hopefully unique ID for button
		 * Searches 'id', 'suGroupButtonId' and 'class' node attributes and groupButtonId Supra.Button attribute
		 * 
		 * @param {Object} button
		 */
		'getUID': function (button, create) {
			if (typeof button === 'string') return button;
			
			var id = button.get('groupButtonId'),
				node = null;
			
			if (!id) {
				//id attribute, whether it is yui_ or user specified
				id = button.get('id');
				
				if (!id || id.indexOf('yui_') === 0) {
					//check node for groupButtonId
					node = Y.Lang.isWidget(button, 'Button') ? button.get('nodeButton') : button;
					id = node.get('groupButtonId');
					
					if (!id) {
						id = node.getAttribute('suGroupButtonId');
						
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
					button.set('groupButtonId', id);
				}
			}
			
			return id;
		},
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
		/**
		 * Returns button by index or id
		 * 
		 * @param {String} id Button id
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
			if (Y.Lang.isArray(button)) {
				var i	= 0,
					ii	= button.length;
				
				for(; i<ii; i++) {
					this.add(button[i], index + i);
				}
				
				return this;
			}
			
			if (Y.Lang.isObject(button)) {
				
				if (Y.Lang.isWidget(button)) {
					if (button.isInstanceOf('Node')) {
						button = new Supra.Button({'srcNode': button});
						button.render(this.get('contentBox'));
					} else if (!button.isInstanceOf('Button')) {
						return this;
					}
				} else {
					button = new Supra.Button(button);
					button.render(this.get('contentBox'));
				}
				
				//Insert button into DOM
				var insert_before	= null,
					this_buttons	= this.buttons,
					this_index		= this.index;
				
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
				
				//Add to data
				var uid = this.getUID(button, true),
					insert_index = -1;
				
				if (this_index[uid]) {
					//Already in index
					index = this_indexOf(uid);
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
				
				//Button type and style
				var type	= this.get('type'),
					btype	= (type == 'checkbox' || type == 'radio' ? 'toggle' : 'push'),
					style	= this.get('style');
				
				button.set('type', btype);
				
				if (style) {
					button.set('style', style);
				}
				
				button.on('click', this._onButtonClick, this);
				
				this_index[uid] = button;
			}
			
			return this;
		},
		
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
					if (buttons[i].get('groupButtonId') === id) return i;
				}
			}
			
			return -1;
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
				if (index != -1) this.buttons.splice(index, 1);
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
				if (buttons[i].get('down')) selection.push({'id': buttons[i].get('groupButtonId'), 'button': buttons[i]});
			}
			
			return selection;
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
				
				Y.Array.each(this.buttons, function (button) {
					button.set('disabled', disabled);
				});
				
			}
			
			return disabled;
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
				btype	= (type == 'checkbox' || type == 'radio' ? 'toggle' : 'push'),
				changed	= false;
			
			if (otype != type) {
				Y.Array.each(this.buttons, function (button) {
					button.set('type', btype);
					
					if (type == 'push' && button.get('down')) {
						button.set('down');
						changed = true;
					}
				});
				
				var selection = this.getSelection();
				
				/*if (type == 'radio' && !selection.length) {
					if (this.buttons.length) {
						this.buttons[0].set('down', true);
						changed = true;
					}
				} else*/
				if (type != 'checkbox' && type != 'radio' && selection.lengh) {
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