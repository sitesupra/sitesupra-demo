//Invoke strict mode
"use strict";

YUI.add('supra.template-helper-intl', function (Y) {
	
	/**
	 * Handlebars {{#if } helper
	 */
	Supra.Handlebars.registerHelper('intl', function(source) {
		var args = [].splice.call(arguments, 0);
		return Supra.Intl.get(args.join(''));
	});
	
	//Since this object has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.template-handlebars']});