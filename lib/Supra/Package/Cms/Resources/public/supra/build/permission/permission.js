YUI.add("supra.permission", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var REQUEST_URI = "/check-permissions";
	
	Supra.Permission = {
		
		/**
		 * Permission list
		 * @type {Object}
		 * @private
		 */
		permissions: {},
		
		/**
		 * List of IDs loading
		 * @type {Object}
		 * @private
		 */
		loading: {},
		
		/**
		 * Permission load listeners. Array where each item is object in
		 * format {"callback": ..., "context": ..., "dependancies": [...]}
		 * @type {Array}
		 * @private
		 */
		listeners: [],
		
		
		/**
		 * Returns all permision values which needs to be loaded
		 * 
		 * @param {Array} permissions List of permissions
		 * @return Permissions which are not loaded yet
		 * @type {Object}
		 */
		diff: function (args) {
			if (!Y.Lang.isArray(args)) {
				args = [args];
			}
			
			var permissions = this.permissions,
				loading     = this.loading,
				item        = null,
				id          = null,
				i           = 0,
				ii          = args.length,
				output      = [];
			
			for(; i<ii; i++) {
				item = args[i];
				id = item.type + '_' + item.id;
				
				if (!permissions[id] || loading[id]) {
					output.push(item)
				}
			}
			
			return output;
		},
		
		/**
		 * Returns single value or null if not found
		 * 
		 * @param {String} type Permission object type
		 * @param {String} id Permission object id
		 * @param {String} key Optional sub-key
		 * @param {Object} default_value Default value
		 * @return Permission values
		 * @type {Object}
		 */
		get: function (type, id, key, default_value) {
			var permissions = this.permissions,
				loading     = this.loading,
				id          = type + '_' + id;
			
			if (permissions[id]) {
				var obj = permissions[id].value;
				if (key) {
					if (obj && key in obj) {
						return obj[key];
					}
				} else {
					if (obj) {
						return obj;
					}
				}
			}
			
			return default_value !== undefined ? default_value : null;
		},
		
		/**
		 * Returns all permission values or null if any of the permissions
		 * is not known
		 * 
		 * @param {Array} permissions List of permissions
		 * @return Permission values if all permissions are known
		 * @type {Object}
		 */
		getPermissions: function (args) {
			if (!Y.Lang.isArray(args)) {
				args = [args];
			}
			
			var permissions = this.permissions,
				loading     = this.loading,
				item        = null,
				id          = null,
				i           = 0,
				ii          = args.length,
				output      = {};
			
			for(; i<ii; i++) {
				item = args[i];
				id = item.type + '_' + item.id;
				
				if (permissions[id] && !loading[id]) {
					output[item.type] = output[item.type] || {};
					output[item.type][item.id] = permissions[id].value;
				} else {
					return null;
				}
			}
			
			return output;
		},
		
		/**
		 * Request permissions
		 * 
		 * @param {Array} permissions List of permissions
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function execution context, optional
		 */
		request: function (permissions, callback, context) {
			//If all permissions already are known, execute callback immediately
			var data = this.getPermissions(permissions),
				diff = null;

			if (data) {
				if (Y.Lang.isFunction(callback)) {
					callback.call(context || this, data);
				}
				return;
			} else {
				diff = this.diff(permissions);
			}

			//Wait till permissions are loaded and execute callback
			this.done(permissions, diff, callback, context);

			//Load permissions
			Supra.io(Supra.Manager.Loader.getDynamicPath() + REQUEST_URI, {'suppress_errors': true, 'method': 'post'}, diff);
		},
		
		/**
		 * Add event listener for permission loading
		 * To callback will be passed object in following format
		 * {page: {111: ..., 222: ...}}
		 * 
		 * @param {Array} permissions List of permissions
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function execution context, optional
		 */
		done: function (permissions, diff, callback, context) {
			var dependancies = [],
				i = 0,
				ii = diff.length;
			
			for(; i<ii; i++) {
				dependancies[dependancies.length] = diff[i].type + '_' + diff[i].id;
			}
			
			this.listeners.push({
				"callback": callback,
				"context": context,
				"permissions": permissions,
				"dependancies": dependancies
			});
		},
		
		/**
		 * Set list of permissions as loading
		 * 
		 * @param {Array} permissions List of permission definitions
		 */
		setIsLoading: function (permissions) {
			var i       = 0,
				ii      = permissions.length,
				item    = null,
				id      = null,
				loading = this.loading;
			
			for(; i<ii; i++) {
				item = permissions[i];
				id = item.type + '_' + item.id;
				loading[id] = true;
			}
		},
		
		/**
		 * Add permissions
		 * 
		 * @param {Array} data List of permission values
		 * @param {Array} args List of permission definitions
		 */
		add: function (data, args) {
			var listeners   = this.listeners,
			    listener    = null,
				l           = 0,
				index       = 0,
				callbacks   = [],
				output      = null,
				
				permissions = this.permissions,
				i           = 0,
				ii          = args.length,
				
				loading     = this.loading,
				item        = null,
				id          = null;
			
			for(; i<ii; i++) {
				item = args[i];
				id = item.type + '_' + item.id;
				
				permissions[id] = args[i];
				permissions[id].value = data[i];
				
				delete(loading[id]);
				
				//Check listeners
				for(l=listeners.length-1; l>=0; l--) {
					listener = listeners[l];
					index = Y.Array.indexOf(listener.dependancies, id);
					
					if (index != -1) {
						if (listener.dependancies.length > 1) {
							//More than 1 dependancy left
							listener.dependancies.splice(index, 1);
						} else {
							//All dependancies has been resolved
							callbacks.push(listener);
							listeners.splice(l, 1);
						}
					}
				}
			}
			
			//Callbacks
			for(i=0,ii=callbacks.length; i<ii; i++) {
				output = this.getPermissions(callbacks[i].permissions);
				callbacks[i].callback.call(callbacks[i].context, output);
			}
		}
		
	};
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);