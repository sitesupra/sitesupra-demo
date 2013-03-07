YUI().add("supra.imageresizer", function (Y) {
	
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
	
	ImageResizer.NAME = "imageresizer";
	ImageResizer.CSS_PREFIX = "su-" + ImageResizer.NAME;
	ImageResizer.CLASS_NAME = Y.ClassNameManager.getClassName(ImageResizer.NAME);
	
	ImageResizer.ATTRS = {
		// Image element
		"image": {
			value: null,
			setter: "_setImageAttr"
		},
		
		// Maximal image width (original image width)
		"maxImageWidth": {
			value: 0
		},
		// Maximal image height (original image height)
		"maxImageHeight": {
			value: 0
		},
		
		// Minimal width
		"minCropWidth": {
			value: 32
		},
		// Maximal width
		"maxCropWidth": {
			value: 0
		},
		// Minimal height
		"minCropHeight": {
			value: 32
		},
		// Maximal height
		"maxCropHeight": {
			value: 0
		},
		
		// Image document element
		"doc": {
			value: null
		},
		// Node used for resize handles
		"resizeHandleNode": {
			value: null
		},
		// Image container node
		"imageContainerNode": {
			value: null,
		},
		// Image size label node
		"sizeLabelNode": {
			value: null
		},
		// Image size label text
		"sizeLabel": {
			value: [0, 0],
			setter: "_setSizeLabelAttr"
		},
		// Cursor:  0 - nw, 1 - ne, 2 - se, 3 - sw, 4 - move
		"cursor": {
			value: 4,
			setter: "_setCursorAttr"
		},
		
		// Stop editing when clicked outside
		"autoClose": {
			value: true
		},
		// Mode: 0 - image, 1 - background
		"mode": {
			value: ImageResizer.MODE_IMAGE
		},
		
		// Resize crop region to smaller on image zoom change if needed
		"allowZoomResize": {
			value: false
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
		 * Event listener object for mouse up event
		 * @type {Object}
		 * @private
		 */
		eventDrop: null,
		
		/**
		 * Event listener object for document click event
		 * @type {Object}
		 * @private
		 */
		eventClick: null,
		
		/**
		 * Zoom panel
		 * Supra.Panel instance
		 * @type {Object}
		 * @private
		 */
		zoomPanel: null,
		
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
		 * On destruction revert changes to image, clean up
		 * 
		 * @private
		 */
		destructor: function () {
			if (this.get("image")) {
				this.set("image", null);
			}
			if (this.zoomSlider) {
				this.zoomSlider.destroy();
			}
			if (this.zoomPanel) {
				this.zoomPanel.destroy();
			}
			if (this.buttonZoomIn) {
				this.buttonZoomIn.destroy();
			}
			if (this.buttonZoomOut) {
				this.buttonZoomOut.destroy();
			}
			
			this.zoomSlider = null;
			this.zoomPanel = null;
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
				var x = e._event.layerX,
					y = e._event.layerY,
					w = this.cropWidth,
					h = this.cropHeight,
					handleSize = RESIZE_HANDLE_SIZE,
					cursor = 4;
				
				if (x < handleSize) {
					if (y < handleSize) {
						cursor = 0;
					} else if (y > h - handleSize) {
						cursor = 3;
					}
				} else if (x > w - handleSize) {
					if (y < handleSize) {
						cursor = 1;
					} else if (y > h - handleSize) {
						cursor = 2;
					}
				}
				
				if (this.get("cursor") != cursor) {
					this.set("cursor", cursor);
				}
			}
		},
		
		/**
		 * Unset mouse cursor
		 */
		unsetMouseCursor: function (e) {
			if (!this.resizeActive && !this.moveActive && this.get("cursor") != 4) {
				this.set("cursor", 4);
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
					return "supra-image-resize-nw";
				case 1:
					return "supra-image-resize-ne";
				case 2:
					return "supra-image-resize-se";
				case 3:
					return "supra-image-resize-sw";
				default:
					return this.get("mode") == ImageResizer.MODE_IMAGE ? "" : "supra-image-resize-move";
			}
		},
		
		
		/* --------------------------------- Zoom --------------------------------- */
		
		
		/**
		 * Create panel and slider
		 * 
		 * @private
		 */
		createPanel: function () {
			if (this.zoomPanel) return;
			
			var panel = this.zoomPanel = new Supra.Panel({
				"zIndex": 100,
				"plugins": [ Y.Plugin.Drag ],
				"style": "dark"
				/*"alignPosition": "T",
				"arrowVisible": true*/
			});
			panel.render();
			
			var boundingBox = panel.get("boundingBox"),
				contentBox = panel.get("contentBox"),
				slider = this.zoomSlider = new Supra.Slider({
					"axis": "x",
					"min": 0,
					"max": 100,
					"value": 100,
					"length": 250
				}),
				zoomIn = this.buttonZoomIn = new Supra.Button({
					"label": "",
					"style": "zoom-in"
				}),
				zoomOut = this.buttonZoomOut = new Supra.Button({
					"label": "",
					"style": "zoom-out"
				});
			
			slider.render(contentBox);
			slider.on("valueChange", this.zoomChange, this);
			
			// 
			zoomIn.render(contentBox);
			zoomOut.render(contentBox);
			
			zoomIn.on('click', function () {
				this.set('value', (this.get('value') + this.get('majorStep')));
			}, slider);
			zoomOut.on('click', function () {
				this.set('value', (this.get('value') - this.get('majorStep')));
			}, slider);
			
			boundingBox.addClass("su-imageresizer");
		},
		
		/**
		 * Position panel, set current zoom value
		 */
		setUpPanel: function () {
			if (!this.zoomPanel) {
				this.createPanel();
			}
			
			this.zoomPanel.set("alignTarget", this.get("image"));
			this.zoomPanel.show();
			this.zoomPanel.centered();
			
			//Set zoom value
			var zoom = this.sizeToZoom(this.imageWidth, this.imageHeight);
			this.zoomSlider.set("value", zoom);
		},
		
		/**
		 * Handle zoom change
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		zoomChange: function (e) {
			if (e.silent || !this.get("image")) return;
			
			var image = this.get("image"),
				zoom = e.newVal,
				size = this.zoomToSize(zoom),
				ratio = null;
			
			this.imageWidth = ~~size[0];
			this.imageHeight = ~~size[1];
			
			if (this.get("mode") == ImageResizer.MODE_IMAGE) {
				ratio = (this.get("maxImageWidth") / this.get("maxImageHeight"));
				
				if (this.get("allowZoomResize")) {
					//Resize crop if needed
					if (this.cropTop + this.cropHeight > this.imageHeight) {
						this.cropTop = this.imageHeight - this.cropHeight;
						if (this.cropTop < 0) {
							this.cropHeight += this.cropTop;
							this.cropTop = 0;
						}
					}
					if (this.cropLeft + this.cropWidth > this.imageWidth) {
						this.cropLeft = this.imageWidth - this.cropWidth;
						if (this.cropLeft < 0) {
							this.cropWidth += this.cropLeft;
							this.cropLeft = 0;
						}
					}
					
					this.get("imageContainerNode").setStyles({
						"width": this.cropWidth + "px",
						"height": this.cropHeight + "px"
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
					"margin": - this.cropTop + "px 0 0 -" + this.cropLeft + "px",
					"width": this.imageWidth + "px",
					"height": this.imageHeight + "px"
				});
				image.setAttribute("width", this.imageWidth);
				image.setAttribute("height", this.imageHeight);
			} else {
				this.cropWidth = this.imageWidth - this.cropLeft;
				this.cropHeight = this.imageHeight - this.cropTop;
				
				image.setStyles({
					"backgroundSize": this.imageWidth + "px " + this.imageHeight + "px"
				});
			}
			
			//Update image size label
			this.set("sizeLabel", [this.cropWidth, this.cropHeight]);
		},
		
		/**
		 * Fix zoom when finished dragging slider
		 * 
		 * @private
		 */
		fixZoom: function () {
			this.zoomSlider.set("value", this.sizeToZoom(this.imageWidth, this.imageHeight), {silent: true});
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
				
			if (this.get('allowZoomResize') || this.get('mode') == ImageResizer.MODE_BACKGROUND) {
				minImageWidth = this.minImageWidth;
				minImageHeight = this.minImageHeight;
			} else {
				minImageWidth = this.cropWidth;
				minImageHeight = this.cropHeight;
			}
			
			if (this.get("maxImageWidth") - minImageWidth < this.get("maxImageHeight") - minImageHeight) {
				return Math.round((width - minImageWidth) / (this.get("maxImageWidth") - minImageWidth) * 100);
			} else {
				return Math.round((height - minImageHeight) / (this.get("maxImageHeight") - minImageHeight) * 100);
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
			
			if (this.get('allowZoomResize') || this.get('mode') == ImageResizer.MODE_BACKGROUND) {
				minImageWidth = this.minImageWidth;
				minImageHeight = this.minImageHeight;
			} else {
				minImageWidth = this.cropWidth;
				minImageHeight = this.cropHeight;
			}
			
			if (this.imageWidth > this.imageHeight) {
				ratio = (this.get("maxImageWidth") / this.get("maxImageHeight"));
				width = (this.get("maxImageWidth") - minImageWidth) * zoom / 100 + minImageWidth;
				height = width / ratio;
			} else {
				ratio = (this.get("maxImageHeight") / this.get("maxImageWidth"));
				height = (this.get("maxImageHeight") - minImageHeight) * zoom / 100 + minImageHeight;
				width = height / ratio;
			}
			
			return [Math.round(width), Math.round(height)];
		},
		
		
		/* --------------------------------- Drag --------------------------------- */
		
		
		/**
		 * Start dragging
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		dragStart: function (e) {
			if (this.resizeActive || this.moveActive) {
				//If user released mouse outside browser
				this.dragEnd(e);
			}
			
			this.eventDrop = Y.Node(this.get("doc")).on("mouseup", this.dragEnd, this);
			
			this.dragCropLeft = this.cropLeft;
			this.dragCropTop = this.cropTop;
			this.mouseStartX = e.clientX;
			this.mouseStartY = e.clientY;
			
			if (this.get("cursor") < 4) {
				//Resize
				this.resizeActive = true;
				this.eventMove = Y.Node(this.get("doc")).on("mousemove", this.dragResize, this);
				
				this.dragStartW = this.dragW = this.cropWidth;
				this.dragStartH = this.dragH = this.cropHeight;
			} else {
				//Move
				this.moveActive = true;
				this.eventMove = Y.Node(this.get("doc")).on("mousemove", this.dragMove, this);
				
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
			var cursor = this.get("cursor"),
				deltaX = (e.clientX - this.mouseStartX) * (cursor == 0 || cursor == 3 || cursor == 4 ? -1 : 1),
				deltaY = (e.clientY - this.mouseStartY) * (cursor == 0 || cursor == 1 || cursor == 4 ? -1 : 1),
				sizeX  = this.dragStartW + deltaX,
				sizeY  = this.dragStartH + deltaY,
				mode   = this.get("mode");
			
			if (this.moveActive) {
				var node = this.get("image"),
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
						sizeX = imageWidth - cropWidth;
					}
					if (sizeY + cropHeight > imageHeight) {
						sizeY = imageHeight - cropHeight;
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
						node.setStyle("margin", - sizeY + "px 0 0 -" + sizeX + "px");
					} else {
						node.setStyle("backgroundPosition", - sizeX + "px -" + sizeY + "px");
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
			var cursor = this.get("cursor"),
				deltaX = (e.clientX - this.mouseStartX) * (cursor == 0 || cursor == 3 || cursor == 4 ? -1 : 1),
				deltaY = (e.clientY - this.mouseStartY) * (cursor == 0 || cursor == 1 || cursor == 4 ? -1 : 1),
				sizeX  = this.dragStartW + deltaX,
				sizeY  = this.dragStartH + deltaY,
				mode   = this.get("mode");
			
			if (this.resizeActive) {
				var node = this.get("imageContainerNode"),
					minW = this.get("minCropWidth"),
					maxW = this.get("maxCropWidth"),
					minH = this.get("minCropHeight"),
					maxH = this.get("maxCropHeight"),
					cropTop = this.dragCropTop,
					cropLeft = this.dragCropLeft,
					imageHeight = this.imageHeight,
					imageWidth = this.imageWidth;
				
				if (!node) return;
				
				if (sizeX < minW) sizeX = minW;
				if (maxW && sizeX > maxW) sizeX = maxW;
				if (sizeY < minH) sizeY = minH;
				if (maxH && sizeY > maxH) sizeY = maxH;
				
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
						"width": sizeX,
						"height": sizeY
					});
					
					//Update label
					this.set("sizeLabel", [sizeX, sizeY]);
				}
				
				if (this.dragCropTop != cropTop || this.dragCropLeft != cropLeft) {
					this.dragCropTop = cropTop;
					this.dragCropLeft = cropLeft;
					
					this.get("image").setStyle("margin", - cropTop + "px 0 0 -" + cropLeft + "px");
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
				
				if (this.resizeActive) { // resize
					this.cropLeft = this.dragCropLeft;
					this.cropTop = this.dragCropTop;
					this.cropWidth = this.dragW;
					this.cropHeight = this.dragH;
					
					//Update label
					this.set("sizeLabel", [this.cropWidth, this.cropHeight]);
				} else { // move
					this.cropLeft = this.dragCropLeft;
					this.cropTop = this.dragCropTop;
				}
			
				e.preventDefault();
				
				//Reset cursor
				this.set("cursor", 4);
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
			var image = this.get("image");
			if (this.get("autoClose") && image && e.target && !e.target.closest("span.supra-image") && !e.target.closest(".supra-background-editing")) {
				this.set("image", null);
			}
		},
		
		
		/* --------------------------------- Image --------------------------------- */
		
		
		/**
		 * Set up needed elements for image resizing
		 * 
		 * @param {Y.Node} image Image node
		 * @private
		 */
		setUpImage: function (image) {
			var doc = this.get("doc"),
				resizeHandleNode = Y.Node(doc.createElement("SPAN")), // create in correct document
				imageContainerNode = Y.Node(doc.createElement("SPAN")),
				sizeLabelNode = Y.Node(doc.createElement("SPAN")),
				containerNode = image.ancestor(),
				width = containerNode.get("offsetWidth"),
				height = containerNode.get("offsetHeight");
			
			resizeHandleNode.addClass("supra-image-resize");
			containerNode.append(resizeHandleNode);
			resizeHandleNode.on("mousemove", this.setMouseCursor, this);
			resizeHandleNode.on("mouseleave", this.unsetMouseCursor, this);
			resizeHandleNode.on("mousedown", this.dragStart, this);
			this.set("resizeHandleNode", resizeHandleNode);
			
			sizeLabelNode.addClass("tooltip").addClass("visible").addClass("bottom");
			containerNode.append(sizeLabelNode);
			this.set("sizeLabelNode", sizeLabelNode);
			
			imageContainerNode.addClass("supra-image-inner");
			containerNode.append(imageContainerNode);
			imageContainerNode.append(image);
			this.set("imageContainerNode", imageContainerNode);
			
			imageContainerNode.setStyles({
				"width": width,
				"height": height
			});
			containerNode.setStyles({
				"width": "auto",
				"height": "auto"
			});
			
			image.setAttribute("unselectable", "on");
			containerNode.setAttribute("contentEditable", "false");
			containerNode.addClass("supra-image-editing");
			
			this.imageWidth = image.get("offsetWidth");
			this.imageHeight = image.get("offsetHeight");
			this.cropWidth = width;
			this.cropHeight = height;
			this.cropLeft = - parseInt(image.getStyle("marginLeft"), 10) || 0;
			this.cropTop = - parseInt(image.getStyle("marginTop"), 10) || 0;
			
			//If image is not loaded, then width and height could be 0
			if (!this.imageWidth || !this.imageHeight) {
				image.on('load', function (event, image) {
					this.imageWidth = image.get("offsetWidth");
					this.imageHeight = image.get("offsetHeight");
				}, this, image);
			}
			
			//Set size label
			this.set("sizeLabel", [this.cropWidth, this.cropHeight]);
			
			//Calculate min image width and height for zoom
			var maxImageWidth = this.get("maxImageWidth"),
				maxImageHeight = this.get("maxImageHeight"),
				minImageWidth = this.get("minCropWidth"),
				minImageHeight = this.get("minCropHeight"),
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
			
			if (!this.get("imageContainerNode")) {
				// Already teared down, 'resize' event triggered this again
				return;
			}
			
			var imageContainerNode = this.get("imageContainerNode"),
				resizeHandleNode = this.get("resizeHandleNode"),
				sizeLabelNode = this.get("sizeLabelNode"),
				containerNode = imageContainerNode.ancestor();
			
			image.removeAttribute("unselectable");
			containerNode.append(image);
			containerNode.removeClass("supra-image-editing");
			containerNode.setStyles({
				"width": this.cropWidth,
				"height": this.cropHeight
			});
			
			resizeHandleNode.remove(true);
			this.set("resizeHandleNode", null);
			
			sizeLabelNode.remove(true);
			this.set("sizeLabelNode", null);
			
			imageContainerNode.remove(true);
			this.set("imageContainerNode", null);
			
			if (this.zoomPanel) {
				this.zoomPanel.hide();
			}
			
			this.fire("resize", {
				"image": image,
				"cropLeft": this.cropLeft,
				"cropTop": this.cropTop,
				"cropWidth": this.cropWidth,
				"cropHeight": this.cropHeight,
				"imageWidth": this.imageWidth,
				"imageHeight": this.imageHeight,
				"silent": !!silent
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
			var doc = this.get("doc"),
				resizeHandleNode = Y.Node(doc.createElement("SPAN")), // create in correct document
				sizeLabelNode = Y.Node(doc.createElement("SPAN")),
				containerNode = image;
			
			resizeHandleNode.addClass("supra-image-resize");
			resizeHandleNode.addClass("supra-image-resize-move");
			image.append(resizeHandleNode);
			resizeHandleNode.on("mousedown", this.dragStart, this);
			this.set("resizeHandleNode", resizeHandleNode);
			
			sizeLabelNode.addClass("tooltip").addClass("visible").addClass("bottom");
			image.append(sizeLabelNode);
			this.set("sizeLabelNode", sizeLabelNode);
			image.addClass("supra-background-editing");
			
			this.imageWidth = this.cropWidth = this.get("maxImageWidth");
			this.imageHeight = this.cropHeight = this.get("maxImageHeight");
			this.cropLeft = 0;
			this.cropTop = 0;
			
			var backgroundSize = image.getStyle("backgroundSize").match(/(\d+)px\s+(\d+)px/);
			if (backgroundSize) {
				this.cropWidth = this.imageWidth = parseInt(backgroundSize[1], 10) || this.cropWidth;
				this.cropHeight = this.imageHeight = parseInt(backgroundSize[2], 10) || this.cropHeight;
			}
			
			var backgroundPosition = image.getStyle("backgroundPosition").match(/(\-?\d+)px\s+(\-?\d+)px/);
			if (backgroundPosition) {
				this.cropLeft = - parseInt(backgroundPosition[1], 10) || 0;
				this.cropTop = - parseInt(backgroundPosition[2], 10) || 0;
				this.cropWidth -= this.cropLeft;
				this.cropHeight -= this.cropTop;
			}
			
			//Set size label
			this.set("sizeLabel", [this.cropWidth, this.cropHeight]);
			
			//Calculate min image width and height for zoom
			var maxImageWidth = this.get("maxImageWidth"),
				maxImageHeight = this.get("maxImageHeight"),
				minImageWidth = this.get("minCropWidth"),
				minImageHeight = this.get("minCropHeight"),
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
			
			var resizeHandleNode = this.get("resizeHandleNode"),
				sizeLabelNode = this.get("sizeLabelNode");
			
			image.removeClass("supra-background-editing");
			
			resizeHandleNode.remove(true);
			this.set("resizeHandleNode", null);
			
			sizeLabelNode.remove(true);
			this.set("sizeLabelNode", null);
			
			this.fire("resize", {
				"image": image,
				"cropLeft": this.cropLeft,
				"cropTop": this.cropTop,
				"cropWidth": this.cropWidth,
				"cropHeight": this.cropHeight,
				"imageWidth": this.imageWidth,
				"imageHeight": this.imageHeight,
				"silent": !!silent
			});
			
			if (this.zoomPanel) {
				this.zoomPanel.hide();
			}
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
			
			if (this.get("image")) {
				
				if (this.get("mode") == ImageResizer.MODE_IMAGE) {
					this.tearDownImage(this.get("image"), silent);
				} else {
					this.tearDownBackground(this.get("image"), silent);
				}
			}
			
			if (this.eventClick) {
				this.eventClick.detach();
				this.eventClick = null;
			}
			
			if (image) {
				this.set("doc", doc);
				
				if (this.get("mode") == ImageResizer.MODE_IMAGE) {
					this.setUpImage(image);
				} else {
					this.setUpBackground(image);
				}
				
				this.eventClick = Y.Node(doc).on("mousedown", this.documentClick, this);
			}
			
			return image;
		},
		
		/**
		 * Set image size label
		 * 
		 * @param {Array} size Image size, array with width and height
		 * @return New attribute value
		 * @type {Array}
		 */
		_setSizeLabelAttr: function (size) {
			//Set size label
			var node = this.get("sizeLabelNode");
			if (node) {
				node.set("text", size[0] + " x " + size[1]);
			}
			
			return size;
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
			var node = this.get("resizeHandleNode");
			if (node) {
				node.replaceClass(
					this.getCursorClassName(this.get("cursor")),
					this.getCursorClassName(cursor)
				);
			}
			return cursor;
		}
		
	});
	
	Supra.ImageResizer = ImageResizer;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["supra.panel", "supra.slider", "dd-plugin"]});