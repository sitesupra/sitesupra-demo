YUI.add('supra.input-video', function (Y) {
	//Invoke strict mode
	"use strict";
	
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
	Input.ATTRS = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		LABEL_TEMPLATE: '', // No label on this widget
		
		/**
		 * Sub widgets, 'source'
		 * @type {Object}
		 * @private
		 */
		widgets: null,
		
		
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
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			//Handle value attribute change
			this.on('valueChange', this._afterValueChange, this);
			
			//On source change update this widget too
			this.widgets.source.on('change', this._onWidgetsChange, this);
		},
		
		/**
		 * Convert 'link' video data into 'source'
		 * 
		 * @param {Object} data Video data
		 * @returns {Object} Normalized video data
		 */
		normalizeData: function (data) {
			if (!data || !data.resource) {
				data = {'resource': 'source', 'source': ''};
			} else if (data.resource == 'link'){
				data = Supra.mix({}, data);
				data.resource = 'source';
				
				switch (data.service) {
					case 'youtube':
						data.source = 'http://' + data.service + '.com/?v=' + data.id;
						break;
					case 'vimeo':
						data.source = 'http://' + data.service + '.com/' + data.id;
						break;
				}
				
				delete(data.id);
				delete(data.service);
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
			var value = '',
				input = this.widgets ? this.widgets.source : null; // May not be rendered yet
			
			data = this.normalizeData(data);
			value = data.source || '';
			
			if (input && input.get('value') !== value) {
				input.set('value', value);
			}
			
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
			var input = this.widgets ? this.widgets.source : null; // May not be rendered yet
			
			return {
				'resource': data && data.resource ? data.resource : 'source',
				'source': input ? input.get('value') : data.source || ''
			};
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
		_onWidgetsChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.set('value', this.get('value'));
			}
		}
		
	});
	
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
			regex_youtube = /http(s)?:\/\/(www\.)?(youtu\.be|youtube.[a-z]+)(\/embed\/|\/v\/|\/.*\&v=|\/.*\?v=|\/)([a-z0-9_\-]+)/i,
			// http://vimeo.com/...
			regex_vimeo = /http(s)?:\/\/(www\.)?(vimeo.com)(\/)([a-z0-9_\-]+)/i,
			
			deferred = new Supra.Deferred();
		
		if (data) {
			if (data.resource == "link") {
				service = data.service;
				video_id = data.id;
			} else if (data.resource == "source") {
				if (match = data.source.match(regex_youtube)) {
					service = 'youtube';
					video_id = match[5];
				} else if (match = data.source.match(regex_vimeo)) {
					service = 'vimeo';
					video_id = match[5];
				}
			}
		}
		
		if (service == 'youtube') {
			deferred.resolveWith(this, [document.location.protocol + '//img.youtube.com/vi/' + video_id + '/0.jpg']);
		} else if (service == 'vimeo') {
			//
			var url = 'http://vimeo.com/api/v2/video/' + video_id + '.json';
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