YUI.add('supra.input-video', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var VIDEO_SIZE_RATIOS = [
		// Youtube
		// http://youtu.be/...
		// http://www.youtube.com/v/...
		// http://www.youtube.com/...?v=...
		{
			'regex': /(http(s)?:)?\/\/(www\.)?(youtu\.be|youtube.[a-z]+)/i,
			'ratio': 16 / 9
		},
		// Vimeo
		// http://vimeo.com/...
		{
			'regex': /(http(s)?:)?\/\/(www\.)?(vimeo.com)(\/)/i,
			'ratio': 7 / 3
		},
		// Facebook
		{
			'regex': /(http(s)?:)?\/\/(www\.)?(facebook.com)/i,
			'ratio': 16 / 9
		}
	];
	
	/**
	 * Video input type
	 * 
	 * Value format if entered an embed code or link:
	 * 		url: "...", // link url
	 *      width: ...,
	 *      height: ...
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-video';
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.ATTRS = {
		'allowSizeControls': {
			'value': true
		},
		'minWidth': {
			value: 160
		},
		'maxWidth': {
			value: 0
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		LABEL_TEMPLATE: '', // No label on this widget
		DESCRIPTION_TEMPLATE: '', // No description on this widget
		
		/**
		 * Sub widgets, 'url'
		 * @type {Object}
		 * @private
		 */
		widgets: null,
		
		/**
		 * Last known value
		 * @type {Object}
		 * @private
		 */
		_last_value: null,
		
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			this.widgets = {};
			
			var url = this.widgets.url = new Supra.Input.String({
				'label': this.get('label'),
				'value': this.get('value').url,
				'description': this.get('description'),
				'parent': this
			});
			
			url.render(this.get('contentBox'));
			
			// Size box
			var sizeBox = this.widgets.sizeBox = Y.Node.create('<div class="clearfix su-sizebox"></div>');
			sizeBox.append('<p class="label">' + Supra.Intl.get(["inputs", "resize_video"]) + '</p>');
			
			// Width
			var width = this.widgets.width = new Supra.Input.String({
				'type': 'String',
				'style': 'size',
				'valueMask': /^[0-9]*$/,
				'label': Supra.Intl.get(['inputs', 'resize_width']),
				'value': 0
			});
			
			width.render(sizeBox);
			
			// Size button
			var btn = new Supra.Button({"label": "", "style": "small-gray"});
				btn.render(sizeBox);
				btn.set("disabled", true);
				btn.addClass("su-button-ratio");
				btn.addClass("su-button-locked");
			
			// Height
			var height = this.widgets.height = new Supra.Input.String({
				'type': 'String',
				'style': 'size',
				'valueMask': /^[0-9]*$/,
				'label': Supra.Intl.get(['inputs', 'resize_height']),
				'value': 0
			});
			
			height.render(sizeBox);
			this.get('contentBox').append(sizeBox);
			
			// Set-up attribute values
			if (!this.get('allowSizeControls')) {
				this._onAllowSizeControlsAttrChange({'newVal': false, 'prevVal': true});
			}
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			//Handle attribute changes
			this.on('valueChange', this._afterValueChange, this);
			this.on('allowSizeControlsChange', this._onAllowSizeControlsAttrChange, this);
			
			//On inputs change update this widget too
			this.widgets.url.after('valueChange', this._onWidgetsChange, this, 'url');
			this.widgets.width.after('valueChange', this._onWidgetsChange, this, 'width');
			this.widgets.height.after('valueChange', this._onWidgetsChange, this, 'height');
			
			this.widgets.width.on('input', Supra.throttle(function (e) {
				if (this.widgets.width.get('focused')) {
					this._onWidthWidgetChange(e.value);
				}
			}, 250, this, true));
			
			this.widgets.height.on('input', Supra.throttle(function (e) {
				if (this.widgets.height.get('focused')) {
					this._onHeightWidgetChange(e.value);
				}
			}, 250, this, true));
			
			this.widgets.height.on('blur', function () {
				this._setValueTrigger = true;
				this.widgets.height.set('value', this._last_value.height); 
				this._setValueTrigger = false;
			}, this);
			
			this.widgets.width.on('blur', function () {
				this._setValueTrigger = true;
				this.widgets.width.set('value', this._last_value.width); 
				this._setValueTrigger = false;
			}, this);
		},
		
		/**
		 * Normalize video data
		 * 
		 * @param {Object} data Video data
		 * @returns {Object} Normalized video data
		 */
		normalizeData: function (data) {
			if (!data) {
				data = {'url': '', 'width': 0, 'height': 0};
			}
			
			var ratio    = Input.getVideoSizeRatio(data),
				minWidth = this.get('minWidth'),
				maxWidth = this.get('maxWidth');
			
			if (data.width < minWidth) {
				data.width = minWidth;
				if (ratio) data.height = ~~(minWidth / ratio);
			} else if (maxWidth && data.width > maxWidth) {
				data.width = maxWidth;
				if (ratio) data.height = ~~(maxWidth / ratio);
			}
			
			return data;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} data New input value
		 * @returns {Object} New input value
		 * @private
		 */
		_setValue: function (data) {
			this._setValueTrigger = true;
			
			var value  = '',
				// May not be rendered yet
				input  = this.widgets ? this.widgets.url : null,
				width  = this.widgets ? this.widgets.width : null,
				height = this.widgets ? this.widgets.height : null;
			
			data = this.normalizeData(data);
			value = data.url || '';
			
			if (input && input.get('value') !== value) {
				input.set('value', value);
			}
			if (width && width.get('value') !== data.width && !width.get('focused')) {
				width.set('value', data.width);
			}
			if (height && height.get('value') !== data.height && !height.get('focused')) {
				height.set('value', data.height);
			}
			
			this._last_value = data;
			this._setValueTrigger = false;
			
			return data;
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @param {Object} data Old value
		 * @returns {Object} New value
		 * @private
		 */
		_getValue: function (data) {
			var url = this.widgets ? this.widgets.url : null,
				width  = this.widgets ? this.widgets.width : null,
				height = this.widgets ? this.widgets.height : null,
				value  = null; // May not be rendered yet
			
			value = {
				'url': url ? url.get('value') : '',
				'width': parseInt(width ? width.get('value') : data.width, 10) || data.width || 0,
				'height': parseInt(height ? height.get('value') : data.height, 10) || data.height || 0
			};
			
			if (width && width.get('focused')) {
				value.width = data.width;
			}
			if (height && height.get('focused')) {
				value.height = data.height;
			}
			
			return value;
		},
		
		/**
		 * Trigger change event when value changes
		 * 
		 * @param {Object} evt
		 */
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		},
		
		/**
		 * Description attribute setter
		 * Set description on url input not this element
		 * 
		 * @param {String} descr Description text
		 * @return New description
		 * @type {String}
		 * @private
		 */
		_setDescription: function (descr) {
			if (this.widgets && this.widgets.url) {
				this.widgets.url.set('description', descr);
			}
			return descr;
		},
		
		/**
		 * When widgets value changes update value for self
		 * 
		 * @param {Object} evt Event facade object
		 * @private
		 */
		_onWidgetsChange: function (evt, name) {
			if (this._setValueTrigger) return;
			this._setValueTrigger = true;
			
			var url = this.widgets.url.get('value'),
				match  = null,
				width  = 0,
				height = 0,
				value  = {},
				
				ratio  = Input.getVideoSizeRatio({
					'url': url
				});
			
			if (name == 'height') {
				height = parseInt(this.widgets.height.get('value'), 10) || 0;
				width = ~~(height * ratio);
				value.width = width;
				value.height = height;
				this.widgets.width.set('value', width);
			} else {
				width = parseInt(this.widgets.width.get('value'), 10) || 0;
				height = ratio ? ~~(width / ratio) : 0;
				value.width = width;
				value.height = height;
				this.widgets.height.set('value', height);
			}
			
			this._setValueTrigger = false;
			this.set('value', Supra.mix(this.get('value'), value));
		},
		
		_onWidthWidgetChange: function (width) {
			var ratio = Input.getVideoSizeRatio({
					'url': this.widgets.url.get('value')
				}),
				height = ~~(width / ratio);
			
			this.set('value', Supra.mix(this.get('value'), {'width': width, 'height': height}));
		},
		
		_onHeightWidgetChange: function (height) {
			var ratio = Input.getVideoSizeRatio({
					'url': this.widgets.url.get('value')
				}),
				width = ~~(height * ratio);
			
			this.set('value', Supra.mix(this.get('value'), {'width': width, 'height': height}));
		},
		
		
		/* ------------------------------ ATTRIBUTE CHANGE HANDLERS -------------------------------- */
		
		
		/**
		 * Handle allowSizeControls attribute change
		 * When enabled width and height controls will be visible,
		 * otherwise they will be hidden
		 * 
		 * @param {Object} e Event facade object
		 * @private 
		 */
		_onAllowSizeControlsAttrChange: function (e) {
			if (e.newVal != e.prevVal) {
				this.widgets.sizeBox.toggleClass('hidden', !e.newVal);
			}
		}
		
	});
	
	/**
	 * Returns video width / height ratio
	 * 
	 * @param {Object} data Video data
	 * @returns {Number} Size ratio
	 */
	Input.getVideoSizeRatio = function (data) {
		var ratios = VIDEO_SIZE_RATIOS,
			i = 0,
			ii = ratios.length;
		
		if (data) {
			for (; i < ii; i++) {
				if (data.url.match(ratios[i].regex)) {
					return ratios[i].ratio;
				}
			}
		}
		
		if (data.width && data.height) {
			return data.width / data.height;
		} else {
			// Default
			return 16 / 9;
		}
	};
	
	/**
	 * Extract image url from video data
	 * @TODO Not supported for now
	 * 
	 * @param {Object} data Video data
	 * @returns {String} Image url
	 */
	Input.getVideoPreviewUrl = function (data) {
		return Supra.Deferred().reject().promise();
	};
	
	
	
	Supra.Input.Video = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
