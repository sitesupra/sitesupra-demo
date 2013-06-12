YUI.add('slideshowmanager.plugin-mask', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Folder rename plugin
	 * Saves item properties when they change
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = 'plugin-mask';
	Plugin.NS = 'mask';
	
	Plugin.ATTRS = {
		// Theme input name
		'themeInputName': {
			value: ''
		},
		
		// Theme input value to which color should be added to
		'themeSlideValue': {
			value: 'mask'
		},
		
		// Mask image input name
		'maskInputName': {
			value: ''
		},
		
		// Mask color input name
		'colorInputName': {
			value: ''
		}
	};
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * List of widgets
		 * @private
		 * @type {Object}
		 */
		widgets: null,
		
		/**
		 * Initialize plugin
		 * 
		 * @constructor
		 */
		initializer: function () {
			var form  = this.get('host'),
				theme = form.getInput(this.get('themeInputName')),
				color = form.getInput(this.get('colorInputName')),
				image = form.getInput(this.get('maskInputName')),
				slide = null;
			
			if (!theme || !color) return;
			
			slide = theme.widgets.slides[this.get('themeSlideValue')];
			if (!slide) return;
			
			slide.one('.su-slide-content').append(color.get('boundingBox'));
			
			this.widgets = {
				'theme': theme,
				'color': color,
				'image': image
			};
			
			color.on('valueChange', this.onColorChange, this);
			theme.on('valueChange', this.onMaskChange, this);
		},
		
		/**
		 * On color property change update mask image
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		onColorChange: function (event) {
			var theme = this.widgets.theme.get('value'),
				color = event.newVal;
			
			this.updateMaskImage(theme, color);
		},
		
		/**
		 * On mask property change update mask image
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		onMaskChange: function (event) {
			var theme = event.newVal,
				color = this.widgets.color.get('value');
		
			this.updateMaskImage(theme, color);
		},
		
		/**
		 * Returns mask image URL
		 * 
		 * @param {String} theme Theme id
		 * @param {String} color Mask overlay color
		 * @returns {String} Image URL
		 */
		getMaskImageURL: function (theme, color) {
			if (theme && color && this.themeValueIsMask(theme)) {
				var url = Supra.Manager.SlideshowManager.MASK_IMAGE_REQUEST_URL;
				return url.replace(/\{\{\s*theme\s*\}\}/, encodeURIComponent(theme))
						  .replace(/\{\{\s*color\s*\}\}/, encodeURIComponent(color));
			} else {
				return '';
			}
		},
		
		/**
		 * Update mask image
		 * 
		 * @private
		 */
		updateMaskImage: function (theme, color) {
			var image = this.widgets.image;
			
			if (image) {
				image.set('value', this.getMaskImageURL(theme, color));
			}
		},
		
		/**
		 * 
		 */
		themeValueIsMask: function (value) {
			var input  = this.widgets.theme,
				
				values = input.get('values'),
				i      = 0,
				ii     = values.length,
				
				sub    = null,
				k      = 0,
				kk     = 0;
			
			for (; i<ii; i++) {
				sub = values[i].values;
				if (sub) {
					for (k=0, kk=sub.length; k<kk; k++) {
						if (sub[k].id == value) return true;
					}
				}
			}
			
			return false;
		},
		
		/**
		 * End of life cycle
		 */
		destructor: function () {
			this.widgets = null;
		}
		
	});
	
	Supra.SlideshowManagerMaskPlugin = Plugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'plugin']});