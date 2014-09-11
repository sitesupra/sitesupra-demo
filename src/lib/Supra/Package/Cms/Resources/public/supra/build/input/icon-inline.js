YUI.add("supra.input-icon-inline", function (Y) {
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
	
	Input.NAME = "input-icon-inline";
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
		},
		
		// Resize image crop to smaller size on zoom if needed
		"allowZoomResize": {
			value: true
		},
		// Stop editing when clicked outside image
		"autoClose": {
			value: true
		},
		// Max crop width is fixed and container can't increase in size
		"fixedMaxCropWidth": {
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
		 * Selected icon info
		 * @type {Object}
		 * @private
		 */
		icon: null,
		
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
			this.icon = null;
			
			var inputNode = this.get("inputNode"),
				renderTarget = inputNode.get("parentNode"),
				value = this.get("value");
			
			// Button "Custom image"
			if (this.get('separateSlide')) {
				var buttonCustom = new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "custom_icon"]),
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
		 * Attach event listeners
		 */
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
		},
		
		/**
		 * Update inline editable style
		 */
		syncUI: function () {
			this._applyStyle(this.get('value'));
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
			
			if (this.icon) {
				this.startEditing();
			}
		},
		
		
		/* ----------------------------- Image edit ------------------------------- */
		
		
		/**
		 * Start image editing
		 */
		startEditing: function () {
			if (!this.icon || !this.icon.isDataComplete()) {
				// No data for image to edit
				return false;
			}
			
			var imageResizer = this.widgets.imageResizer,
				node = this.get("targetNode"),
				
				ratio = this.icon.width / this.icon.height,
				min_width = 16,
				min_height = Math.round(min_width / ratio),
				max_width = 940,
				max_height = Math.round(max_width / ratio);
			
			if (!node) {
				return false;
			}
			
			if (!imageResizer) {
				imageResizer = this.widgets.imageResizer = new Supra.ImageResizer({
					"mode": Supra.ImageResizer.MODE_ICON,
					"allowZoomResize": this.get("allowZoomResize"),
					"autoClose": this.get("autoClose"),
					"minCropWidth": min_width,
					"minCropHeight": min_height
				});
				imageResizer.on("resize", function (event) {
					var value = this.get("value");
					
					//Update size
					value.width = event.imageWidth;
					value.height = event.imageHeight;
					
					this.set("value", value);
					
					if (!event.silent) {
						this.blur();
					}
				}, this);
			}
			
			if (this.get('fixedMaxCropWidth')) {
				max_width = Math.min(max_width, this._getContainerWidth());
				max_height = Math.round(max_width / ratio);
			}
			
			imageResizer.set("maxImageHeight", max_height);
			imageResizer.set("maxImageWidth", max_width);
			imageResizer.set("minImageHeight", min_height);
			imageResizer.set("minImageWidth", min_width);
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
		
		
		/* ---------------------------- Media sidebar ------------------------------ */
		
		
		/**
		 * Set image
		 */
		openIconSidebar: function () {
			// Close settings form
			var properties = this.getPropertiesWidget();
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
			var iconsidebar = Supra.Manager.getAction("IconSidebar"),
				form = this.getParentWidget("form");
			
			iconsidebar.execute({
				"onselect": Y.bind(this.insertIcon, this),
				"onclose": Y.bind(this.showSettingsSidebar, this),
				"hideToolbar": true,
				"item": [this.icon ? this.icon.id || 0 : 0],
				"dndEnabled": false
			});
		},
		
		/**
		 * On image insert change input value
		 * 
		 * @private
		 */
		insertIcon: function (data) {
			var container_width = this._getContainerWidth(),
				width  = data.width,
				height = data.height,
				ratio = width / height,
				
				min_width = 16,
				min_height = Math.round(min_width / ratio),
				max_width = 940,
				max_height = Math.round(max_width / ratio);
				
			if (!this.get('fixedMaxCropWidth') && container_width < min_width) {
				container_width = min_width;
			}
			
			if (container_width && width > container_width) {
				ratio = width / height;
				width = container_width;
				height = Math.round(width / ratio);
			}
			
			data.width = width;
			data.height = height;
			this.set("value", data);
			
			//Start editing image
			if (this.get("editImageAutomatically")) {
				//Small delay to allow icon sidebar to close before doing anything (eg. opening settings sidebar)
				Y.later(100, this, function () {
					if (this._hasIcon()) {
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
				
				if (this.get("editImageAutomatically") && this._hasIcon()) {
					this.startEditing();
				}
				
			} else if (slideshow && slide) {
				
				slideshow.set("slide", this.get("id") + "_slide");
				
				if (this.get("editImageAutomatically") && this._hasIcon()) {
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
				has_icon = this._hasIcon(),
				slide = null,
				slide_id = this.get("id") + "_slide",
				button = null,
				separate = this.get("separateSlide"),
				
				container = null,
				boundingBox = null;
			
			if (slideshow || !separate) {
				if (separate) {
					slide = this.slide = slideshow.addSlide(slide_id);
					container = slide.one(".su-slide-content");
					slideshow.on("slideChange", this.onSlideshowSlideChange, this);
				} else {
					boundingBox = this.get('boundingBox');
					container = boundingBox.ancestor();
				}
				
				//Set button
				button = this.widgets.buttonSet = (new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "set_icon"]),
					"style": "small"
				}));
				button.on("click", this.openIconSidebar, this);
				button.addClass("su-button-fill");
				button.render(container);
				
				if (boundingBox) {
					boundingBox.insert(button.get('boundingBox'), 'before');
				}
				
				//Edit button
				button = this.widgets.buttonEdit = (new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "edit_icon"]),
					"style": "small"
				}));
				button.on("click", this.startEditing, this);
				button.addClass("su-button-fill");
				button.set("disabled", !has_icon);
				button.render(container);
				
				if (boundingBox) {
					boundingBox.insert(button.get('boundingBox'), 'before');
				}
				
				if (this.get("editImageAutomatically")) {
					button.hide();
				}
				
				//Remove button
				button = this.widgets.buttonRemove = (new Supra.Button({
					"label": Supra.Intl.get(["form", "block", "remove_icon"]),
					"style": "small-red"
				}));
				button.on("click", this.removeImage, this);
				button.addClass("su-button-fill");
				button.set("disabled", !has_icon);
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
		_hasIcon: function () {
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
			
			if (value && !(value instanceof Y.DataType.Icon)) {
				value = new Y.DataType.Icon(value);
			}
			
			this.icon = value ? value : "";
			
			if (this.widgets) {
				//Update UI
				if (this.widgets.buttonSet) {
					if (this.icon) {
						this.widgets.buttonSet.set("label", Supra.Intl.get(["form", "block", "change_icon"]));
					} else {
						this.widgets.buttonSet.set("label", Supra.Intl.get(["form", "block", "set_icon"]));
					}
				}
				if (this.widgets.buttonRemove) {
					if (this.icon) {
						this.widgets.buttonRemove.set("disabled", false);
					} else {
						this.widgets.buttonRemove.set("disabled", true);
					}
				}
				if (this.widgets.buttonEdit) {
					if (this.icon) {
						this.widgets.buttonEdit.set("disabled", false);
					} else {
						this.widgets.buttonEdit.set("disabled", true);
					}
				}
			}
			
			this._applyStyle(value);
			
			/*
			 * value == "" // or Y.DataType.Icon
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
			return this.icon ? this.icon : "";
			/*
			 * value == "" // or Y.DataType.Icon
			 */
		},
		
		/**
		 * Returns value for saving
		 * 
		 * @return {Object}
		 * @private
		 */
		_getSaveValue: function () {
			return this.get("value");
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
					value.width = Math.min(value.width, this._getContainerWidth());
				}
				
				if (!container.hasClass("supra-icon")) {
					var doc = node.getDOMNode().ownerDocument;
					container = Y.Node(doc.createElement("span"));
					
					node.insert(container, "after");
					container.addClass("supra-icon");
					container.append(node);
				}
				
				value.render(node);
				
				container.setStyle('display', '');
				
				container.setStyles({
					"width": value.width,
					"height": value.height
				});
				
			} else {
				container.setStyle('display', 'none');
			}
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
			while (container.test('.supra-icon, .supra-image-inner')) {
				container = container.ancestor();
			}
			
			return container.get("offsetWidth");
		}
		
	});
	
	Supra.Input.InlineIcon = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});