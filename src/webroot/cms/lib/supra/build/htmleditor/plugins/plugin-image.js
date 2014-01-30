YUI().add("supra.htmleditor-plugin-image", function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/* Default image size */
		size: "200x200",
		
		/* Allow none, border, lightbox styles */
		styles: true,
		
		/* Classname used for wrapper */
		wrapperClassName: 'supra-image'
	};
	
	var defaultProps = {
		"type": null,
		"title": "",
		"description": "",
		"align": "middle",
		"style": "",
		"image": {}
	};
	
	var Manager = Supra.Manager;
	
	Supra.HTMLEditor.addPlugin("image", defaultConfiguration, {
		
		settings_form: null,
		selected_image: null,
		selected_image_id: null,
		original_data: null,
		silent: false,
		
		/**
		 * DropTarget object for editor srcNode
		 * @type {Object}
		 * @private
		 */
		drop: null,
		
		/**
		 * List of image styles
		 * @type {Array}
		 * @private
		 */
		image_styles: null,
		
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
			
			//Properties form
			var form_config = {
				"inputs": [
					{"id": "title", "type": "String", "label": Supra.Intl.get(["htmleditor", "image_title"]), "value": ""},
					{"id": "description", "type": "String", "label": Supra.Intl.get(["htmleditor", "image_description"]), "value": ""},
					{"id": "align", "style": "minimal", "type": "SelectList", "label": Supra.Intl.get(["htmleditor", "image_alignment"]), "value": "right", "values": [
						{"id": "left", "title": Supra.Intl.get(["htmleditor", "alignment_left"]), "icon": "/cms/lib/supra/img/htmleditor/align-left-button.png"},
						{"id": "middle", "title": Supra.Intl.get(["htmleditor", "alignment_center"]), "icon": "/cms/lib/supra/img/htmleditor/align-center-button.png"},
						{"id": "right", "title": Supra.Intl.get(["htmleditor", "alignment_right"]), "icon": "/cms/lib/supra/img/htmleditor/align-right-button.png"}
					]},
					{"id": "style", "style": "minimal", "type": "SelectVisual", "label": Supra.Intl.get(["htmleditor", "image_style"]), "value": "default", "values": []}
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
			
			//If in configuration styles are disabled, then hide buttons
			if (this.configuration.styles) {
				form.getInput("style").set("visible", true);
			} else {
				form.getInput("style").set("visible", false);
			}
			
			//Fill style list
			this.fillStylesList(form);
			
			//Add "Delete", "Edit" and "Replace buttons"
			//Replace button
			var btn = new Supra.Button({"label": Supra.Intl.get(["htmleditor", "image_replace"]), "style": "small-gray"});
				btn.render(form.get("contentBox"));
				btn.addClass("button-section");
				btn.on("click", this.replaceSelectedImage, this);
				
				//Move into correct place
				form.get("contentBox").prepend(btn.get("boundingBox"));
			
			//Delete button
			var btn = new Supra.Button({"label": Supra.Intl.get(["htmleditor", "image_delete"]), "style": "small-red"});
				btn.render(form.get("contentBox"));
				btn.addClass("su-button-delete");
				btn.on("click", this.removeSelectedImage, this);
			
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
			if (this.selected_image) {
				this.stopEditImage();
				
				var ancestor = this.getImageWrapperNode(this.selected_image),
					classname = this.configuration.wrapperClassName;
				
				ancestor.removeClass(classname + "-selected");
				
				this.selected_image = null;
				this.selected_image_id = null;
				this.original_data = null;
				
				this.hideSettingsForm();
				this.hideMediaSidebar();
				
				//Property changed, update editor "changed" state
				this.htmleditor._changed();
			}
		},
		
		/**
		 * Fill styles list
		 */
		fillStylesList: function (form) {
			var container = this.htmleditor.get("srcNode"),
				styles = this.htmleditor.get("stylesheetParser").getSelectorsByNodeMatch(container)["IMG"],
				
				input  = form.getInput("style"),
				values = [{
							"id": "",
							"title": Supra.Intl.get(["htmleditor", "image_style_none"]),
							"icon": ""
						 }];
			
			if (styles && styles.length) {
				
				for (var i=0, ii=styles.length; i<ii; i++) {
					values.push({
						"id": styles[i].classname,
						"title": styles[i].attributes.title || styles[i].classname,
						"icon": styles[i].attributes.icon || ""
					});
				}
				
				input.set("values", values)
				input.show();
			} else {
				input.hide();
			}
			
			//Save to reuse when changing style
			this.image_styles = styles;
		},
		
		/**
		 * Replace selected image with another one from media library
		 * 
		 * @private
		 */
		replaceSelectedImage: function () {
			//Open Media library on "Replace"
			var image = this.selected_image,
				image_id = this.selected_image_id,
				data = this.original_data,
				path = null;
			
			if (image) {
				//Open settings form and open MediaSidebar
				this.stopEditImage();
				this.hideSettingsForm();
				
				//Restore selected image reference, which was removed in hideSettingsForm
				this.selected_image = image;
				this.selected_image_id = image_id;
				this.original_data = data;
				
				if (data && data.image && data.image.id) {
					path = [].concat(data.image.path || []).concat([data.image.id]);
				}
				
				Manager.getAction("MediaSidebar").execute({
					onselect: Y.bind(this.insertImage, this),
					item: path
				});
			}
		},
		
		/**
		 * Remove selected image
		 * 
		 * @private
		 */
		removeSelectedImage: function () {
			if (this.selected_image) {
				this.stopEditImage();
				
				var image = this.selected_image,
					container = image.ancestor(),
					classname = this.configuration.wrapperClassName;
								
				if (container.test("." + classname)) {
					container.remove();
				} else {
					image.remove();
				}
				
				this.selected_image = null;
				this.selected_image_id = null;
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
			
			if (this.silent || !this.selected_image) return;
			
			var target = event.target,
				id = target.get("id"),
				imageId = this.selected_image_id,
				data = this.htmleditor.getData(imageId),
				value = (event.value !== undefined ? event.value : target.getValue());
			
			//Update image data
			if (imageId) {
				data[id] = value;
				this.htmleditor.setData(imageId, data);
			}
			
			this.setImageProperty(id, value);
		},
		
		/**
		 * Update image tag property
		 * 
		 * @param {String} id Property ID
		 * @param {String} value Property value
		 */
		setImageProperty: function (id, value, image) {
			if (!image) image = this.selected_image;
			
			var ancestor = this.getImageWrapperNode(image);
			
			if (id == "title") {
				image.setAttribute("title", value);
			} else if (id == "description") {
				image.setAttribute("alt", value);
			} else if (id == "align") {
				ancestor.removeClass("align-left").removeClass("align-right").removeClass("align-middle");
				image.removeClass("align-left").removeClass("align-right").removeClass("align-middle");
				
				if (value) {
					ancestor.addClass("align-" + value);
					image.addClass("align-" + value);
				}
			} else if (id == "size_width") {
				
				value = parseInt(value) || 0;
				var data = this.htmleditor.getData(this.selected_image_id),
					size = this.getImageDataBySize(data.image),
					ratio = size.width / size.height,
					height = value ? Math.round(value / ratio) : size.height,
					width = value || size.width;
				
				data.size_width = width;
				data.size_height = height;
				image.setAttribute("width", width);
				image.setAttribute("height", height);
				
			} else if (id == "size_height") {
				
				value = parseInt(value) || 0;
				var data = this.htmleditor.getData(this.selected_image_id),
					size = this.getImageDataBySize(data.image),
					ratio = size.width / size.height,
					width = value ? Math.round(value * ratio) : size.width,
					height = value || size.height;
				
				data.size_width = width;
				data.size_height = height;
				image.setAttribute("width", width);
				image.setAttribute("height", height);
				
			} else if (id == "style") {
				var styles = this.image_styles,
					s = 0,
					ss = styles.length;
				
				if (styles && styles.length) {
					for (; s<ss; s++) {
						if (styles[s].classname) {
							ancestor.removeClass(styles[s].classname);
							image.removeClass(styles[s].classname);
						}
					}
				}
				
				if (value) {
					ancestor.addClass(value);
					image.addClass(value);
				}
			} else if (id == "image") {
				image.setAttribute("src", this.getImageURLBySize(value));
				
				//If lightbox then also update link
				if (this.getImageProperty("style") == "lightbox") {
					this.setImageProperty("style", "lightbox", image);
				}
			} else if (id == "crop_width") {
				ancestor.setStyle("width", value + "px");
			} else if (id == "crop_height") {
				ancestor.setStyle("height", value + "px");
			} else if (id == "crop_left") {
				image.setStyle("marginLeft", - value + "px");
			} else if (id == "crop_top") {
				image.setStyle("marginTop", - value + "px");
			}
		},
		
		/**
		 * Returns image wrapper node
		 * If node doesn't exist then creates it
		 * 
		 * @param {HTMLElement} image Image element
		 * @return Image wrapper node
		 */
		getImageWrapperNode: function (image) {
			var ancestor = image.ancestor(),
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
				//Wrap image in <span class="supra-image">
				ancestor = Y.Node(this.htmleditor.get("doc").createElement("SPAN"));
				ancestor.addClass(classname);
				ancestor.setAttribute("contenteditable", false);
				ancestor.setAttribute("unselectable", "on");
				
				var data = this.getImageDataFromNode(image);
				if (!data) {
					// This image is not associated with any data,
					// there's nothing we can do about it
					return;
				}
				
				if (data.style) {
					ancestor.addClass(data.style);
				}
				if (data.align) {
					ancestor.addClass("align-" + data.align);
				}
				
				var crop_left   = data.crop_left   || image.getAttribute("data-crop-left")   || 0,
					crop_top    = data.crop_top    || image.getAttribute("data-crop-top")    || 0,
					crop_width  = data.crop_width  || image.getAttribute("data-crop-width")  || image.width,
					crop_height = data.crop_height || image.getAttribute("data-crop-height") || image.height,
					data_width  = data.size_width  || image.getAttribute("data-width")       || image.width,
					data_height = data.size_height || image.getAttribute("data-height")      || image.height;
				
				image.setAttribute("width", data_width);
				image.setAttribute("height", data_height);
				image.setStyles({
					"margin-left": crop_left ? -crop_left + "px" : "0px",
					"margin-top": crop_top ? -crop_top + "px" : "0px",
					"width": data_width ? data_width + "px" : "",
					"height": data_height ? data_height + "px" : ""
				});
				ancestor.setStyles({
					"width": crop_width ? crop_width + "px" : "auto",
					"height": crop_height ? crop_height + "px" : "auto"
				});
				
				//Set image to original
				var data = this.getImageDataFromNode(image),
					url  = this.getImageURLBySize(data.image, "original");
				
				image.setAttribute("src", url);
				image.removeAttribute("align");
				
				image.insert(ancestor, "before");
				ancestor.append(image);
			}
			
			return ancestor;
		},
		
		/**
		 * Returns image property value
		 * 
		 * @param {String} id
		 */
		getImageProperty: function (id) {
			if (this.selected_image) {
				var data = this.htmleditor.getData(this.selected_image_id);
				return id in data ? data[id] : null;
			}
			return null;
		},
		
		/**
		 * Returns image data from node
		 * 
		 * @param {HTMLElement} node Node
		 * @return Image data
		 * @type {Object}
		 */
		getImageDataFromNode: function (node) {
			var data = this.htmleditor.getData(node);
			if (!data && node.test("img")) {
				//Parse node and try to fill all properties
				/*
				data = Supra.mix({}, defaultProps, {
					"title": node.getAttribute("title") || "",
					"description": node.getAttribute("alt") || "",
					"align": node.getAttribute("align") || "left",
					"size_width": node.getAttribute("width") || node.offsetWidth || 0,
					"size_height": node.getAttribute("height") || node.offsetHeight || 0,
					"style": node.hasClass("lightbox") ? "lightbox" : (node.hasClass("border") ? "border" : ""),
					"image": "" //We don't know ID
				});
				this.htmleditor.setData(node, data);
				*/
			}
			return data;
		},
		
		/**
		 * Show image settings bar
		 */
		showImageSettings: function (target) {
			if (target.test(".gallery")) return false;
			
			var data = this.getImageDataFromNode(target),
				ancestor = this.getImageWrapperNode(target); // creates wrapper if it doesn't exist
			
			if (!data) {
				Y.log("Missing image data for image " + target.getAttribute("src"), "debug");
				return false;
			}
			
			//Make sure PageContentSettings is rendered
			var form = this.settings_form || this.createSettingsForm(),
				action = Manager.getAction("PageContentSettings");
			
			if (!form) {
				if (action.get("loaded")) {
					if (!action.get("created")) {
						action.renderAction();
						this.showImageSettings(target);
					}
				} else {
					action.once("loaded", function () {
						this.showImageSettings(target);
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
				"title": Supra.Intl.get(["htmleditor", "image_properties"]),
				"scrollable": true,
				"toolbarActionName": "htmleditor-plugin"
			});
			
			//
			this.selected_image = target;
			this.selected_image_id = this.selected_image.getAttribute("id");
			
			var ancestor = this.getImageWrapperNode(this.selected_image),
				classname = this.configuration.wrapperClassName;
				
			ancestor.addClass(classname + "-selected");
			
			this.silent = true;			
			this.settings_form.resetValues()
							  .setValues(data, "id");
			this.silent = false;
			
			//Clone data because data properties will change and orginal properties should stay intact
			this.original_data = Supra.mix({}, data);
			
			//Start editing image immediatelly
			this.editImage();
			
			return true;
		},
		
		/**
		 * Show/hide media library bar
		 */
		toggleMediaSidebar: function () {
			var button = this.htmleditor.get("toolbar").getButton("insertimage");
			if (button.get("down")) {
				Manager.executeAction("MediaSidebar", {
					"onselect": Y.bind(this.insertImage, this),
					"hideToolbar": true
				});
			} else {
				this.hideMediaSidebar();
			}
		},
		
		/**
		 * Hide media library bar
		 */
		hideMediaSidebar: function () {
			Manager.getAction("MediaSidebar").hide();
		},
		
		
		/* ------------------------------- Manage image --------------------------- */
		
		
		/**
		 * Open image management
		 * 
		 * @private
		 */
		editImage: function () {
			var image = this.selected_image,
				ancestor = null,
				data  = this.original_data,
				size = null,
				resizer = this.resizer,
				max_crop_width = 0,
				classname;
			
			if (image) {
				size = this.getImageDataBySize(data.image, "original");
				
				if (!resizer) {
					this.resizer = resizer = new Supra.ImageResizer({"autoClose": false});
					resizer.on("resize", this.onEditImageResize, this);
				}
				
				//Find content width
				classname = this.configuration.wrapperClassName;
				ancestor = image.ancestor();
				
				if (ancestor.test("." + classname)) {
					ancestor = ancestor.ancestor();
				}
				
				max_crop_width = Math.min(size.width, ancestor.get("offsetWidth"));
				
				resizer.set("maxCropWidth", max_crop_width);
				resizer.set("maxImageHeight", size.height);
				resizer.set("maxImageWidth", size.width);
				resizer.set("image", image);
			}
		},
		
		/**
		 * Handle image resize
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onEditImageResize: function (event) {
			//Preserve image data
			var image = event.image,
				imageId = image.getAttribute("id"),
				data  = this.getImageDataFromNode(image);
			
			if (!data) {
				//Can't find image data, where this image appeared from?
				return;
			}
			
			data.crop_top = event.cropTop;
			data.crop_left = event.cropLeft;
			data.crop_width = event.cropWidth;
			data.crop_height = event.cropHeight;
			data.size_width = event.imageWidth;
			data.size_height = event.imageHeight;
			
			this.htmleditor.setData(imageId, data);
			
			//Property changed, update editor 'changed' state
			this.htmleditor._changed();
		},
		
		/**
		 * Stop image management
		 * 
		 * @private
		 */
		stopEditImage: function () {
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
		insertImage: function (event) {
			var htmleditor = this.htmleditor;
			
			var locale = Supra.data.get("locale");
			
			if (!htmleditor.get("disabled") && htmleditor.isSelectionEditable(htmleditor.getSelection())) {
				var image_data = event.image,
					size_data = this.getImageDataBySize(image_data, "original");
				
				if (this.selected_image) {
					//If image in content is already selected, then replace
					var imageId = this.selected_image_id,
						imageData = this.htmleditor.getData(imageId);
					
					var data = Supra.mix({}, defaultProps, {
						"type": this.NAME,
						"title": (image_data.title && image_data.title[locale]) ? image_data.title[locale] : "",
						"description": (image_data.description && image_data.description[locale]) ? image_data.description[locale] : "",
						"align": imageData.align,
						"style": imageData.style,
						"image": image_data,	//Original image data
						"size_width": size_data.width,
						"size_height": size_data.height,
						"crop_left": 0,
						"crop_top": 0,
						"crop_width": Math.min(size_data.width, imageData.crop_width || 9999),
						"crop_height": Math.min(size_data.height, imageData.crop_height || 9999)
					});
					
					//Preserve image data
					this.htmleditor.setData(imageId, data);
					
					//Update image attributes
					this.setImageProperty("image", data.image);
					this.setImageProperty("title", data.title);
					this.setImageProperty("description", data.description);
					this.setImageProperty("size_width", data.size_width);
					this.setImageProperty("size_height", data.size_height);
					this.setImageProperty("crop_left", data.crop_left);
					this.setImageProperty("crop_top", data.crop_top);
					this.setImageProperty("crop_width", data.crop_width);
					this.setImageProperty("crop_height", data.crop_height);
					
					//Update form input values
					this.settings_form.getInput("title").setValue(data.title);
					this.settings_form.getInput("description").setValue(data.description);
					
					this.original_data = data;
					this.editImage();
				} else {
					//Find image by size and set initial image properties
					var src = this.getImageURLBySize(image_data);
					
					//Calculate image size so that it fills container
					var container_width = htmleditor.get("srcNode").get("offsetWidth"),
						size_width = size_data.width,
						size_height = size_data.height,
						classname = this.configuration.wrapperClassName;

					
					if (container_width < size_width) {
						size_height = Math.round(container_width / size_width * size_height);
						size_width = container_width;
					}
					
					//Image data
					var data = Supra.mix({}, defaultProps, {
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
					
					//Generate unique ID for image element, to which data will be attached
					var uid = htmleditor.generateDataUID();
					
					htmleditor.replaceSelection('<span class="' + classname + ' align-' + data.align + '" unselectable="on" contenteditable="false" style="width: ' + data.crop_width + 'px; height: ' + data.crop_height + 'px;"><img draggable="false" id="' + uid + '" style="margin-left: -' + data.crop_left + 'px; margin-top: -' + data.crop_top + 'px;" width="' + data.size_width + '" src="' + src + '" title="' + Y.Escape.html(data.title) + '" alt="' + Y.Escape.html(data.description) + '" class="align-' + data.align + '" /></span>');
					htmleditor.setData(uid, data);
				}
				
				this.hideMediaSidebar();
			}
		},
		
		/**
		 * Update image after it was dropped using HTML5 drag & drop
		 * 
		 * @param {Object} event
		 */
		dropImage: function (target, image_id) {
			//If dropped on un-editable element
			if (!this.htmleditor.isEditable(target)) return true;
			if (!Manager.MediaSidebar) return true;
			
			var htmleditor = this.htmleditor,
				dataObject = Manager.MediaSidebar.dataObject(),
				image_data = dataObject.cache.one(image_id),
				classname = this.configuration.wrapperClassName;
			
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
			var data = Supra.mix({}, defaultProps, {
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
			
			img = Y.Node.create('<span class="' + classname + ' align-' + data.align + '" unselectable="on" contenteditable="false" style="width: ' + data.crop_width + 'px; height: ' + data.crop_height + 'px;"><img draggable="false" id="' + uid + '" style="margin-left: -' + data.crop_left + 'px; margin-top: -' + data.crop_top + 'px;" width="' + data.size_width + '" src="' + src + '" title="' + Y.Escape.html(data.title) + '" alt="' + Y.Escape.html(data.description) + '" class="align-' + data.align + '" />');
			
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
		 * Returns image url matching size
		 * 
		 * @param {Object} data
		 * @param {String} size
		 */
		getImageURLBySize: function (data, size) {
			// Always return original if not specified size
			var size = size ? size : "original";
			
			if (data && data.sizes && size in data.sizes) {
				return data.sizes[size].external_path;
			}
			
			return null;
		},
		
		/**
		 * Returns image size data
		 * 
		 * @param {Object} data
		 * @param {String} size
		 */
		getImageDataBySize: function (data, size) {
			var size = size ? size : this.configuration.size;
			
			if (size in data.sizes) {
				return data.sizes[size];
			}
			
			return null;
		},
		
		/**
		 * On node change check if selected node is image and show settings
		 * 
		 * @private
		 */
		onNodeChange: function () {
			var element = this.htmleditor.getSelectedElement("img"),
				allowEditing = this.htmleditor.editingAllowed,
				button = htmleditor.get("toolbar").getButton("insertimage");
			
			if (allowEditing && element) {
				if (!this.showImageSettings(Y.Node(element))) {
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
			var mediasidebar = Manager.getAction("MediaSidebar"),
				toolbar = htmleditor.get("toolbar"),
				button = toolbar ? toolbar.getButton("insertimage") : null;
			
			// When HTML changes make sure images has wrapper elements
			htmleditor.on("afterSetHTML", this.afterSetHTML, this);
			
			// Add command
			htmleditor.addCommand("insertimage", Y.bind(this.toggleMediaSidebar, this));
			
			// When clicking outside image hide image settings
			this.clickEvent = Y.Node(htmleditor.get("doc")).on("mousedown", this.documentClick, this)
			
			// When clicking on image show image settings
			htmleditor.on("nodeChange", this.onNodeChange, this);
			
			if (button) {
				button.show();
				
				//When media library is shown/hidden make button selected/unselected
				mediasidebar.after("visibleChange", function (evt) {
					button.set("down", evt.newVal);
				});
				
				//When un-editable node is selected disable mediasidebar toolbar button
				htmleditor.on("editingAllowedChange", function (event) {
					button.set("disabled", !event.allowed);
				});
			}
			
			if (!Manager.getAction('PageToolbar').hasActionButtons("htmleditor-plugin")) {
				Manager.getAction('PageToolbar').addActionButtons("htmleditor-plugin", []);
				Manager.getAction('PageButtons').addActionButtons("htmleditor-plugin", []);
			}
			
			//When media library is hidden show settings form if image is selected
			mediasidebar.on("hide", function () {
				if (this.selected_image) {
					Manager.executeAction("PageContentSettings", this.settings_form, {
						"doneCallback": Y.bind(this.settingsFormApply, this),
						
						"title": Supra.Intl.get(["htmleditor", "image_properties"]),
						"scrollable": true,
						"toolbarActionName": "htmleditor-plugin"
					});
				}
			}, this);
			
			//Hide media library when editor is closed
			htmleditor.on("disable", this.hideMediaSidebar, this);
			htmleditor.on("disable", this.settingsFormApply, this);
			htmleditor.on("disable", this.stopEditImage, this);
			
			//If image is rotated, croped or replaced in MediaLibrary update image source
			Manager.getAction("MediaLibrary").on(["rotate", "crop", "replace"], this.updateImageSource, this);
			
			this.bindUIDnD(htmleditor);
		},
		
		bindUIDnD: function (htmleditor) {
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
		 * Update image src attribute
		 * MediaLibrary must be initialized 
		 */
		updateImageSource: function (e) {
			var image_id = (typeof e == "object" ? e.file_id : e),
				all_data = this.htmleditor.getAllData(),
				item_data = null,
				item_id = null,
				data_object = null,
				image_data = null;
			
			for(var i in all_data) {
				if (all_data[i].type == this.NAME && all_data[i].image && all_data[i].image.id == image_id) {
					item_id = i;
					item_data = all_data[i];
					break;
				}
			}
			
			if (item_data) {
				data_object = Manager.getAction("MediaLibrary").medialist.get("dataObject");
				image_data = data_object.getData(image_id);
				
				if (image_data) {
					//Update image data
					image_data.path = data_object.getPath(image_id);
					item_data.image = image_data;
					
					//Change source
					var path = this.getImageURLBySize(image_data);
					var container = htmleditor.get("srcNode");
					var node = container.one("#" + item_id);
					
					if (node) {
						node.setAttribute("src", path);
					}
				}
			}
		},
		
		/**
		 * Handle drop
		 * 
		 * @param {Object} e Event
		 */
		onDrop: function (e) {
			//If image was from content, then prevent
			if (e.drag && e.drag.closest(this.htmleditor.get('srcNode'))) {
				if (e.halt) e.halt();
				return false;
			}
			
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
			if (target.test("img") && data && data.type == this.NAME) {
				this.htmleditor.removeData(target);
				this.setImageProperty("style", "", target);
				this.setImageProperty("align", "", target);
			}
		},
		
		/**
		 * Unclean HTML, add wrapper node around it
		 */
		afterSetHTML: function (event) {
			var htmleditor = this.htmleditor,
				node = htmleditor.get("srcNode"),
				images = node.all("img"),
				i = 0,
				ii = images.size(),
				data = null;
			
			for (; i<ii; i++) {
				data = htmleditor.getData(images.item(i));
				if (data && data.type == "image") {
					this.getImageWrapperNode(images.item(i));
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
		 * Process HTML and replace all nodes with supra tags {supra.image id="..."}
		 * Called before HTML is saved
		 * 
		 * @param {String} html
		 * @return Processed HTML
		 * @type {String}
		 */
		tagHTML: function (html) {
			var htmleditor = this.htmleditor,
				NAME = this.NAME;
			
			html = html.replace(/(<span[^>]*>)?\s*<img [^>]*id="([^"]+)"[^>]*>\s*(<\/span[^>]*>)?/ig, function (html, wrap_open, id, wrap_close) {
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
			
			html = html.replace(/{supra\.image id="([^"]+)"}/ig, function (tag, id) {
				if (!id || !data[id] || data[id].type != NAME) return "";
				
				var item = data[id],
					src = self.getImageURLBySize(item.image);
				
				if (src) {
					item.image.crop_left = item.image.crop_left || 0;
					item.image.crop_top = item.image.crop_top || 0;
					item.image.crop_width = item.image.crop_width || (item.image.size_width - item.image.crop_left);
					item.image.crop_height = item.image.crop_height || (item.image.size_height - item.image.crop_top);
					
					// Fix width/height if image proportions have been changed
					if (item.size_width && item.size_height) {
						var original = self.getImageDataBySize(item.image, 'original');

						if (original) {
							var original_height = original.height,
								original_width = original.width,
								min_ratio = Math.min(item.size_width / original_width, item.size_height / original_height),
								new_width = Math.round(min_ratio * original_width),
								new_height = Math.round(min_ratio * original_height);


							if (Math.abs(new_width - item.size_width) > 1) {
								item.size_width = new_width;
							}
							if (Math.abs(new_height - item.size_height) > 1) {
								item.size_height = new_height;
							}
						}
					}

					var style = ( ! item.image.exists ? '' : (item.size_width && item.size_height ? 'width="' + item.size_width + '" height="' + item.size_height + '"' : ''));
					var img_style = (item.size_width && item.size_height ? 'width: ' + item.size_width + 'px; height:' + item.size_height + ';' : '');					
					var classname = self.configuration.wrapperClassName + " " + (item.align ? "align-" + item.align : "") + " " + item.style;
					var html = '<span class="' + classname + '" unselectable="on" contenteditable="false" style="width: ' + item.crop_width + 'px; height: ' + item.crop_height + 'px;"><img ' + style + ' draggable="false" id="' + id + '" style="' + img_style + 'margin-left: -' + item.crop_left + 'px; margin-top: -' + item.crop_top + 'px;" class="' + classname + '" src="' + ( ! item.image.exists ? item.image.missing_path : src ) + '" title="' + Y.Escape.html(item.title) + '" alt="' + Y.Escape.html(item.description) + '" /></span>';
					
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
			data.image = data.image.id;
			return data;
		}
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {"requires": ["supra.htmleditor-base", "supra.input-proto"]});
