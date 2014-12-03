(function (demo) {
	
	//
	// Checkbox input
	//
	function Checkbox (options) {
		this.node = options.node;
		this.handle = options.node.find('a span');
		
		this.node.mousedown($.proxy(this.toggle, this));
		
		if (options.value) {
			// By default value is false
			this.toggle();
		}
	}
	Checkbox.prototype = {
		toggle: function () {
			this.value(!this.value());
			return false;
		},
		
		value: function (value) {
			if (typeof value === 'boolean') {
				var left = value ? '49px' : '21px';
			
				this.node.toggleClass('c-input-checkbox-checked');
				this.handle.animate({'left': left}, 'fast');
				
				$(this).trigger('change', {'value': value});
			} else {
				return this.node.hasClass('c-input-checkbox-checked');
			}
		},
		
		on: function (event, handle) {
			return $(this).on(event, handle);
		}
	};
	
	demo.Checkbox = Checkbox;

})(demo);