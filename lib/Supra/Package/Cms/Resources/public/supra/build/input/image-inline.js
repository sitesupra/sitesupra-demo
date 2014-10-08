YUI.add('supra.input-image-inline', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-image-inline';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		// Image node which is edited
		"targetNode": {
			value: null
		},
		//Blank image URI or data URI
		"blankImageUrl": {
			value: "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
		},
		// Resize image crop to smaller size on zoom if needed
		"allowZoomResize": {
			value: false
		},
		// Change zoom on crop resize if needed
		"allowCropZooming": {
			value: false
		},
		// Stop editing when clicked outside image
		"autoClose": {
			value: true
		},
		// Max crop width is fixed and container can't increase in size
		"fixedMaxCropWidth": {
			value: true
		},
		// Max crop height is fixed and container can't increase in size
		"fixedMaxCropHeight": {
			value: false
		},
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.BlockBackground, {
		
		/**
		 * Render needed widgets
		 */
		renderUI: function () {
			Supra.Input.BlockBackground.superclass.renderUI.apply(this, arguments);
			
			this.widgets = {};
			this.image = null;
			
			var inputNode = this.get("inputNode"),
				renderTarget = inputNode.get("parentNode"),
				value = this.get("value");
			
			// Button "Custom image"
			if (this.get('separateSlide')) {
				var buttonCustom = new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "custom_image"]),
					"style": "small-gray"
				});
				buttonCustom.addClass("button-section");
				buttonCustom.on("click", this.openSlide, this);
				buttonCustom.render(renderTarget);
				inputNode.insert(buttonCustom.get("boundingBox"), "before");
				
				this.widgets.buttonCustom = buttonCustom;
			} else {
				this.openSlide();
			}
		},
		
		/**
		 * Update inline editable style
		 */
		syncUI: function () {
			this._applyStyle(this.get('value'));
		},
		
		
		/* ----------------------------- Image edit ------------------------------- */
		
		
		/**
		 * Start image editing
		 */
		startEditing: function () {
			if (!this.image || !this.image.image) {
				// No data for image to edit
				return false;
			}
			
			var imageResizer = this.widgets.imageResizer,
				node = this.get("targetNode"),
				size = this.image.image.sizes.original;
			
			if (!node) {
				return false;
			}
			
			if (!imageResizer) {
				imageResizer = this.widgets.imageResizer = new Supra.ImageResizer({
					"mode": Supra.ImageResizer.MODE_IMAGE,
					"allowZoomResize": this.get("allowZoomResize"),
					"allowCropZooming": this.get("allowCropZooming"),
					"autoClose": this.get("autoClose")
				});
				imageResizer.on("resize", function (event) {
					var value = this.get("value");
					
					//Update crop, etc.
					value.crop_top = event.cropTop;
					value.crop_left = event.cropLeft;
					value.crop_width = event.cropWidth;
					value.crop_height = event.cropHeight;
					value.size_width = event.imageWidth;
					value.size_height = event.imageHeight;
					
					this.set("value", value);
					
					if (!event.silent) {
						this.blur();
					}
				}, this);
			}
			
			imageResizer.set("maxCropWidth", this.get('fixedMaxCropWidth') ? Math.min(size.width, this._getContainerWidth()) : 0);
			imageResizer.set("maxCropHeight", this.get('fixedMaxCropHeight') ? Math.min(size.height, this._getContainerHeight()) : 0);
			imageResizer.set("maxImageHeight", size.height);
			imageResizer.set("maxImageWidth", size.width);
			imageResizer.set("minImageHeight", 32);
			imageResizer.set("minImageWidth", 32);
			imageResizer.set("image", node);
			
			this.focus();
			return true;
		},
		
		/**
		 * Remove selected image
		 */
		removeImage: function () {
			this.set("value", null);
			this.closeSlide();
		},
		
		
		/* ---------------------------- Media sidebar ------------------------------ */
		
		
		/**
		 * On image insert change input value
		 * 
		 * @private
		 */
		insertImage: function (data) {
			var container_width = this._getContainerWidth(),
				container_height = this._getContainerHeight(),
				width  = data.image.sizes.original.width,
				height = data.image.sizes.original.height,
				ratio  = 0;
			
			if (!this.get('fixedMaxCropWidth') && container_width < 100) {
				container_width = 100;
			}
			if (!this.get('fixedMaxCropHeight') && container_height < 100) {
				container_height = 100;
			}
			
			if (container_width && width > container_width) {
				ratio = width / height;
				width = container_width;
				height = Math.round(width / ratio);
			}
			if (container_height && height > container_height) {
				ratio = width / height;
				height = container_height;
				width = Math.round(height * ratio);
			}
			
			this.set("value", {
				"image": data.image,
				"crop_left": 0,
				"crop_top": 0,
				"crop_width": width,
				"crop_height": height,
				"size_width": width,
				"size_height": height
			});
			
			//Start editing image
			if (this.get("editImageAutomatically")) {
				//Small delay to allow icon sidebar to close before doing anything (eg. opening settings sidebar)
				Y.later(100, this, function () {
					if (this._hasImage()) {
						this.startEditing();
					}
				});
			}
		},
		
		
		/* ------------------------------ Attributes -------------------------------- */
		
		
		/**
		 * Returns true if image is selected, otherwise false
		 * 
		 * @return True if image is selected
		 * @type {Boolean}
		 * @private
		 */
		_hasImage: function () {
			var value = this.get("value");
			return value;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value Value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_setValue: function (value) {
			value = (value === undefined || value === null || typeof value !== "object" ? "" : value);
			
			if (value) {
				value.crop_left = value.crop_left || 0;
				value.crop_top = value.crop_top || 0;
			}
			
			this.image = value ? value : "";
			
			if (this.widgets) {
				//Update UI
				if (this.widgets.buttonSet) {
					if (this.image) {
						this.widgets.buttonSet.set("label", Supra.Intl.get(["form", "block", "change_image"]));
					} else {
						this.widgets.buttonSet.set("label", Supra.Intl.get(["form", "block", "set_image"]));
					}
				}
				if (this.widgets.buttonRemove) {
					if (this.image) {
						this.widgets.buttonRemove.set("disabled", false);
					} else {
						this.widgets.buttonRemove.set("disabled", true);
					}
				}
				if (this.widgets.buttonEdit) {
					if (this.image) {
						this.widgets.buttonEdit.set("disabled", false);
					} else {
						this.widgets.buttonEdit.set("disabled", true);
					}
				}
			}
			
			this._applyStyle(value);
			
			/*
			 * value == {
			 * 	   "" // or image
			 * }
			 * 
			 */
			this._original_value = value;
			return value;
		},
		
		/**
		 * Value attribute getter
		 * Returns input value
		 * 
		 * @return {Object}
		 * @private
		 */
		_getValue: function () {
			return this.image ? this.image : "";
			
			/*
			 * value == {
			 * 	   "image": { ... image data ... },
			 *     "crop_height": Number, "crop_width": Number, "crop_left": Number, "crop_top": Number,
			 *     "size_width": Number, "size_height": Number
			 * }
			 */
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
			 * 	   "image": "...id...",
			 *     "crop_height": Number, "crop_width": Number, "crop_left": Number, "crop_top": Number,
			 *     "size_width": Number, "size_height": Number
			 * }
			 */
			return value;
		},
		
		/**
		 * Apply style
		 * 
		 * @private
		 */
		_applyStyle: function (value) {
			var node = this.get("targetNode"),
				container = null;
			
			if (!node || !node.getDOMNode()) return;
			container = node.ancestor();
			
			if (value) {
				if (this.get('fixedMaxCropWidth')) {
					value.crop_width = Math.min(value.crop_width, this._getContainerWidth());
				}
				
				if (this.get('fixedMaxCropHeight')) {
					value.crop_height = Math.min(value.crop_height, this._getContainerHeight());
				}
				
				if (!container.hasClass("supra-image")) {
					var doc = node.getDOMNode().ownerDocument;
					container = Y.Node(doc.createElement("span"));
					
					node.insert(container, "after");
					container.addClass("supra-image");
					container.append(node);
				}
				
				node.setStyles({
					"margin": -value.crop_top + "px 0 0 -" + value.crop_left + "px",
					"width": value.size_width + "px",
					"height": value.size_height + "px"
				});
				node.setAttribute("width", value.size_width);
				node.setAttribute("height", value.size_height);
				node.setAttribute("src", Supra.getObjectValue(value, ['image', 'sizes', 'original', 'external_path']) || this.get('blankImageUrl'));
				
				container.setStyles({
					"width": value.crop_width + "px",
					"height": value.crop_height + "px"
				});
			} else {
				node.setStyles({
					"margin": "0",
					"width": "",
					"height": ""
				});
				
				node.setAttribute("src", this.get("blankImageUrl"));
				node.removeAttribute("width");
				node.removeAttribute("height");
				
				if (container && container.hasClass("supra-image")) {
					container.setStyles({
						"width": "auto",
						"height": "auto"
					});
				}
			}
		},
		
		/**
		 * Returns container node width / max crop width
		 * 
		 * @private
		 */
		_getContainerWidth: function () {
			var node = this.get("targetNode"),
				container = null,
				width = 0;
			
			if (!node) return 0;
			
			container = node.ancestor();
			if (!container) return 0;
			
			// Find container width to calculate max possible width
			while (container.test('.supra-image, .supra-image-inner')) {
				container = container.ancestor();
			}
			
			return container.get("offsetWidth");
		},
				
		/**
		 * Returns container node height / max crop height
		 * 
		 * @private
		 */
		_getContainerHeight: function () {
			var node = this.get("targetNode"),
				container = null,
				height = 0;
			
			if (!node) return 0;
			
			container = node.ancestor();
			if (!container) return 0;
			
			// Find container height to calculate max possible height
			while (container.test('.supra-image, .supra-image-inner')) {
				container = container.ancestor();
			}
			
			return container.get("offsetHeight");
		}

	});
	
	Supra.Input.InlineImage = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-block-background"]});