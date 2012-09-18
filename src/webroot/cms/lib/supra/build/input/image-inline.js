//Invoke strict mode
"use strict";

YUI.add('supra.input-image-inline', function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
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
		}
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
			var buttonCustom = new Supra.Button({
				"label": Supra.Intl.get(["form", "block", "custom_image"]),
				"style": "small-gray"
			});
			buttonCustom.addClass("button-section");
			buttonCustom.on("click", this.openSlide, this);
			buttonCustom.render(renderTarget);
			inputNode.insert(buttonCustom.get("boundingBox"), "before");
			
			this.widgets.buttonCustom = buttonCustom;
		},
		
		/* ----------------------------- Image edit ------------------------------- */
		
		
		/**
		 * Start image editing
		 */
		editImage: function () {
			var imageResizer = this.widgets.imageResizer,
				node = this.get("targetNode"),
				size = this.image.image.sizes.original;
			
			if (!node) {
				return;
			}
			
			if (!imageResizer) {
				imageResizer = this.widgets.imageResizer = new Supra.ImageResizer({
					"mode": Supra.ImageResizer.MODE_IMAGE,
					"allowZoomResize": this.get("allowZoomResize")
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
				}, this);
			}
			
			imageResizer.set("maxImageHeight", size.height);
			imageResizer.set("maxImageWidth", size.width);
			imageResizer.set("image", node);
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
			this.set("value", {
				"image": data.image,
				"crop_left": 0,
				"crop_top": 0,
				"crop_width": data.image.sizes.original.width,
				"crop_height": data.image.sizes.original.height,
				"size_width": data.image.sizes.original.width,
				"size_height": data.image.sizes.original.height
			});
			
			//Start editing image
			if (this.get("editImageAutomatically")) {
				//Small delay to allow media library to close before doing anything
				Y.later(100, this, function () {
					if (this._hasImage()) {
						this.editImage();
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
			
			if (!node) return;
			
			container = node.ancestor();
			
			if (value) {
				if (!container.hasClass("supra-image")) {
					var doc = node.getDOMNode().ownerDocument;
					container = Y.Node(doc.createElement("span"));
					
					node.insert(container, "after");
					container.addClass("supra-image");
					container.append(node);
				}
				
				node.setStyle("margin", -value.crop_top + "px 0 0 -" + value.crop_left + "px");
				node.setAttribute("width", value.size_width);
				node.setAttribute("height", value.size_height);
				node.setAttribute("src", value.image.sizes.original.external_path);
				container.setStyles({
					"width": value.crop_width,
					"height": value.crop_height
				});
			} else {
				node.setStyles({
					"margin": "0"
				});
				
				node.setAttribute("src", this.get("blankImageUrl"));
				node.removeAttribute("width");
				node.removeAttribute("height");
				
				if (container) {
					container.setStyles({
						"width": "auto",
						"height": "auto"
					});
				}
			}
		}
		
	});
	
	Supra.Input.InlineImage = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-block-background"]});