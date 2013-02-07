//Invoke strict mode
"use strict";

YUI.add('website.input-fonts', function (Y) {
	
	/**
	 * 
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-fonts';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	
	Y.extend(Input, Supra.Input.SelectVisual, {
		
		/**
		 * Returns button label template
		 * 
		 * @return Label template
		 * @type {String}
		 * @private
		 */
		getButtonLabelTemplate: function (definition) {
			return '<div class="su-button-bg"><div style="' + this.getButtonBackgroundStyle(definition) + '"><p style="' + this.getButtonFontStyle(definition) + '"></p></div></div>';
		},
		
		/**
		 * Returns button font style
		 * 
		 * @param {Object} definition Button definition
		 * @return Font CSS style
		 * @type {String}
		 * @private
		 */
		getButtonFontStyle: function (definition) {
			var family = (definition.family || definition.title || '');
			return family ? 'font-family: ' + family + ';' : '';
		}
		
	});
	
	Supra.Input.Fonts = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);