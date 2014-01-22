YUI.add('supra.input-media-inline', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Vertical button list for selecting value
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-media-inline';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	
	Input.ATTRS = {
		// Render widget into separate form
		// needed because image can be edited inline and in main form
		// InlineImage input may not be welcome
		'separateForm': {
			value: true
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
		},
		
		// Allow file upload using drag and drop
		'allowDropUpload': {
			value: true
		},
		
		// Image upload folder id
		'uploadFolderId': {
			value: 0
		},
		
		// Editing state
		'editing': {
			value: false
		},
		
		// Stop editing when clicked outside image
		"autoClose": {
			value: true
		},
		
		// Allow inserting video
		"allowVideo": {
			value: true
		},
		
		// Allow inserting image
		'allowImage': {
			value: true
		},
		
		// Max crop width is fixed to container width and container can't increase 
		'fixedMaxCropWidth': {
			value: true
		},
		// Max crop height is fixed to container height and container can't increase 
		'fixedMaxCropHeight': {
			value: false
		},
		
		// Resize image crop to smaller size on zoom if needed
		'allowZoomResize': {
			value: false
		},
		// Change zoom on crop
		'allowCropZooming': {
			value: false
		}
	};
	
	Input.HTML_PARSER = {
		
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		LABEL_TEMPLATE: '',
		DESCRIPTION_TEMPLATE: '',
		
		widgets: null,
		
		/**
		 * Value type 'video', 'image' or empty stirng if not set yet
		 * @type {String}
		 * @private
		 */
		type: '',
		
		/**
		 * Value is being updated by input, don't change UI
		 * @type {Boolean}
		 * @private
		 */
		silentValueUpdate: false,
		
		/**
		 * Video or image input value is being updated by this input
		 * @type {Boolean}
		 * @private
		 */
		silentChildValueUpdate: false,
		
		
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
					uploader = this.widgets.uploader,
					key = null;
				
				if (slideshow) {
					
					for (key in inputs) {
						inputs[key].destroy();
					}
					for (key in slides) {
						slideshow.removeSlide(key);
					}
					
				}
				
				if (uploader) {
					uploader.destroy();
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
				slide_image = slideshow.addSlide({'id': this.get('id') + '_slide_image', 'title': this.get('label') || ''}),
				slide_video = slideshow.addSlide({'id': this.get('id') + '_slide_video', 'title': this.get('label') || ''}),
				delete_image = null,
				delete_video = null,
				uploader = null,
				target = null;
			
			// Drag and drop upload
			if (this.get('allowDropUpload')) {
				target = this.get('targetNode');
				uploader = new Supra.Uploader({
					'clickTarget': null,
					'dropTarget': this._getDropTargetNode(target),
					
					'allowBrowse': false,
					'allowMultiple': false,
					'accept': 'image/*',
					
					'requestUri': Supra.Manager.getAction('MediaLibrary').getDataPath('upload'),
					'uploadFolderId': this.get('uploadFolderId')

				});
			}
			
			// Since we are using InlineImage and Video we don't need to show this input
			this.get('boundingBox').addClass('hidden');
			
			// Inputs
			input_image = new Supra.Input.InlineImage({
				'id': this.get('id') + '_input_image',
				'label': Supra.Intl.get(['inputs', 'image']),
				'parent': this,
				'value': null,
				'separateSlide': false,
				'allowRemoveImage': false,
				'autoClose': this.get('autoClose'),
				'fixedMaxCropWidth': this.get('fixedMaxCropWidth'),
				'fixedMaxCropHeight': this.get('fixedMaxCropHeight'),
				'allowZoomResize': this.get('allowZoomResize'),
				'allowCropZooming': this.get('allowCropZooming')
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
			
			delete_image.render(slide_image.one('.su-slide-content'));
			delete_video.render(slide_video.one('.su-slide-content'));
			
			delete_image.addClass("su-button-fill");
			delete_video.addClass("su-button-fill");
			
			this.widgets = {
				// Separate slides
				'slide_image': slide_image,
				'slide_video': slide_video,
				
				// Inputs
				'input_image': input_image,
				'input_video': input_video,
				
				// Buttons
				'delete_image': delete_image,
				'delete_video': delete_video,
				
				// File uploader
				'uploader': uploader
			};
			
			this.renderContent(this.get('targetNode'), this.get('value'));
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var input_image  = this.widgets.input_image,
				input_video  = this.widgets.input_video,
				
				delete_image = this.widgets.delete_image,
				delete_video = this.widgets.delete_video,
				
				uploader     = this.widgets.uploader;
			
			// Video input events
			input_video.on('focus', this.focus, this);
			//input_video.on('blur', this.blur, this); // any sub-input blur causes this, we can't use it!
			
			input_video.on('change', function () {
				this.updateVideoPreviewImage();
				this._fireValueChange();
			}, this);
			
			// Image input events
			input_image.on('focus', this.focus, this);
			input_image.on('blur', this.blur, this);
			
			input_image.on('change', function () {
				this._fireValueChange();
			}, this);
			input_image.on('valueChange', function () {
				this._fireValueChange();
			}, this);
			
			// Button events
			delete_image.on('click', this.removeMedia, this);
			delete_video.on('click', this.removeMedia, this);
			
			// Change event
			this.on('valueChange', this._afterValueChange, this);
			
			// Uploader events
			if (uploader) {
				uploader.on('file:upload',   this._onFileUploadStart, this);
				uploader.on('file:complete', this._onFileUploadEnd, this);
				uploader.on('file:error',    this._onFileUploadError, this);
			}
		},
		
		
		/*
		 * ---------------------------------------- FILE UPLOAD ----------------------------------------
		 */
		
		
		/**
		 * Handle file upload start
		 * 
		 * @private
		 */
		_onFileUploadStart: function (e) {
			// data.title, data.filename, data.id
			var data = e.details[0];
		},
		
		/**
		 * Handle file upload end
		 * 
		 * @private
		 */
		_onFileUploadEnd: function (e) {
			var data = e.details[0]
			this.insertImageData(data);
		},
		
		/**
		 * Handle file upload error
		 * 
		 * @private
		 */
		_onFileUploadError: function (e) {
			// Error
		},
		
		/**
		 * Returns drag and drop target node
		 * 
		 * @private
		 */
		_getDropTargetNode: function (node) {
			return node ? node.closest('.supra-slideshowmanager-wrapper') || node : null;
		},
		
		
		/*
		 * ---------------------------------------- SLIDESHOW ----------------------------------------
		 */
		
		
		/**
		 * Open specific slide
		 * 
		 * @param {String} slide_id Slide id
		 * @private
		 */
		openSpecificSlide: function (slide_id) {
			var slideshow = this.getSlideshow();
			
			slideshow.set('noAnimations', true);
			slideshow.set('slide', slide_id);
			slideshow.set('noAnimations', false);
			
			if (this.get('separateSlide')) {
				Supra.Manager.PageContentSettings.get('backButton').hide();
			} else {
				var evt = slideshow.on('slideChange', function (e) {
					if (e.newVal != e.prevVal && e.prevVal == slide_id) {
						evt.detach();
						
						if (this.get('focused')) {
							this.set('editing', false);
							this.stopEditing();
						}
					}
				}, this);
			}
		},
		
		/**
		 * Open slide matching value
		 */
		openSlide: function () {
			if (this.type === 'video') {
				this.openSpecificSlide(this.get('id') + '_slide_video');
			} else if (this.type === 'image') {
				this.openSpecificSlide(this.get('id') + '_slide_image');
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
			
			if (slideshow) {
				current = slideshow.get("slide");
				if (current == slide_image || current == slide_video) {
					slideshow.scrollBack();
				}
			}
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
				this.closeSlide();
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
				
				var uploader = this.widgets.uploader;
				if (uploader) {
					uploader.set('dropTarget', this._getDropTargetNode(node));
					uploader.set('disabled', this.get('disabled'));
				}
				
				var input = this.widgets.input_video;
				if (input) {
					if (this.get('fixedMaxCropWidth') && node) {
						input.set('maxWidth', node.get('offsetWidth'));
					} else {
						input.set('maxWidth', 0);
					}
					if (this.get('fixedMaxCropHeight') && node) {
						input.set('maxHeight', node.get('offsetHeight'));
					} else {
						input.set('maxHeight', 0);
					}
				}
			}
			return node;
		},
		
		/**
		 * Start editing input
		 */
		startEditing: function () {
			var state = false;
			
			if (!this.get('disabled') && !this.get('editing')) {
				if (this.type === 'video' || this.type === 'image') {
					this.set('editing', true);
					this.focus();
					
					this.showSettingsSidebar();
					
					if (this.type === 'video') {
						if (this.get('fixedMaxCropWidth')) {
							this.widgets.input_video.set('maxWidth', this.get('targetNode').get('offsetWidth'));
						} else {
							this.widgets.input_video.set('maxWidth', 0);
						}
						if (this.get('fixedMaxCropHeight')) {
							this.widgets.input_video.set('maxHeight', this.get('targetNode').get('offsetHeight'));
						} else {
							this.widgets.input_video.set('maxHeight', 0);
						}
						
						state = this.widgets.input_video.startEditing();
					} else {
						state = this.widgets.input_image.startEditing();
						
						if (!this.get('value').image) {
							// Open media library to choose image
							var promise = this.widgets.input_image.openMediaSidebar();
							promise.done(function (value) {
								if (!value) {
									// Image isn't selected, show "Image" / "Video" choice
									this.removeMedia();
								}
							}, this);
						}
					}
				}
			}
			
			return state;
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
			
			if (this.get('editing')) {
				this.hideSettingsSidebar();
				this.set('editing', false);
			}
		},
		
		insertImage: function () {
			this.set('value', {
				'type': 'image'
			});
			
			this.startEditing();
		},
		
		insertImageData: function (data) {
			var node = this.get('targetNode'),
				size = data.sizes.original,
				width = Math.min(size.width, node.get('offsetWidth')) || size.width,
				height = size.height;
			
			if (width != size.width) {
				// Change height
				height = Math.round(width / (size.width / size.height));
			}
			
			this.set('value', {
				'type': 'image',
				'crop_left': 0,
				'crop_top': 0,
				'crop_width': width,
				'crop_height': height,
				'size_height': height,
				'size_width': width,
				'image': data
			});
		},
		
		insertVideo: function () {
			var node   = this.get('targetNode'),
				ratio  = Supra.Input.Video.getVideoSizeRatio(),
				width  = node.get('offsetWidth'),
				height = ~~(width / ratio);
			
			this.set('value', {
				'type': 'video',
				'resource': 'source',
				'source': '',
				'width': width,
				'height': height
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
			this.stopEditing();
		},
		
		/**
		 * Render value inside content
		 * 
		 * @param {Object} node Node in which to render
		 * @param {Object} data Media data
		 * @private
		 */
		renderContent: function (node, data) {
			var node = node,
				type = data.type || this.type;
			
			if (!node) {
				this.widgets.input_image.set('targetNode', null);
				return;
			}
			
			if (data && type == 'image') {
				var style = null,
					//html = '<img class="as-layer" src="' + this.get('blankImageUrl') + '" width="100%" height="220" style="background: #e5e5e5 url(/cms/lib/supra/img/medialibrary/icon-broken-plain.png) 50% 50% no-repeat;" alt="" />';
					html = '<img class="as-layer" src="' + this.get('blankImageUrl') + '" width="100%" height="220" alt="" />';
				
				node.set('innerHTML', html);
				this.widgets.input_image.set('targetNode', node.one('img'));
				this.widgets.input_image.syncUI();
			} else {
				this.widgets.input_image.stopEditing();
				this.widgets.input_image.set('targetNode', null);
			}
			
			if (data && type == 'video') {
				var Input = Supra.Input.Video,
					width = data.width || node.get('offsetWidth'),
					height = ~~(width / Input.getVideoSizeRatio(data)),
					html = '<div class="supra-video" style="width: ' + width + 'px; height: ' + height + 'px;"></div>';
				
				node.set('innerHTML', html);
				this.updateVideoPreviewImage(data);
			}
			
			if (!data || (type !== 'image' && type !== 'video')) {
				// Empty with buttons
				var allow_video = this.get('allowVideo'),
					allow_image = this.get('allowImage'),
					tmp = null,
					
					label = this.get('label'),
					description = this.get('description'),
					
					html = (label ? '<h2>' + Y.Escape.html(label) + '</h2>' : '') +
						   (description ? '<p>' + Y.Escape.html(description) + '</p>' : '') +
						   '<div align="center" class="yui3-box-reset">' +
						       (allow_image ? ('<a class="supra-button" data-supra-action="addImage">' + (this.get('labelAddImage') || Supra.Intl.get(['inputs', 'media', 'add_image'])) + '</a>') : '') +
						       (allow_video ? ('<a class="supra-button" data-supra-action="addVideo">' + (this.get('labelAddVideo') || Supra.Intl.get(['inputs', 'media', 'add_video'])) + '</a>') : '') +
						   '</div>';
				
				node.addClass(this.getClassName('empty'));
				node.set('innerHTML', html);
				
				tmp = node.one('a[data-supra-action="addImage"]');
				if (tmp) tmp.on('click', this.insertImage, this);
				
				tmp = node.one('a[data-supra-action="addVideo"]');
				if (tmp) tmp.on('click', this.insertVideo, this);
			} else {
				node.removeClass(this.getClassName('empty'));
			}
		},
		
		/**
		 * Update video preview image size
		 * 
		 * @param {Object} data Video data
		 * @private
		 */
		updateVideoSize: function (node, data) {
			if (!node) return;
			
			var Input = Supra.Input.Video,
				width = parseInt(data.width, 10) || node.get('offsetWidth'),
				height = ~~(width / Input.getVideoSizeRatio(data));
			
			if (data.width != width) {
				data.width = width;
				data.height = height;
			}
			
			node.setStyles({
				'width': data.width + 'px',
				'height': data.height + 'px'
			});
		},
		
		/**
		 * Update video preview image
		 * 
		 * @param {Object} data Video data
		 * @private
		 */
		updateVideoPreviewImage: function (data) {
			var targetNode = this.get('targetNode');
			if (!targetNode) return;
			
			var Input = Supra.Input.Video,
				node = targetNode.one('.supra-video'),
				data = data || this.widgets.input_video.get('value');
			
			if (node) {
				Input.getVideoPreviewUrl(data).always(function (url) {
					if (url) {
						// Using setAttribute because it's not possible to use !important in styles
						node.setAttribute('style', 'background: #000000 url("' + url + '") no-repeat scroll center center !important; background-size: 100% !important;')
					} else {
						node.removeAttribute('style')
					}
					
					this.updateVideoSize(node, data);
				}, this);
			}
		},
		
		
		/*
		 * ---------------------------------------- VALUE ----------------------------------------
		 */
		
		
		/**
		 * Trigger value change events
		 * 
		 * @private
		 */
		_fireValueChange: function () {
			if (this.silentChildValueUpdate) return;
			
			this.silentValueUpdate = true;
			this.set('value', this.get('value'));
			this.silentValueUpdate = false;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value New value
		 * @returns {Object} New value
		 * @private
		 */
		_setValue: function (value) {
			if (!this.widgets || this.silentValueUpdate) return value;
			this.silentChildValueUpdate = true;
			
			var data = Supra.mix({'type': ''}, value || {}),
				type = data.type;
			
			delete(data.type);
			
			if (type == 'image' && Y.Object.size(data)) {
				this.widgets.input_image.set('value', data);
			} else {
				this.widgets.input_image.set('value', null);
			}
			
			if (type == 'video') {
				if (data && !data.width) {
					var node = this.get('targetNode');
					if (node) {
						data.width = node.get('offsetWidth');
						data.height = ~~(data.width / Supra.Input.Video.getVideoSizeRatio(data));
					}
				}
				
				this.widgets.input_video.set('value', data);
			} else {
				this.widgets.input_video.set('value', null);
			}
			
			this.type = type;
			
			this.renderContent(this.get('targetNode'), data);
			
			this.silentChildValueUpdate = false;
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
			} else if (type) {
				return {'type': type};
			} else {
				return '';
			}
		},
		
		/**
		 * Returns value for saving
		 * 
		 * @return {Object}
		 * @private
		 */
		_getSaveValue: function () {
			var value = this.get("value");
			
			if (value && value.image) {
				//We want to send only image ID
				//We clone image info to be sure that we don't overwrite info
				value = Supra.mix({}, value, {
					"image": value.image.id
				});
			}
			
			/*
			 * value == {
			 * 	   "type": "image",
			 * 	   "image": "...id...",
			 *     "crop_height": Number, "crop_width": Number, "crop_left": Number, "crop_top": Number,
			 *     "size_width": Number, "size_height": Number
			 * }
			 */
			return value;
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		},
		
		/**
		 * Returns 'video' or 'image'
		 */
		getValueType: function () {
			var value = this.get('value');
		},
		
		/**
		 * Visible attribute setter
		 * 
		 * @private
		 */
		_uiSetVisible: function (visible) {
			return visible;
		}
		
	});
	
	Supra.Input.InlineMedia = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'supra.uploader']});