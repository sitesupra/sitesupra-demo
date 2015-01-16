YUI().add('supra.imageresizer', function (Y) {
	
	// Resize handle size
	var RESIZE_HANDLE_SIZE = 16;
	
	/**
	 * Image resize class
	 * 
	 * @extends Y.Base
	 * @param {Object} config Attribute values
	 */
	function ImageResizer (config) {
		ImageResizer.superclass.constructor.apply(this, arguments);
		
		// Throttle (debouce) window resize event calls
		this._onWindowResize = Supra.throttle(this._onWindowResize, 60, this);
	}
	
	ImageResizer.MODE_IMAGE = 0;
	ImageResizer.MODE_BACKGROUND = 1;
	ImageResizer.MODE_ICON = 2;
	
	ImageResizer.NAME = 'imageresizer';
	ImageResizer.CLASS_NAME = ImageResizer.CSS_PREFIX = 'su-' + ImageResizer.NAME;
	
	ImageResizer.ATTRS = {
		// Image element
		'image': {
			value: null,
			setter: '_setImageAttr'
		},
		
		// Maximal image width (original image width)
		'maxImageWidth': {
			value: 0
		},
		// Maximal image height (original image height)
		'maxImageHeight': {
			value: 0
		},
		
		// Minimal width
		'minCropWidth': {
			value: 32
		},
		// Maximal width
		'maxCropWidth': {
			value: 0
		},
		// Minimal height
		'minCropHeight': {
			value: 32
		},
		// Maximal height
		'maxCropHeight': {
			value: 0
		},
		
		// Image document element
		'doc': {
			value: null
		},
		// Node used for resize handles
		'resizeHandleNode': {
			value: null
		},
		// Image container node
		'imageContainerNode': {
			value: null,
		},
		// Cursor:  0 - nw, 1 - ne, 2 - se, 3 - sw, 4 - move
		'cursor': {
			value: 4,
			setter: '_setCursorAttr'
		},
		
		// Stop editing when clicked outside
		'autoClose': {
			value: true
		},
		
		// Mode: 0 - image, 1 - background, 2 - icon
		'mode': {
			value: ImageResizer.MODE_IMAGE,
			setter: '_setMode'
		},
		
		// Background position, valid only in background mode
		'position': {
			value: '0% 0%',
			setter: '_setPosition'
		},
		
		// Background attachment, valid only in background mode
		'attachment': {
			value: 'scroll',
			setter: '_setAttachment'
		},
		
		// Resize crop region to smaller on image zoom change if needed
		'allowZoomResize': {
			value: false
		},
		// Change zoom on crop resize if needed
		'allowCropZooming': {
			value: false
		},
		
		// Width and height ratio is locked
		// when changing width or height input other one changes too
		'ratioLocked': {
			value: false,
			setter: '_setRatioLocked'
		}
	};
	
	Y.extend(ImageResizer, Y.Base, {
		
		/**
		 * Image width after zoom
		 * Crop left + crop width can't exceed this
		 * @type {Number}
		 * @protected
		 */
		imageWidth: 0,
		
		/**
		 * Image height after zoom
		 * Crop top + crop height can't exceed this
		 * @type {Number}
		 * @protected
		 */
		imageHeight: 0,
		
		/**
		 * Minimal image width, based on minCropWidth and image ratio
		 * @type {Number}
		 * @protected
		 */
		minImageWidth: 0,
		
		/**
		 * Minimal image height, based on minCropHeight and image ratio
		 * @type {Number}
		 * @protected
		 */
		minImageHeight: 0,
		
		
		/**
		 * Image crop width
		 * @type {Number}
		 * @protected
		 */
		cropWidth: 0,
		
		/**
		 * Image crop width
		 * @type {Number}
		 * @protected
		 */
		cropHeight: 0,
		
		/**
		 * Image left crop
		 * @type {Number}
		 * @protected
		 */
		cropLeft: 0,
		
		/**
		 * Image top crop
		 * @type {Number}
		 * @protected
		 */
		cropTop: 0,
		
		/**
		 * Image zoom level
		 * @type {Number}
		 * @protected
		 */
		zoom: 1,
		
		/**
		 * Image offset created by position
		 * @type {Array}
		 * @protected
		 */
		offset: [0, 0],
		
		/**
		 * Image position
		 * @type {Array}
		 * @protected
		 */
		position: ['0%', '0%'],
		
		
		
		/**
		 * Drag start mouse X position
		 * @type {Number}
		 * @protected
		 */
		mouseStartX: null,
		
		/**
		 * Drag start mouse Y position
		 * @type {Number}
		 * @protected
		 */
		mouseStartY: null,
		
		/**
		 * Drag start image width
		 * @type {Number}
		 * @protected
		 */
		dragStartW: null,
		
		/**
		 * Drag start image height
		 * @type {Number}
		 * @protected
		 */
		dragStartH: null,
		
		/**
		 * Drag image width
		 * @type {Number}
		 * @protected
		 */
		dragW: null,
		
		/**
		 * Drag image height
		 * @type {Number}
		 * @protected
		 */
		dragH: null,
		
		/**
		 * Drag image left offset
		 * @type {Number}
		 * @protected
		 */
		dragCropLeft: null,
		
		/**
		 * Drag image top offset
		 * @type {Number}
		 * @protected
		 */
		dragCropTop: null,
		
		
		/**
		 * Currently resizing crop
		 * @type {Boolean}
		 * @protected
		 */
		resizeActive: false,
		
		/**
		 * Currently moving
		 * @type {Boolean}
		 * @protected
		 */
		moveActive: false,
		
		/**
		 * Event listener object for mouse move event
		 * @type {Object}
		 * @protected
		 */
		eventMove: null,
		
		/**
		 * Event listener object for mouse move event on main document
		 * @type {Object}
		 * @protected
		 */
		eventMoveMain: null,
		
		/**
		 * Event listener object for mouse up event
		 * @type {Object}
		 * @protected
		 */
		eventDrop: null,
		
		/**
		 * Event listener object for mouse up event on main document
		 * @type {Object}
		 * @protected
		 */
		eventDropMain: null,
		
		/**
		 * Event listener object for document click event
		 * @type {Object}
		 * @protected
		 */
		eventClick: null,
		
		/**
		 * Zoom and size panel
		 * Supra.Panel instance
		 * @type {Object}
		 * @protected
		 */
		adjustmentPanel: null,
		
		/**
		 * Zoom slider
		 * Supra.Slider instance
		 * @type {Object}
		 * @protected
		 */
		zoomSlider: null,
		
		/**
		 * Zoom in button
		 * Supra.Button instance
		 * @type {Object}
		 * @protected
		 */
		buttonZoomIn: null,
		
		/**
		 * Zoom out button
		 * Supra.Button instance
		 * @type {Object}
		 * @protected
		 */
		buttonZoomOut: null,
		
		/**
		 * Width input
		 * Supra.Input.String instance
		 * @type {Object}
		 * @protected
		 */
		inputWidth: null,
		
		/**
		 * Height input
		 * Supra.Input.String instance
		 * @type {Object}
		 * @protected
		 */
		inputHeight: null,
		
		/**
		 * Width/height ratio lock button
		 * Supra.Button instance
		 * @type {Object}
		 * @protected
		 */
		buttonRatio: null,
		
		/**
		 * Size input values are changed by something other than
		 * direct input.
		 * @type {Boolean}
		 * @protected
		 */
		sizeInputValuesChanging: false,
		
		/**
		 * Content overlay
		 * When dragging it's shown to allow to prevent mouse events from
		 * interfering with other elements/widgets
		 *
		 * @type {Object}
		 * @protected
		 */
		contentOverlay: null,
		
		
		/**
		 * Initialization life cycle method
		 */
		initializer: function () {
			this.after('imageChange', this._uiImageAttrChange, this);
		},
		
		/**
		 * On destruction revert changes to image, clean up
		 * 
		 * @protected
		 */
		destructor: function () {
			if (this.get('image')) {
				this.set('image', null);
			}
			if (this.zoomSlider) {
				this.zoomSlider.destroy();
			}
			if (this.inputWidth) {
				this.inputWidth.destroy();
			}
			if (this.inputHeight) {
				this.inputHeight.destroy();
			}
			if (this.buttonRatio) {
				this.buttonRatio.destroy();
			}
			if (this.adjustmentPanel) {
				this.adjustmentPanel.destroy();
			}
			if (this.buttonZoomIn) {
				this.buttonZoomIn.destroy();
			}
			if (this.buttonZoomOut) {
				this.buttonZoomOut.destroy();
			}
			if (this.contentOverlay) {
				this.contentOverlay.destroy(true);
				
			}
			
			this.zoomSlider = null;
			this.inputWidth = null;
			this.inputHeight = null;
			this.buttonRatio = null;
			this.adjustmentPanel = null;
			this.buttonZoomIn = null;
			this.buttonZoomOut = null;
			this.contentOverlay = null;
		},
		
		
		/* --------------------------------- Cursor --------------------------------- */
		
		
		/**
		 * Set mouse cursor 
		 */
		setMouseCursor: function (e) {
			//Resize is available only if resizing image
			//If currently resizing, then don't change cursor
			if (!this.resizeActive && !this.moveActive) {
				var xy = this.get('resizeHandleNode').getXY(),
					x = e.pageX - xy[0],
					y = e.pageY - xy[1],
					w = this.cropWidth + 6 * 2,
					h = this.cropHeight + 6 * 2,
					handleSize = RESIZE_HANDLE_SIZE,
					cursor = 4;
				
				if (x > w - handleSize) {
					if (y > h - handleSize) {
						cursor = 2;
					} else if (y < handleSize) {
						cursor = 1;
					}
				} else if (x < handleSize) {
					if (y > h - handleSize) {
						cursor = 3;
					} else if (y < handleSize) {
						cursor = 0;
					}
				}
				
				if (this.get('cursor') != cursor) {
					this.set('cursor', cursor);
				}
			}
		},
		
		/**
		 * Unset mouse cursor
		 */
		unsetMouseCursor: function (e) {
			if (!this.resizeActive && !this.moveActive && this.get('cursor') != 4) {
				this.set('cursor', 4);
			}
		},
		
		/**
		 * Returns cursor classname
		 * 
		 * @param {Number} cursor
		 * @return Classname for that cursor
		 * @type {String}
		 */
		getCursorClassName: function (cursor) {
			switch (cursor) {
				case 0:
					return 'supra-image-resize-nw';
				case 1:
					return 'supra-image-resize-ne';
				case 2:
					return 'supra-image-resize-se';
				case 3:
					return 'supra-image-resize-sw';
				default:
					var mode = this.get('mode');
					return mode == ImageResizer.MODE_IMAGE || mode == ImageResizer.MODE_ICON ? '' : 'supra-image-resize-move';
			}
		},
		
		
		/* --------------------------------- Zoom --------------------------------- */
		
		
		/**
		 * Create panel and slider
		 * 
		 * @protected
		 */
		createPanel: function () {
			if (this.adjustmentPanel) return;
			
			var panel = this.adjustmentPanel = new Supra.Panel({
				'zIndex': 10,
				'plugins': [ Y.Plugin.Drag ],
				'style': 'dark'
				/*'alignPosition': 'T',
				'arrowVisible': true*/
			});
			panel.render();
			
			var boundingBox = panel.get('boundingBox'),
				contentBox = panel.get('contentBox'),
				sizeBox = Y.Node.create('<div class="clearfix su-imageresizer-sizebox"></div>'),
				slider = new Supra.Slider({
					'axis': 'x',
					'min': 0,
					'max': 100,
					'value': 100,
					'length': 250
				}),
				zoomIn = new Supra.Button({
					'label': '',
					'style': 'zoom-in'
				}),
				zoomOut = new Supra.Button({
					'label': '',
					'style': 'zoom-out'
				}),
				inputWidth = new Supra.Input.String({
					'label': Supra.Intl.get(['inputs', 'resize_width']),
					'style': 'size',
					'valueMask': /^[0-9]*$/,
					'value': this.cropWidth || this.imageWidth
				}),
				inputHeight = new Supra.Input.String({
					'label': Supra.Intl.get(['inputs', 'resize_height']),
					'style': 'size',
					'valueMask': /^[0-9]*$/,
					'value': this.cropHeight || this.imageHeight
				}),
				buttonRatio = new Supra.Button({
					'label': '',
					'style': 'small-gray'
				});
			
			this.zoomSlider = slider;
			this.buttonZoomIn = zoomIn;
			this.buttonZoomOut = zoomOut;
			this.inputWidth = inputWidth;
			this.inputHeight = inputHeight;
			this.buttonRatio = buttonRatio;
			
			slider.render(contentBox);
			slider.on('valueChange', this.zoomChange, this);
			
			// Zoom in, zoom out buttons
			zoomIn.render(contentBox);
			zoomOut.render(contentBox);
			
			zoomIn.on('click', function () {
				this.set('value', Math.min(100, this.get('value') + this.get('majorStep')));
			}, slider);
			zoomOut.on('click', function () {
				this.set('value', Math.max(0, this.get('value') - this.get('majorStep')));
			}, slider);
			
			// Width and height
			contentBox.append(sizeBox);
			
			sizeBox.append('<p class="label">' + Supra.Intl.get(['inputs', 'resize_frame_size']) + '</label>');
			
			inputWidth.render(sizeBox);
			
			buttonRatio.render(sizeBox);
			buttonRatio.addClass('su-button-ratio');
			
			if (this.get('mode') !== ImageResizer.MODE_IMAGE) {
				this.set('ratioLocked', true);
				buttonRatio.set('disabled', true);
			}
			if (this.get('ratioLocked')) {
				buttonRatio.addClass('su-button-locked');
			}
			
			inputHeight.render(sizeBox);
			
			inputWidth.on('input', Supra.throttle(function (e) {
				if (this.inputWidth.get('focused')) {
					this.widthChange(e, 'width');
				}
			}, 250, this, true), this);
			inputHeight.on('input', Supra.throttle(function (e) {
				if (this.inputHeight.get('focused')) {
					this.heightChange(e, 'height');
				}
			}, 250, this, true), this);
			
			// On blur change value
			inputWidth.on('blur', function () {
				var value = this.cropWidth;
				if (this.get('mode') == ImageResizer.MODE_BACKGROUND) {
					value = this.imageWidth;
				}
				if (this.inputWidth.get('value') != value) {
					this.inputWidth.set('value', value);
				}
			}, this);
			inputHeight.on('blur', function () {
				var value = this.cropHeight;
				if (this.get('mode') == ImageResizer.MODE_BACKGROUND) {
					value = this.imageHeight;
				}
				if (this.inputHeight.get('value') != value) {
					this.inputHeight.set('value', value);
				}
			}, this);
			
			inputWidth.on('valueChange', this.widthChange, this);
			inputHeight.on('valueChange', this.heightChange, this);
			
			buttonRatio.on('click', this.toggleRatioLock, this)
			buttonRatio.set('title', Supra.Intl.get(['inputs', 'resize_button_title']));
			
			// Panel style
			boundingBox.addClass('su-imageresizer');
			boundingBox.addClass('ui-dark');
			
			// On position attribute change update image
			this.after('positionChange', this.sync, this);
			this.after('attachmentChange', this.sync, this);
		},
		
		/**
		 * Position panel, set current zoom value
		 */
		setUpPanel: function () {
			if (!this.adjustmentPanel) {
				this.createPanel();
			}
			
			this.adjustmentPanel.set('alignTarget', this.get('image'));
			this.adjustmentPanel.show();
			this.adjustmentPanel.centered();
			
			//Set zoom value
			var zoom = this.sizeToZoom(this.imageWidth, this.imageHeight);
			this.zoomSlider.set('value', zoom, {'silent': true});
			
			//Enable or disable zoom slider
			this.uiSyncZoomSliderState();
		},
		
		/**
		 * Handle zoom change
		 * 
		 * @param {Event} e Event facade object
		 * @protected
		 */
		zoomChange: function (e) {
			if (e.silent || !this.get('image')) return;
			
			var image = this.get('image'),
				zoom = e.newVal,
				size = this.zoomToSize(zoom),
				ratio = null,
				containerNode = null,
				imageContainerNode = null,
				position;
			
			this.imageWidth = ~~size[0];
			this.imageHeight = ~~size[1];
			
			if (this.get('mode') == ImageResizer.MODE_IMAGE) {
				ratio = (this.get('maxImageWidth') / this.get('maxImageHeight'));
				
				if (this.get('allowZoomResize')) {
					//Resize crop if needed
					if (this.cropTop + this.cropHeight > this.imageHeight) {
						this.cropTop = this.imageHeight - this.cropHeight;
						if (this.cropTop < 0) {
							this.cropHeight = Math.max(this.cropHeight + this.cropTop, 0);
							this.cropTop = 0;
						}
					}
					if (this.cropLeft + this.cropWidth > this.imageWidth) {
						this.cropLeft = this.imageWidth - this.cropWidth;
						if (this.cropLeft < 0) {
							this.cropWidth = Math.max(this.cropWidth + this.cropLeft, 0);
							this.cropLeft = 0;
						}
					}
					
					imageContainerNode = this.get('imageContainerNode');
					containerNode = imageContainerNode.ancestor();
					
					imageContainerNode.setStyles({
						'width': this.cropWidth + 'px',
						'height': this.cropHeight + 'px'
					});
					containerNode.setStyles({
						'width': this.cropWidth + 'px',
						'height': this.cropHeight + 'px'
					});
				} else {
					//Crop resize not allowed, validate new size against crop
					if (this.cropTop + this.cropHeight > this.imageHeight) {
						this.cropTop = Math.max(0, this.imageHeight - this.cropHeight);
						this.imageHeight = this.cropTop + this.cropHeight;
						this.imageWidth = ~~(this.imageHeight * ratio);
					}
					if (this.cropLeft + this.cropWidth > this.imageWidth) {
						this.cropLeft = Math.max(0, this.imageWidth - this.cropWidth);
						this.imageWidth = this.cropLeft + this.cropWidth;
						this.imageHeight = ~~(this.imageWidth / ratio);
					}
				}
				
				image.setStyles({
					'margin': - this.cropTop + 'px 0 0 -' + this.cropLeft + 'px',
					'width': this.imageWidth + 'px',
					'height': this.imageHeight + 'px'
				});
				image.setAttribute('width', this.imageWidth);
				image.setAttribute('height', this.imageHeight);
				
				//Update size input values
				this._uiSetSizeInputValues(this.cropWidth, this.cropHeight);
			} else if (this.get('mode') == ImageResizer.MODE_ICON) {
				this.cropWidth = this.imageWidth;
				this.cropHeight = this.imageHeight;
				
				imageContainerNode = this.get('imageContainerNode');
				containerNode = imageContainerNode.ancestor();
				
				imageContainerNode.setStyles({
					'width': this.imageWidth + 'px',
					'height': this.imageHeight + 'px'
				});
				containerNode.setStyles({
					'width': this.imageWidth + 'px',
					'height': this.imageHeight + 'px'
				});
				image.setStyles({
					'width': this.imageWidth + 'px',
					'height': this.imageHeight + 'px'
				});
				
				image.setAttribute('width', this.imageWidth);
				image.setAttribute('height', this.imageHeight);
				
				//Update size input values
				this._uiSetSizeInputValues(this.imageWidth, this.imageHeight);
			} else {
				if (this.position[0] == '0%') {
					this.cropWidth = Math.max(0, this.imageWidth - this.cropLeft);
				} else if (this.position[0] == '50%') {
					this.cropWidth = this.imageWidth;
				} else if (this.position[0] == '100%') {
					this.cropWidth = Math.min(this.cropWidth, this.imageWidth);
				}
				
				if (this.position[1] == '0%') {
					this.cropHeight = Math.max(0, this.imageHeight - this.cropTop);
				} else if (this.position[1] == '50%') {
					this.cropHeight = this.imageHeight;
				} else if (this.position[1] == '100%') {
					this.cropHeight = Math.min(this.cropHeight, this.imageHeight);
				}
				
				position = this.getBackgroundPosition();
				
				image.setStyles({
					'backgroundSize': this.imageWidth + 'px ' + this.imageHeight + 'px',
					'backgroundPosition': position[4] + ' ' + position[5]
				});
				
				//Update size input values
				this._uiSetSizeInputValues(this.imageWidth, this.imageHeight);
			}
		},
		
		/**
		 * Fix zoom when finished dragging slider
		 * 
		 * @protected
		 */
		fixZoom: function () {
			this.zoomSlider.set('value', this.sizeToZoom(this.imageWidth, this.imageHeight), {silent: true});
			this.uiSyncZoomSliderState();
		},
		
		/**
		 * Returns true if changing zoom will have any effect or not
		 * 
		 * @returns {Boolean} True if changing zoom values will have any effect, otherwise false
		 */
		isZoomMinMaxDifferent: function () {
			var width = this.imageWidth,
				height = this.imageHeight;
			
			var minImageWidth = 0,
				minImageHeight = 0,
				maxImageWidth = this.get('maxImageWidth'),
				maxImageHeight = this.get('maxImageHeight');
			
			if (this.get('allowZoomResize') || this.get('mode') == ImageResizer.MODE_BACKGROUND || this.get('mode') == ImageResizer.MODE_ICON) {
				minImageWidth = this.minImageWidth;
				minImageHeight = this.minImageHeight;
			} else {
				minImageWidth = this.cropWidth;
				minImageHeight = this.cropHeight;
			}
			
			if (minImageWidth == maxImageWidth && minImageHeight == maxImageHeight) {
				return false;
			} else {
				return true;
			}
		},
		
		/**
		 * Returns zoom by width and height
		 * 
		 * @param {Number} width Image width
		 * @param {Number} height Image height
		 * @return Image zoom
		 * @type {Number}
		 */
		sizeToZoom: function (width, height) {
			var minImageWidth = 0,
				minImageHeight = 0;
			
			if (this.get('allowZoomResize') || this.get('mode') == ImageResizer.MODE_BACKGROUND || this.get('mode') == ImageResizer.MODE_ICON) {
				minImageWidth = this.minImageWidth;
				minImageHeight = this.minImageHeight;
			} else {
				minImageWidth = this.cropWidth;
				minImageHeight = this.cropHeight;
			}
			
			if (this.get('maxImageWidth') - minImageWidth < this.get('maxImageHeight') - minImageHeight) {
				return Math.round((width - minImageWidth) / (this.get('maxImageWidth') - minImageWidth) * 100);
			} else {
				return Math.round((height - minImageHeight) / (this.get('maxImageHeight') - minImageHeight) * 100);
			}
		},
		
		/**
		 * Returns image width and height from zoom
		 * 
		 * @param {Number} zoom Image zoom
		 * @return Image width and height
		 * @type {Array}
		 */
		zoomToSize: function (zoom) {
			var width = 0,
				height = 0,
				ratio = 0,
				minImageWidth = 0,
				minImageHeight = 0;
			
			if (this.get('allowZoomResize') || this.get('mode') == ImageResizer.MODE_BACKGROUND || this.get('mode') == ImageResizer.MODE_ICON) {
				minImageWidth = this.minImageWidth;
				minImageHeight = this.minImageHeight;
			} else {
				minImageWidth = this.cropWidth;
				minImageHeight = this.cropHeight;
			}
			
			if (this.imageWidth > this.imageHeight) {
				ratio = (this.get('maxImageWidth') / this.get('maxImageHeight'));
				width = (this.get('maxImageWidth') - minImageWidth) * zoom / 100 + minImageWidth;
				height = width / ratio;
			} else {
				ratio = (this.get('maxImageHeight') / this.get('maxImageWidth'));
				height = (this.get('maxImageHeight') - minImageHeight) * zoom / 100 + minImageHeight;
				width = height / ratio;
			}
			
			return [Math.round(width), Math.round(height)];
		},
		
		
		/* ------------------------ Width & height change ------------------------- */
		
		
		/**
		 * Update image position and size
		 */
		sync: function () {
			var image = this.get('image'),
				node  = null,
				container = null,
				
				position = null,
				mode = this.get('mode');
			
			if (!image) return;
			
			if (mode == ImageResizer.MODE_IMAGE) {
				// Image size and position
				node = this.get('imageContainerNode');
				if (!node) return;
				
				containerNode = node.ancestor();
				
				node.setStyles({
					'width': this.cropWidth,
					'height': this.cropHeight
				});
				containerNode.setStyles({
					'width': this.cropWidth,
					'height': this.cropHeight
				});
				
				image.setStyle('margin', - this.cropTop + 'px 0 0 -' + this.cropLeft + 'px');
				
			} else if (mode === ImageResizer.MODE_BACKGROUND) {
				// Background position and size
				position = this.getBackgroundPosition();
				
				this.cropLeft = position[0];
				this.cropTop = position[1];
				this.cropWidth = position[2];
				this.cropHeight = position[3];
				
				// Sync input values
				this._uiSetSizeInputValues(this.imageWidth, this.imageHeight);
				this.zoomSlider.set('value', this.sizeToZoom(this.imageWidth, this.imageHeight), {silent: true});
				
				image.setStyles({
					'backgroundSize': this.imageWidth + 'px ' + this.imageHeight + 'px',
					'backgroundPosition': position[4] + ' ' + position[5]
				});	
			} else if (mode === ImageResizer.MODE_ICON) {
				// Icon size
				node = this.get('imageContainerNode');
				containerNode = node.ancestor();
				
				node.setStyles({
					'width': this.cropWidth,
					'height': cropHeight
				});
				containerNode.setStyles({
					'width': this.cropWidth,
					'height': cropHeight
				});
				image.setStyles({
					'width': this.cropWidth,
					'height': cropHeight
				});
				
				image.setAttribute('width', cropWidth + 'px');
				image.setAttribute('height', cropHeight + 'px');
				
			}
			
			this.uiSyncZoomSliderState();
		},
		
		/**
		 * Update zoom slider state
		 */
		uiSyncZoomSliderState: function () {
			this._uiSetZoomDisabled(!this.isZoomMinMaxDifferent());
		},
		
		/**
		 * Handle width change
		 * 
		 * @protected
		 */
		widthChange: function (e, type) {
			if (this.sizeInputValuesChanging) return;
			
			var height = 0,
				width  = parseInt(e.newVal || e.value, 10),
				mode   = this.get('mode');
			
			if (mode == ImageResizer.MODE_IMAGE) {
				if (width == this.cropWidth) return;
				width = width || this.cropWidth;
				
				if (this.get('ratioLocked')) {
					height = Math.round(width * this.cropHeight / this.cropWidth);
				} else {
					height  = this.cropHeight;
				}
				
				this.sizeChangeImage(width, height, type);
			} else if (mode == ImageResizer.MODE_BACKGROUND) {
				if (width == this.imageWidth) return;
				width = width || this.imageWidth;
				this.sizeChangeBackground(width, null, type);
			} else {
				if (width == this.cropWidth) return;
				this.sizeChangeIcon(width || this.cropWidth, null, type);
			}
		},
		
		/**
		 * Handle height change
		 * 
		 * @protected
		 */
		heightChange: function (e, type) {
			if (this.sizeInputValuesChanging) return;
			
			var height = parseInt(e.newVal || e.value, 10),
				width  = 0,
				mode   = this.get('mode');
			
			if (mode == ImageResizer.MODE_IMAGE) {
				if (height == this.cropHeight) return;
				height = height || this.cropHeight;
				
				if (this.get('ratioLocked')) {
					width = Math.round(height * this.cropWidth / this.cropHeight);
				} else {
					width  = this.cropWidth;
				}
				
				this.sizeChangeImage(width, height, type);
			} else if (mode == ImageResizer.MODE_BACKGROUND) {
				if (height == this.imageHeight) return;
				height = height || this.imageHeight;
				this.sizeChangeBackground(null, height, type);
			} else {
				if (height == this.cropHeight) return;
				this.sizeChangeIcon(null, height || this.cropHeight, type)
			}
		},
		
		/**
		 * Handle width and height change for image
		 * 
		 * @protected
		 */
		sizeChangeImage: function (sizeX, sizeY, input_type) {
			if (!this.get('imageContainerNode')) return;
			this.sizeInputValuesChanging = true;
			
			var image = this.get('image'),
				node = this.get('imageContainerNode'),
				containerNode = node.ancestor(),
				
				imageWidth = this.imageWidth,
				imageHeight = this.imageHeight,
				
				cropTop = this.cropTop,
				cropLeft = this.cropLeft,
				
				cropWidth = this.cropWidth,
				cropHeight = this.cropHeight,
				
				minW = this.get('minCropWidth'),
				maxW = this.get('maxCropWidth'),
				minH = this.get('minCropHeight'),
				maxH = this.get('maxCropHeight'),
				
				locked = this.get('ratioLocked'),
				lock_ratio  = 0,
				changed_x = true,
				changed_y = true,
				itt = 2;
		
			// Image
			lock_ratio  = cropWidth / cropHeight;
		
			while ((changed_x || changed_y) && itt) {
				changed_x = false;
				changed_y = false;
				
				// Update X
				if (sizeX < minW) {
					sizeX = minW;
					changed_x = true;
				}
				if (maxW && sizeX > maxW) {
					sizeX = maxW;
					changed_x = true;
				}
				if (sizeX > imageWidth - cropLeft) {
					cropLeft = Math.max(0, imageWidth - sizeX);
					sizeX = imageWidth - cropLeft;
					changed_x = true;
				}
				
				if (changed_x && locked) {
					sizeY = Math.round(sizeX / lock_ratio);
				}
				
				// Update Y
				if (sizeY < minH) {
					sizeY = minH;
					changed_y = true;
				}
				if (maxH && sizeY > maxH) {
					sizeY = maxH;
					changed_y = true;
				}
				if (sizeY > imageHeight - cropTop) {
					cropTop = Math.max(0, imageHeight - sizeY);
					sizeY = imageHeight - cropTop;
					changed_y = true;
				}
				
				if (changed_y && locked) {
					sizeX = Math.round(sizeY * lock_ratio);
				}
				
				if (!locked) {
					changed_x = false;
					changed_y = false;
				}
				
				itt--;
			}
			
			if (this.cropTop != cropTop || this.cropLeft != cropLeft) {
				this.cropTop = cropTop;
				this.cropLeft = cropLeft;
				
				image.setStyle('margin', - cropTop + 'px 0 0 -' + cropLeft + 'px');
			}
			
			if (this.cropWidth != sizeX || this.cropHeight != sizeY) {
				this.cropWidth = sizeX;
				this.cropHeight = sizeY;
				
				node.setStyles({
					'width': sizeX,
					'height': sizeY
				});
				containerNode.setStyles({
					'width': sizeX,
					'height': sizeY
				});
				
				// Update size input values
				if (input_type !== 'width') {
					this.inputWidth.set('value', sizeX);
				}
				if (input_type !== 'height') {
					this.inputHeight.set('value', sizeY);
				}
				
				this.fixZoom();
			}
			
			this.sizeInputValuesChanging = false;
		},
		
		/**
		 * Handle width and height change for icon
		 * 
		 * @protected
		 */
		sizeChangeIcon: function (sizeX, sizeY, input_type) {
			this.sizeInputValuesChanging = true;
			
			var node = this.get('imageContainerNode'),
				image = this.get('image'),
				minW = this.get('minCropWidth'),
				maxW = this.get('maxCropWidth'),
				minH = this.get('minCropHeight'),
				maxH = this.get('maxCropHeight'),
				imageHeight = this.imageHeight,
				imageWidth = this.imageWidth,
				ratio = (maxW && maxH ? maxW / maxH : (minW && minH ? minW / minH : imageWidth / imageHeight)),
				containerNode = node.ancestor();
			
			if (!sizeX) {
				sizeX = sizeY * ratio;
			} else if (!sizeY) {
				sizeY = sizeX / ratio;
			}
			
			if (!node) return;
			
			if (sizeX < minW) {
				sizeX = minW;
				sizeY = Math.round(sizeX / ratio);
			}
			if (sizeY < minH) {
				sizeY = minH;
				sizeX = Math.round(sizeY * ratio);
			}
			if (maxW && sizeX > maxW) {
				sizeX = maxW;
				sizeY = Math.round(sizeX / ratio);
			}
			if (maxH && sizeY > maxH) {
				sizeY = maxH;
				sizeX = Math.round(sizeY * ratio);
			}
			
			if (this.cropWidth != sizeX || this.cropHeight != sizeY) {
				this.cropWidth = this.imageWidth = sizeX;
				this.cropHeight = this.imageHeight = sizeY;
				
				// Update size input values
				this.inputWidth.set('value', sizeX);
				this.inputHeight.set('value', sizeY);
				
				node.setStyles({
					'width': sizeX,
					'height': sizeY
				});
				containerNode.setStyles({
					'width': sizeX,
					'height': sizeY
				});
				image.setStyles({
					'width': sizeX,
					'height': sizeY
				});
				
				image.setAttribute('width', sizeX + 'px');
				image.setAttribute('height', sizeY + 'px');
				
				// Update size input values
				if (input_type !== 'width') {
					this.inputWidth.set('value', sizeX);
				}
				if (input_type !== 'height') {
					this.inputHeight.set('value', sizeY);
				}
				
				this.fixZoom();
			}
			
			this.sizeInputValuesChanging = false;
		},
		
		/**
		 * Handle width and height change for background image
		 * 
		 * @protected
		 */
		sizeChangeBackground: function (sizeX, sizeY, input_type) {
			var image  = this.get('image'),
				width  = sizeX,
				height = sizeY,
				maxImageWidth = this.get('maxImageWidth'),
				maxImageHeight = this.get('maxImageHeight'),
				minImageWidth = this.minImageWidth,
				minImageHeight = this.minImageHeight,
				ratio = (maxImageWidth / maxImageHeight),
				position = null;
			
			if (!width) {
				width = Math.round(height * ratio);
			} else if (!height) {
				height = Math.round(width / ratio);
			}
			
			if (width < minImageWidth) {
				width = minImageWidth;
				height = Math.round(width / ratio);
			}
			if (height < minImageHeight) {
				height = minImageHeight;
				width = Math.round(height * ratio);
			}
			if (width > maxImageWidth) {
				width = maxImageWidth;
				height = Math.round(width / ratio);
			}
			if (height > maxImageHeight) {
				height = maxImageHeight;
				width = Math.round(height * ratio);
			}
			
			if (width != this.imageWidth || height != this.imageHeight) {
				this.imageWidth = width;
				this.imageHeight = height;
				
				if (this.position[0] == '0%') {
					this.cropWidth = Math.max(0, this.imageWidth - this.cropLeft);
				} else if (this.position[0] == '50%') {
					this.cropWidth = this.imageWidth;
				} else if (this.position[0] == '100%') {
					this.cropWidth = Math.min(this.cropWidth, this.imageWidth);
				}
				
				if (this.position[1] == '0%') {
					this.cropHeight = Math.max(0, this.imageHeight - this.cropTop);
				} else if (this.position[1] == '50%') {
					this.cropHeight = this.imageHeight;
				} else if (this.position[1] == '100%') {
					this.cropHeight = Math.min(this.cropHeight, this.imageHeight);
				}
				
				position = this.getBackgroundPosition();
				
				image.setStyles({
					'backgroundSize': this.imageWidth + 'px ' + this.imageHeight + 'px',
					'backgroundPosition': position[4] + ' ' + position[5]
				});
				
				// Update size input values
				if (input_type !== 'width') {
					this.inputWidth.set('value', width);
				}
				if (input_type !== 'height') {
					this.inputHeight.set('value', height);
				}
				
				this.fixZoom();
			}
		},
		
		/**
		 * Toggle ratio lock state
		 * 
		 * @protected
		 */
		toggleRatioLock: function () {
			this.set('ratioLocked', !this.get('ratioLocked'));
		},
		
		
		/* --------------------------------- Drag --------------------------------- */
		
		
		/**
		 * Start dragging
		 * 
		 * @param {Event} e Event facade object
		 * @protected
		 */
		dragStart: function (e) {
			if (e.button !== 1) return;
			this.resetPointerCache();
			
			var pointer = this.getPointerPosition(e),
				clientX = pointer[0],
				clientY = pointer[1],
				doc = this.get('doc'),
				node = null,
				mode = this.get('mode'),
				overlay;
			
			if (this.resizeActive || this.moveActive) {
				//If user released mouse outside browser
				this.dragEnd(e);
			}
			
			
			if (mode == ImageResizer.MODE_BACKGROUND) {
				node = this.get('image');
			} else {
				node = this.get('imageContainerNode');
			}
			
			this.dragCropLeft = this.cropLeft;
			this.dragCropTop = this.cropTop;
			this.dragStartCropLeft = this.cropLeft;
			this.dragStartCropTop = this.cropTop;
			
			this.dragCropWidth = this.cropWidth;
			this.dragCropHeight = this.cropHeight;
			this.dragStartCropWidth = this.cropWidth;
			this.dragStartCropHeight = this.cropHeight;
			
			this.mouseStartX = clientX;
			this.mouseStartY = clientY;
			this.dragStartNodeWidth = node.get('offsetWidth');
			this.dragStartNodeHeight = node.get('offsetHeight');
			
			// Events
			overlay = this.showContentOverlay();
			this.eventDrop = overlay.on('mouseup', this.dragEnd, this);
			
			if (document !== doc) {
				this.eventDropMain = Y.Node(document).on('mouseup', this.dragEnd, this);
			}
			
			if (this.get('cursor') < 4) {
				//Resize
				this.resizeActive = true;
				
				if (mode == ImageResizer.MODE_ICON) {
					this.eventMove = Y.Node(doc).on('mousemove', this.dragIconResize, this);
					
					if (document !== doc) {
						this.eventMoveMain = Y.Node(document).on('mousemove', this.dragIconResize, this);
					}
				} else {
					this.eventMove = Y.Node(doc).on('mousemove', this.dragResize, this);
					
					if (document !== doc) {
						this.eventMoveMain = Y.Node(document).on('mousemove', this.dragResize, this);
					}
				}
			} else if (this.get('mode') == ImageResizer.MODE_IMAGE) {
				//Move
				this.moveActive = true;
				this.eventMove = Y.Node(this.get('doc')).on('mousemove', this.dragImageMove, this);
				
				if (document !== doc) {
					this.eventMoveMain = Y.Node(document).on('mousemove', this.dragImageMove, this);
				}
			} else if (this.get('mode') == ImageResizer.MODE_BACKGROUND) {
				//Move
				this.moveActive = true;
				this.eventMove = Y.Node(this.get('doc')).on('mousemove', this.dragBackgroundMove, this);
				
				if (document !== doc) {
					this.eventMoveMain = Y.Node(document).on('mousemove', this.dragBackgroundMove, this);
				}
			}
			
			e.preventDefault();
		},
		
		/**
		 * Handle mouse move while dragging image
		 * 
		 * @param {Event} e Event facade object
		 * @protected
		 */
		dragImageMove: function (e) {
			var pointer = this.getPointerPosition(e),
				clientX = pointer[0],
				clientY = pointer[1],
				
				cursor = this.get('cursor'),
				deltaX = (clientX - this.mouseStartX) * (cursor == 0 || cursor == 3 || cursor == 4 ? -1 : 1),
				deltaY = (clientY - this.mouseStartY) * (cursor == 0 || cursor == 1 || cursor == 4 ? -1 : 1),
				sizeX  = this.dragStartCropLeft + deltaX,
				sizeY  = this.dragStartCropTop + deltaY,
				mode   = this.get('mode'),
				
				offset = null;
			
			if (this.moveActive) {
				var node = this.get('image'),
					cropWidth = this.cropWidth,
					cropHeight = this.cropHeight,
					imageHeight = this.imageHeight,
					imageWidth = this.imageWidth,
					position = this.position,
					nodeWidth = this.dragStartNodeWidth,
					nodeHeight = this.dragStartNodeHeight,
					posX = -sizeX + 'px',
					posY = -sizeY + 'px';
				
				if (!node) return;
				
				if (sizeX < 0) sizeX = 0;
				if (sizeY < 0) sizeY = 0;
				
				// Image
				if (sizeX + cropWidth > imageWidth) {
					sizeX = Math.max(0, imageWidth - cropWidth);
				}
				if (sizeY + cropHeight > imageHeight) {
					sizeY = Math.max(0, imageHeight - cropHeight);
				}
				
				posX = -sizeX + 'px';
				posY = -sizeY + 'px';
				
				if (sizeX != this.dragCropLeft || sizeY != this.dragCropTop) {
					this.dragCropLeft = sizeX;
					this.dragCropTop  = sizeY;
					node.setStyle('margin', posY + ' 0 0 ' + posX);
				}
			}
		},
		
		/**
		 * Handle mouse move while dragging background
		 * 
		 * @param {Event} e Event facade object
		 * @protected
		 */
		dragBackgroundMove: function (e) {
			var pointer = this.getPointerPosition(e),
				clientX = pointer[0],
				clientY = pointer[1],
				
				cursor = this.get('cursor'),
				deltaX = (clientX - this.mouseStartX) * (cursor == 0 || cursor == 3 || cursor == 4 ? -1 : 1),
				deltaY = (clientY - this.mouseStartY) * (cursor == 0 || cursor == 1 || cursor == 4 ? -1 : 1),
				
				crop_left   = this.dragStartCropLeft,
				crop_top    = this.dragStartCropTop,
				crop_width  = this.dragStartCropWidth,
				crop_height = this.dragStartCropHeight,
				
				mode   = this.get('mode'),
				
				position = null;
			
			if (this.moveActive) {
				var node = this.get('image'),
					cropWidth = this.cropWidth,
					cropHeight = this.cropHeight,
					imageHeight = this.imageHeight,
					imageWidth = this.imageWidth,
					position = this.position,
					nodeWidth = this.dragStartNodeWidth,
					nodeHeight = this.dragStartNodeHeight,
					posX = '',
					posY = '';
				
				if (!node) return;
				
				// Background
				if (position[0] == '0%') {
					crop_left += deltaX;
					crop_left = Math.min(Math.max(0, crop_left), imageWidth);
					crop_width = imageWidth - crop_left;
				} else if (position[0] == '50%') {
					crop_left = 0;
				} else if (position[0] == '100%') {
					crop_width = Math.min(imageWidth, Math.max(0, this.dragStartCropWidth + deltaX));
				}
				
				if (position[1] == '0%') {
					crop_top += deltaY;
					crop_top = Math.min(Math.max(0, crop_top), imageHeight);
					crop_height = imageHeight - crop_top;
				} else if (position[1] == '50%') {
					crop_top = 0;
				} else if (position[1] == '100%') {
					crop_height = Math.min(imageHeight, Math.max(0, this.dragStartCropHeight + deltaY));
				}
				
				if (crop_left != this.dragCropLeft || crop_top != this.dragCropTop || crop_width != this.dragCropWidth || crop_height != this.dragCropHeight) {
					this.dragCropLeft = crop_left;
					this.dragCropTop  = crop_top;
					this.dragCropWidth = crop_width;
					this.dragCropHeight  = crop_height;
					
					position = this.getBackgroundPosition(crop_left, crop_top, crop_width, crop_height, nodeWidth, nodeHeight);
					
					node.setStyle('backgroundPosition', position[4] + ' ' + position[5]);
				}
			}
		},
		
		/**
		 * Handle mouse move while resizing
		 * 
		 * @param {Event} e Event facade object
		 * @protected
		 */
		dragResize: function (e) {
			var pointer = this.getPointerPosition(e),
				clientX = pointer[0],
				clientY = pointer[1],
				
				cursor = this.get('cursor'),
				deltaX = (clientX - this.mouseStartX) * (cursor == 0 || cursor == 3 || cursor == 4 ? -1 : 1),
				deltaY = (clientY - this.mouseStartY) * (cursor == 0 || cursor == 1 || cursor == 4 ? -1 : 1),
				sizeX  = this.dragStartCropWidth + deltaX,
				sizeY  = this.dragStartCropHeight + deltaY,
				mode   = this.get('mode');
			
			if (this.resizeActive) {
				var image = this.get('image'),
					node = this.get('imageContainerNode'),
					minW = this.get('minCropWidth'),
					maxW = this.get('maxCropWidth'),
					minH = this.get('minCropHeight'),
					maxH = this.get('maxCropHeight'),
					cropTop = this.dragCropTop,
					cropLeft = this.dragCropLeft,
					imageHeight = this.imageHeight,
					imageWidth = this.imageWidth,
					allowCropZooming = this.get('allowCropZooming'),
					containerNode = node.ancestor();
				
				if (!node) return;
				
				if (sizeX < minW) sizeX = minW;
				if (maxW && sizeX > maxW) sizeX = maxW;
				if (sizeY < minH) sizeY = minH;
				if (maxH && sizeY > maxH) sizeY = maxH;
				
				if (allowCropZooming) {
					var maxImageWidth = this.get('maxImageWidth'),
						maxImageHeight = this.get('maxImageHeight'),
						ratio = maxImageWidth / maxImageHeight;
					
					if (sizeX > imageWidth) {
						sizeX = imageWidth = Math.min(sizeX, maxImageWidth);
						imageHeight = Math.min(Math.round(sizeX / ratio));
					}
					if (sizeY > imageHeight) {
						sizeY = imageHeight = Math.min(sizeY, maxImageHeight);
						imageWidth = Math.min(Math.round(sizeY * ratio));
					}
				}
				
				if (sizeY > imageHeight - cropTop) {
					cropTop = Math.max(0, imageHeight - sizeY);
					sizeY = imageHeight - cropTop;
				}
				if (sizeX > imageWidth - cropLeft) {
					cropLeft = Math.max(0, imageWidth - sizeX);
					sizeX = imageWidth - cropLeft;
				}
				
				if (this.dragCropWidth != sizeX || this.dragCropHeight != sizeY) {
					this.dragCropWidth = sizeX;
					this.dragCropHeight = sizeY;
					
					node.setStyles({
						'width': sizeX,
						'height': sizeY
					});
					containerNode.setStyles({
						'width': sizeX,
						'height': sizeY
					});
					
					//Update size input values
					this._uiSetSizeInputValues(sizeX, sizeY);
				}
				
				if (allowCropZooming && this.imageWidth != imageWidth && this.imageHeight != imageHeight) {
					this.imageWidth = imageWidth;
					this.imageHeight = imageHeight;
					
					image.setStyles({
						'width': imageWidth + 'px',
						'height': imageHeight + 'px'
					});
					image.setAttribute('width', imageWidth);
					image.setAttribute('height', imageHeight);
				}
				
				if (this.dragCropTop != cropTop || this.dragCropLeft != cropLeft) {
					this.dragCropTop = cropTop;
					this.dragCropLeft = cropLeft;
					
					image.setStyle('margin', - cropTop + 'px 0 0 -' + cropLeft + 'px');
				}
			}
		},
		
		/**
		 * Handle mouse move while resizing icon
		 * 
		 * @param {Event} e Event facade object
		 * @protected
		 */
		dragIconResize: function (e) {
			var pointer = this.getPointerPosition(e),
				clientX = pointer[0],
				clientY = pointer[1],
				
				cursor = this.get('cursor'),
				deltaX = (clientX - this.mouseStartX) * (cursor == 0 || cursor == 3 || cursor == 4 ? -1 : 1),
				deltaY = (clientY - this.mouseStartY) * (cursor == 0 || cursor == 1 || cursor == 4 ? -1 : 1),
				delta  = Math.max(deltaX, deltaY),
				sizeX  = this.dragStartCropWidth + delta,
				sizeY  = this.dragStartCropHeight + delta;
			
			if (this.resizeActive) {
				var node = this.get('imageContainerNode'),
					image = this.get('image'),
					minW = this.get('minCropWidth'),
					maxW = this.get('maxCropWidth'),
					minH = this.get('minCropHeight'),
					maxH = this.get('maxCropHeight'),
					imageHeight = this.imageHeight,
					imageWidth = this.imageWidth,
					ratio = (maxW && maxH ? maxW / maxH : (minW && minH ? minW / minH : imageWidth / imageHeight)),
					containerNode = node.ancestor();
				
				if (!node) return;
				
				if (sizeX < minW) {
					sizeX = minW;
					sizeY = Math.round(sizeX / ratio);
				}
				if (sizeY < minH) {
					sizeY = minH;
					sizeX = Math.round(sizeY * ratio);
				}
				if (maxW && sizeX > maxW) {
					sizeX = maxW;
					sizeY = Math.round(sizeX / ratio);
				}
				if (maxH && sizeY > maxH) {
					sizeY = maxH;
					sizeX = Math.round(sizeY * ratio);
				}
				
				if (this.dragCropWidth != sizeX || this.dragCropHeight != sizeY) {
					this.dragCropWidth = sizeX;
					this.dragCropHeight = sizeY;
					
					node.setStyles({
						'width': sizeX,
						'height': sizeY
					});
					containerNode.setStyles({
						'width': sizeX,
						'height': sizeY
					});
					image.setStyles({
						'width': sizeX,
						'height': sizeY
					});
					
					image.setAttribute('width', sizeX + 'px');
					image.setAttribute('height', sizeY + 'px');
					
					//Update size input values
					this._uiSetSizeInputValues(sizeX, sizeY);
				}
			}
		},
		
		/**
		 * Stop drag
		 * 
		 * @param {Event} e Event facade object
		 * @protected
		 */
		dragEnd: function (e) {
			if (this.resizeActive || this.moveActive) {
				this.eventDrop.detach();
				this.eventDrop = null;
				
				this.eventMove.detach();
				this.eventMove = null;
				
				if (this.eventDropMain) {
					this.eventDropMain.detach();
					this.eventDropMain = null;	
				}
				
				if (this.eventMoveMain) {
					this.eventMoveMain.detach();
					this.eventMoveMain = null;	
				}
				
				if (this.resizeActive) { // resize
					this.cropLeft = this.dragCropLeft;
					this.cropTop = this.dragCropTop;
					this.cropWidth = this.dragCropWidth;
					this.cropHeight = this.dragCropHeight;
					
					if (this.get('mode') == ImageResizer.MODE_ICON) {
						this.cropLeft = this.cropTop = 0;
						this.imageWidth = this.cropWidth;
						this.imageHeight = this.cropHeight;
					}
					
					//Update size input values
					this._uiSetSizeInputValues(this.cropWidth, this.cropHeight);
				} else { // move
					this.cropLeft = this.dragCropLeft;
					this.cropTop = this.dragCropTop;
					this.cropWidth = this.dragCropWidth;
					this.cropHeight = this.dragCropHeight;
				}
			
				e.preventDefault();
				
				//Hide overlay
				this.hideContentOverlay();
				
				//Reset cursor
				this.set('cursor', 4);
				
				this.resizeActive = false;
				this.moveActive = false;
				this.fixZoom();
			}
		},
		
		/**
		 * Handle document click outside of image
		 * 
		 * @param {Event} e Event facade object
		 * @protected
		 */
		documentClick: function (e) {
			var image = this.get('image');
			if (this.get('autoClose') && image && e.target && !e.target.closest('span.supra-icon') && !e.target.closest('span.supra-image') && !e.target.closest('.supra-background-editing')) {
				this.set('image', null);
			}
		},
		
		/**
		 * Returns pointer position relative to the window of attribute doc 
		 * 
		 * @param {Event} e Event facade object
		 * @returns {Array} Array with x and y coordinates of pointer
		 * @protected
		 */
		getPointerPosition: function (e) {
			var x          = 0,
				y          = 0,
				target     = e.target.getDOMNode(),
				doc_target = target.ownerDocument,
				doc        = this.get('doc'),
				offset     = this._iframeOffset,
				iframes    = null,
				iframe     = null,
				i          = 0,
				ii         = 0,
				tmp        = null;
			
			if (e.type.indexOf('touch') == -1) {
				// Mouse cursor
				x = e.clientX;
				y = e.clientY;
			} else {
				// Touch
				x = e.touches[0].clientX;
				y = e.touches[0].clientY;
			}
			
			if (doc !== doc_target) {
				// Adjust position by removing iframe position
				if (!offset) {
					// Find iframe
					iframes = Y.all('iframe');
					
					for (ii=iframes.size(); i < ii; i++) {
						tmp = iframes.item(i).getDOMNode();
						if ((tmp.contentDocument || tmp.contentWindow.document) === doc) {
							iframe = tmp; break;
						}
					}
					
					// Get offset
					offset = this._iframeOffset = (iframe ? Y.DOM.getXY(iframe) : [0, 0]);
				}
				
				x -= offset[0];
				y -= offset[1];
			}
			
			return [x, y];
		},
		
		/**
		 * Reset pointer cache
		 * 
		 * @protected
		 */
		resetPointerCache: function () {
			this._iframeOffset = null;
		},
		
		
		/* --------------------------------- Image --------------------------------- */
		
		
		/**
		 * Set up needed elements for image resizing
		 * 
		 * @param {Y.Node} image Image node
		 * @protected
		 */
		setUpImage: function (image) {
			var doc = this.get('doc'),
				resizeHandleNode = Y.Node(doc.createElement('SPAN')), // create in correct document
				imageContainerNode = Y.Node(doc.createElement('SPAN')),
				containerNode = image.ancestor(),
				width = containerNode.get('offsetWidth'),
				height = containerNode.get('offsetHeight');
			
			resizeHandleNode.addClass('supra-image-resize');
			containerNode.append(resizeHandleNode);
			resizeHandleNode.on('mousemove', this.setMouseCursor, this);
			resizeHandleNode.on('mouseleave', this.unsetMouseCursor, this);
			resizeHandleNode.on('mousedown', this.dragStart, this);
			this.set('resizeHandleNode', resizeHandleNode);
			
			imageContainerNode.addClass('supra-image-inner');
			containerNode.append(imageContainerNode);
			imageContainerNode.append(image);
			this.set('imageContainerNode', imageContainerNode);
			
			imageContainerNode.setStyles({
				'width': width,
				'height': height
			});
			containerNode.setStyles({
				'width': width,
				'height': height
			});
			
			containerNode.setAttribute('contentEditable', 'false');
			containerNode.addClass('supra-image-editing');
			
			this.imageWidth = image.get('offsetWidth');
			this.imageHeight = image.get('offsetHeight');
			this.cropWidth = width;
			this.cropHeight = height;
			this.cropLeft = Math.max(0, - parseInt(image.getStyle('marginLeft'), 10) || 0);
			this.cropTop  = Math.max(0, - parseInt(image.getStyle('marginTop'), 10) || 0);
			
			//If image is not loaded, then width and height could be 0
			if (!this.imageWidth || !this.imageHeight) {
				image.on('load', function (event, image) {
					this.imageWidth = image.get('offsetWidth');
					this.imageHeight = image.get('offsetHeight');
				}, this, image);
			}
			
			//Update size input values
			this._uiSetSizeInputValues(this.cropWidth, this.cropHeight);
			
			//Calculate min image width and height for zoom
			var maxImageWidth = this.get('maxImageWidth'),
				maxImageHeight = this.get('maxImageHeight'),
				minImageWidth = this.get('minCropWidth'),
				minImageHeight = this.get('minCropHeight'),
				ratio = maxImageWidth / maxImageHeight;
			
			if (minImageWidth / ratio < minImageHeight) {
				minImageWidth = Math.ceil(minImageHeight * ratio);
			}
			if (minImageHeight * ratio < minImageWidth) {
				minImageHeight = Math.ceil(minImageWidth / ratio);
			}
			
			this.minImageWidth = minImageWidth;
			this.minImageHeight = minImageHeight;
			
			this.setUpPanel();
		},
		
		/**
		 * Remove all created elements and events
		 * 
		 * @param {Y.Node} image Image node
		 * @param {Boolean} silent Image is removed, but another will be set shortly
		 * @protected
		 */
		tearDownImage: function (image, silent) {
			if (!image) return;
			
			if (!this.get('imageContainerNode')) {
				// Already teared down, 'resize' event triggered this again
				return;
			}
			
			var imageContainerNode = this.get('imageContainerNode'),
				resizeHandleNode = this.get('resizeHandleNode'),
				containerNode = imageContainerNode.ancestor();
			
			containerNode.append(image);
			containerNode.removeClass('supra-image-editing');
			containerNode.setStyles({
				'width': this.cropWidth,
				'height': this.cropHeight
			});
			
			resizeHandleNode.remove(true);
			this.set('resizeHandleNode', null);
			
			imageContainerNode.remove(true);
			this.set('imageContainerNode', null);
			
			if (this.adjustmentPanel) {
				this.adjustmentPanel.hide();
			}
			
			this.fire('resize', {
				'image': image,
				'cropLeft': this.cropLeft,
				'cropTop': this.cropTop,
				'cropWidth': this.cropWidth,
				'cropHeight': this.cropHeight,
				'imageWidth': this.imageWidth,
				'imageHeight': this.imageHeight,
				'silent': !!silent
			});
		},
		
		
		/* --------------------------------- Background --------------------------------- */
		
		
		/**
		 * Set up needed elements for background resizing
		 * 
		 * @param {Y.Node} image Node which background is resized
		 * @protected
		 */
		setUpBackground: function (image) {
			var doc = this.get('doc'),
				resizeHandleNode = Y.Node(doc.createElement('SPAN')), // create in correct document
				containerNode = image,
				node_width = image.get('offsetWidth'),
				node_height = image.get('offsetHeight');
			
			resizeHandleNode.addClass('supra-image-resize');
			resizeHandleNode.addClass('supra-image-resize-move');
			image.append(resizeHandleNode);
			resizeHandleNode.on('mousedown', this.dragStart, this);
			this.set('resizeHandleNode', resizeHandleNode);
			
			image.addClass('supra-background-editing');
			
			this.imageWidth = this.cropWidth = this.get('maxImageWidth');
			this.imageHeight = this.cropHeight = this.get('maxImageHeight');
			this.cropLeft = 0;
			this.cropTop = 0;
			
			var backgroundSize = image.getStyle('backgroundSize').match(/(\d+)px\s+(\d+)px/);
			if (backgroundSize) {
				this.cropWidth = this.imageWidth = parseInt(backgroundSize[1], 10) || this.cropWidth;
				this.cropHeight = this.imageHeight = parseInt(backgroundSize[2], 10) || this.cropHeight;
			}
			
			var backgroundPosition = image.getStyle('backgroundPosition').match(/(\-?\d+)(px|%)\s+(\-?\d+)(px|%)/),
				position = this.position;
			
			if (backgroundPosition) {
				if (position[0] == '0%') {
					this.cropLeft = Math.max(0, - parseInt(backgroundPosition[1], 10) || 0);
					this.cropWidth -= this.cropLeft;
				} else if (position[0] == '100%') {
					this.cropWidth = node_width - parseInt(backgroundPosition[1], 10);
				}
				
				if (position[1] == '0%') {
					this.cropTop = Math.max(0, - parseInt(backgroundPosition[3], 10) || 0);
					this.cropHeight -= this.cropTop;
				} else if (position[1] == '100%') {
					this.cropHeight = node_height - parseInt(backgroundPosition[3], 10);
				}
			}
			
			//Update size input values
			this._uiSetSizeInputValues(this.cropWidth, this.cropHeight);
			
			//Calculate min image width and height for zoom
			var maxImageWidth = this.get('maxImageWidth'),
				maxImageHeight = this.get('maxImageHeight'),
				minImageWidth = this.get('minCropWidth'),
				minImageHeight = this.get('minCropHeight'),
				ratio = maxImageWidth / maxImageHeight;
			
			if (minImageWidth / ratio < minImageHeight) {
				minImageWidth = Math.ceil(minImageHeight * ratio);
			}
			if (minImageHeight * ratio < minImageWidth) {
				minImageHeight = Math.ceil(minImageWidth / ratio);
			}
			
			this.minImageWidth = minImageWidth;
			this.minImageHeight = minImageHeight;
			
			this.setUpPanel();
			
			//On window resize update background position,
			//this is needed, because it may be aligned to the right or bottom
			this._resizeEventHandle = Y.on('resize', this._onWindowResize, window);
		},
		
		/**
		 * Remove all created elements and events
		 * 
		 * @param {Y.Node} image Node which background was resized
		 * @param {Boolean} silent Image is removed, but another will be set shortly
		 * @protected
		 */
		tearDownBackground: function (image, silent) {
			if (!image) return;
			
			var resizeHandleNode = this.get('resizeHandleNode');
			
			image.removeClass('supra-background-editing');
			
			resizeHandleNode.remove(true);
			this.set('resizeHandleNode', null);
			
			this.fire('resize', {
				'image': image,
				'cropLeft': this.cropLeft,
				'cropTop': this.cropTop,
				'cropWidth': this.cropWidth,
				'cropHeight': this.cropHeight,
				'imageWidth': this.imageWidth,
				'imageHeight': this.imageHeight,
				'silent': !!silent
			});
			
			if (this.adjustmentPanel) {
				this.adjustmentPanel.hide();
			}
			
			if (this._resizeEventHandle) {
				this._resizeEventHandle.detach();
				this._resizeEventHandle = null;
			}
		},
		
		/**
		 * Returns background position, which is off-shifted by 'position' attribute value
		 * 
		 * @param {Number} crop_left Optional, crop left position
		 * @param {Number} crop_top Optional, crop top position
		 * @param {Number} crop_width Optional, crop width
		 * @param {Number} crop_height Optional, crop height
		 * @param {Number} crop_width Optional, container node width
		 * @param {Number} crop_height Optional, container node height
		 * @returns {Array} Array with [crop_left, crop_top, crop_width, crop_height, style_left, style_top]
		 * @protected
		 */
		getBackgroundPosition: function (crop_left, crop_top, crop_width, crop_height, node_width, node_height) {
			if (this.get('mode') == ImageResizer.MODE_BACKGROUND) {
				var position = this.get('position'),
					attachment = this.get('attachment'),
					pos = null;
				
				if (typeof crop_left !== 'number') {
					crop_left = this.cropLeft;
				}
				if (typeof crop_top !== 'number') {
					crop_top = this.cropTop;
				}
				if (typeof crop_width !== 'number') {
					crop_width = this.cropWidth;
				}
				if (typeof crop_height !== 'number') {
					crop_height = this.cropHeight;
				}
				
				pos = Y.DataType.Image.position({
					'crop_left': crop_left,
					'crop_top': crop_top,
					'crop_width': crop_width,
					'crop_height': crop_height,
					'size_width': this.imageWidth,
					'size_height': this.imageHeight
				}, {
					'node': !node_width || !node_height || attachment === 'fixed' ? this.get('image') : null,
					'nodeFilter': null,
					'position': position,
					'attachment': attachment,
					'maxCropWidth': node_width,
					'maxCropHeight': node_height
				});
				
				return pos;
			} else {
				return [this.cropLeft, this.cropTop, this.cropWidth, this.cropHeight, -this.cropLeft + 'px', -this.cropTop + 'px'];
			}
		},
		
		/**
		 * On window resize update background position if on X or Y it's positioned
		 * on the right side of the screen
		 * 
		 * @protected
		 */
		_onWindowResize: function () {
			if (this.position[0] == '100%' || this.position[1] == '100%') {
				this.sync();
			}
		},
		
		
		/* --------------------------------- Icon --------------------------------- */
		
		
		/**
		 * Set up needed elements for background resizing
		 * 
		 * @param {Y.Node} image Node which background is resized
		 * @protected
		 */
		setUpIcon: function (image) {
			var doc = this.get('doc'),
				resizeHandleNode = Y.Node(doc.createElement('SPAN')), // create in correct document
				imageContainerNode = Y.Node(doc.createElement('SPAN')),
				containerNode = image.ancestor(),
				width = containerNode.get('offsetWidth'),
				height = containerNode.get('offsetHeight');
			
			resizeHandleNode.addClass('supra-image-resize');
			containerNode.append(resizeHandleNode);
			resizeHandleNode.on('mousemove', this.setMouseCursor, this);
			resizeHandleNode.on('mouseleave', this.unsetMouseCursor, this);
			resizeHandleNode.on('mousedown', this.dragStart, this);
			this.set('resizeHandleNode', resizeHandleNode);
			
			imageContainerNode.addClass('supra-image-inner');
			containerNode.append(imageContainerNode);
			imageContainerNode.append(image);
			this.set('imageContainerNode', imageContainerNode);
			
			imageContainerNode.setStyles({
				'width': width,
				'height': height
			});
			containerNode.setStyles({
				'width': width,
				'height': height
			});
			
			containerNode.setAttribute('contentEditable', 'false');
			containerNode.addClass('supra-icon-editing');
			
			this.cropWidth = this.imageWidth = image.get('offsetWidth');
			this.cropHeight = this.imageHeight = image.get('offsetHeight');
			this.cropLeft = this.cropTop = 0;
			
			//SVGAnimateLength object
			if (!this.cropWidth && !this.cropHeight) {
				this.cropWidth = this.imageWidth = Supra.getObjectValue(image.get('width'), ['baseVal', 'value']) || 0;
				this.cropHeight = this.imageHeight = Supra.getObjectValue(image.get('height'), ['baseVal', 'value']) || 0;
			}
			
			//Update size input values
			this._uiSetSizeInputValues(this.cropWidth, this.cropHeight);
			
			//Calculate min image width and height for zoom
			var maxImageWidth = this.get('maxImageWidth'),
				maxImageHeight = this.get('maxImageHeight'),
				minImageWidth = this.get('minCropWidth'),
				minImageHeight = this.get('minCropHeight'),
				ratio = maxImageWidth / maxImageHeight;
			
			if (minImageWidth / ratio < minImageHeight) {
				minImageWidth = Math.ceil(minImageHeight * ratio);
			}
			if (minImageHeight * ratio < minImageWidth) {
				minImageHeight = Math.ceil(minImageWidth / ratio);
			}
			
			this.minImageWidth = minImageWidth;
			this.minImageHeight = minImageHeight;
			
			this.setUpPanel();
		},
		
		/**
		 * Remove all created elements and events
		 * 
		 * @param {Y.Node} image Node which background was resized
		 * @param {Boolean} silent Image is removed, but another will be set shortly
		 * @protected
		 */
		tearDownIcon: function (image, silent) {
			if (!image) return;
			
			if (!this.get('imageContainerNode')) {
				// Already teared down, 'resize' event triggered this again
				return;
			}
			
			var imageContainerNode = this.get('imageContainerNode'),
				resizeHandleNode = this.get('resizeHandleNode'),
				containerNode = imageContainerNode.ancestor();
			
			containerNode.append(image);
			containerNode.removeClass('supra-icon-editing');
			containerNode.setStyles({
				'width': this.imageWidth,
				'height': this.imageHeight
			});
			
			resizeHandleNode.remove(true);
			this.set('resizeHandleNode', null);
			
			imageContainerNode.remove(true);
			this.set('imageContainerNode', null);
			
			if (this.adjustmentPanel) {
				this.adjustmentPanel.hide();
			}
			
			this.fire('resize', {
				'image': image,
				'imageWidth': this.imageWidth,
				'imageHeight': this.imageHeight,
				'silent': !!silent
			});
		},
		
		
		/* --------------------------------- Overlay --------------------------------- */
		
		
		showContentOverlay: function () {
			var overlay = this.contentOverlay,
				doc = this.get('doc');
			
			if (!doc) {
				return;
			}
			if (!overlay) {
				overlay = Y.Node.create('<div class="yui3-box-reset ' + ImageResizer.CLASS_NAME + '-overlay"></div>');
				Y.Node(doc.body).append(overlay);
				this.contentOverlay = overlay;
			} else {
				overlay.removeClass('hidden');
			}
			
			return overlay;
		},
		
		hideContentOverlay: function () {
			var overlay = this.contentOverlay;
			if (overlay) {
				overlay.addClass('hidden');
			}
		},
		
		
		/* --------------------------------- Attributes --------------------------------- */
		
		
		/**
		 * Image attribute setter
		 * 
		 * @param {Y.Node} image
		 * @return New attribute value
		 * @type {Y.Node}
		 * @protected
		 */
		_setImageAttr: function (image) {
			return image ? (image.getDOMNode ? image : Y.Node(image)) : null;
		},
		
		/**
		 * Handle image attribute change
		 *
		 * @param {Object} e Event facade object
		 * @protected
		 */
		_uiImageAttrChange: function (e) {
			if ((!e.newVal && !e.prevVal) || (e.newVal && e.prevVal && e.newVal.compareTo(e.prevVal))) return;
			
			var image = e.newVal,
				prevImage = e.prevVal,
				doc = image ? image.getDOMNode().ownerDocument : null,
				silent = !!image,
				overlay = this.contentOverlay;
			
			if (overlay) {
				overlay.destroy(true);
				this.contentOverlay = null;
			}
			
			if (prevImage) {
				
				if (this.get('mode') == ImageResizer.MODE_IMAGE) {
					this.tearDownImage(prevImage, silent);
				} else if (this.get('mode') == ImageResizer.MODE_ICON) {
					this.tearDownIcon(prevImage, silent);
				} else {
					this.tearDownBackground(prevImage, silent);
				}
			}
			
			if (this.eventClick) {
				this.eventClick.detach();
				this.eventClick = null;
			}
			
			if (image) {
				this.set('doc', doc);
				
				if (this.get('mode') == ImageResizer.MODE_IMAGE) {
					this.setUpImage(image);
				} else if (this.get('mode') == ImageResizer.MODE_ICON) {
					this.setUpIcon(image);
				} else {
					this.setUpBackground(image);
				}
				
				this.eventClick = Y.Node(doc).on('mousedown', this.documentClick, this);
			}
			
			return image;
		},
		
		/**
		 * Cursor attribute value setter
		 * Set classname to show which one is choosen
		 * 
		 * @param {Number} cursor
		 * @return New attribute value
		 * @type {Number}
		 * @protected
		 */
		_setCursorAttr: function (cursor) {
			var node = this.get('resizeHandleNode');
			if (node) {
				node.replaceClass(
					this.getCursorClassName(this.get('cursor')),
					this.getCursorClassName(cursor)
				);
			}
			return cursor;
		},
		
		/**
		 * Mode attribute setter
		 * 
		 * @param {String} cursor
		 * @returns {String} New attribute value
		 * @protected
		 */
		_setMode: function (mode) {
			var button = this.buttonRatio;
			
			// Width/height ratio should be locked
			if (button) {
				if (mode == ImageResizer.MODE_IMAGE) {
					button.set('disabled', false);
				} else {
					this.set('ratioLocked', true);
					button.set('disabled', true);
				}
			}
			
			return mode;
		},
		
		/**
		 * Ratio locked attribute setter
		 * 
		 * @param {Boolean} locked
		 * @returns {Boolean} New attribute value
		 * @protected
		 */
		_setRatioLocked: function (locked) {
			locked = !!locked;
			
			var button = this.buttonRatio;
			if (button) {
				button.toggleClass('su-button-locked', locked);
			}
			
			return locked;
		},
		
		/**
		 * Position attribute setter
		 * 
		 * @param {String} position Attribute value
		 * @returns {String} New attribute value
		 */
		_setPosition: function (position) {
			position = String(position || '0% 0%');
			
			var match = position.match(/(\d+\%)\s(\d+%)/) || ['', '0%', '0%'];
			this.position = [match[1], match[2]];
			
			return position;
		},
		
		/**
		 * Attachment attribute setter
		 * 
		 * @param {String} attachment Attribute value
		 * @returns {String} New attribute value
		 */
		_setAttachment: function (attachment) {
			if (attachment !== 'scroll' && attachment !== 'fixed') {
				return 'scroll';
			} else {
				return attachment;
			}
		},
		
		/**
		 * Set size input values
		 * 
		 * @param {Object} width
		 * @param {Object} height
		 * @protected
		 */
		_uiSetSizeInputValues: function (width, height) {
			this.sizeInputValuesChanging = true;
			
			//Update size input values
			if (this.inputWidth) {
				this.inputWidth.set('value', width);
				this.inputHeight.set('value', height);
			}
			
			this.sizeInputValuesChanging = false;
		},
		
		/**
		 * Disable or enable zoom controls
		 * 
		 * @param {Object} disabled
		 */
		_uiSetZoomDisabled: function (disabled) {
			var zoomSlider = this.zoomSlider,
				buttonZoomIn = this.buttonZoomIn,
				buttonZoomOut = this.buttonZoomOut;
			
			if (zoomSlider) {
				if (disabled) {
					zoomSlider.set('disabled', true);
					buttonZoomIn.set('disabled', true);
					buttonZoomOut.set('disabled', true);
				} else {
					zoomSlider.set('disabled', false);
					buttonZoomIn.set('disabled', false);
					buttonZoomOut.set('disabled', false);
				}
			}
		}
		
		
	});
	
	Supra.ImageResizer = ImageResizer;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.panel', 'supra.slider', 'dd-plugin']});
