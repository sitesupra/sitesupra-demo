//Invoke strict mode
"use strict";

YUI.add("supra.authorization", function (Y) {
	
	Supra.Authorization = {
		
		/**
		 * Returns true if user is allowed to perform operation, otherwise returns default_value
		 * 
		 * @param {String} permission_id
		 * @param {Boolean} default_value Optional. Default is true
		 */
		isAllowed: function (permission_id, default_value) {
			var default_value = (default_value === undefined || default_value === null ? true : !!default_value);
			return default_value;
			//@TOOD
		},
		
		/**
		 * Set permissions
		 * 
		 * @param {Array} permissions
		 */
		setPermissions: function (permissions) {
			//@TODO
			return this;
		}
		
	};
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
});