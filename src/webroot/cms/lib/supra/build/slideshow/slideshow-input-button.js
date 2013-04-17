YUI.add("supra.slideshow-input-button", function (Y) {
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
	
	Input.NAME = "input-button";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		/**
		 * Button element, Supra.Button
		 */
		'button': {
			'value': null
		},
		
		/**
		 * Supra.Slideshow instance
		 */
		'slideshow': {
			'value': null
		},
		
		/**
		 * Slide ID
		 */
		'slideId': {
			'value': null
		},
		
		/**
		 * Icon
		 */
		'icon': {
			'value': null
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '',
		LABEL_TEMPLATE: '',
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var icon = this.get('icon');
			
			this.get('boundingBox')
					.addClass('input-group')
					.addClass('input-group-button')
					.addClass('button-section');
			
			var button = new Supra.Button({
				'label': this.get('label'),
				'style': icon ? 'icon' : 'small-gray',
				'icon': icon
			});
			button.render(this.get('boundingBox'));
			this.set('button', button);
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var slideshow = this.get('slideshow'),
				slide_id = this.get('slideId');
			
			if (slideshow && slide_id) {
				this.get('button').on('click', this._scrollToSlide, this);
			}
		},
		
		_scrollToSlide: function () {
			this.get('slideshow').set('slide', this.get('slideId'));
		},
		
		_setValue: function (value) {
			return undefined;
		},
		
		_getValue: function (value) {
			return undefined;
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		}
	});
	
	Supra.Input.Button = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});