/**
 * Crud instance controller
 */
YUI.add('supra.crud-configuration', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Configuration object
	 */
	function ConfigurationObject (providerId, configuration) {
		this._providerId = providerId;
		this._configuration = configuration;
	};
	
	ConfigurationObject.prototype = {
		
		/**
		 * Returns raw object wth configuration data
		 * 
		 * @returns {Object} Configuration data
		 */
		toObject: function () {
			return this._configuration;
		},
		
		/**
		 * Return value from configuration
		 *
		 * @param {String|Array} Key, path to the value or dot separated path to the value
		 * @returns {Object} Value
		 */
		getConfigValue: function (_path, default_value) {
			var path = typeof _path === 'string' ? _path.split('.') : _path;
			return Supra.getObjectValue(this._configuration, path) || default_value;
		},
		
		/**
		 * Returns field configuration
		 *
		 * @param {Array|String} fields List of field ids or dot separated path to the names
		 * of the fields from configuration
		 * @param {String} mode Optional, if set as "DataGrid" then data will be transformed
		 * @returns {Object} Field configuration
		 */
		getConfigFields: function (_fields, mode) {
			var fields = _fields,
				definition,
				definitions = [],
				i, ii;
			
			if (typeof fields === 'string') {
				fields = this.getConfigValue(fields);
			}
			if (Y.Lang.isArray(fields)) {
				for (i=0, ii=fields.length; i<ii; i++) {
					definition = this.getConfigField(fields[i], mode);
					
					if (definition) {
						definitions.push(definition);
					}
				}
			}
			
			return definitions;
		},
		
		/**
		 * Returns full configuration for a field
		 *
		 * @param {String} field Field id
		 * @param {String} mode Optional, if set as "DataGrid" then data will be transformed
		 * @returns {Object|Null} Field configuration
		 */
		getConfigField: function (field, mode) {
			var fields = this.getConfigValue('fields'),
				i = 0,
				ii = fields.length;
			
			for (; i<ii; i++) {
				if (fields[i].id === field) {
					if (mode === 'DataGrid') {
						return Supra.mix({'title': fields[i].label}, fields[i]);
					} else {
						return fields[i];
					}
				}
			}
			
			return null;
		},
		
		/**
		 * Returns path to CRUD data endpoint
		 * 
		 * @param {String} path Specific path like "configuration", "datalist", "datatree", etc.
		 * @returns {String} Path
		 */
		getDataPath: function (path) {
			return Supra.Crud.getDataPath(this._providerId, path);
		}
	};
	
	
	Supra.mix(Supra.Crud || (Supra.Crud = {}), {
		
		/**
		 * Configuration object class export
		 */
		ConfigurationObject: ConfigurationObject,
		
		/**
		 * Configuration cache
		 * @type {Object}
		 */
		_configuration: {},
		
		/**
		 * Retrieves configuration for CRUD manager
		 *
		 * @param {String} providerId Crud manager ID
		 * @returns {Object} Promise
		 */
		getConfiguration: function (providerId) {
			if (!providerId) {
				return Supra.Deferred().reject().promise();
			} else {
				var configuration = this._configuration[providerId];
				if (configuration) {
					return Supra.Deferred().resolve(configuration).promise();
				}
			}
			
			var url = this.getDataPath(providerId, 'configuration'),
				promise = Supra.Deferred();
			
			Supra.io(url, {
				'data': {'providerId': providerId}
			}).then(function (response) {
				var object = new ConfigurationObject(providerId, response);
				this._configuration[providerId] = object;
				promise.resolve(object);
			}, function () {
				promise.reject();
			}, this);
			
			return promise.promise();
		},
		
		/**
		 * Returns path to CRUD data endpoint
		 *
		 * @param {String} providerId Crud manager ID
		 * @param {String} path Specific path like "configuration", "datalist", "datatree", etc.
		 * @returns {String} Path
		 */
		getDataPath: function (providerId, path) {
			return Supra.Manager.getAction('Crud').getDataPath('dev/' + path, {'providerId': providerId});
		}
	});
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager']});
