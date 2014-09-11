YUI().add('supra.htmleditor-plugins', function (Y) {
	//Invoke strict mode
	"use strict";
	
	Y.mix(Supra.HTMLEditor.prototype, {
		
		/**
		 * Plugin instances
		 */
		plugins: {},
		
		/**
		 * Initialize all plugins
		 */
		initPlugins: function () {
			this.plugins = {};
			var plugins = Supra.HTMLEditor.PLUGINS,
				configuration = null,
				mode = this.get('mode'),
				default_modes = [],
				
				type = this.get('standalone') ? Supra.HTMLEditor.TYPE_STANDALONE : Supra.HTMLEditor.TYPE_INLINE,
				default_type = [Supra.HTMLEditor.TYPE_STANDALONE, Supra.HTMLEditor.TYPE_INLINE];
			
			for(var id in plugins) {
				configuration = this.getPluginConfiguration(id);
				
				if (configuration !== false) {
					
					//If plugin doesn't support this mode then skip it
					if (configuration) {
						if (Y.Array.indexOf(configuration.modes || default_modes, mode) == -1) {
							continue;
						}
						if (Y.Array.indexOf(configuration.types || default_type, type) == -1) {
							continue;
						}
					}
					
					this.plugins[id] = Supra.mix({
						'htmleditor': this,
						'id': id,
						'configuration': configuration,
						'NAME': id
					}, plugins[id].properties);
					
					if (this.plugins[id].init(this, configuration) === false) {
						//Initialization failed
						delete(this.plugins[id]);
					}
				}
			}
		},
		
		/**
		 * Add plugin only to this HTMLEditor instance
		 * 
		 * @param {Object} id
		 * @param {Object} configuration
		 * @param {Object} properties
		 */
		addPlugin: function (id, configuration, properties) {
			if (id in this.plugins) return;
			
			//Configuration is optional argument
			if (!properties && configuration) {
				properties = configuration;
				configuration = {};
			}
			
			configuration = this.getPluginConfiguration(id, configuration);
			if (configuration !== false) {
				this.plugins[id] = Supra.mix({
					'htmleditor': this,
					'id': id,
					'configuration': configuration,
					'NAME': id
				}, properties);
				
				if (this.plugins[id].init(this, configuration) === false) {
					//Initialization failed
					delete(this.plugins[id]);
				}
			}
		},
		
		/**
		 * Returns all plugin instances
		 * 
		 * @return All plugins
		 * @type {Object}
		 */
		getAllPlugins: function () {
			return this.plugins;
		},
		
		/**
		 * Returns plugin by ID
		 * 
		 * @param {String} pluginId
		 * @return Plugin instance
		 * @type {Object}
		 */
		getPlugin: function (pluginId) {
			var plugins = this.plugins;
			return pluginId in plugins ? plugins[pluginId] : null;
		},
		
		/**
		 * Destroy all plugins
		 */
		destroyPlugins: function () {
			var i,
				plugins = this.plugins;
				
			for(i in plugins) {
				if (plugins[i].destroy) plugins[i].destroy();
				delete(plugins[i]);
			}
			
			this.plugins = {};
		},
		
		/**
		 * Call plugin cleanUp method to remove everything plugin did to this node
		 * 
		 * @param {Object} node
		 */
		pluginsCleanUpNode: function (node, traverse) {
			var data = this.getData(node);
			if (data && data.type) {
				var plugin = this.getPlugin(data.type);
				if (plugin && 'cleanUp' in plugin) plugin.cleanUp(node, data);
				this.removeData(node);
			}
			if (traverse) {
				var nodes = node.all('[id^="su"]');
				for(var i=0,ii=nodes.size(); i<ii; i++) {
					this.pluginsCleanUpNode(nodes.item(i));
				}
			}
		},
		
		/**
		 * Returns plugins configuration
		 * 
		 * @return Default configuration mixed with user set configuration
		 * @type {Object}
		 */
		getPluginConfiguration: function (pluginId, defaultConfig) {
			if (!(pluginId in Supra.HTMLEditor.PLUGINS) && !defaultConfig) return false;
			
			var // Configuration from Supra.data
				configuration = Supra.data.get(['supra.htmleditor', 'plugins', pluginId]),
				// Configuration from plugin itself
				defaultConfig = defaultConfig || Supra.HTMLEditor.PLUGINS[pluginId].configuration,
				// Configuration from html editor configuration
				attrConfig    = this.get('plugins'),
				// Default modes
				defaultModes = [Supra.HTMLEditor.MODE_STRING, Supra.HTMLEditor.MODE_TEXT, Supra.HTMLEditor.MODE_BASIC, Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH];
			
			if (attrConfig) {
				attrConfig = attrConfig[pluginId];
			} else {
				attrConfig = null;
			}
			
			if (attrConfig === false) {
				//If configuration is false then plugin is disabled
				return false;
			} else {
				var type = Y.Lang.type(attrConfig);
				if ((type === 'null' || type === 'undefined') && configuration === false) {
					//If configuration is false then plugin is disabled
					return false;
				} 
			} 
			
			if (!Y.Lang.isObject(attrConfig)) {
				attrConfig = {};
			}
			if (!Y.Lang.isObject(configuration)) {
				configuration = {};
			}
			
			return Supra.mix({'modes': defaultModes}, defaultConfig, configuration, attrConfig);
		}
		
	});

	//List of plugins
	Supra.HTMLEditor.PLUGINS = {};
	
	/**
	 * Add plugin
	 * 
	 * @param {String} id
	 * @param {Object} configuration
	 * @param {Object} properties
	 */
	Supra.HTMLEditor.addPlugin = function (id, configuration, properties) {
		//Configuration is optional argument
		if (!properties && configuration) {
			properties = configuration;
			configuration = {};
		}
		
		Supra.HTMLEditor.PLUGINS[id] = {'properties': properties, 'configuration': configuration || {}};
	};


	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});