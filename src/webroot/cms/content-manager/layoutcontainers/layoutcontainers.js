/**
 * Actions: LayoutLeftContainer, LayoutRightContainer, LayoutTopContainer
 * 
 * Automatically syncs with iframe
 * Containers for other actions, for example:
 * 		LayoutLeftContainer - PageInsertBlock, MediaSidebar
 * 		LayoutRightContainer - PageSettings,
 * 		LayoutTopContainer - PageToolbar, EditorToolbar
 */
Supra('supra.plugin-layout', 'supra.manager-action-plugin-layout-sidebar', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	/**
	 * All layout containers extend this prototype
	 *  
	 * @type {Object}
	 * @private
	 */
	var ContainerProto = {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: '',
		
		/**
		 * No template for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * No stylehseet for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		
		
		/**
		 * Container classname
		 * @type {String}
		 * @private
		 */
		CONTAINER_SELECTOR: '',
		
		/**
		 * Container can be hidden
		 * @type {Boolean}
		 * @private
		 */
		CAN_HIDE: true,
		
		/**
		 * Action which will be visible if container can't be hidden
		 * @type {Boolean}
		 * @private
		 */
		PRIMARY_ACTION: '',
		
		/**
		 * Currently visible action
		 * @type {String}
		 * @private
		 */
		active_action: null,
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			//Update 
			var nodes = Manager.getAction('LayoutContainers').one(this.CONTAINER_SELECTOR);
			this.set('srcNode', new Y.NodeList(nodes));
			
			//Set contentBox
			this.set('boundingBox', this.get('srcNode'));
			this.set('contentBox', this.one(this.CONTAINER_SELECTOR + '-content'));
			
			//Show / hide buttons when action is shown / hidden
			this.on('visibleChange', function (evt) {
				if (evt.prevVal != evt.newVal) {
					if (evt.newVal) {
						this.one().removeClass('hidden');
					} else if (this.CAN_HIDE) {
						this.one().addClass('hidden');
						this.setActiveAction(null);
					} else {
						this.setActiveAction(null);
					}
					
					this.fire('contentResize');
					this.one().fire('contentResize');
				}
			}, this);
		},
		
		/**
		 * Returns currently active child action
		 * 
		 * @return Active action ID
		 * @type {String}
		 */
		getActiveAction: function () {
			return this.active_action;
		},
		
		/**
		 * Changes active child action
		 * 
		 * @param {String} actionId Action ID
		 */
		setActiveAction: function (actionId) {
			if (!this.CAN_HIDE && !actionId) {
				actionId = this.PRIMARY_ACTION;
				Manager.getAction(actionId).execute();
				return;
			}
			
			if (this.active_action != actionId) {
				var children = this.getChildActions(),
					oldActionId = this.active_action;
				
				this.active_action = null;
				
				for(var id in children) {
					if (id == actionId) {
						children[id].show();
						this.active_action = id;
					} else if (id == oldActionId) {
						children[id].hide();
					}
				}
				
				this.fire('activeActionChange', {newVal: this.active_action, oldVal: oldActionId});
				
				if (this.active_action) {
					this.show();
					if (this.layout) {
						this.layout.syncUI();
					} else {
						this.fire('contentResize');
						this.one().fire('contentResize');
					}
				} else if (this.CAN_HIDE) {
					this.hide();
				} else {
					if (this.layout) {
						this.layout.syncUI();
					} else {
						this.fire('contentResize');
						this.one().fire('contentResize');
					}
				}
			} else {
				if (this.layout) {
					this.layout.syncUI();
				}
			}
		},
		
		/**
		 * If active action is actionId, then unset it
		 * 
		 * @param {String} actionId Action ID
		 */
		unsetActiveAction: function (actionId) {
			if (this.active_action == actionId) {
				this.setActiveAction(null);
			}
		},
		
		/**
		 * Add action
		 * @param {Object} actionId
		 */
		addChildAction: function (actionId) {
			//Check if it's not already added
			var actions = this.getChildActions();
			if (actionId in actions) return;
			
			//Change action place holder
			var action = Manager.getAction(actionId);
			if (action.get('visible')) {
				action.hide();
			}
			action.setPlaceHolder(this.get('contentBox'));
			
			//Call super class method
			Supra.Manager.Action.Base.prototype.addChildAction.apply(this, arguments);
		},
		
		/**
		 * Update layout size
		 * @private
		 */
		syncLayout: function () {
			this.fire('contentResize');
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			if (!this.CAN_HIDE) {
				this.show();
			}
		},
		
		/**
		 * Execute action
		 * 
		 * @param {String} actionId Action name which will be visible inside container
		 */
		execute: function (actionId) {
			this.setActiveAction(actionId);
		}
	};
	
	//Create Action for right container
	new Action(Supra.mix({}, ContainerProto, {
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'LayoutRightContainer',
		
		/**
		 * Container classname
		 * @type {String}
		 * @private
		 */
		CONTAINER_SELECTOR: '.right-container',
		
	}));
	
	
	//Create Action for left container
	new Action(Supra.mix({}, ContainerProto, {
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'LayoutLeftContainer',
		
		/**
		 * Container classname
		 * @type {String}
		 * @private
		 */
		CONTAINER_SELECTOR: '.left-container'
		
	}));
	
	//Create Action for top container
	new Action(Supra.mix({}, ContainerProto, {
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'LayoutTopContainer',
		
		/**
		 * Container classname
		 * @type {String}
		 * @private
		 */
		CONTAINER_SELECTOR: '.top-container',
		
		/**
		 * Container can be hidden
		 * @type {Boolean}
		 * @private
		 */
		CAN_HIDE: false,
		
		/**
		 * Action which will be visible if container can't be hidden
		 * @type {String}
		 * @private
		 */
		PRIMARY_ACTION: 'PageToolbar'
		
	}));
	
	/*
	 * LayoutContainers action manages Left Right and Top actions
	 * and links them to other actions
	 */
	new Action({
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'LayoutContainers',
		
		/**
		 * Load action template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Load action stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			this.addChildAction('LayoutTopContainer');
			this.addChildAction('LayoutLeftContainer');
			this.addChildAction('LayoutRightContainer');
		},
		
		/**
		 * Instant layout resize without CSS animation
		 */
		setInstantResize: function (instant) {
			var nodes = Y.all('div.center-container, div.left-container, div.right-container'),
				fn    = instant ? 'addClass' : 'removeClass',
				i     = 0,
				ii    = nodes.size();
			
			for (; i<ii; i++) {
				nodes.item(i)[fn]('no-transitions');
			}
		},
		
		
		/**
		 * Bind layouts together
		 * @private
		 */
		bindLayouts: function () {
			var layoutTopContainer = Supra.Manager.getAction('LayoutTopContainer'),
				layoutLeftContainer = Supra.Manager.getAction('LayoutLeftContainer'),
				layoutRightContainer = Supra.Manager.getAction('LayoutRightContainer');
			
			//On resize trigger events
			layoutLeftContainer.plug(Supra.PluginLayout, {'offset': [0, 0, 0, 0]});
			layoutRightContainer.plug(Supra.PluginLayout, {'offset': [0, 0, 0, 0]});
			
			//On left container show hide right container and wise versa
			layoutLeftContainer.on('visibleChange', function (evt) {
				if (evt.newVal != evt.prevVal && evt.newVal) layoutRightContainer.hide();
			});
			layoutRightContainer.on('visibleChange', function (evt) {
				if (evt.newVal != evt.prevVal && evt.newVal) layoutLeftContainer.hide();
			});
			
			//Call only once
			this.bindLayouts = function () {};
		},
		
		/**
		 * Execute
		 */
		execute: function (callback, context) {
			Manager.executeAction('LayoutLeftContainer');
			Manager.executeAction('LayoutRightContainer');
			Manager.executeAction('LayoutTopContainer');
			
			this.bindLayouts();
			
			if (Y.Lang.isFunction(callback)) {
				callback.call(context, this);
			}
		}
	});
	
});