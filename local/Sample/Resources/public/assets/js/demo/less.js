// Enable lesscss development mode
var less = {
	env: "development",
	functions: {}
};

(function (demo) {
	
	var LESS_CHECK_VARIABLE = /[\/:+]/;
	
	demo.less = {
		
		'data': {
			'boxed': false
		},
		
		'lessData': {
		},
		
		'set': function (property, value, silent) {
			if (this.data[property] === value) return;
			
			this.data[property] = value;
			
			if (silent !== true) {
				this.updatePreview(property, value);
			}
		},
		
		'redraw': function (instant) {
			if (instant === true) {
				if (this.updateTimer) {
					clearTimeout(this.updateTimer);
					this.updateTimer = null;
				}
				
				this.updatePreviewDelayed();
			} else if (!this.updateTimer) {
				// Update after small delay (throttle)
				this.updateTimer = setTimeout($.proxy(this.updatePreviewDelayed, this), 1);
			}
		},
		
		'updatePreview': function (property, value) {
			var redraw = true;
			
			if (property === 'boxed') {
				$('body').toggleClass('boxed', !!value);
				$('body').toggleClass('wide', !value);
				redraw = false;
			}
			
			// 'noLess' parameter is used for properties, which don't affect less
			var property = demo.getProperty(property);
			if (property && property.noLess) {
				redraw = false;
			}
			
			if (redraw) {
				this.redraw();
			}
		},
		
		'updatePreviewDelayed': function () {
			$('body').addClass('applying-theme-properties');
			
			var less_data = window.less_data = this.convertDataToLessVariables(this.data);
			less.modifyVars(less_data);
			
			this.updateTimer = null;
			
			setTimeout(function () {
				$('body').removeClass('applying-theme-properties');
			}, 16);
		},
		
		/**
		 * Convert object keys into underscore separated
		 */
		'convertDataToLessVariables': function (data) {
			var less = this.lessData,
				value = null,
				property = null;
			
			for (property in data) {
				value = data[property];
				
				if (value && $.type(value) == 'object') {
					this.objectToPlain(less, property, value);
				} else {
					less[property] = this.toLessPropertyValue(property, value);
				}
			}
			
			return less;
		},
		
		'objectToPlain': function (output, prefix, object) {
			var key = null,
				value = null,
				name = null;
			
			for (key in object) {
				value = object[key];
				name = prefix + '_' + key;
				
				if (value && $.type(value) == 'object') {
					this.objectToPlain(output, name, value);
				} else {
					output[name] = this.toLessPropertyValue(key, value);
				}
			}
		},
		
		'toLessPropertyValue': function (property, value) {
			if (typeof value === 'string' && value.match(LESS_CHECK_VARIABLE)) {
				var start = value.substr(0, 1),
					end   = value.substr(-1, 1);
				
				if ((start != "'" && start != '"') || (end != "'" && end != '"')) {
					value = '"' + value + '"';
				}
			}
			
			if (value === "" && property.toLowerCase().indexOf('color') !== -1) {
				// Transparent color
				value = "transparent";
			} else if (value === undefined || value === null || value === false || value === "") {
				value = "0";
			} else if (value === true) {
				value = "1";
			}
			
			return String(value);
		}
		
	};
	
})(demo);