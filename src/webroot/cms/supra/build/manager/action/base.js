YUI.add('supra.manager-action-base', function (Y) {
	
	var Manager = Supra.Manager,
		empty = function () {};
		
	function updateAttributeOnEvent (event, attr_name) {
		this.set(attr_name, event.newVal);
	}
	
	function bubbleEvent (event, event_name) {
		this.fire(event_name, event);
	}
	
	/**
     * Supra Action temporary class
     *
     * @namespace Supra.Manager.Action.Base
     */
	function ActionBase (name) {
		ActionBase.superclass.constructor.apply(this, []);
		this.init.apply(this, []);
		
		this.set('NAME', name);
		this.NAME = name;
		this.PLACE_HOLDER = null;
		this.HAS_STYLESHEET = null;
		this.HAS_TEMPLATE = null;
		this.template = '';
		this.plugins = null;
		this.children = {};
	};
	
	ActionBase.NAME = 'ActionBase';
	
	/*
	 * Action attributes 
	 */
	ActionBase.ATTRS = {
		'created': { 'value': false },
		'ready': { 'value': false },
		'actionPath': { 'value': null },
		'templatePath': { 'value': null },
		'stylesheetPath': { 'value': null },
		'dataPath': { 'value': null },
		'srcNode': { 'value': null },
		'placeHolderNode': { 'value': null, 'getter': '_getPlaceHolderNode' },
		'hasStylesheet': { 'value': false },
		'hasTemplate': { 'value': true },
		'visible': { 'value': false }
	};
	
	Y.extend(ActionBase, Y.Base, {
		/**
		 * Action name
		 * @type {String}
		 */
		NAME: null,
		
		/**
		 * Content placeholder
		 * @type {HTMLElement}
		 */
		PLACE_HOLDER: null,
		
		/**
		 * Action has stylesheet
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: null,
		
		/**
		 * Action has template
		 * @type {Boolean}
		 */
		HAS_TEMPLATE: null,
		
		/**
		 * Template
		 * @type {String}
		 */
		template: '',
		
		/**
		 * Plugin manager
		 * @type {Object}
		 */
		plugins: null,
		
		/**
		 * Children actions
		 * @type {Object}
		 */
		children: {},
		
		/**
		 * Add children action
		 * 
		 * @param {String} action_id
		 */
		addChildAction: function (action_id) {
			this.children[action_id] = Supra.Manager.getAction(action_id);
			return this;
		},
		
		/**
		 * Remove children action
		 * 
		 * @param {String} action_id
		 */
		removeChildAction: function (action_id) {
			if (action_id in this.children) {
				delete(this.children[action_id]);
			}
			return this;
		},
		
		/**
		 * Returns children actions
		 * 
		 * @return Object with child actions
		 * @type {Object}
		 */
		getChildActions: function () {
			return this.children;
		},
		
		/**
		 * Returns children actions which state is 'ready'
		 */
		getActiveChildActions: function () {
			var ret = {}, children = this.children;
			for(var id in children) {
				if (children[id].get('created')) {
					ret[id] = children[id];
				}
			}
			return ret;
		},
		
		/**
		 * Returns action name
		 * 
		 * @return Action name
		 * @type {String}
		 */
		getName: function () {
			return this.NAME;
		},
		
		/**
		 * Returns true if action has template
		 * 
		 * @return True if action has template
		 * @type {Boolean}
		 */
		getHasTemplate: function () {
			return this.get('hasTemplate');
		},
		
		/**
		 * Set if action has template
		 * 
		 * @param {Boolean} has_template
		 */
		setHasTemplate: function (has_template) {
			this.set('hasTemplate', !!has_template);
			return this;
		},
		
		/**
		 * Returns true if action has stylesheet
		 * 
		 * @return True if action has stylesheet
		 * @type {Boolean}
		 */
		getHasStylesheet: function () {
			return this.get('hasStylesheet');
		},
		
		/**
		 * Set if action has stylesheet
		 * 
		 * @param {Boolean} has_stylesheet
		 */
		setHasStylesheet: function (has_stylesheet) {
			this.set('hasStylesheet', !!has_stylesheet);
			return this;
		},
		
		/**
		 * Returns template path
		 * 
		 * @return URI path to template
		 * @type {String}
		 */
		getTemplatePath: function () {
			return this.get('templatePath');
		},
		
		/**
		 * Change template path
		 * 
		 * @param {String} path
		 */
		setTemplatePath: function (path) {
			this.set('templatePath', path || '');
			return this;
		},
		
		/**
		 * Returns data path
		 * 
		 * @return URI path to template
		 * @type {String}
		 */
		getDataPath: function () {
			return this.get('dataPath');
		},
		
		/**
		 * Change data path
		 * 
		 * @param {String} path
		 */
		setDataPath: function (path) {
			this.set('dataPath', path || '');
			return this;
		},
		
		/**
		 * Returns stylesheet path
		 * 
		 * @return URI path to stylesheet
		 * @type {String}
		 */
		getStylesheetPath: function () {
			return this.get('stylesheetPath');
		},
		
		/**
		 * Change stylesheet path
		 * 
		 * @param {String} path
		 */
		setStylesheetPath: function (path) {
			this.set('templatePath', path || '');
			return this;
		},
		
		/**
		 * Returns action path
         *
         * @return URI path to action
         * @type {String}
		 */
		getPath: function () {
			return this.get('actionPath');
		},
		
		/**
		 * Returns action container nodes
		 * 
		 * @return Container node list, Y.NodeList
		 * @type {Object}
		 */
		getContainers: function () {
			return this.get('srcNode');
		},
		
		/**
		 * Returns action container node matching css selector or first container node
		 * 
		 * @param {String} selector Optional. CSS selector
		 * @return Container node element matching css selector or first container element, Y.Node
		 * @type {Object}
		 */
		getContainer: function (selector) {
			//Y.NodeList
			var srcNode = this.get('srcNode');
			if (!srcNode) return null;
			
			if (selector) {
				//Check if one of the nodes matches selector
				var matches = srcNode.filter(selector);
				var node = matches.item(0);
				
				if (!node) {
					for(var i=0,ii=srcNode.size(); i<ii; i++) {
						node = srcNode.item(i).one(selector);
						if (node) break;
					}
				}
				
				return node;
			} else {
				return srcNode.item(0);
			}
		},
		
		/**
		 * Returns place holder node
		 * Place holder is node where content will be inserted
		 * 
		 * @return Place holder node, Y.Node
		 * @type {Y.Node}
		 */
		getPlaceHolder: function () {
			return this.get('placeHolderNode');
		},
		
		/**
		 * Set place holder node where action content will be inserted
		 * 
		 * @param {Object} node
		 */
		setPlaceHolder: function (node) {
			this.set('placeHolderNode', node);
			return this;
		},
		
		/**
		 * Returns place holder node
		 * 
		 * @param {Object} value
		 * @return Place holder node
		 * @type {Y.Node}
		 * @private
		 */
		_getPlaceHolderNode: function (value) {
			return value || Manager.getContainerNode();
		},
		
		/**
		 * Returns all widgets for given plugin
		 * 
		 * @param {String} plugin_name
		 * @return All widgets for given plugin
		 * @type {Object}
		 */
		getPluginWidgets: function (plugin_name, as_array) {
			var plugin = this.plugins.getPlugin(plugin_name);
			var widgets = {};
			
			if (plugin) {
				widgets = plugin.getWidgets();
			} else {
				return (as_array ? [] : {});
			}
			
			if (as_array) {
				var dest = [];
				for(var i in widgets) {
					dest[dest.length] = widgets[i];
				}
				widgets = dest;
			}
			
			return widgets;
		},
		
		/**
		 * Bind widget attribute to Action attribute,
		 * when ones attribute changes others attribute is changed also
		 * 
		 * @param {Object} widget Widget instance
		 * @param {Object} attributes List of attributes, which should be linked
		 */
		bindAttributes: function (widget, attributes) {
			if (Y.Lang.isArray(attributes)) {
				for(var i=0,ii=attributes.length; i<ii; i++) {
					if (Y.Lang.isString(attributes[i])) {
						widget.after(attributes[i] + 'Change', updateAttributeOnEvent, this, attributes[i]);
						this.after(attributes[i] + 'Change', updateAttributeOnEvent, widget, attributes[i]);
					}
				}
			} else if (Y.Lang.isObject(attributes)) {
				for(var i in attributes) {
					if (Y.Lang.isString(attributes[i])) {
						var action_attr = attributes[i];
						var widget_attr = i;
						
						widget.after(widget_attr + 'Change', updateAttributeOnEvent, this, action_attr);
						this.after(action_attr + 'Change', updateAttributeOnEvent, widget, widget_attr);
					}
				}
			}
			return this;
		},
		
		/**
		 * Bubble widgets events to Action
		 * 
		 * @param {Object} widget Widget instance
		 * @param {Object} events List of events, which should be propagated
		 */
		bubbleEvents: function (widget, events) {
			if (Y.Lang.isArray(events)) {
				for(var i=0,ii=events.length; i<ii; i++) {
					if (Y.Lang.isString(events[i])) {
						widget.on(events[i], bubbleEvent, this, events[i]);
					}
				}
			} else if (Y.Lang.isObject(events)) {
				for(var i in events) {
					if (Y.Lang.isString(events[i])) {
						widget.on(i, bubbleEvent, this, events[i]);
					}
				}
			}
			return this;
		},
		
		/**
		 * Bind widget methods to Action
		 * Creates method for action, which will call widget method when called
		 * 
		 * @param {Object} widget Widget instance
		 * @param {Object} methods List of methods
		 */
		importMethods: function (widget, methods) {
			if (Y.Lang.isArray(methods)) {
				for(var i=0,ii=methods.length; i<ii; i++) {
					if (Y.Lang.isString(methods[i])) {
						var fn = widget[methods[i]];
						if (Y.Lang.isFunction(fn)) {
							this[methods[i]] = Y.bind(widget[methods[i]], widget);
						}
					}
				}
			} else if (Y.Lang.isObject(methods)) {
				for(var i in methods) {
					if (Y.Lang.isString(methods[i])) {
						this[methods[i]] = Y.bind(widget[i], widget);
					}
				}
			}
			return this;
		},
		
		/**
		 * Returns a function that will execute the supplied function in the Action context
		 * 
		 * @param {Function} fn
		 * @return Function which will execute in Action context
		 * @type {Function}
		 */
		bind: function (fn) {
			return Y.bind(fn, this);
		},
		
		/**
		 * Returns true if action is ready, otherwise false
		 * 
		 * @return Action ready state
		 * @type {Boolean}
		 */
		isReady: function () {
			return this.get('ready');
		},
		
		/**
		 * Returns true if action is initialized, otherwise false
		 * 
		 * @return Action intialized state
		 * @type {Boolean}
		 */
		isInitialized: function () {
			return this.get('created');
		},
		
		/**
		 * Load template, stylesheet
         *
         * @private
		 */
		_loadTemplate: function () {
			Manager.fire(this.NAME + ':load');
			this.fire('load');
			
			var hasTemplate = this.getHasTemplate() && this.getTemplatePath();
			var hasStylesheet = this.getHasStylesheet() && this.getStylesheetPath();
			
			if (hasTemplate || hasStylesheet) {
				//Load template
				Manager.Loader.loadTemplate(this.NAME, Y.bind(function (template) {
					this.template = template;
					this._fireReady();
					Manager.runExecutionQueue();
				}, this));
			} else {
				//Action don't have a template
				this._fireReady();
				Manager.runExecutionQueue();
			}
		},

        /**
         * Fires 'ready' event
         *
         * @private
         */
		_fireReady: function () {
			this.set('ready', true);
			this.fire('ready');
			Manager.fire(this.NAME + ':ready');
		},
		
		/**
		 * Called before initialize()
         *
         * @private
		 */
		_preInitialize: function () {
			//Insert template into DOM
			if (this.template) {
				var container = this.getPlaceHolder();
				
				//Create node from HTML
				var nodes = Y.Node.create(this.template);
				
				//If template was several nodes, then 'node' is document fragment,
				//but we need Y.NodeList 
				if (nodes._node.nodeType == 11) {	//11 - DocumentFragment
					nodes = nodes.get('children');
				} else {
					//Convert Y.Node into Y.NodeList
					nodes = new Y.NodeList(nodes);
				}
				
				//Append all nodes to container
				nodes.each(function () {
				    container.append(this);
				});
				
				this.set('srcNode', nodes);
			}
			
			//On visibility change, update children actions
			this.on('visibleChange', function (event) {
				if (!event.newVal) {
					var children = this.getActiveChildActions();
					for(var id in children) {
						children[id].hide();
					}
				}
			}, this);
			
			//Initialize plugins
			this.plugins.initialize();
			
			//Bind destroy
			this.on('destroy', this._destroy, this);
		},
		
		/**
		 * Called before render()
         *
         * @private
		 */
		_preRender: function () {
			//Fire 'initialized' callback
			this.set('created', true);
			this.fire('initialize');
			Manager.fire(this.NAME + ':initialize');
			
			//Render all plugins
			this.plugins.render();
		},
		
		/**
		 * Called after render()
         *
         * @private
		 */
		_postRender: function () {
			this.fire('render');
		},
		
		/**
		 * Called before 'execute' event.
		 * Initializes Action if not done already
         *
         * @private
		 */
		_preExecute: function (event) {
			//Initialization phase
			if (!this.get('created')) {
				this._preInitialize();
				this.initialize();
				this._preRender();
				this.render();
				this._postRender();
			}
		},
		
		/**
		 * Called after execute()
         *
         * @private
		 */
		_postExecute: function (event) {
			//Execute plugins (show panel, reload data, etc.)
			this.plugins.execute.apply(this.plugins, event.details[0]);
		},
		
		/**
		 * Execute action, overwrite .execute()
         *
         * @private
		 */
		_execute: function () {
			var args = [].slice.call(arguments, 0);
			
			if (!this.get('ready') || !this.get('initialized')) {
				args = [this.NAME].concat(args);
				Manager.executeAction.apply(Manager, args);
			} else {
				this.fire('execute', args);
			}
		},
		
		/**
		 * Call manager to load action and execute it
		 * Overwritten by _execute
		 */
		execute: function () {
			if (!this.get('ready')) {
				var args = [].slice.call(arguments, 0);
					args = [this.NAME].concat(args);
				
				Manager.executeAction.apply(Manager, args);
			}
			
			return this;
		},
		
		/**
		 * Show action, Action plugins are responsible for showing
		 * needed elements
		 */
		show: function () {
			this.set('visible', true);
			return this;
		},
		
		/**
		 * Hide action, Action plugins are responsible for hiding
		 * needed elements
		 */
		hide: function () {
			this.set('visible', false);
			return this;
		},
		
		/**
         * Initialize action
         *
         * @private
         */
		initialize: function () {},

        /**
         * Render action widgets
         *
         * @private
         */
		render: function () {},
		
		/**
		 * Destroy action and all children actions
		 * 
		 * @private
		 */
		_destroy: function () {
			//Remove listener to prevent being called again and creating a loop
			this.detach('destroy', this._destroy, this);
			
			//Destroy children
			for(var id in this.children) {
				this.children[id].destroy();
			}
			
			delete(this.children);
			
			//Plugins
			if (this.plugins) {
				this.plugins.destroy();
				delete(this.plugins);
			}
			
			//Other data
			delete(this.template);
			
			//Remove node
			if (this.get('srcNode')) {
				this.get('srcNode').detach().remove();
			}
			
			//Remove from Supra.Manager
			Manager.destroyAction(this.NAME);
		},
		
        /**
         * Original 'execute' function
         * 
         * @private
         */
		_originalExecute: function () {}
		
	});
	
	Supra.Manager.Action.Base = ActionBase;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-base']});