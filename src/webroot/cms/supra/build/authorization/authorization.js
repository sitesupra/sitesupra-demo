//Invoke strict mode
"use strict";

YUI.add("supra.authorization", function (Y) {
	
	Supra.Authorization = {
		
		/**
		 * Returns true if user is allowed to perform operation, otherwise returns default_value
		 * 
		 * @param {String} permission_id
		 * @param {Boolean} default_value Optional.
		 */
		isAllowed: function (permission_id, default_value) {
			return true;
			//@TOOD
		}
		
	};
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
});