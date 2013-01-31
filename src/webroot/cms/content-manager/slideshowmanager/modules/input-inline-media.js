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
		// Render widget into separate form
		'separateForm': {
			value: false
		},
		
		// Node inside which should be placed image or video
		'targetNode': {
			value: null,
			setter: '_setTargetNode'
		},
		
		// Button label to add video
		'labelAddVideo': {
			value: ''
		},
		
		// Button label to add image
		'labelAddImage': {
			value: ''
		},
		
		//Blank image URI or data URI
		'blankImageUrl': {
			value: "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
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
				slide_video = slideshow.addSlide(this.get('id') + '_slide_video'),
				delete_image = null,
				delete_video = null;
			
			// Inputs
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
			
			// Buttons
			delete_image = new Supra.Button({
				'style': 'small-red',
				'label': Supra.Intl.get(['inputs', 'media', 'delete_image'])
			});
			
			delete_video = new Supra.Button({
				'style': 'small-red',
				'label': Supra.Intl.get(['inputs', 'media', 'delete_video'])
			});
			
			delete_image.on('click', this.removeMedia, this);
			delete_video.on('click', this.removeMedia, this);
			
			delete_image.render(slide_image.one('.su-slide-content'));
			delete_video.render(slide_video.one('.su-slide-content'));
			
			this.widgets = {
				// Separate slides
				'slide_image': slide_image,
				'slide_video': slide_video,
				
				// Inputs
				'input_image': input_image,
				'input_video': input_video,
				
				// Buttons
				'delete_image': delete_image,
				'delete_video': delete_video
			};
			
			this.renderContent(this.get('targetNode'), this.get('value'));
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
			if (this.type === 'video') {
				this.openVideoSlide();
			} else if (this.type === 'image') {
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
		
		
		/* ------------------------------ SIDEBAR -------------------------------- */
		
		
		/**
		 * Show settings form
		 */
		showSettingsSidebar: function () {
			var form = this.getParentWidget("form"), 
				properties = this.getParentWidget("page-content-properties"),
				group = null;
			
			if (form && properties) {
				//We can get input group from input definition
				group = (form.getConfig(this.get("id")) || {}).group || "";
				
				properties.showPropertiesForm(group);
			} else {
				//Not part of block properties, search for Action
				var parent = this.getParentWidget("ActionBase");
				if (parent && parent.plugins.getPlugin("PluginSidebar")) {
					//Has sidebar plugin, so this action is in sidebar
					if (parent.get("frozen")) {
						//In frozen state show/execute are not called, so we have to
						//force it to show content
						parent.showFrozen();
						parent.set("frozen", false);
					} else {
						parent.execute(form);
					}
				}
			}
			
			this.openSlide();
		},
		
		/**
		 * Hide settings form
		 */
		hideSettingsSidebar: function () {
			var form = this.getParentWidget("form"), 
				properties = this.getParentWidget("page-content-properties"),
				group = null;
			
			if (form && properties) {
				properties.hidePropertiesForm();
			} else {
				//Not part of block properties, search for Action
				var parent = this.getParentWidget("ActionBase");
				if (parent && parent.plugins.getPlugin("PluginSidebar")) {
					//Has sidebar plugin, so this action is in sidebar
					parent.hide();
				}
			}
		},
		
		
		/*
		 * ---------------------------------------- EDITING ----------------------------------------
		 */
		
		
		_setTargetNode: function (node) {
			if (this.get('rendered')) {
				this.renderContent(node, this.get('value'));
			}
			return node;
		},
		
		/**
		 * Start editing input
		 */
		startEditing: function () {
			if (!this.get('disabled')) {
				this.focus();
				
				if (this.type === 'video' || this.type === 'image') {
					this.showSettingsSidebar();
					
					if (this.type === 'video') {
						this.widgets.input_video.startEditing();
					} else {
						this.widgets.input_image.startEditing();
					}
				}
			}
		},
		
		/**
		 * Stop editing input
		 */
		stopEditing: function () {
			this.blur();
			
			if (this.type === 'video') {
				this.widgets.input_video.stopEditing();
			} else if (this.type === 'image') {
				this.widgets.input_image.stopEditing();
			}
		},
		
		insertImage: function () {
			this.set('value', {
				'type': 'image'
			});
			
			this.startEditing();
		},
		
		insertVideo: function () {
			this.set('value', {
				'type': 'video',
				'resource': 'source',
				'source': ''
			});
			
			this.startEditing();
		},
		
		/**
		 * Remove image or video
		 * 
		 * @private
		 */
		removeMedia: function () {
			this.set('value', {'type': ''});
			this.hideSettingsSidebar();
		},
		
		/**
		 * Render value inside content
		 * 
		 * @param {Object} node Node in which to render
		 * @param {Object} data Media data
		 * @private
		 */
		renderContent: function (node, data) {
			var node = this.get('targetNode'),
				type = data.type || this.type;
			
			if (!node) return;
			
			if (data && type == 'image') {
				var html = '<span class="supra-image" unselectable="on" contenteditable="false" style="width: auto; height: auto;"><img class="as-layer" src="' + this.get('blankImageUrl') + '" alt="" /></span>';
				
				node.set('innerHTML', html);
				this.widgets.input_image.set('targetNode', node.one('img'));
			} else {
				this.widgets.input_image.stopEditing();
				this.widgets.input_image.set('targetNode', null);
			}
			
			if (data && type == 'video') {
				var html = 'VIDEO PREVIEW HERE';
				
				node.set('innerHTML', html);
				//@TODO
			}
			
			if (!data || (type !== 'image' && type !== 'video')) {
				// Empty with buttons
				var html = '<div align="center" class="yui3-box-reset"><a class="button" data-supra-action="addImage">' + (this.get('labelAddImage') || Supra.Intl.get(['inputs', 'media', 'add_image'])) + '</a>' +
						   '<a class="button" data-supra-action="addVideo">' + (this.get('labelAddVideo') || Supra.Intl.get(['inputs', 'media', 'add_video'])) + '</a></div>';
				
				node.set('innerHTML', html);
				node.one('a[data-supra-action="addImage"]').on('click', this.insertImage, this);
				node.one('a[data-supra-action="addVideo"]').on('click', this.insertVideo, this);
			}
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
			
			if (type == 'image' && Y.Object.size(data)) {
				this.widgets.input_image.set('value', data);
			} else {
				this.widgets.input_image.set('value', null);
			}
			
			if (type == 'video') {
				this.widgets.input_video.set('value', data);
			} else {
				this.widgets.input_video.set('value', null);
			}
			
			this.type = type;
			
			this.renderContent(this.get('targetNode'), data);
			
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