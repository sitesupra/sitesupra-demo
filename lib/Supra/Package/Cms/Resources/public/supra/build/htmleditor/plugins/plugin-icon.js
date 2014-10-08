YUI().add("supra.htmleditor-plugin-icon", function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/* Classname used for wrapper */
		wrapperClassName: 'supra-icon'
	};
	
	var Manager = Supra.Manager;
	
	/**
	 * Icon plugin, PORTAL ONLY!!!
	 */
	Supra.HTMLEditor.addPlugin("icon", defaultConfiguration, {
		
		settings_form: null,
		selected_icon: null,
		selected_icon_id: null,
		original_data: null,
		silent: false,
		
		/**
		 * DropTarget object for editor srcNode
		 * @type {Object}
		 * @private
		 */
		drop: null,
		
		/**
		 * Manage image
		 * @type {Object}
		 * @private
		 */
		resizer: null,
		
		/**
		 * Click event
		 * @type {Object}
		 * @private
		 */
		clickEvent: null,
		
		
		/**
		 * Generate settings form
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction("PageContentSettings").get("contentInnerNode");
			if (!content) return;
			
			//Find color presets
			var presets = [],
				container = this.htmleditor.get("srcNode"),
				styles = this.htmleditor.get("stylesheetParser").getSelectorsByNodeMatch(container)["COLOR"],
				i = 0,
				ii = styles.length;
			
			for (; i<ii; i++) {
				if (styles[i].attributes.color) {
					presets.push(styles[i].attributes.color);
				}
			}
			
			//Properties form
			var form_config = {
				"inputs": [
					{"id": "align", "style": "minimal", "type": "SelectList", "label": Supra.Intl.get(["htmleditor", "image_alignment"]), "value": "right", "values": [
						{"id": "left", "title": Supra.Intl.get(["htmleditor", "alignment_left"]), "icon": "/cms/lib/supra/img/htmleditor/align-left-button.png"},
						{"id": "middle", "title": Supra.Intl.get(["htmleditor", "alignment_center"]), "icon": "/cms/lib/supra/img/htmleditor/align-center-button.png"},
						{"id": "right", "title": Supra.Intl.get(["htmleditor", "alignment_right"]), "icon": "/cms/lib/supra/img/htmleditor/align-right-button.png"}
					]},
					{"id": "color", "type": "Color", "label": Supra.Intl.get(["htmleditor", "icon_color"]), "value": "#000000", "presets": presets}
				],
				"style": "vertical"
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
			
			//On title, description, etc. change update image data
			for(var i=0,ii=form_config.inputs.length; i<ii; i++) {
				form.getInput(form_config.inputs[i].id).on("change", this.onPropertyChange, this);
			}
			
			//Add "Delete", "Edit" and "Replace buttons"
			//Replace button
			var btn = new Supra.Button({"label": Supra.Intl.get(["htmleditor", "icon_replace"]), "style": "small-gray"});
				btn.render(form.get("contentBox"));
				btn.addClass("button-section");
				btn.on("click", this.replaceSelectedIcon, this);
				
				//Move into correct place
				form.get("contentBox").prepend(btn.get("boundingBox"));
			
			//Delete button
			var btn = new Supra.Button({"label": Supra.Intl.get(["htmleditor", "icon_delete"]), "style": "small-red"});
				btn.render(form.get("contentBox"));
				btn.addClass("su-button-delete");
				btn.on("click", this.removeSelectedIcon, this);
			
			this.settings_form = form;
			return form;
		},
		
		/**
		 * Returns true if form is visible, otherwise false
		 */
		hideSettingsForm: function () {
			if (this.settings_form && this.settings_form.get("visible")) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * Apply settings changes
		 */
		settingsFormApply: function () {
			if (this.selected_icon) {
				this.stopEditIcon();
				
				var ancestor = this.getIconWrapperNode(this.selected_icon),
					classname = this.configuration.wrapperClassName;
				
				ancestor.removeClass(classname + "-selected");

				
				this.selected_icon = null;
				this.selected_icon_id = null;
				this.original_data = null;
				
				this.hideSettingsForm();
				this.hideIconSidebar();
				
				//Property changed, update editor "changed" state
				this.htmleditor._changed();
			}
		},
		
		/**
		 * Replace selected image with another one from media library
		 * 
		 * @private
		 */
		replaceSelectedIcon: function () {
			//Open Media library on "Replace"
			if (this.selected_icon) {
				var icon = this.selected_icon,
					icon_id = this.selected_icon_id,
					data = this.original_data;
				
				//Open settings form and open IconSidebar
				this.stopEditIcon();
				this.hideSettingsForm();
				
				Manager.getAction("IconSidebar").execute({
					onselect: Y.bind(function (data) {
						// Restore selection
						this.selected_icon = icon;
						this.selected_icon_id = icon_id;
						this.original_data = data;
						
						this.insertIcon(data);
					}, this)
				});
			}
		},
		
		/**
		 * Remove selected i
		 * 
		 * @private
		 */
		removeSelectedIcon: function () {
			if (this.selected_icon_id) {
				this.removeIcon(this.selected_icon_id);
			}
		},
		
		/**
		 * Remove icon
		 * 
		 * @private
		 */
		removeIcon: function (id) {
			var current = this.selected_icon && this.selected_icon_id == id;
			if (current) {
				this.stopEditIcon();
			}
			
			var image = current ? this.selected_icon : this.htmleditor.one('#' + id),
				container = image ? image.ancestor() : null,
				classname = this.configuration.wrapperClassName;
			
			if (container) {
				if (container.test("." + classname)) {
					container.remove();
				} else {
					image.remove();
				}
			}
			
			if (current) {
				this.selected_icon = null;
				this.selected_icon_id = null;
				this.original_data = null;
				this.htmleditor.refresh(true);
				this.hideSettingsForm();
			}
		},
		
		/**
		 * Handle property input value change
		 * Save data and update UI
		 * 
		 * @param {Object} event Event
		 */
		onPropertyChange: function (event) {
			if (this.silent || !this.selected_icon) return;
			
			var target = event.target,
				id = target.get("id"),
				icon_id = this.selected_icon_id,
				data = this.htmleditor.getData(icon_id),
				value = (event.value !== undefined ? event.value : target.getValue());
			
			//Update image data
			if (icon_id) {
				data[id] = value;
				this.htmleditor.setData(icon_id, data);
			}
			
			this.setIconProperty(id, value);
		},
		
		/**
		 * Update image tag property
		 * 
		 * @param {String} id Property ID
		 * @param {String} value Property value
		 */
		setIconProperty: function (id, value, node) {
			if (!node) node = this.selected_icon;
			var ancestor = this.getIconWrapperNode(node);
			
			if (id == "align") {
				ancestor.removeClass("align-left").removeClass("align-right").removeClass("align-middle");
				node.removeClass("align-left").removeClass("align-right").removeClass("align-middle");
				
				if (value) {
					ancestor.addClass("align-" + value);
					node.addClass("align-" + value);
				}
			} else if (id == "width") {
				
				value = parseInt(value) || 0;
				var data = this.htmleditor.getData(this.selected_icon_id),
					ratio = data.width / data.height,
					height = value ? Math.round(value / ratio) : data.height,
					width = value || data.width;
				
				data.width = width;
				data.height = height;
				
				node.setAttribute('width', width + 'px');
				node.setAttribute('height', height + 'px');
				
				node.setStyles({
					'width': width + 'px',
					'height': height + 'px'
				});
				
			} else if (id == "height") {
				
				value = parseInt(value) || 0;
				var data = this.htmleditor.getData(this.selected_icon_id),
					ratio = data.width / data.height,
					width = value ? Math.round(value * ratio) : data.width,
					height = value || data.height;
				
				data.width = width;
				data.height = height;
				
				node.setAttribute('width', width + 'px');
				node.setAttribute('height', height + 'px');
				
				node.setStyles({
					'width': width + 'px',
					'height': height + 'px'
				});
			} else if (id == "color") {
				
				var data = this.htmleditor.getData(this.selected_icon_id);
				data.color = value;
				node.setStyle("fill", value);
				
			} else if (id == "icon") {
				value.render(node);
			}
		},
		
		/**
		 * Returns icon wrapper node
		 * If node doesn't exist then creates it
		 * 
		 * @param {HTMLElement} image Image element
		 * @return Image wrapper node
		 */
		getIconWrapperNode: function (icon) {
			var ancestor = icon.ancestor(),
				classname = this.configuration.wrapperClassName;
			
			if (ancestor) {
				if (!ancestor.test("span." + classname)) {
					ancestor = ancestor.ancestor();
					if (ancestor && !ancestor.test("span." + classname)) {
						ancestor = null;
					}
				}
			}
			
			if (!ancestor) {
				//Wrap image in <span class="supra-icon">
				ancestor = Y.Node(this.htmleditor.get("doc").createElement("SPAN"));
				ancestor.addClass(classname);
				ancestor.setAttribute("contenteditable", false);
				ancestor.setAttribute("unselectable", "on");
				
				var data = this.getIconDataFromNode(icon);
				if (!data) {
					// This icon is not associated with any data,
					// there's nothing we can do about it
					return;
				}
				
				if (data.align) {
					ancestor.addClass("align-" + data.align);
				}
				
				var width  = data.width || parseInt(Y.DOM.getStyle(icon, 'width') || 0, 10),
					height = data.height || parseInt(Y.DOM.getStyle(icon, 'height') || 0, 10);
				
				ancestor.setStyles({
					"width": width + "px",
					"height": height + "px"
				});
				
				icon.insert(ancestor, "before");
				ancestor.append(icon);
			}
			
			return ancestor;
		},
		
		/**
		 * Returns image data from node
		 * 
		 * @param {HTMLElement} node Node
		 * @return Image data
		 * @type {Object}
		 */
		getIconDataFromNode: function (node) {
			var data = this.htmleditor.getData(node);
			return data;
		},
		
		/**
		 * Show icon settings bar
		 */
		showIconSettings: function (target) {
			if (target.test(".gallery")) return false;
			
			var data = this.getIconDataFromNode(target),
				ancestor = this.getIconWrapperNode(target); // creates wrapper if it doesn't exist
			
			if (!data) {
				Y.log("Missing image data for icon " + target.getAttribute("src"), "debug");
				return false;
			}
			
			//Make sure PageContentSettings is rendered
			var form = this.settings_form || this.createSettingsForm(),
				action = Manager.getAction("PageContentSettings");
			
			if (!form) {
				if (action.get("loaded")) {
					if (!action.get("created")) {
						action.renderAction();
						this.showIconSettings(target);
					}
				} else {
					action.once("loaded", function () {
						this.showIconSettings(target);
					}, this);
					action.load();
				}
				return false;
			}
			
			if (!Manager.getAction('PageToolbar').hasActionButtons("htmleditor-plugin")) {
				Manager.getAction('PageToolbar').addActionButtons("htmleditor-plugin", []);
				Manager.getAction('PageButtons').addActionButtons("htmleditor-plugin", []);
			}
			
			action.execute(form, {
				"hideCallback": Y.bind(this.settingsFormApply, this),
				"title": Supra.Intl.get(["htmleditor", "icon_properties"]),
				"scrollable": true,
				"toolbarActionName": "htmleditor-plugin"
			});
			
			//
			this.selected_icon = target;
			this.selected_icon_id = this.selected_icon.getAttribute("id");
			
			var classname = this.configuration.wrapperClassName,
				ancestor = this.getIconWrapperNode(this.selected_icon);
			
			ancestor.addClass(classname + "-selected");
			
			this.silent = true;			
			this.settings_form.resetValues()
							  .setValues({"align": data.align, "color": data.color}, "id");
			this.silent = false;
			
			//Clone data because data properties will change and orginal properties should stay intact
			this.original_data = data;
			
			//Start editing image immediatelly
			this.editIcon();
			
			return true;
		},
		
		/**
		 * Show/hide media library bar
		 */
		toggleIconSidebar: function () {
			var button = this.htmleditor.get("toolbar").getButton("inserticon");
			if (button.get("down")) {
				Manager.executeAction("IconSidebar", {
					"onselect": Y.bind(this.insertIcon, this),
					"hideToolbar": true
				});
			} else {
				this.hideIconSidebar();
			}
		},
		
		/**
		 * Hide media library bar
		 */
		hideIconSidebar: function () {
			Manager.getAction("IconSidebar").hide();
		},
		
		
		/* ------------------------------- Manage image --------------------------- */
		
		
		/**
		 * Open image management
		 * 
		 * @private
		 */
		editIcon: function () {
			var node = this.selected_icon,
				ancestor = null,
				data  = this.original_data,
				size = null,
				resizer = this.resizer,
				max_size = 0,
				min_size = 16,
				ratio = 0;
			
			if (node) {
				if (!resizer) {
					this.resizer = resizer = new Supra.ImageResizer({
						"autoClose": false,
						"mode": Supra.ImageResizer.MODE_ICON,
						"allowZoomResize": true,
						"minCropWidth": min_size,
						"minCropHeight": min_size
					});
					resizer.on("resize", this.onEditIconResize, this);
				}
				
				//Find content width
				ancestor = node.ancestor();
				if (ancestor.test("." + this.configuration.wrapperClassName)) {
					ancestor = ancestor.ancestor();
				}
				
				max_size = Math.max(min_size, ancestor.get("offsetWidth") || 220);
				ratio = data.width / data.height;
				
				resizer.set("maxCropWidth", max_size);
				resizer.set("maxCropHeight", Math.round(max_size / ratio));
				resizer.set("maxImageWidth", max_size);
				resizer.set("maxImageHeight", Math.round(max_size / ratio));
				resizer.set("minImageWidth", min_size);
				resizer.set("minImageHeight", Math.round(min_size / ratio));
				resizer.set("image", node);
			}
		},
		
		/**
		 * Handle image resize
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onEditIconResize: function (event) {
			//Preserve image data
			var node = event.image,
				id = node.getAttribute("id"),
				data  = this.getIconDataFromNode(node);
			
			if (!data) {
				//Can't find image data, where this image appeared from?
				return;
			}
			
			data.width = event.imageWidth;
			data.height = event.imageHeight;
			
			this.htmleditor.setData(id, data);
			
			//Property changed, update editor 'changed' state
			this.htmleditor._changed();
		},
		
		/**
		 * Stop image management
		 * 
		 * @private
		 */
		stopEditIcon: function () {
			if (this.resizer && this.resizer.get("image")) {
				this.resizer.set("image", null);
			}
		},
		
		
		/* ------------------------------- Image insert/drop -------------------------- */
		
		
		/**
		 * Insert image into HTMLEditor content
		 * 
		 * @param {Object} event
		 */
		insertIcon: function (event) {
			var htmleditor = this.htmleditor;
			
			var locale = Supra.data.get("locale");
			
			if (!htmleditor.get("disabled") && htmleditor.isSelectionEditable(htmleditor.getSelection())) {
				var icon = event.icon;
				
				if (this.selected_icon) {
					//If icon in content is already selected, then replace
					var iconId = this.selected_icon_id,
						iconData = this.htmleditor.getData(iconId),
						data = icon;
					
					icon.width = iconData.width;
					icon.height = iconData.height;
					icon.align = iconData.align;
					icon.color = iconData.color;
					icon.type = this.NAME;
					
					//Preserve image data
					this.htmleditor.setData(iconId, data);
					
					//Update icon attributes
					this.setIconProperty("icon", data);
					this.setIconProperty("width", data.width);
					this.setIconProperty("height", data.height);
					
					this.editIcon();
				} else {
					//Calculate icon size so that it fills container
					var container_width = htmleditor.get("srcNode").get("offsetWidth");
					
					if (container_width < icon.width) {
						icon.height = Math.round(container_width / icon.width * icon.height);
						icon.width  = container_width;
					}
					
					//Icon data
					icon.type = this.NAME;
					
					//Generate unique ID for image element, to which data will be attached
					var uid = htmleditor.generateDataUID(),
						html = icon.toHTML({'id': uid}, true),
						classname = this.configuration.wrapperClassName;
					
					htmleditor.replaceSelection('<span class="' + classname + (icon.align ? ' align-' + icon.align : '') + '" unselectable="on" contenteditable="false" style="width: ' + icon.width + 'px; height: ' + icon.height + 'px;">' + html + '</span>');
					htmleditor.setData(uid, icon);
					
					if (!icon.isDataComplete()) {
						// Load
						icon.load()
							.done(function () {
								var node = htmleditor.one('#' + uid);
								icon.render(node);
							}, this)
							.fail(function () {
								// Couldn't load icon SVG data, remove
								this.removeIcon(uid);
							}, this);
					}
				}
				
				this.hideIconSidebar();
			}
		},
		
		/**
		 * Update image after it was dropped using HTML5 drag & drop
		 * 
		 * @param {Object} event
		 */
		dropImage: function (target, image_id) {
			//@TODO
			
			//If dropped on un-editable element
			if (!this.htmleditor.isEditable(target)) return true;
			if (!Manager.MediaSidebar) return true;
			
			var htmleditor = this.htmleditor,
				dataObject = Manager.MediaSidebar.dataObject(),
				image_data = dataObject.cache.one(image_id);
			
			if (image_data.type != Supra.MediaLibraryList.TYPE_IMAGE) {
				//Only handling images; folders should be handled by gallery plugin 
				return false;
			}
			
			if (dataObject.has(image_id) != 2) {
				// Load full data for image
				dataObject.one(image_id, true).done(function () {
					this.dropImage(target, image_id);
				}, this);
				return true;
			}
			
			var uid = htmleditor.generateDataUID(),
				size_data = this.getImageDataBySize(image_data, "original"),
				src = this.getImageURLBySize(image_data),
				img = null;
			
			var locale = Supra.data.get("locale");
			
			//Calculate image size so that it fills container
			var container_width = htmleditor.get("srcNode").get("offsetWidth"),
				size_width = size_data.width,
				size_height = size_data.height;
			
			if (container_width < size_width) {
				size_height = Math.round(container_width / size_width * size_height);
				size_width = container_width;
			}
			
			//Set additional image properties
			var data = Supra.mix({}, {
				"type": this.NAME,
				"title": (image_data.title && image_data.title[locale]) ? image_data.title[locale] : "",
				"description": (image_data.description && image_data.description[locale]) ? image_data.description[locale] : "",
				"image": image_data,	//Original image data
				"size_width": size_width,
				"size_height": size_height,
				"crop_left": 0,
				"crop_top": 0,
				"crop_width": size_width,
				"crop_height": size_height
			});
			
			img = Y.Node.create('<span class="' + this.configuration.wrapperClassName + ' align-' + data.align + '" unselectable="on" contenteditable="false" style="width: ' + data.crop_width + 'px; height: ' + data.crop_height + 'px;"><img id="' + uid + '" style="margin-left: -' + data.crop_left + 'px; margin-top: -' + data.crop_top + 'px;" width="' + data.size_width + '" src="' + src + '" title="' + Y.Escape.html(data.title) + '" alt="' + Y.Escape.html(data.description) + '" class="align-' + data.align + '" />');

			//If droping on inline element then insert image before it, otherwise append to element
			if (target.test("em,i,strong,b,s,strike,sub,sup,u,a,span,big,small,img")) {
				target.insert(img, "before");
			} else {
				target.prepend(img);
			}
			
			//Save into HTML editor data about image
			htmleditor.setData(uid, data);
			
			return true;
		},
		
		/**
		 * On node change check if selected node is image and show settings
		 * 
		 * @private
		 */
		onNodeChange: function () {
 			var element = this.htmleditor.getSelectedElement("svg"),
				container = this.htmleditor.get('srcNode'),
				button = htmleditor.get("toolbar").getButton("inserticon");
				allowEditing = this.htmleditor.editingAllowed;
 			
			if (allowEditing && element && Y.Node(element).closest(container)) {
 				if (!this.showIconSettings(Y.Node(element))) {
 					this.settingsFormApply();
 				}
 			}
			
			if (!allowEditing || this.htmleditor.getSelectedElement("svg, img")) {
				button.set('disabled', true);
			} else {
				button.set('disabled', !allowEditing);
			}
		},
		
		/**
		 * If clicking outside image then hide settings form
		 * 
		 * @private
		 */
		documentClick: function (e) {
			var classname = this.configuration.wrapperClassName;
			
			if (e.target && !e.target.closest("span." + classname)) {
				this.settingsFormApply();
			}
		},
			
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			// If not portal, then don't do anything
			//if (!Supra.data.get(['site', 'portal'])) return;
			
			var iconsidebar = Manager.getAction("IconSidebar"),
				toolbar = htmleditor.get("toolbar"),
				button = toolbar ? toolbar.getButton("inserticon") : null;
			
			// When HTML changes make sure images has wrapper elements
			htmleditor.on("afterSetHTML", this.afterSetHTML, this);
			
			// Add command
			htmleditor.addCommand("inserticon", Y.bind(this.toggleIconSidebar, this));
			
			// When clicking outside icon hide icon settings
			this.clickEvent = Y.Node(htmleditor.get("doc")).on("mousedown", this.documentClick, this)
			
			// When clicking on icon show icon settings
			htmleditor.on("nodeChange", this.onNodeChange, this);
			
			if (button) {
				//Show button
				button.show();
				
				//When icon library is shown/hidden make button selected/unselected
				iconsidebar.after("visibleChange", function (evt) {
					button.set("down", evt.newVal);
				});
				
				//When un-editable node is selected disable iconsidebar toolbar button
				htmleditor.on("editingAllowedChange", function (event) {
					button.set("disabled", !event.allowed);
				});
			}
			
			if (!Manager.getAction('PageToolbar').hasActionButtons("htmleditor-plugin")) {
				Manager.getAction('PageToolbar').addActionButtons("htmleditor-plugin", []);
				Manager.getAction('PageButtons').addActionButtons("htmleditor-plugin", []);
			}
			
			//When media library is hidden show settings form if image is selected
			iconsidebar.on("hide", function () {
				if (this.selected_icon) {
					Manager.executeAction("PageContentSettings", this.settings_form, {
						"doneCallback": Y.bind(this.settingsFormApply, this),
						
						"title": Supra.Intl.get(["htmleditor", "icon_properties"]),
						"scrollable": true,
						"toolbarActionName": "htmleditor-plugin"
					});
				}
			}, this);
			
			//Hide media library when editor is closed
			htmleditor.on("disable", this.hideIconSidebar, this);
			htmleditor.on("disable", this.settingsFormApply, this);
			htmleditor.on("disable", this.stopEditIcon, this);
			
			//this.bindUIDnD(htmleditor);
		},
		
		bindUIDnD: function (htmleditor) {
			//@TODO
			var srcNode = htmleditor.get("srcNode"),
				doc = htmleditor.get("doc");
			
			//On drop insert image
			srcNode.on("dataDrop", this.onDrop, this);
			
			//Enable drag & drop
			if (Manager.PageContent) {
				this.drop = new Manager.PageContent.PluginDropTarget({
					"srcNode": srcNode,
					"doc": doc
				});
			}
		},
		
		/**
		 * Handle drop
		 * 
		 * @param {Object} e Event
		 */
		onDrop: function (e) {
			//@TODO			
			var image_id = e.drag_id;
			if (!image_id) return;
			
			//Only if dropped from gallery
			if (image_id.match(/^\d[a-z0-9]+$/i) && e.drop) {
				if (this.dropImage(e.drop, image_id)) {
					//If image drop was successful then prevent other plugins
					//from doing anything
					if (e.halt) e.halt();
					return false;
				}
			}
		},
		
		/**
		 * Clean up node
		 * Remove all styles and data about node
		 */
		cleanUp: function (target, data) {
			if (target.test("svg") && data && data.type == this.NAME) {
				this.htmleditor.removeData(target);
				this.setIconProperty("align", "", target);
			}
		},
		
		/**
		 * Unclean HTML, add wrapper node around it
		 */
		afterSetHTML: function (event) {
			var htmleditor = this.htmleditor,
				node = htmleditor.get("srcNode"),
				icons = node.all("svg"),
				i = 0,
				ii = icons.size(),
				data = null,
				data_icon = null;
			
			for (; i<ii; i++) {
				data = htmleditor.getData(icons.item(i));
				if (data && data.type == "icon") {
					data_icon = new Y.DataType.Icon(data);
					data_icon.render(icons.item(i));
					
					this.getIconWrapperNode(icons.item(i));
				}
			}
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			if (this.clickEvent) this.clickEvent.detach();
			this.clickEvent = null;
		},
		
		/**
		 * Process HTML and replace all nodes with supra tags {supra.icon id="..."}
		 * Called before HTML is saved
		 * 
		 * @param {String} html
		 * @return Processed HTML
		 * @type {String}
		 */
		tagHTML: function (html) {
			var htmleditor = this.htmleditor,
				NAME = this.NAME;
			
			html = html.replace(/<svg [^>]*id="([^"]+)"[\s\S]*?<\/svg>/ig, function (html, id) {
				if (!id) return html;
				var data = htmleditor.getData(id);
				
				if (data && data.type == NAME) {
					return "{supra." + NAME + " id=\"" + id + "\"}";
				} else {
					return html;
				}
			});
			return html;
		},
		
		/**
		 * Process HTML and replace all supra tags with nodes
		 * Called before HTML is set
		 * 
		 * @param {String} html HTML
		 * @param {Object} data Data
		 * @return Processed HTML
		 * @type {String}
		 */
		untagHTML: function (html, data) {
			var NAME = this.NAME,
				self = this;
			
			html = html.replace(/{supra\.icon id="([^"]+)"}/ig, function (tag, id) {
				if (!id || !data[id] || data[id].type != NAME) return "";
				
				var item = data[id],
					icon = new Y.DataType.Icon(item);
				
				if (icon.isDataComplete()) {
					var classname = self.configuration.wrapperClassName + " " + (icon.align ? "align-" + icon.align : "");
					var svg = icon.toHTML({'id': id});
					var html = '<span class="' + classname + '" unselectable="on" contenteditable="false" style="width: ' + icon.width + 'px; height: ' + icon.height + 'px;">' + svg + '</span>';
					
					return html;
				}
				
				return "";
			});
			
			return html;
		},
		
		/**
		 * Process data and remove all unneeded before it's sent to server
		 * Called before save
		 * 
		 * @param {String} id Data ID
		 * @param {Object} data Data
		 * @return Processed data
		 * @type {Object}
		 */
		processData: function (id, data) {
			return data;
		}
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {"requires": ["supra.htmleditor-base", "supra.input-proto"]});
