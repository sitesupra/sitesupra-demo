YUI.add('supra.input-fonts', function (Y) {
	//Invoke strict mode
	"use strict";
	
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
	
	Input.ATTRS = {
		/**
		 * Style:
		 * "" or "no-labels", "mid"
		 */
		'style': {
			value: 'no-labels',
			setter: '_setStyle'
		}
	};
	
	Y.extend(Input, Supra.Input.SelectVisual, {
		
		/**
		 * Google fonts object, used to load fonts into current document for live preview of fonts
		 * Supra.GoogleFonts instance
		 * @type {Object}
		 * @private
		 */
		googleFonts: null,
		
		/**
		 * Decorate button
		 * 
		 * @param {Object} definition Option definition, configuration
		 * @param {Object} button Button
		 * @private
		 */
		decorateButton: function (definition, button) {
			var font_style = this.getButtonFontStyle(definition);
			
			button._getLabelTemplate = function () {
				return '<div class="su-button-bg"><div style="' + this._getButtonBackgroundStyle(this.get('icon')) + '"></div><p style="' + font_style + '"></p></div></div>';
			};
			button.after('render', function () {
				button.removeClass('su-button-group');
			});
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
		},
		
		
		/* ------------------------------ FONTS ------------------------------ */
		
		
		/**
		 * Load all fonts from all values
		 */
		loadFonts: function (values) {
			var google_fonts = this.googleFonts,
				fonts = [],
				i = 0,
				ii = values.length;
			
			for (; i<ii; i++) {
				if (values[i].apis) {
					fonts.push(values[i]);
				}
			}
			
			if (google_fonts) {
				google_fonts.set('fonts', fonts);
			} else {
				google_fonts = this.googleFonts = new Supra.GoogleFonts({
					'fonts': fonts,
					'doc': document
				});
			}
		},
		
		
		/* ------------------------------ ATTRIBUTES ------------------------------ */
		
		
		/**
		 * Values attribute setter
		 * 
		 * @private
		 */
		_setValues: function (values) {
			values = Input.superclass._setValues.apply(this, arguments);
			
			this.loadFonts(values);
			
			return values;
		}
		
		
	});
	
	Supra.Input.Fonts = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);
