//Invoke strict mode
"use strict";

YUI.add("supra.authorization", function (Y) {
	
	Supra.Authorization = {
		
		/**
		 * Permission list
		 * @type {Object}
		 * @private
		 */
		permissions: {},
		
		
		
		/**
		 * Returns true if user is allowed to perform operation, otherwise returns default_value
		 * 
		 * @param {String} permission_id
		 * @param {Boolean} default_value Optional. Default is true
		 */
		isAllowed: function (permission_id, default_value) {
			var default_value = (default_value === undefined || default_value === null ? true : !!default_value),
				permission_id = Y.Lang.isString(permission_id) ? [permission_id] : permission_id,
				key = '',
				permissions = this.permissions;
			
			while(permission_id.length) {
				key = permission_id.join('/');
				if (key in permissions) return permissions[key].allow;
				permission_id.pop();
			}
			
			if ('/' in permissions) {
				return permissions['/'].allow;
			}
			
			return default_value;
		},
		
		/**
		 * Set permissions
		 * 
		 * @param {Array} permissions
		 */
		setPermissions: function (permissions) {
			var current = this.permissions,
				key = '';
			
			for(var i=0,ii=permissions.length; i<ii; i++) {
				key = permissions[i].id.join('/') || '/';
				current[key] = permissions[i];
			}
			
			return this;
		}
		
	};
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
});