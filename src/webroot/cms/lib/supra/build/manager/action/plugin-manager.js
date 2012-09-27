YUI.add('supra.manager-action-plugin-manager', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Shortcuts
	 */
	var Manager = Supra.Manager;
	
	/**
	 * Plugin manager for action
	 * 
	 * @param {Object} host
	 * @param {Array} plugins
	 */
	var PluginManager = function (host, plugins) {
		this.host = host;
		this.plugins = plugins || [];
		this.instances = {};
		this.initialized = false;
		this.created = false;
	};
	
	PluginManager.prototype = {
		/**
		 * Initialization state
		 * @type {Boolean}
		 */
		initialized: false,
		
		/**
		 * Created state
		 * @type {Boolean}
		 */
		created: false,
		
		/**
		 * Action object
		 * @type {Object}
		 */
		host: null,
		
		/**
		 * Plugin classes
		 * @type {Array}
		 */
		plugins: [],
		
		/**
		 * Plugin instances
		 * @type {Object}
		 */
		instances: {},
		
		/**
		 * Returns plugin by name
		 * 
		 * @param {String} plugin_name
		 * @return Plugin instance
		 * @type {Object}
		 */
		getPlugin: function (plugin_name) {
			var instances = this.instances;
			if (plugin_name in instances) {
				return instances[plugin_name];
			} else {
				return null;
			}
		},
		
		/**
		 * Call create on plugins for compatibility with action
		 */
		create: function () {
			if (this.created) return;
			this.created = true;
			
			var host = this.host,
				plugins = this.plugins,
				base = Manager.Action.PluginBase;
				
			for(var i=0,ii=plugins.length; i<ii; i++) {
				//If plugin doesn't exist, throw error
				if (!plugins[i]) {
					//Debug info
					var trace_path = Supra.Manager.Loader.getActionInfo(host.NAME).path_script;
					Y.log('Action plugin doesn\'t exist, used in ' + host.NAME + ' (' + trace_path + ')', 'error');
					continue;
				}
				
				//Create plugin
				var plugin = new plugins[i](host);
				
				if (plugin instanceof base) {
					//Get NAME
					var plugin_id = plugins[i].NAME;
					
					//Create instance
					this.instances[plugin_id] = plugin;
					
					//Initialize
					plugin.create();
				} else {
					//Debug info
					var trace_path = Supra.Manager.Loader.getActionInfo(host.NAME).path_script;
					Y.log('Plugin ' + plugins[i].NAME || '"unnamed"' + ' is not subclass of PluginBase. Used in action ' + host.NAME + ' (' + trace_path + ')', 'error');
				}
			}
			
			host.fire('plugins:create');
		},
		
		/**
		 * Render plugins
		 */
		render: function () {
			var host = this.host,
				instances = this.instances;
			
			for(var i in instances) {
				instances[i].render();
			}
			
			host.fire('plugins:render');
		},
		
		/**
		 * @constructor
		 */
		initialize: function () {
			if (this.initialized) return;
			this.initialized = true;
			
			var host = this.host,
				instances = this.instances;
			
			for(var i in instances) {
				instances[i].initializeBase();
				instances[i].initialize();
			}
			
			host.fire('plugins:initialize');
		},
		
		/**
		 * Execute plugin
		 */
		execute: function () {
			var host = this.host,
				instances = this.instances;
			
			for(var i in instances) {
				instances[i].execute.apply(instances[i], arguments);
			}
			
			host.fire('plugins:execute');
		},
		
		/**
		 * Destroy all plugins
		 */
		destroy: function () {
			var plugins = this.plugins;
			for(var i=0,ii=plugins.length; i<ii; i++) {
				plugins[i].destroy();
			}
			this.host.unplug();
			
			delete(this.instances);
			delete(this.plugins);
			delete(this.host);
			this.initialized = false;
		}
	};
	
	Manager.Action.PluginManager = PluginManager;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-base']});