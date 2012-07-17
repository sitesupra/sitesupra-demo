/**
 * Font sidebar
 */
YUI().add("supra.htmleditor-plugin-fonts", function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	/*
	 * Font plugin handles font selection
	 */
	Supra.HTMLEditor.addPlugin("fonts", defaultConfiguration, {
		
		// Font input
		fontInput: null,
		
		// Font size input
		fontSizeInput: null,
		
		// Updating input to reflect selected element styles
		silentUpdating: false,
		
		// Font list
		fonts: null,
		
		// Select color type, "text" or "back"
		colorType: null,
		
		
		/**
		 * Update selected element font
		 * 
		 * @private
		 */
		updateFont: function () {
			if (!this.silentUpdating) {
				var value = this.fontInput.get("value");
				this.exec(value, "fontname");
			}
		},
		
		/**
		 * Update selected element text or background color
		 * 
		 * @private
		 */
		updateColor: function () {
			if (!this.silentUpdating) {
				var value = this.colorInput.get("value");
				this.exec(value, this.colorType + "color");
			}
		},
		
		/**
		 * Update selected element font size
		 * 
		 * @private
		 */
		updateFontSize: function () {
			if (!this.silentUpdating) {
				var value = this.fontSizeInput.get("value");
				this.exec(value, "fontsize");
			}
		},
		
		/**
		 * When node changes update font, font size and color input values
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleNodeChange: function (event) {
			var allowEditing = this.htmleditor.editingAllowed,
				element = this.htmleditor.getSelectedElement();
			
			this.silentUpdating = true;
			
			if (this.color_settings_form && this.color_settings_form.get("visible")) {
				
				//@TODO
				
			} else if (this.font_settings_form && this.font_settings_form.get("visible")) {
				var face = null;
				if (element && element.tagName === "FONT") {
					face = element.getAttribute("face");
				} else {
					//Try finding font from the list, which matches selected font
					face = Y.Node(element).getStyle("font-family") || "";
					var fonts = this.fonts,
						i = 0,
						ii = fonts.length;
					
					for (; i<ii; i++) {
						if (face && face.toLowerCase().indexOf(fonts[i].search) !== -1) {
							face = fonts[i].family;
						}
					}
				}
				this.fontInput.set("value", face);
			}
			
			if (element) {
				var size = parseInt(Y.Node(element).getStyle("font-size"), 10) || 0;
				if (this.fontSizeInput.hasValue(size)) {
					this.fontSizeInput.set("value", size);
				} else {
					//In case elements font size is not in the list
					this.fontSizeInput.setText(size);
				}
			}
			
			this.fontSizeInput.set("opened", false);
			this.silentUpdating = false;
		},
		
		/**
		 * When editing allowed changes update sidebar visibility
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleEditingAllowChange: function (event) {
			if (!event.allowed) {
				this.hideSidebar();
			}
		},
		
		
		/* -------------------------------------- API ---------------------------------------- */
		
		
		/**
		 * Execute command
		 * 
		 * @param {Object} data
		 * @param {String} command
		 * @return True on success, false on failure
		 * @type {Boolean}
		 */
		exec: function (data, command) {
			var editor = this.htmleditor,
				node,
				testNode,
				fontname,
				realSize,
				res; // execCommand result
			
			if (editor.selectionIsCollapsed()) {
				//Increase selection to all element if there isn't any
				editor.selectNode(editor.getSelectedElement());
				editor._resetSelection();
			}
			
			if (command == "fontname") {
				node = editor.getSelectedElement();
				testNode = (node.tagName == "FONT" ? node.parentNode : node);
				
				//If node font family is the same as new font, then don't set "face"
				fontname = Y.Node(testNode).getStyle("font-family");
				if (fontname && fontname.indexOf(data.replace(/,.*/, "")) !== -1) {
					if (node.tagName == "FONT") {
						node.removeAttribute("face");
						
						if (this.cleanUpNode(node)) {
							editor._changed();
							editor.refresh(true);
						}
					}
					return;
				}
			} else if (command == "forecolor") {
				
				if (!data) {
					node = editor.getSelectedElement();
					if (node.tagName == "FONT") {
						node.removeAttribute("color");
						
						if (this.cleanUpNode(node)) {
							editor._changed();
							editor.refresh(true);
						}
						return;
					}
				}
				
			} else if (command == "backcolor") {
				
				//@TODO
				
			}
			
			//Insert <font> for fontsize, fontname
			res = this.htmleditor.get("doc").execCommand(command, null, data);
			
			// If all text inside DIV, P, ... was selected, then selection didn't changed
			// (according to text), but new wrapper element was added, so need to reset
			editor._resetSelection();
			
			if (command == "fontsize") {
				//Get <font /> element
				node = editor.getSelectedElement();
				
				//Remove "size" attribute, since we will be using classname
				node.removeAttribute("size");
				node.className = "";
				
				//We want to make sure classname if is actually needed
				realSize = parseInt(Y.Node(node).getStyle("font-size"), 10);
				
				if (data && data != realSize) {
					//Fontsize set as classname
					node.className = "font-" + data;
				} else {
					node.className = "";
				}
			}
			
			//Remove <font> which don't have font size and font family and color 
			this.cleanUp();
			
			editor._changed();
			editor.refresh(true);
			return res;
		},
		
		/**
		 * Remove node if doesn't have any styles
		 * 
		 * @param {Object} node Node
		 * @return True if node was removed, otherwise false
		 */
		cleanUpNode: function (node) {
			node.removeAttribute("size");
			if (!node.getAttribute("face") && !node.className && !node.getAttribute("color")) {
				editor.unwrapNode(node);
				return true;
			}
			return false;
		},
		
		/**
		 * Remove all <font> nodes which don't have any style
		 */
		cleanUp: function () {
			var editor = this.htmleditor,
				nodes = this.htmleditor.get("srcNode").all("font");
			
			nodes.each(this.cleanUpNode);
		},
		
		/**
		 * Returns list of used fonts
		 * 
		 * @return List of font API ids
		 */
		getUsedFonts: function () {
			if (!this.fonts) return [];
			
			var editor = this.htmleditor,
				nodes = this.htmleditor.get("srcNode").all("font"),
				used = [],
				fonts = this.fonts,
				ii = fonts.length;
			
			nodes.each(function (node) {
				var face = node.getAttribute("face");
				if (face) {
					for (var i=0; i<ii; i++) {
						if (face.toLowerCase().indexOf(fonts[i].search) !== -1) {
							if (fonts[i].apis) {
								used.push(face);
							}
							return;
						}
					}
				}
			});
			
			return Y.Array.unique(used);
		},
		
		/**
		 * Returns list of all fonts
		 * 
		 * @return List of all fonts from configuration
		 */
		getAllFonts: function () {
			return Supra.data.get(["supra.htmleditor", "fonts"]) || [];
		},
		
		
		/* -------------------------------------- Sidebar ---------------------------------------- */
		
		
		/**
		 * Create font sidebar
		 */
		createFontSidebar: function () {
			//Get form placeholder
			var content = Manager.getAction("PageContentSettings").get("contentInnerNode");
			if (!content) return;
			
			//Properties form
			var fonts = this.fonts = Y.Array.map(this.getAllFonts(), function (item) {
									return {
										"id": item.family,
										"title": item.title,
										"family": item.family,
										"apis": item.apis,
										// used to search for matches
										"search": item.family.replace(/,.*/, "").toLowerCase() 
									};
							 	});
			
			var form_config = {
				"inputs": [{
					"id": "font",
					"type": "Fonts",
					"label": "",
					"values": fonts
				}],
				"style": "vertical"
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
			
			//When user selects a value, update content
			this.fontInput = form.getInput("font");
			this.fontInput.after("valueChange", this.updateFont, this);
			
			this.font_settings_form = form;
			return form;
		},
		
		/**
		 * Show fonts sidebar
		 */
		showFontSidebar: function () {
			//Make sure PageContentSettings is rendered
			var form = this.font_settings_form || this.createFontSidebar(),
				action = Manager.getAction("PageContentSettings");
			
			if (!form) {
				if (action.get("loaded")) {
					if (!action.get("created")) {
						action.renderAction();
						this.showFontSidebar(target);
					}
				} else {
					action.once("loaded", function () {
						this.showFontSidebar(target);
					}, this);
					action.load();
				}
				return false;
			}
			
			action.execute(form, {
				"doneCallback": Y.bind(this.hideSidebar, this),
				"hideCallback": Y.bind(this.onSidebarHide, this),
				
				"title": Supra.Intl.get(["htmleditor", "fonts"]),
				"scrollable": true
			});
			
			//Fonts toolbar button
			this.htmleditor.get("toolbar").getButton("fonts").set("down", true);
		},
		
		/**
		 * Create color sidebar
		 */
		createColorSidebar: function () {
			//Get form placeholder
			var content = Manager.getAction("PageContentSettings").get("contentInnerNode");
			if (!content) return;
			
			//Properties form
			var form_config = {
				"inputs": [{
					"id": "color",
					"type": "Color",
					"label": "",
					"allowUnset": true
				}],
				"style": "vertical"
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
			
			//When user selects a value, update content
			this.colorInput = form.getInput("color");
			this.colorInput.after("valueChange", this.updateColor, this);
			
			this.color_settings_form = form;
			return form;
		},
		
		/**
		 * Show color sidebar
		 */
		showColorSidebar: function () {
			//Make sure PageContentSettings is rendered
			var form = this.color_settings_form || this.createColorSidebar(),
				action = Manager.getAction("PageContentSettings");
			
			if (!form) {
				if (action.get("loaded")) {
					if (!action.get("created")) {
						action.renderAction();
						this.showColorSidebar(target);
					}
				} else {
					action.once("loaded", function () {
						this.showColorSidebar(target);
					}, this);
					action.load();
				}
				return false;
			}
			
			action.execute(form, {
				"doneCallback": Y.bind(this.hideSidebar, this),
				"hideCallback": Y.bind(this.onSidebarHide, this),
				
				"title": Supra.Intl.get(["htmleditor", this.colorType + "color"]),
				"scrollable": true
			});
			
			//Color toolbar button
			this.htmleditor.get("toolbar").getButton(this.colorType + "color").set("down", true);
		},
		
		/**
		 * Show text color sidebar
		 */
		showBackColorSidebar: function () {
			this.colorType = "back";
			this.showColorSidebar();
		},
		
		/**
		 * Show text color sidebar
		 */
		showTextColorSidebar: function () {
			this.colorType = "fore";
			this.showColorSidebar();
		},
		
		/**
		 * Hide fonts sidebar
		 */
		hideSidebar: function () {
			if (this.font_settings_form && this.font_settings_form.get("visible")) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * When fonts sidebar is hidden update toolbar button to reflect that
		 * 
		 * @private
		 */
		onSidebarHide: function () {
			//Unstyle toolbar button
			this.htmleditor.get("toolbar").getButton("fonts").set("down", false);
			this.htmleditor.get("toolbar").getButton("textcolor").set("down", false);
			this.htmleditor.get("toolbar").getButton("backcolor").set("down", false);
		},
		
		
		/* -------------------------------------- Plugin ---------------------------------------- */
		
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			var toolbar = htmleditor.get("toolbar");
			
			this.listeners = [];
			
			htmleditor.addCommand("fonts", Y.bind(this.showFontSidebar, this));
			htmleditor.addCommand("forecolor", Y.bind(this.showTextColorSidebar, this));
			htmleditor.addCommand("backcolor", Y.bind(this.showBackColorSidebar, this));
			
			// Font size input
			var input = this.fontSizeInput = toolbar.getButton("fontsize");
			input.addClass("align-center");
			input.set("values", [
				{"id": 6, "title": "6"},
				{"id": 8, "title": "8"},
				{"id": 9, "title": "9"},
				{"id": 10, "title": "10"},
				{"id": 11, "title": "11"},
				{"id": 12, "title": "12"},
				{"id": 14, "title": "14"},
				{"id": 16, "title": "16"},
				{"id": 18, "title": "18"},
				{"id": 24, "title": "24"},
				{"id": 30, "title": "30"},
				{"id": 36, "title": "36"},
				{"id": 48, "title": "48"},
				{"id": 60, "title": "60"},
				{"id": 72, "title": "72"}
			]);
			input.set("value", 12);
			input.after("valueChange", this.updateFontSize, this);
			
			//When un-editable node is selected disable toolbar button
			this.listeners.push(
				htmleditor.on("editingAllowedChange", this.handleEditingAllowChange, this)
			);
			this.listeners.push(
				htmleditor.on("nodeChange", this.handleNodeChange, this)
			);
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			for(var i=0,ii=this.listeners.length; i<ii; i++) {
				this.listeners[i].detach();
			}
			
			this.listeners = null;
			this.fontInput = null;
			
			if (this.font_settings_form) {
				this.font_settings_form.destroy();
			}
			if (this.color_settings_form) {
				this.color_settings_form.destroy();
			}
		}
		
	});
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {"requires": ["supra.htmleditor-base", "supra.template"]});