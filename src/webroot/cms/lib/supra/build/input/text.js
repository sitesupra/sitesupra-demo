YUI.add('supra.input-text', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-text';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {};
	
	Y.extend(Input, Supra.Input.String, {
		INPUT_TEMPLATE: '<textarea spellcheck="false"></textarea>',
		KEY_RETURN_ALLOW: false
	});
	
	Input.lipsum = function () {
		return Supra.Lipsum.paragraph({'count': 4});
	};
	
	Supra.Input.Text = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-string']});