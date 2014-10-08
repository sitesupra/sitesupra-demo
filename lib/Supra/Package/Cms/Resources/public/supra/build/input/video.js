YUI.add('supra.input-video', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var REGEX_YOUTUBE  = /(http(s)?:)?\/\/(www\.)?(youtu\.be|youtube.[a-z]+)(\/embed\/|\/v\/|\/.*\&v=|\/.*\?v=|\/)([a-z0-9_\-]+)/i,
		REGEX_VIMEO    = /(http(s)?:)?\/\/(www\.)?(vimeo.com)(\/)([a-z0-9_\-]+)/i,
		REGEX_FACEBOOK = /(http(s)?:)?\/\/(www\.)?(facebook.com)(\/.*video_id=)([a-z0-9_\-]+)/i;
	
	/**
	 * Video input type
	 * 
	 * Value format if entered an embed code or link:
	 * 		resource: "source",
	 * 		source: "...", // embed code or link url
	 * 
	 * Value format if entered a link:
	 * 		resource: "link",
	 * 		service: "...", // service name "youtube" or "vimeo"
	 * 		id: "...", // youtube or vimeo video ID
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
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		'allowAlign': {
			'value': false
		},
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
		
		/**
		 * Sub widgets, 'source'
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
			
			var source = this.widgets.source = new Supra.Input.Text({
				'label': this.get('label'),
				'value': this.get('value').source,
				'description': this.get('description'),
				'parent': this
			});
			
			source.render(this.get('contentBox'));
			
			// Align box
			var align = this.widgets.align = new Supra.Input.SelectList({
				"style": "minimal",
				"type": "SelectList",
				"label": Supra.Intl.get(["htmleditor", "video_alignment"]),
				"value": "middle",
				"values": [
					{"id": "left", "title": Supra.Intl.get(["htmleditor", "alignment_left"]), "icon": "/cms/lib/supra/img/htmleditor/align-left-button.png"},
					{"id": "middle", "title": Supra.Intl.get(["htmleditor", "alignment_center"]), "icon": "/cms/lib/supra/img/htmleditor/align-center-button.png"},
					{"id": "right", "title": Supra.Intl.get(["htmleditor", "alignment_right"]), "icon": "/cms/lib/supra/img/htmleditor/align-right-button.png"}
				]
			});
			
			align.render(this.get('contentBox'));
			
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
			if (!this.get('allowAlign')) {
				this._onAllowAlignAttrChange({'newVal': false, 'prevVal': true});
			}
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			//Handle attribute changes
			this.on('valueChange', this._afterValueChange, this);
			this.on('allowSizeControlsChange', this._onAllowSizeControlsAttrChange, this);
			this.on('allowAlignChange', this._onAllowAlignAttrChange, this);
			
			//On inputs change update this widget too
			this.widgets.source.after('valueChange', this._onWidgetsChange, this, 'source');
			this.widgets.width.after('valueChange', this._onWidgetsChange, this, 'width');
			this.widgets.height.after('valueChange', this._onWidgetsChange, this, 'height');
			this.widgets.align.after('valueChange', this._onWidgetsChange, this, 'align');
			
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
		 * Convert 'link' video data into 'source'
		 * 
		 * @param {Object} data Video data
		 * @returns {Object} Normalized video data
		 */
		normalizeData: function (data) {
			if (!data || !data.resource) {
				data = {'resource': 'source', 'source': '', 'width': 0, 'height': 0};
			} else if (data.resource == 'link'){
				data = Supra.mix({}, data);
				data.resource = 'source';
				
				switch (data.service) {
					case 'youtube':
						data.source = document.location.protocol + '//' + data.service + '.com/?v=' + data.id;
						break;
					case 'vimeo':
						data.source = document.location.protocol + '//' + data.service + '.com/' + data.id;
						break;
				}
				
				delete(data.id);
				delete(data.service);
			}
			
			if (this.get('allowAlign')) {
				if (data.align != 'left' && data.align != 'right' && data.align != 'middle') {
					data.align = 'middle';
				}
			}
			
			var ratio    = Input.getVideoSizeRatio(data),
				minWidth = this.get('minWidth'),
				maxWidth = this.get('maxWidth');
			
			if (data.width < minWidth) {
				data.width = minWidth;
				data.height = ~~(minWidth / ratio);
			} else if (maxWidth && data.width > maxWidth) {
				data.width = maxWidth;
				data.height = ~~(maxWidth / ratio);
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
				input  = this.widgets ? this.widgets.source : null,
				width  = this.widgets ? this.widgets.width : null,
				height = this.widgets ? this.widgets.height : null,
				align  = this.widgets ? this.widgets.align : null;
			
			data = this.normalizeData(data);
			value = data.source || '';
			
			if (input && input.get('value') !== value) {
				input.set('value', value);
			}
			if (width && width.get('value') !== data.width && !width.get('focused')) {
				width.set('value', data.width);
			}
			if (height && height.get('value') !== data.height && !height.get('focused')) {
				height.set('value', data.height);
			}
			if (align && align.get('value') !== data.align) {
				align.set('value', data.align);
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
			var source = this.widgets ? this.widgets.source : null,
				width  = this.widgets ? this.widgets.width : null,
				height = this.widgets ? this.widgets.height : null,
				align  = this.widgets ? this.widgets.align : null,
				value  = null; // May not be rendered yet
			
			value = {
				'resource': data && data.resource ? data.resource : 'source',
				'source': source ? source.get('value') : data.source || '',
				'width': parseInt(width ? width.get('value') : data.width, 10) || data.width || 0,
				'height': parseInt(height ? height.get('value') : data.height, 10) || data.height || 0
			};
			
			if (width && width.get('focused')) {
				value.width = data.width;
			}
			if (height && height.get('focused')) {
				value.height = data.height;
			}
			
			if (align && this.get('allowAlign')) {
				value.align = this.widgets.align.get('value');
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
		 * Set description on source input not this element
		 * 
		 * @param {String} descr Description text
		 * @return New description
		 * @type {String}
		 * @private
		 */
		_setDescription: function (descr) {
			if (this.widgets && this.widgets.source) {
				this.widgets.source.set('description', descr);
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
			
			if (name !== 'align') {
				this._setValueTrigger = true;
				
				var source = this.widgets.source.get('value'),
					match  = null,
					width  = 0,
					height = 0,
					value  = {},
					
					ratio  = Input.getVideoSizeRatio({
						'resource': 'source',
						'source': source
					});
				
				if (name == 'height') {
					height = parseInt(this.widgets.height.get('value'), 10) || 0;
					width = ~~(height * ratio);
					value.width = width;
					value.height = height;
					this.widgets.width.set('value', width);
				} else {
					width = parseInt(this.widgets.width.get('value'), 10) || 0;
					height = ~~(width / ratio);
					value.width = width;
					value.height = height;
					this.widgets.height.set('value', height);
				}
				
				if (name == 'source') {
					match = source.match(/width="?([\d]+)/);
					value.source = source;
					
					if (match) {
						width = parseInt(match[1], 10) || width;
						height = ~~(width / ratio); // we use original service ratio
						
						value.width = width;
						value.height = height;
						
						this.widgets.width.set('value', width);
						this.widgets.height.set('value', height);
					}
				}
				
				this._setValueTrigger = false;
			}
			
			this.set('value', Supra.mix(this.get('value'), value));
		},
		
		_onWidthWidgetChange: function (width) {
			var ratio = Input.getVideoSizeRatio({
					'resource': 'source',
					'source': this.widgets.source.get('value')
				}),
				height = ~~(width / ratio);
			
			this.set('value', Supra.mix(this.get('value'), {'width': width, 'height': height}));
		},
		
		_onHeightWidgetChange: function (height) {
			var ratio = Input.getVideoSizeRatio({
					'resource': 'source',
					'source': this.widgets.source.get('value')
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
		},
		
		/**
		 * Align property attribute setter
		 * Show or hide align controls
		 * 
		 * @param {Object} e Event facade object
		 * @private 
		 */
		_onAllowAlignAttrChange: function (e) {
			if (e.newVal != e.prevVal) {
				if (this.widgets && this.widgets.align) {
					if (e.newVal) {
						this.widgets.align.show();
					} else {
						this.widgets.align.hide();
					}
				}
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
		var service = null,
			match = null,
			
			// http://youtu.be/...
			// http://www.youtube.com/v/...
			// http://www.youtube.com/...?v=...
			regex_youtube = REGEX_YOUTUBE,
			// http://vimeo.com/...
			regex_vimeo = REGEX_VIMEO,
			
			ratio_youtube = 16/9,
			ratio_vimeo   = 7/3;
		
		if (data) {
			if (data.resource == "link") {
				service = data.service;
			} else if (data.resource == "source") {
				if (match = data.source.match(regex_youtube)) {
					service = 'youtube';
				} else if (match = data.source.match(regex_vimeo)) {
					service = 'vimeo';
				}
			}
		}
		
		if (service == 'youtube') {
			return ratio_youtube;
		} else if (service == 'vimeo') {
			return ratio_vimeo;
		} else {
			// Default
			return ratio_youtube;
		}
	};
	
	/**
	 * Extract image url from video data
	 * 
	 * @param {Object} data Video data
	 * @returns {String} Image url
	 */
	Input.getVideoPreviewUrl = function (data) {
		var service = null,
			video_id = null,
			match = null,
			
			// http://youtu.be/...
			// http://www.youtube.com/v/...
			// http://www.youtube.com/...?v=...
			regex_youtube = REGEX_YOUTUBE,
			// http://vimeo.com/...
			regex_vimeo = REGEX_VIMEO,
			
			deferred = new Supra.Deferred();
		
		if (data) {
			if (data.resource == "link") {
				service = data.service;
				video_id = data.id;
			} else if (data.resource == "source") {
				if (match = data.source.match(regex_youtube)) {
					service = 'youtube';
					video_id = match[6];
				} else if (match = data.source.match(regex_vimeo)) {
					service = 'vimeo';
					video_id = match[6];
				}
			}
		}
		
		if (service == 'youtube') {
			deferred.resolveWith(this, [document.location.protocol + '//img.youtube.com/vi/' + video_id + '/0.jpg']);
		} else if (service == 'vimeo') {
			//
			var url = document.location.protocol + '//vimeo.com/api/v2/video/' + video_id + '.json';
			Supra.io(url, {
				'suppress_errors': true, // don't display errors
				'context': this,
				'on': {
					'complete': function (data, success) {
						if (data && data[0]) {
							deferred.resolveWith(this, [data[0].thumbnail_large]);
						} else {
							deferred.rejectWith(this, []);
						}
					}
				}
			});
		} else {
			deferred.rejectWith(this, []);
		}
		
		return deferred.promise();
	};
	
	
	
	Supra.Input.Video = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});