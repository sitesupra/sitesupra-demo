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
		this.init.apply(this, arguments);
	}
	
	ImageResizer.MODE_IMAGE = 0;
	ImageResizer.MODE_BACKGROUND = 1;
	ImageResizer.MODE_ICON = 2;
	
	ImageResizer.NAME = 'imageresizer';
	ImageResizer.CSS_PREFIX = 'su-' + ImageResizer.NAME;
	ImageResizer.CLASS_NAME = Y.ClassNameManager.getClassName(ImageResizer.NAME);
	
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
		 * @private
		 */
		imageWidth: 0,
		
		/**
		 * Image height after zoom
		 * Crop top + crop height can't exceed this
		 * @type {Number}
		 * @private
		 */
		imageHeight: 0,
		
		/**
		 * Minimal image width, based on minCropWidth and image ratio
		 * @type {Number}
		 * @private
		 */
		minImageWidth: 0,
		
		/**
		 * Minimal image height, based on minCropHeight and image ratio
		 * @type {Number}
		 * @private
		 */
		minImageHeight: 0,
		
		
		/**
		 * Image crop width
		 * @type {Number}
		 * @private
		 */
		cropWidth: 0,
		
		/**
		 * Image crop width
		 * @type {Number}
		 * @private
		 */
		cropHeight: 0,
		
		/**
		 * Image left crop
		 * @type {Number}
		 * @private
		 */
		cropLeft: 0,
		
		/**
		 * Image top crop
		 * @type {Number}
		 * @private
		 */
		cropTop: 0,
		
		/**
		 * Image zoom level
		 * @type {Number}
		 * @private
		 */
		zoom: 1,
		
		
		
		/**
		 * Drag start mouse X position
		 * @type {Number}
		 * @private
		 */
		mouseStartX: null,
		
		/**
		 * Drag start mouse Y position
		 * @type {Number}
		 * @private
		 */
		mouseStartY: null,
		
		/**
		 * Drag start image width
		 * @type {Number}
		 * @private
		 */
		dragStartW: null,
		
		/**
		 * Drag start image height
		 * @type {Number}
		 * @private
		 */
		dragStartH: null,
		
		/**
		 * Drag image width
		 * @type {Number}
		 * @private
		 */
		dragW: null,
		
		/**
		 * Drag image height
		 * @type {Number}
		 * @private
		 */
		dragH: null,
		
		/**
		 * Drag image left offset
		 * @type {Number}
		 * @private
		 */
		dragCropLeft: null,
		
		/**
		 * Drag image top offset
		 * @type {Number}
		 * @private
		 */
		dragCropTop: null,
		
		
		/**
		 * Currently resizing crop
		 * @type {Boolean}
		 * @private
		 */
		resizeActive: false,
		
		/**
		 * Currently moving
		 * @type {Boolean}
		 * @private
		 */
		moveActive: false,
		
		/**
		 * Event listener object for mouse move event
		 * @type {Object}
		 * @private
		 */
		eventMove: null,
		
		/**
		 * Event listener object for mouse move event on main document
		 * @type {Object}
		 * @private
		 */
		eventMoveMain: null,
		
		/**
		 * Event listener object for mouse up event
		 * @type {Object}
		 * @private
		 */
		eventDrop: null,
		
		/**
		 * Event listener object for mouse up event on main document
		 * @type {Object}
		 * @private
		 */
		eventDropMain: null,
		
		/**
		 * Event listener object for document click event
		 * @type {Object}
		 * @private
		 */
		eventClick: null,
		
		/**
		 * Zoom and size panel
		 * Supra.Panel instance
		 * @type {Object}
		 * @private
		 */
		adjustmentPanel: null,
		
		/**
		 * Zoom slider
		 * Supra.Slider instance
		 * @type {Object}
		 * @private
		 */
		zoomSlider: null,
		
		/**
		 * Zoom in button
		 * Supra.Button instance
		 * @type {Object}
		 * @private
		 */
		buttonZoomIn: null,
		
		/**
		 * Zoom out button
		 * Supra.Button instance
		 * @type {Object}
		 * @private
		 */
		buttonZoomOut: null,
		
		/**
		 * Width input
		 * Supra.Input.String instance
		 * @type {Object}
		 * @private
		 */
		inputWidth: null,
		
		/**
		 * Height input
		 * Supra.Input.String instance
		 * @type {Object}
		 * @private
		 */
		inputHeight: null,
		
		/**
		 * Width/height ratio lock button
		 * Supra.Button instance
		 * @type {Object}
		 * @private
		 */
		buttonRatio: null,
		
		/**
		 * Size input values are changed by something other than
		 * direct input.
		 * @type {Boolean}
		 * @private
		 */
		sizeInputValuesChanging: false,
		
		
		/**
		 * On destruction revert changes to image, clean up
		 * 
		 * @private
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
			
			this.zoomSlider = null;
			this.inputWidth = null;
			this.inputHeight = null;
			this.buttonRatio = null;
			this.adjustmentPanel = null;
			this.buttonZoomIn = null;
			this.buttonZoomOut = null;
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
		 * @private
		 */
		createPanel: function () {
			if (this.adjustmentPanel) return;
			
			var panel = this.adjustmentPanel = new Supra.Panel({
				'zIndex': 100,
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
					'value': this.imageWidth
				}),
				inputHeight = new Supra.Input.String({
					'label': Supra.Intl.get(['inputs', 'resize_height']),
					'style': 'size',
					'valueMask': /^[0-9]*$/,
					'value': this.imageHeight
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
			this.zoomSlider.set('value', zoom);
		},
		
		/**
		 * Handle zoom change
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		zoomChange: function (e) {
			if (e.silent || !this.get('image')) return;
			
			var image = this.get('image'),
				zoom = e.newVal,
				size = this.zoomToSize(zoom),
				ratio = null,
				containerNode = null,
				imageContainerNode = null;
			
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
				this.cropWidth = Math.max(0, this.imageWidth - this.cropLeft);
				this.cropHeight = Math.max(0, this.imageHeight - this.cropTop);
				
				image.setStyles({
					'backgroundSize': this.imageWidth + 'px ' + this.imageHeight + 'px'
				});
				
				//Update size input values
				this._uiSetSizeInputValues(this.imageWidth, this.imageHeight);
			}
		},
		
		/**
		 * Fix zoom when finished dragging slider
		 * 
		 * @private
		 */
		fixZoom: function () {
			this.zoomSlider.set('value', this.sizeToZoom(this.imageWidth, this.imageHeight), {silent: true});
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
		 * Handle width change
		 * 
		 * @private
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
		 * @private
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
		 * @private
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
		 * @private
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
		 * @private
		 */
		sizeChangeBackground: function (sizeX, sizeY, input_type) {
			var image  = this.get('image'),
				width  = sizeX,
				height = sizeY,
				maxImageWidth = this.get('maxImageWidth'),
				maxImageHeight = this.get('maxImageHeight'),
				minImageWidth = this.minImageWidth,
				minImageHeight = this.minImageHeight,
				ratio = (maxImageWidth / maxImageHeight);
			
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
				
				this.cropWidth = Math.max(0, width - this.cropLeft);
				this.cropHeight = Math.max(0, height - this.cropTop);
				
				image.setStyles({
					'backgroundSize': this.imageWidth + 'px ' + this.imageHeight + 'px'
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
		 * @private
		 */
		toggleRatioLock: function () {
			this.set('ratioLocked', !this.get('ratioLocked'));
		},
		
		
		/* --------------------------------- Drag --------------------------------- */
		
		
		/**
		 * Start dragging
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		dragStart: function (e) {
			this.resetPointerCache();
			
			var pointer = this.getPointerPosition(e),
				clientX = pointer[0],
				clientY = pointer[1],
				doc = this.get('doc');
			
			if (this.resizeActive || this.moveActive) {
				//If user released mouse outside browser
				this.dragEnd(e);
			}
			
			this.eventDrop = Y.Node(doc).on('mouseup', this.dragEnd, this);
			
			if (document !== doc) {
				this.eventDropMain = Y.Node(document).on('mouseup', this.dragEnd, this);
			}
			
			this.dragCropLeft = this.cropLeft;
			this.dragCropTop = this.cropTop;
			this.mouseStartX = clientX;
			this.mouseStartY = clientY;
			
			if (this.get('cursor') < 4) {
				//Resize
				this.resizeActive = true;
				
				if (this.get('mode') == ImageResizer.MODE_ICON) {
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
				
				this.dragStartW = this.dragW = this.cropWidth;
				this.dragStartH = this.dragH = this.cropHeight;
			} else if (this.get('mode') != ImageResizer.MODE_ICON) {
				//Move
				this.moveActive = true;
				this.eventMove = Y.Node(this.get('doc')).on('mousemove', this.dragMove, this);
				
				if (document !== doc) {
					this.eventMoveMain = Y.Node(document).on('mousemove', this.dragMove, this);
				}
				
				this.dragStartW = this.dragW = this.cropLeft;
				this.dragStartH = this.dragH = this.cropTop;
			}
			
			e.preventDefault();
		},
		
		/**
		 * Handle mouse move while dragging
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		dragMove: function (e) {
			var pointer = this.getPointerPosition(e),
				clientX = pointer[0],
				clientY = pointer[1],
				
				cursor = this.get('cursor'),
				deltaX = (clientX - this.mouseStartX) * (cursor == 0 || cursor == 3 || cursor == 4 ? -1 : 1),
				deltaY = (clientY - this.mouseStartY) * (cursor == 0 || cursor == 1 || cursor == 4 ? -1 : 1),
				sizeX  = this.dragStartW + deltaX,
				sizeY  = this.dragStartH + deltaY,
				mode   = this.get('mode');
			
			if (this.moveActive) {
				var node = this.get('image'),
					cropWidth = this.cropWidth,
					cropHeight = this.cropHeight,
					imageHeight = this.imageHeight,
					imageWidth = this.imageWidth;
				
				if (!node) return;
				
				if (sizeX < 0) sizeX = 0;
				if (sizeY < 0) sizeY = 0;
				
				if (mode == ImageResizer.MODE_IMAGE) {
					// Image
					if (sizeX + cropWidth > imageWidth) {
						sizeX = Math.max(0, imageWidth - cropWidth);
					}
					if (sizeY + cropHeight > imageHeight) {
						sizeY = Math.max(0, imageHeight - cropHeight);
					}
				} else {
					// Background
					if (sizeX > imageWidth) {
						sizeX = imageWidth;
					}
					if (sizeY > imageHeight) {
						sizeY = imageHeight;
					}
				}
				
				if (sizeX != this.dragCropLeft || sizeY != this.dragCropTop) {
					this.dragCropLeft = sizeX;
					this.dragCropTop  = sizeY;
					
					if (mode == ImageResizer.MODE_IMAGE) {
						node.setStyle('margin', - sizeY + 'px 0 0 -' + sizeX + 'px');
					} else {
						node.setStyle('backgroundPosition', - sizeX + 'px -' + sizeY + 'px');
					}
				}
			}
		},
		
		/**
		 * Handle mouse move while resizing
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		dragResize: function (e) {
			var pointer = this.getPointerPosition(e),
				clientX = pointer[0],
				clientY = pointer[1],
				
				cursor = this.get('cursor'),
				deltaX = (clientX - this.mouseStartX) * (cursor == 0 || cursor == 3 || cursor == 4 ? -1 : 1),
				deltaY = (clientY - this.mouseStartY) * (cursor == 0 || cursor == 1 || cursor == 4 ? -1 : 1),
				sizeX  = this.dragStartW + deltaX,
				sizeY  = this.dragStartH + deltaY,
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
				
				if (this.dragW != sizeX || this.dragH != sizeY) {
					this.dragW = sizeX;
					this.dragH = sizeY;
					
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
		 * @private
		 */
		dragIconResize: function (e) {
			var pointer = this.getPointerPosition(e),
				clientX = pointer[0],
				clientY = pointer[1],
				
				cursor = this.get('cursor'),
				deltaX = (clientX - this.mouseStartX) * (cursor == 0 || cursor == 3 || cursor == 4 ? -1 : 1),
				deltaY = (clientY - this.mouseStartY) * (cursor == 0 || cursor == 1 || cursor == 4 ? -1 : 1),
				delta  = Math.max(deltaX, deltaY),
				sizeX  = this.dragStartW + delta,
				sizeY  = this.dragStartH + delta;
			
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
				
				if (this.dragW != sizeX || this.dragH != sizeY) {
					this.dragW = sizeX;
					this.dragH = sizeY;
					
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
		 * @private
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
					this.cropWidth = this.dragW;
					this.cropHeight = this.dragH;
					
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
				}
			
				e.preventDefault();
				
				//Reset cursor
				this.set('cursor', 4);
			}
			
			this.resizeActive = false;
			this.moveActive = false;
			this.fixZoom();
		},
		
		/**
		 * Handle document click outside of image
		 * 
		 * @param {Event} e Event facade object
		 * @private
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
		 * @private
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
				// @TODO In the future
				// x = e.touches[0].clientX;
				// y = e.touches[0].clientY;
			}
			
			if (doc !== doc_target) {
				// Adjust position by removing iframe position
				if (!offset) {
					// Find iframe
					iframes = Y.all('iframe');
					
					for (ii=iframes.size(); i<ii; i++) {
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
		 * @private
		 */
		resetPointerCache: function () {
			this._iframeOffset = null;
		},
		
		
		/* --------------------------------- Image --------------------------------- */
		
		
		/**
		 * Set up needed elements for image resizing
		 * 
		 * @param {Y.Node} image Image node
		 * @private
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
			
			image.setAttribute('unselectable', 'on');
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
		 * @private
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
			
			image.removeAttribute('unselectable');
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
		 * @private
		 */
		setUpBackground: function (image) {
			var doc = this.get('doc'),
				resizeHandleNode = Y.Node(doc.createElement('SPAN')), // create in correct document
				containerNode = image;
			
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
			
			var backgroundPosition = image.getStyle('backgroundPosition').match(/(\-?\d+)px\s+(\-?\d+)px/);
			if (backgroundPosition) {
				this.cropLeft = Math.max(0, - parseInt(backgroundPosition[1], 10) || 0);
				this.cropTop = Math.max(0, - parseInt(backgroundPosition[2], 10) || 0);
				this.cropWidth -= this.cropLeft;
				this.cropHeight -= this.cropTop;
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
		 * @private
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
		},
		
		
		/* --------------------------------- Icon --------------------------------- */
		
		
		/**
		 * Set up needed elements for background resizing
		 * 
		 * @param {Y.Node} image Node which background is resized
		 * @private
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
			
			image.setAttribute('unselectable', 'on');
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
		 * @private
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
			
			image.removeAttribute('unselectable');
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
		
		
		/* --------------------------------- Attributes --------------------------------- */
		
		
		/**
		 * Image attribute setter
		 * 
		 * @param {Y.Node} image
		 * @return New attribute value
		 * @type {Y.Node}
		 */
		_setImageAttr: function (image) {
			var image = image ? (image.getDOMNode ? image : Y.Node(image)) : null,
				doc = image ? image.getDOMNode().ownerDocument : null,
				silent = !!image;
			
			if (this.get('image')) {
				
				if (this.get('mode') == ImageResizer.MODE_IMAGE) {
					this.tearDownImage(this.get('image'), silent);
				} else if (this.get('mode') == ImageResizer.MODE_ICON) {
					this.tearDownIcon(this.get('image'), silent);
				} else {
					this.tearDownBackground(this.get('image'), silent);
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
		 * Set size input values
		 * 
		 * @param {Object} width
		 * @param {Object} height
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
		
		
		
	});
	
	Supra.ImageResizer = ImageResizer;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.panel', 'supra.slider', 'dd-plugin']});