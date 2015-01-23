YUI.add("supra.input-block-background", function (Y) {
	//Invoke strict mode
	"use strict";
	
	// Shortcuts
	var Manager = Supra.Manager,
	
		DEFAULT_POSITION = "0% 0%",
		DEFAULT_REPEAT = "no-repeat",
		DEFAULT_ATTACHMENT = "scroll";
	
	/*
	 * Block background input, should be used only in block properties
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = "block-background";
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-input-' + Input.NAME;
	
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
		"allowAdvancedControls": {
			value: false,
			setter: "_setAllowAdvancedControls"
		},
		/**
		 * Render widget into separate slide and add
		 * button to the place where this widget should be
		 */
		"separateSlide": {
			value: true
		},
		
		/**
		 * Exceptional case, slide is already created manually what ever
		 * created this widget, in that case "separateSlide" will be false
		 * and "slideshowSlideId" will be set  
		 */
		"slideshowSlideId": {
			value: null
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
		 *   inputRepeat
		 *   inputPosition
		 *   inputAttachment
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
		 * Advanced controls value change will not affect anything while
		 * frozen
		 * @type {Boolean}
		 * @private
		 */
		advanced_controls_frozen: false,
		
		/**
		 * Background position
		 * @type {String}
		 * @private
		 */
		position: DEFAULT_POSITION,
		
		/**
		 * Background repeat
		 * @type {String}
		 * @private
		 */
		repeat: DEFAULT_REPEAT,
		
		/**
		 * Background attachment
		 * @type {String}
		 * @private
		 */
		attachment: DEFAULT_ATTACHMENT,
		
		/**
		 * When UI is frozen, then UI input value change will not affect anything
		 * This is used to prevent setting value on inputs to trigger another value set call
		 * @type {Boolean}
		 * @private
		 */
		uiFrozen: false,
		
		/**
		 * Controls has been rendered
		 * @type {Boolean}
		 * @private
		 */
		uiControlsRendered: false,
		
		/**
		 * Window resize listener
		 * @type {Object}
		 * @private
		 */
		resizeListener: null,
		
		
		/**
		 * Render needed widgets
		 * 
		 * @protected
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
			} else if (!this.get('slideshowSlideId')) {
				// Since there is a custom slide created by something else
				// then we expect that something else to call edit start
				this.openSlide();
			}
			
			//Handle value attribute change
			selectList.on("valueChange", this._afterValueChange, this);
			
			//On target node change reattach resize listeners
			this.after('targetNodeChange', this._afterTargetNodeChange, this);
			this._afterTargetNodeChange({'newVal': this.get('targetNode')});
		},
		
		/**
		 * Render advanced controls
		 * 
		 * @param {Object} container Node where to render into
		 * @protected
		 */
		renderAdvancedControlsUI: function (container) {
			var inputRepeat     = new Supra.Input.SelectList({
						'label': Supra.Intl.get(["inputs", "background", "repeat", "title"]),
						'values': [
							{"id": "no-repeat", "title": Supra.Intl.get(["inputs", "background", "repeat", "no-repeat"]), "icon": "/public/cms/supra/img/sidebar/icons/background/select-list-large-no-repeat.png"},
							{"id": "repeat-x",  "title": Supra.Intl.get(["inputs", "background", "repeat", "repeat-x"]),  "icon": "/public/cms/supra/img/sidebar/icons/background/select-list-large-repeat-x.png"},
							{"id": "repeat-y",  "title": Supra.Intl.get(["inputs", "background", "repeat", "repeat-y"]),  "icon": "/public/cms/supra/img/sidebar/icons/background/select-list-large-repeat-y.png"},
							{"id": "repeat",    "title": Supra.Intl.get(["inputs", "background", "repeat", "repeat"]),    "icon": "/public/cms/supra/img/sidebar/icons/background/select-list-large-repeat.png"}
							//{"id": "cover",     "title": Supra.Intl.get(["inputs", "background", "repeat", "cover"]),     "icon": "/public/cms/supra/img/sidebar/icons/background/select-list-large-cover.png"}
						],
						'value': 'no-repeat',
						'style': 'large-icons'
					}),
				inputPosition   = new Supra.Input.SelectList({
						'label': Supra.Intl.get(["inputs", "background", "position", "title"]),
						'values': [
							{"id": "0% 0%",     "title": Supra.Intl.get(["inputs", "background", "position", "top_left"]),       "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/position-top-left.png"},
							{"id": "50% 0%",    "title": Supra.Intl.get(["inputs", "background", "position", "top_center"]),     "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/position-top-center.png"},
							{"id": "100% 0%",   "title": Supra.Intl.get(["inputs", "background", "position", "top_right"]),      "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/position-top-right.png"},
							{"id": "0% 50%",    "title": Supra.Intl.get(["inputs", "background", "position", "center_left"]),    "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/position-center-left.png"},
							{"id": "50% 50%",   "title": Supra.Intl.get(["inputs", "background", "position", "center_center"]),  "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/position-center-center.png"},
							{"id": "100% 50%",  "title": Supra.Intl.get(["inputs", "background", "position", "center_right"]),   "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/position-center-right.png"},
							{"id": "0% 100%",   "title": Supra.Intl.get(["inputs", "background", "position", "bottom_left"]),    "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/position-bottom-left.png"},
							{"id": "50% 100%",  "title": Supra.Intl.get(["inputs", "background", "position", "bottom_center"]),  "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/position-bottom-center.png"},
							{"id": "100% 100%", "title": Supra.Intl.get(["inputs", "background", "position", "bottom_right"]),   "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/position-bottom-right.png"}
						],
						'value': '0% 0%'
					}),
				inputAttachment =  new Supra.Input.SelectList({
						'label': Supra.Intl.get(["inputs", "background", "attachment", "title"]),
						'values': [
							{"id": "scroll", "title": Supra.Intl.get(["inputs", "background", "attachment", "scroll"]), "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/attachment-scroll.png"},
							{"id": "fixed",  "title": Supra.Intl.get(["inputs", "background", "attachment", "fixed"]),  "icon": "/public/cms/supra/build/input/assets/skins/supra/block-background/attachment-fixed.png"}
						],
						'value': 'scroll'
					});
			
			inputRepeat.render(container);
			inputRepeat.after('valueChange', this.onImageRepeatChange, this);
			//referenceNode.insert(inputRepeat.get("boundingBox"), "before");
			
			inputPosition.render(container);
			inputPosition.after('valueChange', this.onImagePositionChange, this);
			//referenceNode.insert(inputPosition.get("boundingBox"), "before");
			
			inputAttachment.render(container);
			inputAttachment.after('valueChange', this.onImageAttachmentChange, this);
			//referenceNode.insert(inputAttachment.get("boundingBox"), "before");
			
			if (!this.get('allowAdvancedControls')) {
				inputRepeat.set('visible', false);
				inputPosition.set('visible', false);
				inputAttachment.set('visible', false);
			}
			
			this.widgets.inputRepeat = inputRepeat;
			this.widgets.inputPosition = inputPosition;
			this.widgets.inputAttachment = inputAttachment;
		},
		
		/**
		 * Attach event listeners
		 * 
		 * @protected
		 */
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			// When form is hidden stop editing
			var form = this.getForm();
			if (form) {
				form.on('visibleChange', function (e) {
					if (e.newVal != e.prevVal && !e.newVal) {
						this.stopEditing();
					}
				}, this);
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
		
		destructor: function () {
			// Close image resizer
			this.closeSlide();
			
			// Remove slide
			if (this.slide) {
				if (!this.get('slideshowSlideId')) {
					// Only if slide was created by this widget
					var slideshow = this.getSlideshow();
					slideshow.removeSlide(this.getSlideshowSlideId());
				}
				
				this.slide = null;
			}
			
			// Remove advanced control widgets
			if (this.widgets.inputRepeat) {
				this.widgets.inputRepeat.destroy(true);
				this.widgets.inputPosition.destroy(true);
				this.widgets.inputAttachment.destroy(true);
				
				this.widgets.inputRepeat = null;
				this.widgets.inputPosition = null;
				this.widgets.inputAttachment = null;
			}
			
			// Controls buttons
			if (this.widgets.buttonSet) {
				this.widgets.buttonSet.destroy(true);
				this.widgets.buttonEdit.destroy(true);
				this.widgets.buttonRemove.destroy(true);
				
				this.widgets.buttonSet = null;
				this.widgets.buttonEdit = null;
				this.widgets.buttonRemove = null;
			}
			if (this.widgets.buttonCustom) {
				this.widgets.buttonCustom.destroy(true);
				this.widgets.buttonCustom = null;
			}
			
			// Destroy image resizer widget
			if (this.widgets.imageResizer) {
				this.widgets.imageResizer.destroy(true);
				this.widgets.imageResizer = null;
			}
			
			if (this.widgets.selectList) {
				this.widgets.selectList.destroy(true);
				this.widgets.selectList = null;
			}
			
			// Reset value
			this.image = null;
			this.position = null;
			this.repeat = null;
			this.attachment = null;
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
				size = this.image.image.sizes.original,
				value = this.get("value");
			
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
			
			if (this.get("allowAdvancedControls")) {
				imageResizer.set("position", this.position);
				imageResizer.set("attachment", this.attachment);
			} else {
				imageResizer.set("position", DEFAULT_POSITION);
				imageResizer.set("attachment", DEFAULT_ATTACHMENT);
			}
			
			imageResizer.set("maxImageHeight", size.height);
			imageResizer.set("maxImageWidth", size.width);
			imageResizer.set("image", node);
			
			if (value && value.image) {
				imageResizer.cropTop = value.image.crop_top;
				imageResizer.cropLeft = value.image.crop_left;
				imageResizer.cropWidth = value.image.crop_width;
				imageResizer.cropHeight = value.image.crop_height;
				imageResizer.imageWidth = value.image.size_width;
				imageResizer.imageHeight = value.image.size_height;
				
				// Set correct image
				if (node) {
					node.setStyle('backgroundImage', 'url(' + size.external_path + ')');
				}
				
				imageResizer.sync();
			}
			
			this.focus();
			this.openSlide();
			
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
				this.fire('stopEditing');
			}
		},
		
		/**
		 * Remove selected image
		 */
		removeImage: function () {
			this.stopEditing();
			
			var value = {
				"classname": "",
				"image": null
			};
			
			if (this.get('allowAdvancedControls')) {
				value.position = DEFAULT_POSITION;
				value.repeat = DEFAULT_REPEAT;
				value.attachment = DEFAULT_ATTACHMENT;
			}
			
			this.set("value", value);
			
			this.closeSlide();
		},
		
		/**
		 * Handle background position property change
		 * 
		 * @param {Object} e Event facade object 
		 * @private
		 */
		onImagePositionChange: function () {
			if (this._uiFrozen) return;
			
			var position = this.widgets.inputPosition.get("value") || DEFAULT_POSITION,
				resizer  = this.widgets.imageResizer;
			
			this.position = position;
			this.set("value", this.get("value"));
			
			if (resizer) {
				resizer.set('position', position);
			}
		},
		
		/**
		 * Handle background repeat property change
		 * 
		 * @param {Object} e Event facade object 
		 * @private
		 */
		onImageRepeatChange: function () {
			if (this._uiFrozen) return;
			
			var resizer  = this.widgets.imageResizer;
			
			this.repeat = this.widgets.inputRepeat.get("value") || DEFAULT_REPEAT;
			this.set("value", this.get("value"));
			
			if (resizer) {
				resizer.sync();
			}
		},
		
		/**
		 * Handle background attachment property change
		 * 
		 * @param {Object} e Event facade object 
		 * @private
		 */
		onImageAttachmentChange: function () {
			if (this._uiFrozen) return;
			
			var attachment = this.widgets.inputAttachment.get("value") || DEFAULT_ATTACHMENT,
				resizer    = this.widgets.imageResizer;
			
			this.attachment = attachment;
			this.set("value", this.get("value"));
			
			if (resizer) {
				resizer.set('attachment', attachment);
			}
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
				"icon": "/public/cms/supra/build/input/assets/skins/supra/icons/icon-block-background-none.png",
				"iconStyle": "center"
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
		applyImageStyle: function (value) {
			var image = value.image,
				styles = {
					'backgroundImage': 'none',
					'backgroundSize': 'auto',
					'backgroundPosition': '0 0'
				},
				block = this.get("root"),
				node = this.get("targetNode") || (block && block.getNode ? block.getNode().one("*") : null),
				size = null,
				position;
			
			if (node) {
				if (image) {
					size = image.image.sizes.original;
					
					if (size) {
						styles = {	
							'backgroundImage': 'url(' + size.external_path + ')',
							'backgroundSize': image.size_width + 'px ' + image.size_height + 'px',
							'backgroundPosition': -image.crop_left + 'px ' + (-image.crop_top) + 'px',
							'backgroundRepeat': 'no-repeat'
						};
						
						if (this.get('allowAdvancedControls')) {
							styles['backgroundAttachment'] = value.attachment;
							styles['backgroundRepeat'] = value.repeat;
							
							position = Y.DataType.Image.position(image, {
								'node': node || (value.attachment === 'fixed' ? Y.Node(this.get('doc')) : null),
								'nodeFilter': null,
								'position': value.position,
								'attachment': value.attachment
							});
							
							image.crop_left = position[0];
							image.crop_top = position[1];
							image.crop_width = position[2];
							image.crop_height = position[3];
							
							styles['backgroundPosition'] = position[4] + ' ' + position[5];
						}
					}
				}
				
				node.setStyles(styles);
			}
		},
		
		/**
		 * Update background image position for fixed background images
		 * 
		 * @private
		 */
		applyImagePosition: function (value) {
			value = value || this.get('value');
			if (!value || !value.image || value.attachment !== 'fixed' || !this.get('allowAdvancedControls')) return;
			
			var image = value.image,
				attachment = value.attachment,
				pos = value.position,
				block,
				node,
				size,
				position;
			
			if (pos === '100% 0%' || pos === '100% 50%' || pos === '100% 100%' || pos === '0% 100%' || pos === '50% 100%') {
				block = this.get("root");
				node = this.get("targetNode") || (block && block.getNode ? block.getNode().one("*") : null);
				
				if (node) {
					size = image.image.sizes.original;
					
					position = Y.DataType.Image.position(image, {
						'node': node || Y.Node(this.get('doc')),
						'nodeFilter': null,
						'position': value.position,
						'attachment': 'fixed'
					});
					
					node.setStyles({
						'backgroundPosition': position[4] + ' ' + position[5]
					});
				}
			}
		},
		
		/**
		 * On window resize update background position
		 * 
		 * @private
		 */
		onWindowResize: function () {
			this.applyImagePosition();
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
			var value = {
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
			};
			
			if (this.get('allowAdvancedControls')) {
				value.position = DEFAULT_POSITION;
				value.repeat = DEFAULT_REPEAT;
				value.attachment = DEFAULT_ATTACHMENT;
			}
			
			this.set("value", value);
			
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
			// Prevent loop when startEditing -> openSlide -> startEditing -> ... 
			if (this._uiSlideOpening) return;
			this._uiSlideOpening = true;
			
			var slideshow = this.getSlideshow(),
				slide = this.getSlideshowSlide(),
				slide_id = this.get('slideshowSlideId');
			
			if (!this.get('separateSlide')) {
				
				if (this.get("editImageAutomatically") && this._hasImage()) {
					this.startEditing();
				}
				
				if (slide_id) {
					slideshow.set("slide", this.getSlideshowSlideId());
				}
				
			} else if (slideshow && slide) {
				
				slideshow.set("slide", this.getSlideshowSlideId());
				
				if (this.get("editImageAutomatically")) {
					if (this._hasImage()) {
						this.startEditing();
					} else {
						// Open media library
						this.openMediaSidebar();
					}
				}
				
			}
			
			this._uiSlideOpening = false;
		},
		
		/**
		 * Close slideshow slide
		 */
		closeSlide: function () {
			var slideshow = this.getSlideshow();
			if (slideshow && slideshow.get("slide") == this.getSlideshowSlideId()) {
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
			var slide_id = this.getSlideshowSlideId();
			
			if (event.newVal != event.prevVal) {
				if (event.prevVal == slide_id) {
					this.stopEditing();
				}
			}
		},
		
		/**
		 * Returns slideshow slide id for this input
		 * 
		 * @returns {String} Slideshow slide id specifically for this input
		 * @private
		 */
		getSlideshowSlideId: function () {
			return this.get('slideshowSlideId') || this.get("id") + "_slide";
		},
		
		/**
		 * Returns slideshow slide for image controls
		 * 
		 * @returns {Object} Slideshow slide
		 * @private
		 */
		getSlideshowSlide: function () {
			if (this.slide) return this.slide;
			
			var slideshow = this.getSlideshow(),
				has_image = this._hasImage(),
				slide = null,
				slide_id = this.getSlideshowSlideId(),
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
				
				if (!this.uiControlsRendered) {
					this.uiControlsRendered = true;
					
					// Advanced controls: repeat, position and attachment
					this.renderAdvancedControlsUI(container);
					
					//Set button
					button = this.widgets.buttonSet = (new Supra.Button({
						"label": Supra.Intl.get(["form", "block", "set_image"]),
						"style": "mid-blue"
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
						"style": "mid-blue"
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
				}
				
				//Update value
				this._uiSync(this.get('value'));
				
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
		
		_getImageFromValue: function (value) {
			return value && value.image ? value.image.image : "";
		},
		
		/**
		 * Extracts image data from value
		 *
		 * @param {Object|Null} value Value
		 * @returns {Object|Null} Image data or null
		 * @protected
		 */ 
		_uiSync: function (value) {
			this._uiFrozen = true;
			
			var value = (value === undefined || value === null ? "" : value),
				has_image = this._getImageFromValue(value),
				position = "",
				repeat = "",
				attachment = "";
			
			if (this.get('allowAdvancedControls')) {
				position = value && value.position ? value.position : DEFAULT_POSITION;
				repeat = value && value.repeat ? value.repeat : DEFAULT_REPEAT;
				attachment = value && value.attachment ? value.attachment : DEFAULT_ATTACHMENT;
			}
			
			if (this.widgets) {
				//Update UI
				if (this.widgets.selectList) {
					var classname = value && value.classname ? value.classname : (value.image ? "_custom" : "");
					this.widgets.selectList.set("value", classname);
				}
				
				if (this.widgets.buttonSet) {
					if (has_image) {
						this.widgets.buttonSet.set("label", Supra.Intl.get(["form", "block", "change_image"]));
					} else {
						this.widgets.buttonSet.set("label", Supra.Intl.get(["form", "block", "set_image"]));
					}
				}
				if (this.widgets.buttonRemove) {
					if (has_image) {
						this.widgets.buttonRemove.set("disabled", false);
					} else {
						this.widgets.buttonRemove.set("disabled", true);
					}
				}
				if (this.widgets.buttonEdit) {
					if (has_image) {
						this.widgets.buttonEdit.set("disabled", false);
					} else {
						this.widgets.buttonEdit.set("disabled", true);
					}
				}
				
				// Advanced controls
				if (this.widgets.inputPosition) {
					this.widgets.inputPosition.set("value", position);
					
					if (has_image) {
						this.widgets.inputPosition.set("disabled", false);
					} else {
						this.widgets.inputPosition.set("disabled", true);
					}
				}
				if (this.widgets.inputRepeat) {
					this.widgets.inputRepeat.set("value", repeat);
					
					if (has_image) {
						this.widgets.inputRepeat.set("disabled", false);
					} else {
						this.widgets.inputRepeat.set("disabled", true);
					}
				}
				if (this.widgets.inputAttachment) {
					this.widgets.inputAttachment.set("value", attachment);
					
					if (has_image) {
						this.widgets.inputAttachment.set("disabled", false);
					} else {
						this.widgets.inputAttachment.set("disabled", true);
					}
				}
				
				// Standalone button background preview
				if (this.widgets.buttonCustom) {
					var button = this.widgets.buttonCustom,
						style = '',
						icon = null;
					
					if (has_image) {
						icon = Supra.getObjectValue(value, ['image', 'image', 'sizes', '200x200', 'external_path']);
					}
					
					if (icon) {
						button.set('iconStyle', '');
					}
					
					if (button.get('iconStyle') != style) {
						button.set('iconStyle', style);
					}
					
					button.set('icon', icon);					
				}
			}
			
			this._uiFrozen = false;
		},
		
		
		/* ------------------------------ Attributes -------------------------------- */
		
		
		/**
		 * On target node change re-attach resize listeners for fixed background
		 * 
		 * @private
		 */
		_afterTargetNodeChange: function (e) {
			if (this.resizeListener) {
				this.resizeListener.detach();
				this.resizeListener = null;
			}

			if (e.newVal) {
				var node = e.newVal.getDOMNode(),
					win  = node.ownerDocument.defaultView || node.ownerDocument.parentWindow;
				
				if (win) {
					this.resizeListener = Y.Node(win).on('resize', Supra.throttle(this.onWindowResize, 150, this, true));
				}
			}
		},
		
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
		_setValue: function (_value) {
			var value = (_value === undefined || _value === null || typeof _value !== 'object' ? '' : _value),
				position,
				repeat,
				attachment;
			
			if (this.get('allowAdvancedControls')) {
				this.position = position = value && value.position ? value.position : DEFAULT_POSITION;
				this.repeat = repeat = value && value.repeat ? value.repeat : DEFAULT_REPEAT;
				this.attachment = attachment = value && value.attachment ? value.attachment : DEFAULT_ATTACHMENT;
			}
			
			if (value && value.image) {
				value = Supra.mix({}, value, true);
				value.image = Y.DataType.Image.parse(value.image);
				
				if (!value.image) {
					value.image = '';
				}
				
				this.image = value.image;
			} else {
				this.image = '';
			}
			
			this._uiSync(value);
			
			this.applyImageStyle({
				"image": this.image,
				"position": position,
				"repeat": repeat,
				"attachment": attachment
			});
			
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
			var value = {
				"classname": "",
				"image": ""
			};
			
			if (!this.widgets || !this.widgets.selectList) {
				if (this.get('allowAdvancedControls')) {
					value.position = this.position || DEFAULT_POSITION;
					value.repeat = this.repeat || DEFAULT_REPEAT;
					value.attachment = this.attachment || DEFAULT_ATTACHMENT;
				}
				
				return value;
			}
			
			value = {
				"classname": this.widgets.selectList.get("value") || "",
				"image": this.image ? this.image : ""
			};
			
			if (value.classname == "_custom") {
				value.classname = "";
			} else {
				value.image = "";
			}
			
			if (this.get('allowAdvancedControls')) {
				value.position = this.position;
				value.repeat = this.repeat;
				value.attachment = this.attachment;
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
			
			if (value && value.image && value.image.image) {
				value.image = Y.DataType.Image.format(value.image);
			} else {
				// There is no value
				value = null;
			}
			
			/*
			 * value == {
			 * 	   "classname": "",
			 *     "image": {
			 * 	       "image": "...id...",
			 *         "crop_height": Number, "crop_width": Number, "crop_left": Number, "crop_top": Number,
			 *         "size_width": Number, "size_height": Number
			 *     },
			 *     "repeat": "no-repeat",
			 *     "position": "0% 0%",
			 *     "attachment": "scroll"
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
		 * @private
		 */
		_afterValueChange: function (evt) {
			this.fire("change", {"value": this.get("value")});
		},
		
		/**
		 * When slide is opened start editing instead of waiting for user to click "Edit" button
		 * @param {Boolean} value Attribute value
		 * @return {Boolean} New attribute value
		 * @private
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
		 * @private
		 */
		_setAllowRemoveImage: function (value) {
			var button = this.widgets.buttonRemove;
			if (button) {
				button.set("visible", value);
			}
			return value;
		},
		
		/**
		 * Allow position, repetition and fixed position of image
		 * 
		 * @param {Boolean} value Attribute value
		 * @returns {Boolean} New attribute value
		 * @private
		 */
		_setAllowAdvancedControls: function (value) {
			if (this.widgets) {
				var inputRepeat     = this.widgets.inputRepeat,
					inputPosition   = this.widgets.inputPosition,
					inputAttachment = this.widgets.inputAttachment;
				
				if (inputRepeat) {
					inputRepeat.set('visible', value);
				}
				if (inputPosition) {
					inputPosition.set('visible', value);
				}
				if (inputAttachment) {
					inputAttachment.set('visible', value);
				}
			}
			
			return value;
		},
		
		
		/* ------------------------------ ATTRIBUTE CHANGE HANDLERS -------------------------------- */
		
		
		/**
		 * Disabled attribute change event listener
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_onDisabledAttrChange: function (e) {
			Input.superclass._onDisabledAttrChange.apply(this, arguments);
			
			if (e.newVal != e.prevVal && e.newVal) {
				// Stop editing
				this.closeSlide();
			}
		}
		
	});
	
	Supra.Input.BlockBackground = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});
