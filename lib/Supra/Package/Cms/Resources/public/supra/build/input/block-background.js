YUI.add("supra.input-block-background", function (Y) {
	//Invoke strict mode
	"use strict";
	
	// Shortcuts
	var Manager = Supra.Manager;
	
	/*
	 * Block background input, should be used only in block properties
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
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
		},
		"editImageAutomatically": {
			value: true,
			setter: "_setEditImageAutomatically"
		},
		"allowRemoveImage": {
			value: true,
			setter: "_setAllowRemoveImage"
		},
		/**
		 * Render widget into separate slide and add
		 * button to the place where this widget should be
		 */
		"separateSlide": {
			value: true
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
				values = this.getBackgroundStyles(),
				value = this.get("value");
			
			// Select list
			// Visible only if there are other options than "No image" and "Custom"
			var selectList = new Supra.Input.SelectVisual({
				"values": values,
				"value": value ? (value.image ? "_custom" : value.classname || "") : "",
				"visible": values.length > 2
			});
			
			selectList.render(renderTarget);
			inputNode.insert(selectList.get("boundingBox"), "before");
			selectList.buttons._custom.hide();
			
			this.widgets.selectList = selectList;
			
			// Button "Custom image"
			if (this.get('separateSlide')) {
				
				var button = new Supra.Button({
					'label': Supra.Intl.get(["form", "block", "custom_image"]),
					'style': 'group',
					'groupStyle': 'mid',
					'iconStyle': '',
					'icon': ''
				});
				
				button.addClass("button-section");
				button.on("click", this.openSlide, this);
				button.render(renderTarget);
				inputNode.insert(button.get("boundingBox"), "before");
				
				this.widgets.buttonCustom = button;
			} else {
				this.openSlide();
			}
			
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
			var form = this.getParentWidget("form"),
				parent = form ? form.get("parent") : null;
			
			if (parent && parent.isInstanceOf('page-content-properties')) {
				return parent
			}
			
			return null;
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
						parent.execute();
					}
				}
			}
			
			if (this.image) {
				this.startEditing();
			}
		},
		
		
		/* ----------------------------- Image edit ------------------------------- */
		
		
		/**
		 * Start image editing
		 */
		startEditing: function () {
			var imageResizer = this.widgets.imageResizer,
				block = this.get("root"),
				node = this.get("targetNode") || (block && block.getNode ? block.getNode().one("*") : null),
				size = this.image.image.sizes.original;
			
			if (!node) {
				// There are no nodes for this block
				return false;
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
					
					if (!event.silent) {
						this.blur();
					}
				}, this);
			}
			
			imageResizer.set("maxImageHeight", size.height);
			imageResizer.set("maxImageWidth", size.width);
			imageResizer.set("image", node);
			
			this.focus();
			return true;
		},
		
		/**
		 * Stop editing image
		 */
		stopEditing: function () {
			var imageResizer = this.widgets.imageResizer;
			if (imageResizer) {
				imageResizer.set("image", null);
				this.blur();
			}
		},
		
		/**
		 * Remove selected image
		 */
		removeImage: function () {
			this.stopEditing();
			
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
		
		/**
		 * Apply image styles after input value changes
		 * 
		 * @private
		 */
		applyImageStyle: function (image) {
			var styles = {'backgroundImage': 'none', 'backgroundSize': 'auto', 'backgroundPosition': '0 0'},
				block = this.get("root"),
				node = this.get("targetNode") || (block && block.getNode ? block.getNode().one("*") : null),
				size = null;
			
			if (node) {
				if (image) {
					size = image.image.sizes.original;
					if (size) {
						styles = {	
							'backgroundImage': 'url(' + size.external_path + ')',
							'backgroundSize': image.size_width + 'px ' + image.size_height + 'px',
							'backgroundPosition': -image.crop_left + 'px ' + (-image.crop_top) + 'px'
						};
					}
				}
				
				node.setStyles(styles);
			}
		},
		
		
		/* ---------------------------- Media sidebar ------------------------------ */
		
		
		/**
		 * Set image
		 */
		openMediaSidebar: function () {
			// Close settings form
			var properties = this.getPropertiesWidget(),
				deferred = new Supra.Deferred();
			
			if (properties) {
				properties.hidePropertiesForm({
					"keepToolbarButtons": true // we keep them because settings sidebar is hidden temporary
				});
			} else {
				// Not part of block properties, search for Action
				var parent = this.getParentWidget("ActionBase");
				if (parent && parent.plugins.getPlugin("PluginSidebar")) {
					// Freeze to prevent from closing, so that we can restore the state
					// after media sidebar is closed
					parent.set("frozen", true);
				}
			}
			
			// Stop editing image
			this.stopEditing();
			
			//Open MediaSidebar
			var mediasidebar = Supra.Manager.getAction("MediaSidebar"),
				form = this.getParentWidget("form"),
				path = this.image && this.image.image ? [].concat(this.image.image.path).concat(this.image.image.id) : 0;
			
			mediasidebar.execute({
				"onselect": Y.bind(function (data) {
					this.insertImage(data);
					deferred.resolve([data]);
				}, this),
				"onclose": Y.bind(function () {
					this.showSettingsSidebar();
					deferred.resolve([this.get('value')]);
				}, this),
				"hideToolbar": true,
				"item": path,
				"dndEnabled": false
			});
			
			return deferred.promise();
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
			
			//Start editing image
			if (this.get("editImageAutomatically")) {
				//Small delay to allow media library to close before doing anything
				Y.later(100, this, function () {
					if (this._hasImage()) {
						this.startEditing();
					}
				});
			}
		},
		
		
		/* ------------------------------ Slideshow -------------------------------- */
		
		
		/**
		 * Open slideshow slide
		 */
		openSlide: function () {
			var slideshow = this.getSlideshow(),
				slide = this.getSlideshowSlide();
			
			if (!this.get('separateSlide')) {
				
				if (this.get("editImageAutomatically") && this._hasImage()) {
					this.startEditing();
				}
				
			} else if (slideshow && slide) {
				
				slideshow.set("slide", this.get("id") + "_slide");
				
				if (this.get("editImageAutomatically") && this._hasImage()) {
					this.startEditing();
				}
				
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
		 * When slideshow slide changes back then stop editing
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		onSlideshowSlideChange: function (event) {
			var slide_id = this.get("id") + "_slide";
			
			if (event.newVal != event.prevVal) {
				if (event.prevVal == slide_id) {
					this.stopEditing();
				}
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
				has_image = this._hasImage(),
				slide = null,
				slide_id = this.get("id") + "_slide",
				button = null,
				separate = this.get("separateSlide"),
				
				container = null,
				boundingBox = null;
			
			if (slideshow || !separate) {
				if (separate) {
					slide = this.slide = slideshow.addSlide({
						'id': slide_id,
						'title': this.get('label')
					});
					container = slide.one(".su-slide-content");
					slideshow.on("slideChange", this.onSlideshowSlideChange, this);
				} else {
					boundingBox = this.get('boundingBox');
					container = boundingBox.ancestor();
				}
				
				//Set button
				button = this.widgets.buttonSet = (new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "set_image"]),
					"style": "small"
				}));
				button.on("click", this.openMediaSidebar, this);
				button.addClass("su-button-fill");
				button.render(container);
				
				if (boundingBox) {
					boundingBox.insert(button.get('boundingBox'), 'before');
				}
				
				//Edit button
				button = this.widgets.buttonEdit = (new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "edit_image"]),
					"style": "small"
				}));
				button.on("click", this.startEditing, this);
				button.addClass("su-button-fill");
				button.set("disabled", !has_image);
				button.render(container);
				
				if (boundingBox) {
					boundingBox.insert(button.get('boundingBox'), 'before');
				}
				
				if (this.get("editImageAutomatically")) {
					button.hide();
				}
				
				//Remove button
				button = this.widgets.buttonRemove = (new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "remove_image"]),
					"style": "small-red"
				}));
				button.on("click", this.removeImage, this);
				button.addClass("su-button-fill");
				button.set("disabled", !has_image);
				button.set("visible", this.get("allowRemoveImage"));
				button.render(container);
				
				if (boundingBox) {
					boundingBox.insert(button.get('boundingBox'), 'before');
				}
				
				//When slide is hidden stop editing image
				if (separate) {
					slideshow.on("slideChange", function (evt) {
						if (evt.prevVal == slide_id && this.widgets.imageResizer) {
							this.widgets.imageResizer.set("image", null);
							this.blur();
						}
					}, this);
				}
				
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
		 * Returns true if image is selected, otherwise false
		 * 
		 * @return True if image is selected
		 * @type {Boolean}
		 * @private
		 */
		_hasImage: function () {
			var value = this.get("value");
			return value && value.image;
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
				
				// Standalone button background preview
				if (this.widgets.buttonCustom) {
					var button = this.widgets.buttonCustom,
						style = '',
						icon = null;
					
					if (value && value.image && value.image.image) {
						icon = Supra.getObjectValue(value, ['image', 'image', 'sizes', '200x200', 'external_path']);
					}
					
					if (icon) {
						button.set('iconStyle', '');
					} else {
						button.set('iconStyle', 'center');
						style = 'center';
						icon = '/cms/lib/supra/build/input/assets/skins/supra/icons/select-visual-none.png'
					}
					
					if (button.get('iconStyle') != style) {
						button.set('iconStyle', style);
					}
					
					button.set('icon', icon);					
				}
			}
			
			this.applyImageStyle(this.image);
			
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
		},
		
		/**
		 * When slide is opened start editing instead of waiting for user to click "Edit" button
		 * @param {Boolean} value Attribute value
		 * @return {Boolean} New attribute value
		 */
		_setEditImageAutomatically: function (value) {
			var button = this.widgets.buttonEdit;
			if (button) {
				button.set("visible", !value);
			}
			return value;
		},
		
		/**
		 * Allow removing image / allow having no image
		 * @param {Boolean} value Attribute value
		 * @return {Boolean} New attribute value
		 */
		_setAllowRemoveImage: function (value) {
			var button = this.widgets.buttonRemove;
			if (button) {
				button.set("visible", value);
			}
			return value;
		}
		
	});
	
	Supra.Input.BlockBackground = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});