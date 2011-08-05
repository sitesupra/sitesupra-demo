//Invoke strict mode
"use strict";

SU(function (Y) {

	/**
	 * Animations
	 * @type {Object}
	 */
	var ANIMATION = {
		'up_out': {
			'from': {top: 0, opacity: 1},
			'to':   {top: -50, opacity: 0}
		},
		'down_out': {
			'from': {top: 0, opacity: 1},
			'to':   {top: 50, opacity: 0}
		},
		'down_in': {
			'from': {top: -50, opacity: 0},
			'to':   {top: 0, opacity: 1}
		},
		'up_in': {
			'from': {top: 50, opacity: 0},
			'to':   {top: 0, opacity: 1}
		}
	};

	//Shortcuts
	var Manager = SU.Manager,
		Action = Manager.Action,
		Loader = Manager.Loader;
	
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageToolbar',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Dependancy list
		 * @type {Array}
		 */
		DEPENDANCIES: ['LayoutContainers'],
		
		
		
		
		/**
		 * Currently selected action
		 * @type {String}
		 */
		active_action: null,
		
		/**
		 * Currently selected group
		 * @type {String}
		 */
		active_group: null,
		
		/**
		 * Button list
		 * @type {Object}
		 */
		buttons: {},
		
		/**
		 * Button group list (Nodes)
		 * @type {Object}
		 */
		groups: {},
		
		/**
		 * List of previous actions IDs
		 * Used to show previous action buttons when action is hidden
		 */
		history: [],
		
		/**
		 * Animation queue
		 * @type {Array}
		 */
		animationQueue: [],
		
		/**
		 * Animation running
		 * @type {Boolean}
		 */
		animationRunning: false,
		
		
		
		
		/**
		 * Add button group.
		 * Chainable
		 * 
		 * @param {String} id Group ID
		 * @param {Array} buttons Button list
		 */
		addActionButtons: function (id, buttons) {
			var data = {};
			
			data[id] = buttons;
			this.renderButtons(data);
			
			return this;
		},
		
		/**
		 * Returns true if group exists
		 * 
		 * @param {String} id Group ID
		 * @return True if group exists, otherwise false
		 * @type {Boolean}
		 */
		hasActionButtons: function (id) {
			var buttons = this.get('buttons');
			return id in buttons;
		},
		
		/**
		 * Set active group action, changes buttons to ones associated with action.
		 * Chainable
		 * 
		 * @param {String} active_group
		 */
		setActiveAction: function (active_group) {
			var old_animation_index = null,
				button_groups;
			
			button_groups = this.get('buttons');
			if (active_group && !(active_group in button_groups)) {
				console.log(active_group, 'FAIL');
				return this;
			}
			
			if (!active_group && this.active_group) {
				old_animation_index = Y.Array.indexOf(this.history, this.active_group);
				this.removeHistory(this.active_group);
				active_group = this.history[this.history.length-1];
			}
			
			if (active_group != this.active_group) {
				
				if (this.active_group) {
					if (old_animation_index === null) old_animation_index = Y.Array.indexOf(this.history, this.active_group); 
					this.animationQueue.push({'action_id': this.active_group, 'visible': false, 'index': old_animation_index});
					
					//Hide old group actions
					var button_config = this.get('buttons')[this.active_group],
						action = null,
						type = null;
					
					for(var i=0,ii=button_config.length; i<ii; i++) {
						type = button_config[i].type || 'toggle';
						if (type == 'toggle' || type == 'tab') {
							action = Manager.getAction(button_config[i].action);
							action.hide();
						}
					}
				}
				
				if (active_group && active_group in this.groups) {
					this.animationQueue.push({'action_id': active_group, 'visible': true, 'index': Y.Array.indexOf(this.history, active_group)});
					this.addHistory(active_group);
				} else {
					active_group = null;
				}
				
				this.active_group = active_group;
				
				/*
				 * If next animation which will be added is the same as this, then
				 * ignore
				 */
				setTimeout(Y.bind(this.animate, this), 10);
			}
			
			return this;
		},
		
		unsetActiveAction: function (active_group) {
			if (active_group && active_group == this.active_group) {
				this.setActiveAction(null);
			}
			
			return this;
		},
		
		/**
		 * Run next animation from queue
		 */
		animate: function () {
			if (this.animationRunning || !this.animationQueue.length) return;
			this.animationRunning = true;
			
			var show = this.animationQueue.shift(),
				showNode = this.groups[show.action_id],
				showIndex = show.index,
				showAnim = null,
				hide = null,
				hideNode = null,
				hideIndex = -2,
				hideAnim = null,
				animName = 'up';
			
			if (!show.visible) {
				hide = show;
				hideNode = showNode;
				hideIndex = showIndex;
				
				show = this.animationQueue.shift();
				showIndex = hideIndex + 1;
				if (show) {
					showNode = this.groups[show.action_id];
					if (show.index != -1) showIndex = show.index;
				}
				
				//Determine if animation should go up or down
				if (hideIndex == showIndex) hideIndex++;
			}
			
			animName = hideIndex < showIndex ? 'up' : 'down';
			
			if (hide) {
				hideAnim = new Y.Anim(Supra.mix({
					node: hideNode,
					duration: 0.35,
					easing: Y.Easing.easeOut
				}, ANIMATION[animName + '_out']));
			}
			if (show) {
				showAnim = new Y.Anim(Supra.mix({
					node: showNode,
					duration: 0.35,
					easing: Y.Easing.easeOut
				}, ANIMATION[animName + '_in']));
			}
			
			(hideAnim || showAnim).on('end', function () {
				this.animationRunning = false;
				this.animate();
			}, this);
			
			if (hideAnim) hideAnim.run();
			if (showAnim) showAnim.run();
		},
		
		/**
		 * On action create it's possible to change place holder
		 * @private
		 */
		create: function () {
			//Add action as top bar child
			Manager.getAction('LayoutTopContainer').addChildAction('PageToolbar');
		},
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			//Hide content until all widgets are rendered
			this.getPlaceHolder().addClass('hidden');
		},
		
		/**
		 * When button is clicked call action which is configured with button
		 * 
		 * @param {Object} event
		 */
		handleButtonClick: function (event) {
			var target_id = event.target.get('topbarButtonId'),
				group_id = this.active_group,
				buttons = this.buttons;
			
			var config = this.get('buttons')[group_id];
			
			for(var i=0,ii=config.length; i<ii; i++) {
				if (config[i].id == target_id) {
					config = config[i]; break;
				}
			}
			
			if (!config) return;
			
			var action_id = config.action;
			var action = SU.Manager.getAction(action_id);
			var type = (config.type ? config.type : 'toggle');
			
			if (event.target.get('down') || (type != 'toggle' && type != 'tab')) {
				//Hide previous action
				if (this.active_action !== null && this.active_action != action_id) {
					var old_action = SU.Manager.getAction(this.active_action);
					old_action.hide();
				}
				if (type == 'toggle' || type == 'tab') {
					this.active_action = action_id;
				} else {
					this.active_action = null;
				}
				
				if (config.actionFunction) {
					//If actionFunction is specified then call it
					action.once('execute', function () {
						action[config.actionFunction](config.id);
					});
				}
				
				Manager.executeAction(action_id);
			} else {
				//Click on button which already is 'down'
				
				if (type == 'tab') {
					//Restore 'down' state
					event.target.set('down', true);
				} else {
					//Hide action
					action.hide();
					if (this.active_action == action_id) this.active_action = null;
				}
			}
		},
		
		/**
		 * Create buttons
		 * @private
		 */
		renderButtons: function (button_groups) {
			var container = this.one('.yui3-editor-toolbar-main'),
				subcontainer = null,
				button_config,
				button,
				action,
				id,
				type,
				permissions,
				attr_buttons = this.get('buttons') || {},
				empty = true;
			
			for(var group_id in this.groups) {
				empty = false;
				break;
			}
			
			for(var group_id in button_groups) {
				attr_buttons[group_id] = button_groups[group_id];
				button_config = button_groups[group_id];
				
				//Create group container
				subcontainer = Y.Node.create('<div class="yui3-editor-toolbar-group"></div>');
				container.append(subcontainer);
				this.groups[group_id] = subcontainer;
				
				if (empty) {
					empty = false;
					this.active_group = group_id;
					this.history.push(group_id);
				}
						
				//Create buttons
				for(var i=0,ii=button_config.length; i<ii; i++) {
					if (Y.Lang.isObject(button_config[i])) {
						
						id = button_config[i].id;
						
						type = button_config[i].type || 'toggle';
						if (type == 'tab') type = 'toggle';
						
						permissions = button_config[i].permissions;
						
						if (permissions && !Supra.Authorization.isAllowed(permissions, true)) {
							continue;
						}
						
						button = new Supra.Button({"type": type, "label": button_config[i].title, "icon": button_config[i].icon});
						button.set('topbarButtonId', id);
						button.render(subcontainer);
						button.on('click', this.handleButtonClick, this);
						
						this.buttons[id] = button;
						
						if (type == 'toggle') {
							action = Manager.getAction(button_config[i].action);
							action.on('visibleChange', function (evt, button, action_id) {
								if (evt.newVal != evt.prevVal) {
									button.set('down', evt.newVal);
									
									if (evt.newVal) {
										this.active_action = action_id;
									}
								}
							}, this, button, button_config[i].action);
						}
					}
				}
			}
			
			this.set('buttons', attr_buttons);
		},
		
		/**
		 * Add action to history to revert to it later if needed
		 * If action is found already in history, then all history about actions
		 * which were opened after it is removed 
		 *  
		 * @param {String} active_group Group action ID
		 */
		addHistory: function (active_group) {
			var history = this.history;
			for(var i=0,ii=history.length; i<ii; i++) {
				if (history[i] == active_group) {
					this.history = history.splice(0, i + 1);
					return this;
				}
			}
			this.history.push(active_group);
			return this;
		},
		
		/**
		 * Returns true if group is in history
		 * 
		 * @param {String} active_group Group action ID
		 * @return True if group is in history
		 * @type {Boolean}
		 */
		inHistory: function (active_group) {
			return Y.Array.indexOf(this.history, active_group) != -1;
		},
		
		/**
		 * Removes action and all actions which were opened after it
		 * 
		 * @param {String} active_group Group action ID
		 */
		removeHistory: function (active_group) {
			var history = this.history;
			for(var i=0,ii=history.length; i<ii; i++) {
				if (history[i] == active_group) {
					this.history = history.splice(0, i);
					return this;
				}
			}
			return this;
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Render buttons
			var button_groups = this.get('buttons') || {};
			this.renderButtons(button_groups);
			
			//Show / hide buttons when action is shown / hidden
			this.on('visibleChange', function (evt) {
				if (evt.newVal) {
					this.one().removeClass('hidden');
				} else {
					this.one().addClass('hidden');
					
					//Hide all subactions
					var buttons = this.buttons;
					for(var id in buttons) {
						if (buttons[id].get('down')) {
							buttons[id].fire('click', {});
							break;
						}
					}
				}
			}, this);
			
			//Show content
			this.getPlaceHolder().removeClass('hidden');
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			Manager.getAction('LayoutTopContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			Manager.getAction('LayoutTopContainer').setActiveAction(this.NAME);
		}
	});
	
});