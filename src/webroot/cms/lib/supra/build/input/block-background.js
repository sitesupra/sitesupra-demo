//Invoke strict mode
"use strict";
	
YUI.add("supra.input-block-background", function (Y) {
	
	// Shortcuts
	var Manager = Supra.Manager;
	
	/*
	 * Block background input, should be used only in block properties
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "block-background";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		// Node to which should be applied background
		"targetNode": {
			value: null
		},
		// Tag name used to identify styles for block
		"selectorTagName": {
			value: "BLOCK"
		},
		// Select list values attribute getter
		"values": {
			value: null,
			getter: "_getSelectListValues"
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		/**
		 * List of supporting widgets:
		 *   selectList
		 *   buttonCustom
		 *   buttonSet
		 *   buttonEdit
		 *   buttonRemove
		 *   imageResizer
		 * @type {Object}
		 * @private
		 */
		widgets: null,
		
		/**
		 * Selected custom image info
		 * @type {Object}
		 * @private
		 */
		image: null,
		
		/**
		 * Slideshow slide containing "Set", "Manage", "Remove" buttons
		 * @type {Object}
		 * @private
		 */
		slide: null,
		
		
		/**
		 * Render needed widgets
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			this.widgets = {};
			this.image = null;
			
			var inputNode = this.get("inputNode"),
				renderTarget = inputNode.get("parentNode"),
				value = this.get("value");
			
			// Select list
			var selectList = new Supra.Input.SelectVisual({
				"values": this.getBackgroundStyles(),
				"value": value ? (value.image ? "_custom" : value.classname || "") : ""
			});
			
			selectList.render(renderTarget);
			inputNode.insert(selectList.get("boundingBox"), "before");
			selectList.buttons._custom.hide();
			
			// Button "Custom image"
			var buttonCustom = new Supra.Button({
				"label": Supra.Intl.get(["form", "block", "custom_image"]),
				"style": "small-gray"
			});
			buttonCustom.addClass("button-section");
			buttonCustom.on("click", this.openSlide, this);
			buttonCustom.render(renderTarget);
			inputNode.insert(buttonCustom.get("boundingBox"), "before");
			
			this.widgets.selectList = selectList;
			this.widgets.buttonCustom = buttonCustom;
			
			//Handle value attribute change
			selectList.on("valueChange", this._afterValueChange, this);
		},
		
		/**
		 * Attach event listeners
		 */
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
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
		 * Returns properties widget
		 */
		getPropertiesWidget: function () {
			var form = this.getParentWidget("form");
			return form ? form.get("parent") : null;
		},
		
		
		/* ------------------------------ Sidebar -------------------------------- */
		
		
		/**
		 * Show settings form
		 */
		showSettingsSidebar: function () {
			var form = this.getParentWidget("form"), 
				properties = this.getPropertiesWidget(),
				group = null;
			
			if (form && properties) {
				//We can get input group from input definition
				group = (form.getConfig(this.get("id")) || {}).group || "";
				
				properties.showPropertiesForm(group);
			}
		},
		
		
		/* ----------------------------- Image edit ------------------------------- */
		
		
		/**
		 * Start image editing
		 */
		editImage: function () {
			var imageResizer = this.widgets.imageResizer,
				block = this.get("root"),
				node = block && block.getNode ? block.getNode().one("*") : null,
				size = this.image.image.sizes.original;
			
			if (!node) {
				// There are no nodes for this block
				return;
			}
			
			if (!imageResizer) {
				imageResizer = this.widgets.imageResizer = new Supra.ImageResizer({
					"mode": Supra.ImageResizer.MODE_BACKGROUND
				});
				imageResizer.on("resize", function (event) {
					var value = this.get("value"),
						image = value.image;
					
					//Update crop, etc.
					image.crop_top = event.cropTop;
					image.crop_left = event.cropLeft;
					image.crop_width = event.cropWidth;
					image.crop_height = event.cropHeight;
					image.size_width = event.imageWidth;
					image.size_height = event.imageHeight;
					
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
			this.set("value", {
				"classname": "",
				"image": null
			});
			
			this.closeSlide();
		},
		
		/**
		 * Returns background styles from stylesheet
		 * 
		 * @return List of styles
		 * @type {Array}
		 * @private
		 */
		getBackgroundStyles: function () {
			var block = this.get("root"),
				styles = [],
				result = [],
				tagName = null;
			
			result.push({
				"id": "",
				"title": Supra.Intl.get(["form", "block", "no_image"]),
				"icon": "/cms/lib/supra/build/input/assets/skins/supra/icons/icon-block-background-none.png"
			});
			
			if (block && block.getStylesheetParser) {
				tagName = this.get("selectorTagName");
				styles = block.getStylesheetParser().getSelectorsByTag(tagName);
				
				for (var i=0, ii=styles.length; i<ii; i++) {
					result.push({
						"id": styles[i].classname,
						"title": styles[i].attributes.title,
						"icon": styles[i].attributes.icon
					});
				}
			}
			
			// This option is not visible
			result.push({
				"id": "_custom",
				"title": ""
			});
			
			return result;
		},
		
		
		/* ---------------------------- Media sidebar ------------------------------ */
		
		
		/**
		 * Set image
		 */
		openMediaSidebar: function () {
			//Close settings form
			var properties = this.getPropertiesWidget();
			if (properties) {
				properties.hidePropertiesForm({
					"keepToolbarButtons": true // we keep them because settings sidebar is hidden temporary
				});
			}
			
			//Open MediaSidebar
			var mediasidebar = Supra.Manager.getAction("MediaSidebar"),
				form = this.getParentWidget("form"),
				path = this.image ? [].concat(this.image.path).concat(this.image.id) : 0;
			
			mediasidebar.execute({
				"onselect": Y.bind(this.insertImage, this),
				"hideToolbar": true,
				"item": path,
				"dndEnabled": false
			});
			
			//When media library is hidden show settings form again
			mediasidebar.once("hide", function () {
				this.showSettingsSidebar();
			}, this);
		},
		
		/**
		 * On image insert change input value
		 * 
		 * @private
		 */
		insertImage: function (data) {
			this.set("value", {
				"classname": "_custom",
				"image": {
					"image": data.image,
					"crop_left": 0,
					"crop_top": 0,
					"crop_width": data.image.sizes.original.width,
					"crop_height": data.image.sizes.original.height,
					"size_width": data.image.sizes.original.width,
					"size_height": data.image.sizes.original.height
				}
			});
		},
		
		
		/* ------------------------------ Slideshow -------------------------------- */
		
		
		/**
		 * Open slideshow slide
		 */
		openSlide: function () {
			var slideshow = this.getSlideshow(),
				slide = this.getSlideshowSlide();
			
			if (slideshow && slide) {
				
				slideshow.set("slide", this.get("id") + "_slide");
				
			}
		},
		
		/**
		 * Close slideshow slide
		 */
		closeSlide: function () {
			var slideshow = this.getSlideshow();
			if (slideshow && slideshow.get("slide") == this.get("id") + "_slide") {
				slideshow.scrollBack();
			}
		},
		
		/**
		 * Returns slideshow slide for image controls
		 * 
		 * @return Slideshow slide
		 * @type {Object}
		 * @private
		 */
		getSlideshowSlide: function () {
			if (this.slide) return this.slide;
			
			var slideshow = this.getSlideshow(),
				value = this.get("value"),
				slide = null,
				slide_id = this.get("id") + "_slide",
				button = null;
			
			if (slideshow) {
				slide = this.slide = slideshow.addSlide(slide_id);
				slide = slide.one(".su-slide-content");
				
				//Set button
				button = this.widgets.buttonSet = (new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "set_image"]),
					"style": "small"
				}));
				button.on("click", this.openMediaSidebar, this);
				button.addClass("button-section")
				button.render(slide);
				
				//Edit button
				button = this.widgets.buttonEdit = (new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "edit_image"]),
					"style": "small"
				}));
				button.on("click", this.editImage, this);
				button.addClass("button-section");
				button.set("disabled", !value || !value.image);
				button.render(slide);
				
				//Remove button
				button = this.widgets.buttonRemove = (new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "remove_image"]),
					"style": "small-red"
				}));
				button.on("click", this.removeImage, this);
				button.set("disabled", !value || !value.image);
				button.render(slide);
				
				//When slide is hidden stop editing image
				slideshow.on("slideChange", function (evt) {
					if (evt.prevVal == slide_id && this.widgets.imageResizer) {
						this.widgets.imageResizer.set("image", null);
					}
				}, this);
				
				return slide;
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
		
		
		/* ------------------------------ Attributes -------------------------------- */
		
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value Value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_setValue: function (value) {
			value = (value === undefined || value === null ? "" : value);
			
			this.image = value && value.image ? value.image : "";
			
			if (this.widgets) {
				//Update UI
				var classname = value && value.classname ? value.classname : (value.image ? "_custom" : "");
				this.widgets.selectList.set("value", classname);
				
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
			
			/*
			 * value == {
			 * 	   classname: "",
			 *     image: ""
			 * }
			 * 
			 */
			this._original_value = value;
			return value;
		},
		
		/**
		 * Value attribute getter
		 * Returns input value, object with "classname" and "image" keys
		 * 
		 * @return {Object}
		 * @private
		 */
		_getValue: function () {
			if (!this.widgets || !this.widgets.selectList) {
				return {
					"classname": "",
					"image": ""
				};
			}
			
			var value = {
				"classname": this.widgets.selectList.get("value") || "",
				"image": this.image ? this.image : ""
			};
			
			if (value.classname == "_custom") {
				value.classname = "";
			} else {
				value.image = "";
			}
			
			/*
			 * value == {
			 * 	   "classname": "",
			 *     "image": {
			 * 	       "image": { ... image data ... },
			 *         "crop_height": Number, "crop_width": Number, "crop_left": Number, "crop_top": Number,
			 *         "size_width": Number, "size_height": Number
			 *     }
			 * }
			 */
			return value;
		},
		
		/**
		 * Returns value for saving
		 * 
		 * @return {Object}
		 * @private
		 */
		_getSaveValue: function () {
			var value = this.get("value");
			
			if (value.image && value.image.image) {
				//We want to send only image ID
				//We clone image info to be sure that we don't overwrite info
				value.image = Supra.mix({}, value.image, {
					"image": value.image.image.id
				});
			}
			
			/*
			 * value == {
			 * 	   "classname": "",
			 *     "image": {
			 * 	       "image": "...id...",
			 *         "crop_height": Number, "crop_width": Number, "crop_left": Number, "crop_top": Number,
			 *         "size_width": Number, "size_height": Number
			 *     }
			 * }
			 */
			return value;
		},
		
		/**
		 * Values attribute getter
		 * Returns select list values
		 * 
		 * @return {Array}
		 * @private
		 */
		_getSelectListValues: function () {
			return this.widgets && this.widgets.selectList ? this.widgets.selectList.get("values") : [];
		},
		
		/**
		 * After value change trigger event
		 * @param {Object} evt
		 */
		_afterValueChange: function (evt) {
			this.fire("change", {"value": this.get("value")});
		}
		
	});
	
	Supra.Input.BlockBackground = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});