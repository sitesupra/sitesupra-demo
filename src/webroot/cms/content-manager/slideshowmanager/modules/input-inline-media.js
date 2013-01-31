YUI.add('slideshowmanager.input-inline-media', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Vertical button list for selecting value
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = 'input-inline-media';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	
	Input.ATTRS = {
		/**
		 * Node inside which should be placed image or video
		 */
		"targetNode": {
			value: null
		},
		/**
		 * Render widget into separate form
		 */
		"separateForm": {
			value: false
		}
	};
	
	Input.HTML_PARSER = {
		
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		LABEL_TEMPLATE: '',
		
		widgets: null,
		
		/**
		 * Value type 'video', 'image' or empty stirng if not set yet
		 * @type {String}
		 * @private
		 */
		type: '',
		
		
		/**
		 * On desctruction life cycle clean up
		 * 
		 * @private
		 */
		destructor: function () {
			if (this.widgets) {
				var slideshow = this.get('slideshow'),
					inputs = this.widgets.inputs,
					slides = this.widgets.slides,
					key = null;
				
				if (slideshow) {
					
					for (key in inputs) {
						inputs[key].destroy();
					}
					for (key in slides) {
						slideshow.removeSlide(key);
					}
					
				}
				
				this.widgets = null;
			}
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var form = this.getParentWidget("form"),
				slideshow = this.getSlideshow(),
				input_image = null,
				input_video = null,
				slide_image = slideshow.addSlide(this.get('id') + '_slide_image'),
				slide_video = slideshow.addSlide(this.get('id') + '_slide_video');
			
			input_image = new Supra.Input.InlineImage({
				'id': this.get('id') + '_input_image',
				'label': Supra.Intl.get(['inputs', 'image']),
				'parent': this,
				'value': null,
				'separateSlide': false
			});
			
			input_video = new Supra.Input.Video({
				'id': this.get('id') + '_input_video',
				'label': Supra.Intl.get(['inputs', 'video_label']),
				'description': Supra.Intl.get(['inputs', 'video_description']),
				'parent': this,
				'value': null
			});
			
			input_image.render(slide_image.one('.su-slide-content'));
			input_video.render(slide_video.one('.su-slide-content'));
			
			this.widgets = {
				// Separate slides
				'slide_image': slide_image,
				'slide_video': slide_video,
				
				// Inputs
				'input_image': input_image,
				'input_video': input_video
			};
		},
		
		
		/*
		 * ---------------------------------------- SLIDESHOW ----------------------------------------
		 */
		
		
		/**
		 * Open image slide
		 * 
		 * @private
		 */
		openImageSlide: function () {
			var slideshow = this.getSlideshow(),
				slide_id  = this.get('id') + '_slide_image';
			
			slideshow.set('noAnimations', true);
			slideshow.set('slide', slide_id);
			slideshow.set('noAnimations', false);
		},
		
		/**
		 * Open video slide
		 * 
		 * @private
		 */
		openVideoSlide: function () {
			var slideshow = this.getSlideshow(),
				slide_id  = this.get('id') + '_slide_video';
			
			slideshow.set('noAnimations', true);
			slideshow.set('slide', slide_id);
			slideshow.set('noAnimations', false);
		},
		
		/**
		 * Open slide matching value
		 */
		openSlide: function () {
			if (this.type == 'video') {
				this.openVideoSlide();
			} else {
				this.openImageSlide();
			}
		},
		
		/**
		 * Close slide
		 */
		closeSlide: function () {
			var slideshow = this.getSlideshow(),
				current = null,
				slide_image = this.get('id') + '_slide_image',
				slide_video = this.get('id') + '_slide_video';
			
			if (sldieshow) {
				current = slideshow.get("slide");
				if (current == slide_image || current == slide_video) {
					slideshow.scrollBack();
				}
			}
		},
		
		
		/**
		 * Returns parent widget by class name
		 * 
		 * @param {String} classname Parent widgets class name
		 * @return Widget instance or null if not found
		 * @private
		 */
		getParentWidget: function (classname) {
			var parent = this.get("parent");
			while (parent) {
				if (parent.isInstanceOf(classname)) return parent;
				parent = parent.get("parent");
			}
			return null;
		},
		
		/**
		 * Returns slideshow
		 * 
		 * @return Slideshow
		 * @type {Object}
		 * @private
		 */
		getSlideshow: function () {
			var form = this.getParentWidget("form");
			return form ? form.get("slideshow") : null;
		},
		
		
		/*
		 * ---------------------------------------- VALUE ----------------------------------------
		 */
		
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value New value
		 * @returns {Object} New value
		 * @private
		 */
		_setValue: function (value) {
			if (!this.widgets) return value;
			
			var data = Supra.mix({'type': ''}, value || {}),
				type = data.type;
			
			delete(data.type);
			
			if (type == 'image') {
				this.widgets.input_image.set('value', data);
			} else if (type == 'video') {
				this.widgets.input_video.set('value', data);
			} else {
				// Nothing
			}
			
			this.type = type;
			return value;
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @returns {Object} Value
		 * @private
		 */
		_getValue: function (value) {
			if (!this.widgets) return value;
			
			var type = this.type,
				data = null;
			
			if (type == 'image') {
				data = this.widgets.input_image.get('value');
			} else if (type == 'video') {
				data = this.widgets.input_video.get('value');
			}
			
			if (data) {
				return Supra.mix({'type': type}, data);
			} else {
				return '';
			}
		},
		
		/**
		 * Returns 'video' or 'image'
		 */
		getValueType: function () {
			var value = this.get('value');
		}
		
	});
	
	Supra.Input.InlineMedia = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});