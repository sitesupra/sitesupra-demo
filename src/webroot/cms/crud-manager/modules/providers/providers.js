//Invoke strict mode
"use strict";

YUI.add('website.providers', function (Y) {
	
	/**
	 * Providers
	 */
	var CRUD = Supra.CRUD = Supra.CRUD || {};
	
	function Providers () {
		Providers.superclass.constructor.apply(this, arguments);
		this.providers = {};
		this.init.apply(this, arguments);
	}
	
	Providers.NAME = 'Providers';
	
	Providers.ATTRS = {
		/**
		 * Active provider ID
		 */
		'activeProvider': {
			'value': null,
			'setter': '_setActiveProvider'
		},
		
		/**
		 * Provider configuration
		 */
		'configuration': {
			value: null
		}
	};
	
	Y.extend(Providers, Y.Base, {
		
		/**
		 * Provider list
		 * @type {Object}
		 * @private
		 */
		providers: {},
		
		
		
		/**
		 * Load configuration data
		 * 
		 * @private
		 */
		initialize: function () {
			//Load data
			var uri = Supra.Manager.getAction('Root').getDataPath('configuration');
			Supra.io(uri, this.setup, this);
		},
		
		/**
		 * Set up provider list, create provider instances
		 * 
		 * @param {Object} configuration
		 * @private
		 */
		setup: function (conf) {
			if (!conf || !conf.length) {
				Y.error('Provider configuration is empty');
				return;
			}
			
			//Save config
			this.set('configuration', conf);
			
			//Create provider instances
			var i = 0,
				len = conf.length,
				provider = null,
				providers = {};
			
			for(; i<len; i++) {
				provider = new CRUD.Provider(conf[i].attributes, conf[i]);
				providers[provider.get('id')] = provider;
			}
			
			this.providers = providers;
			this.fire('ready', {'providers': providers});
		},
		
		/**
		 * Returns provider by ID
		 * 
		 * @param {String} provider_id Provider ID
		 * @return Provider instance
		 * @type {Supra.CRUD.Provider}
		 */
		getProvider: function (provider_id) {
			if (typeof provider_id == 'object') {
				return provider_id;
			}
			
			if (this.providers[provider_id]) {
				return this.providers[provider_id];
			} else {
				return null;
			}
		},
		
		/**
		 * Set active provider
		 * 
		 * @param {String} provider_id Provider ID or provider instance
		 */
		setActiveProvider: function (provider_id) {
			this.set('activeProvider', provider_id);
		},
		
		/**
		 * Returns active provider instance
		 * 
		 * @return Active provider instance
		 * @type {Supra.CRUD.Provider}
		 */
		getActiveProvider: function () {
			var provider_id = this.get('activeProvider');
			return this.getProvider(provider_id);
		},
		
		/**
		 * Active provider attribute setter
		 * 
		 * @param {String} provider_id Provider ID or provider instance
		 * @private
		 */
		_setActiveProvider: function (provider_id) {
			var current_provider_id = this.get('activeProvider');
			if (provider_id === null) return current_provider_id;
			
			if (typeof provider_id == 'object') {
				provider_id = provider_id.get('id');
			}
			if (!this.providers[provider_id]) {
				return current_provider_id;
			}
			
			if (current_provider_id) {
				this.getProvider(current_provider_id).set('active', false);
			}
			
			this.getProvider(provider_id).set('active', true);
			return provider_id;
		}
		
	});
	
	Supra.CRUD.Providers = new Providers();
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'website.provider']});