//Invoke strict mode
"use strict";

YUI.add('supra.template-block-if', function (Y) {
	
	/**
	 * Handlebars {{#if } helper
	 */
	Supra.Handlebars.registerHelper('if', function(a, comp, b, fn, elseFn) {
		
		//Normalize arguments
		if (typeof comp != 'string') {
			fn = comp;
			elseFn = b;
			comp = null;
		}
		
		if (!comp) {
			return (!a || a == [] ? elseFn(this) : fn(this));
		} else {
			if (comp == 'eq') {
				return (a == b ? fn(this) : elseFn(this));
			} else if (comp == 'gt') {
				return (a > b ? fn(this) : elseFn(this));
			} else if (comp == 'lt') {
				return (a < b ? fn(this) : elseFn(this));
			} else {
				return elseFn(this);
			}
		}
	});
	
	//Since this object has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.template-handlebars']});