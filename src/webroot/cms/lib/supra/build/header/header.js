YUI.add('supra.header', function(Y) {
	//Invoke strict mode
	"use strict";
	
	//Add application configuration to Intl for templates
	Supra.Intl.add({'application': Supra.data.get('application')});
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.header.appdock']});