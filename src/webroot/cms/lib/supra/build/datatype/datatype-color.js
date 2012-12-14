/*
 * Add color parsing and formatting
 */
YUI.add('supra.datatype-color', function(Y) {
	//Invoke strict mode
	"use strict";
	
	var Color = Y.namespace("DataType.Color");
	
	//Regular expressions
	var REGEX_RGB = Color.REGEX_RGB = /^rgb(a)?\((\d+)\s?,\s?(\d+)\s?,\s?(\d+)/i,
		REGEX_HEX = Color.REGEX_HEX = /^#[0-9ABCDEF]{3}([0-9ABCDEF]{3})?/i,
		REGEX_HEX_LONG_TO_RGB = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i,
		REGEX_HEX_SHORT_TO_RGB = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
	
	
	
	/**
	 * Parse string color and convert to RGB object with keys
	 * 'red', 'blue' and 'green'
	 * 
	 * @param {String} value Color value
	 * @return Object with red, blue and green keys
	 * @type {Object}
	 */
	Color.parse = function (value) {
		
		if (typeof value === 'string') {
			var match = null;
			
			if (match = value.match(REGEX_RGB)) {
				return {
					'red': parseInt(match[2], 10),
					'green': parseInt(match[3], 10),
					'blue': parseInt(match[4], 10)
				};
			} else if (REGEX_HEX.test(value)) {
				return Color.convert.HEXtoRGB(value);
			}
		} else if (typeof value === 'object' && value) {
			if ('red' in value && 'green' in value && 'blue' in value) {
				return value;
			} else if ('hue' in value && 'saturation' in value && 'brightness' in value) {
				return Color.convert.HSBtoRGB(object);
			} else {
				//Unknown format
				return null;
			}
		} else {
			//Unknown format
			return null;
		}
		
	};
	
	/**
	 * Format color object into a string
	 * 
	 * @param {Object} object Color object in RGB or HSB formats
	 * @return Color in HEX format
	 * @type {String}
	 */
	Color.format = function (object) {
		if (typeof object === 'object' && object) {
			
			if ('red' in object && 'green' in object && 'blue' in object) {
				return Color.convert.RGBtoHEX(object);
			} else if ('hue' in object && 'saturation' in object && 'brightness' in object) {
				return Color.convert.HSBtoHEX(object);
			}
			
		} else if (typeof object === 'string') {
			return object;
		} else {
			//Unknown format
			return '';
		}
	};
	
	
	
	
	/**
	 * Math functions for colors
	 */
	Color.math = {
		/**
		 * Returns difference between colors
		 * 
		 * @param {Object} a Color a
		 * @param {Object} b Color b
		 * @return Difference between colors as RGB color object
		 * @type {Object}
		 */
		'diff': function (a, b) {
			a = Color.parse(a);
			b = Color.parse(b);
			
			if (a && b) {
				return {'red': a.red - b.red, 'green': a.green - b.green, 'blue': a.blue - b.blue};
			} else {
				return {'red': 0, 'green': 0, 'blue': 0};
			}
		},
		
		/**
		 * Add color b to a
		 * 
		 * @param {Object} a Color a
		 * @param {Object} b Color b
		 * @return RGB color
		 * @type {Object}
		 */
		'add': function (a, b) {
			a = Color.parse(a);
			b = Color.parse(b);
			
			return this.normalize({
				'red': a.red + b.red,
				'green': a.green + b.green,
				'blue': a.blue + b.blue
			});
		},
		
		/**
		 * Subtract color b from a
		 * 
		 * @param {Object} a Color a
		 * @param {Object} b Color b
		 * @return RGB color
		 * @type {Object}
		 */
		'subtract': function (a, b) {
			a = Color.parse(a);
			b = Color.parse(b);
			
			return this.normalize({
				'red': a.red - b.red,
				'green': a.green - b.green,
				'blue': a.blue - b.blue
			});
		},
		
		/**
		 * Multiply color a by b
		 * 
		 * @param {Object} a Color a
		 * @param {Object} b Color b or number to multiply by
		 * @return RGB color
		 * @type {Object}
		 */
		'multiply': function (a, b) {
			a = Color.parse(a);
			b = (typeof b === 'number' ? {'red': b, 'green': b, 'blue': b} : Color.parse(b));
			
			return this.normalize({
				'red': ~~(a.red * b.red),
				'green': ~~(a.green * b.green),
				'blue': ~~(a.blue * b.blue)
			});
		},
		
		/**
		 * Normalize RGB or HSB color object by validating all properties
		 * 
		 * @param {Object} object Color object
		 * @param {Boolean} clone Clone color object instead of changing passed in
		 * @return Normalized color object
		 * @type {Object}
		 */
		'normalize': function (object, clone) {
			if (clone) {
				object = Supra.mix({}, object);
			}
			
			if ('red' in object && 'green' in object && 'blue' in object) {
				//RGB
				object.red   = Math.min(255, Math.max(0, object.red));
				object.green = Math.min(255, Math.max(0, object.green));
				object.blue  = Math.min(255, Math.max(0, object.blue));
			} else {
				//HSB
				object.hue         = Math.min(255, Math.max(0, object.hue));
				object.saturation  = Math.min(100, Math.max(0, object.saturation));
				object.brightness  = Math.min(100, Math.max(0, object.brightness));
			}
			
			return object;
		},
		
		/**
		 * Returns average color between a and b
		 * 
		 * @param {Object} a Color a
		 * @param {Object} b Color b
		 * @return RGB color
		 * @type {Object}
		 */
		'average': function () {
			a = Color.parse(a);
			b = Color.parse(b);
			
			return this.normalize({
				'red': ~~((a.red + b.red) / 2),
				'green': ~~((a.green + b.green) / 2),
				'blue': ~~((a.blue + b.blue) / 2)
			});
		},
		
		/**
		 * Brighten or darken a with b
		 * 
		 * @param {Object} a Color a
		 * @param {Object} b Color b
		 * @return RGB color
		 * @type {Object}
		 */
		'overlay': function (a, b) {
			a = Color.parse(a);
			b = Color.parse(b);
			
			if (!a || !b) return null;
			
			var d = {
				'red': b.red - 128,
				'green': b.green - 128,
				'blue': b.blue - 128
			};
			
			return this.add(a, d);
			
			/*
			var brightness = Color.convert.RGBtoHSB(a).brightness;
			
			var d = {
				'red': ((b.red - 128) / 128),
				'green': ((b.green - 128) / 128),
				'blue': ((b.blue - 128) / 128)
			};
			
			return this.normalize({
				'red': ~~((a.red + b.red) / 2),
				'green': ~~((a.green + b.green) / 2),
				'blue': ~~((a.blue + b.blue) / 2)
			});
			*/
		},
		
		/**
		 * Convert color to grayscale
		 * 
		 * @param {Object} color Color
		 * @return Grayscale color
		 * @type {Object}
		 */
		'grayscale': function (color) {
			color = Color.parse(color);
			color = Color.convert.RGBtoHSB(color);
			color.saturation = 0;
			return Color.convert.HSBtoRGB(color);
		},
		
		/**
		 * Invert color
		 * 
		 * @param {Object} color Color
		 * @return Inverted color
		 * @type {Object}
		 */
		'invert': function (color) {
			color = Color.parse(color);
			return {
				'red': 255 - color.red,
				'green': 255 - color.green,
				'blue': 255 - color.blue
			};
		}
	};
	
	
	
	
	/**
	 * Pad string with "0"
	 */
	function strPad (str) {
		if (str.length == 1) return '0' + str;
		return str;
	}

	/**
	 * Color format convertation
	 */
	Color.convert = {
		/**
		 * Convert RGB color into HSB
		 * 
		 * @param {Object} rgb RGB color object with red, green and blue keys
		 * @return Object with hue (0 - 360), saturation (0 - 100) and brightness (0 - 100) keys
		 * @type {Object}
		 */
		RGBtoHSB: function (rgb) {
			var minRGB = null,
				maxRGB = null,
				delta  = 0,
				h      = 0,
				s      = 0,
				b      = 0;
			
			minRGB = Math.min(Math.min(rgb.red, rgb.green), rgb.blue);
			maxRGB = Math.max(Math.max(rgb.red, rgb.green), rgb.blue);
			
			delta = maxRGB - minRGB;
			b = maxRGB;
			
			if (maxRGB) {
				s = 255 * delta / maxRGB;
			
				if (s) {
					if (rgb.red == maxRGB) {
						h = (rgb.green - rgb.blue) / delta;
					} else if (rgb.green == maxRGB) {
						h = 2 + (rgb.blue - rgb.red) / delta;
					} else if (rgb.blue == maxRGB) {
						h = 4 + (rgb.red - rgb.green) / delta;
					}
				} else {
					h = -1;
				}
			} else {
				h = 0;
			}
			
			h *= 60;
			if (h < 0) h += 360;
			
			return {
				"hue": h,
				"saturation": s * 100 / 255,
				"brightness": b * 100 / 255
			};
		},
		
		/**
		 * Convert HSB color into RGB
		 * 
		 * @param {Object} hsb HSBB color object with hue, saturation and brightness keys
		 * @return Object with red, green and blue keys
		 * @type {Object}
		 */
		HSBtoRGB: function (hsb) {
			var r = 0, g = 0, b = 0,
				h = hsb.hue, s = hsb.saturation / 100, v = hsb.brightness / 100,
				
				i = 0,
				f = 0,
				p = 0,
				q = 0,
				t = 0;
			
			if (s == 0){
				r = g = b = v;
			} else {
	
				h /= 60;
				i = Math.floor(h);
				f = h - i;
				p = v * (1 - s);
				q = v * (1 - s * f);
				t = v * (1 - s * (1 - f));
				
				switch(i) {
					case 0:
						r = v; g = t; b = p; break;
					case 1:
						r = q; g = v; b = p; break;
					case 2:
						r = p; g = v; b = t; break;
					case 3:
						r = p; g = q; b = v; break;
					case 4:
						r = t; g = p; b = v; break;
					default:
						r = v; g = p; b = q; break;
				}
			}
			
			return {
				"red": Math.round(r * 255),
				"green": Math.round(g * 255),
				"blue": Math.round(b * 255)
			};
		},
		
		/**
		 * Convert RGB color into HEX
		 * 
		 * @param {Object} rgb RGB color object with red, green and blue keys
		 * @return Color in hex format
		 * @type {String}
		 */
		RGBtoHEX: function (rgb) {
			var str = strPad(rgb.red.toString(16)) +
					  strPad(rgb.green.toString(16)) +
					  strPad(rgb.blue.toString(16));
			
			return "#" + str.toUpperCase();
		},
		
		/**
		 * Convert HEX color into RGB
		 * 
		 * @param {String} hex Color in hex format
		 * @return Object with red, green and blue keys
		 * @type {Object}
		 */
		HEXtoRGB: function (hex) {
			var result = REGEX_HEX_LONG_TO_RGB.exec(hex);
			
			if (result) {
				return {
					red:   parseInt(result[1], 16),
					green: parseInt(result[2], 16),
					blue:  parseInt(result[3], 16)
				};
			} else {
				result = REGEX_HEX_SHORT_TO_RGB.exec(hex);
				return result ? {
					red:   parseInt(result[1] + result[1], 16),
					green: parseInt(result[2] + result[2], 16),
					blue:  parseInt(result[3] + result[3], 16)
				} : null;
			}
		},
		
		/**
		 * Convert HSB color into HEX
		 * 
		 * @param {Object} hsb HSBB color object with hue, saturation and brightness keys
		 * @return Color in hex format
		 * @type {Object}
		 */
		HSBtoHEX: function (hsb) {
			var rgb = Color.convert.HSBtoRGB(hsb);
			return Color.convert.RGBtoHEX(rgb);
		},
		
		/**
		 * Convert HEX color into HSB
		 * 
		 * @param {String} hex Color in hex format
		 * @return Object with hue (0 - 360), saturation (0 - 100) and brightness (0 - 100) keys
		 * @type {Object}
		 */
		HEXtoHSB: function (hex) {
			var rgb = Color.convert.HEXtoRGB(hex);
			return Color.convert.RGBtoHSB(rgb);
		}
	};
	
}, YUI.version);