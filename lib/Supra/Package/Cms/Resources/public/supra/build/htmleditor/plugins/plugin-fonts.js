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
		
		// Font button
		fontFamilyInput: null,
		
		// Fore color button
		foreColorInput: null,
		
		// Back color button
		backColorInput: null,
		
		// Font size button
		fontSizeInput: null,
		
		// Updating input to reflect selected element styles
		silentUpdating: false,
		
		// Font list
		fonts: null,
		
		// Select color type, "fore" or "back"
		colorType: null,
		
		// Font size input change listener
		toolbarFontSizeChangeListener: null,
		
		googleFonts: null,
		
		
		/**
		 * Update selected element font
		 * 
		 * @private
		 */
		updateFont: function () {
			if (!this.silentUpdating && !this.htmleditor.get("disabled")) {
				var value = this.fontInput.get("value"),
					data  = value ? this.fontInput.getValueData(value) : null,
					fonts = this.googleFonts;
				
				if (data) {
					if (!fonts) {
						fonts = new Supra.GoogleFonts({
							"doc": this.htmleditor.get("doc")
						});
					}
					
					fonts.addFonts([data]);
				}
				
				this.exec(value, "fontname");
				this.htmleditor._changed();
			}
		},
		
		/**
		 * Update selected element text or background color
		 * 
		 * @private
		 */
		updateColor: function () {
			if (!this.silentUpdating && !this.htmleditor.get('disabled')) {
				var value = this.colorInput.get("value");
				this.exec(value, this.colorType + "color");
				this.htmleditor._changed();
			}
		},
		
		/**
		 * Update selected element font size
		 * 
		 * @private
		 */
		updateFontSize: function () {
			if (!this.silentUpdating && !this.htmleditor.get('disabled')) {
				var value = this.fontSizeInput.get("value");
				this.exec(value, "fontsize");
				this.htmleditor._changed();
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
				element = null;
			
			this.silentUpdating = true;

			if (this.htmleditor.getSelectedElement('img, svg')) {
				// Image is selected, don't allow any text/font manipulation
				allowEditing = false;
			} else {
				element = this.htmleditor.getSelectedElement();

				if (this.color_settings_form && this.color_settings_form.get("visible")) {
					
					var color = "";
					if (element) {
						
						//Traverse up the tree
						var tmpElement = element,
							srcElement = this.htmleditor.get("srcNode").getDOMNode();
						
						while (tmpElement && tmpElement.style) {
							
							if (this.colorType == "fore") {
								//Text color
								color = tmpElement.tagName === "FONT" ? tmpElement.getAttribute("color") : "";
							} else {
								//Background color
								color = tmpElement.style.backgroundColor || "";
							}
							
							if (color) {
								//Color found, stop traverse
								tmpElement = null;
							} else {
								tmpElement = tmpElement.parentNode;
								if (tmpElement === srcElement) tmpElement = null;
							}
						}
					}
					
					this.colorInput.set("value", color);
					
				} else if (this.font_settings_form && this.font_settings_form.get("visible")) {
					var face = null;
					if (element && element.tagName === "FONT") {
						face = element.getAttribute("face");
					} else {
						//Try finding font from the list, which matches selected font
						face = Y.Node(element).getStyle("fontFamily") || "";
					}
					this.fontInput.set("value", face);
				}
				
				if (element) {
					var size = parseInt(Y.Node(element).getStyle("fontSize"), 10) || "";
					if (this.fontSizeInput.hasValue(size)) {
						this.fontSizeInput.set("value", size);
					} else {
						//In case elements font size is not in the list
						this.fontSizeInput.setText(size);
					}
				}
			}
			
			this.fontSizeInput.set("disabled", !allowEditing);
			this.fontFamilyInput.set("disabled", !allowEditing);
			this.foreColorInput.set("disabled", !allowEditing);
			this.backColorInput.set("disabled", !allowEditing);
			
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
			
			this.fontSizeInput.set("disabled", !event.allowed);
			this.fontFamilyInput.set("disabled", !event.allowed);
			this.foreColorInput.set("disabled", !event.allowed);
			this.backColorInput.set("disabled", !event.allowed);
		},
		
		/**
		 * Disabled attribute change
		 * 
		 * @param {Object} event Attribute change event facade object
		 * @private
		 */
		handleDisabledChange: function (event) {
			var listener = this.toolbarFontSizeChangeListener;
			
			if (event.newVal && listener) {
				//Disable
				listener.detach();
				this.toolbarFontSizeChangeListener = null;
			} else if (!event.newVal && !listener) {
				//Enable
				this.toolbarFontSizeChangeListener = this.fontSizeInput.after("valueChange", this.updateFontSize, this);
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
				realSize;
			
			if (editor.selectionIsCollapsed()) {
				//Increase selection to all element if there isn't any
				node = editor.getSelectedElement();
				if (!node) return;
				
				editor.selectNode(node);
			}
			
			if (command == "fontname") {
				node = editor.getSelectedElement();
				testNode = (node.tagName == "FONT" ? node.parentNode : node);
				
				//If node font family is the same as new font, then don't set "face"
				fontname = Y.Node(testNode).getStyle("fontFamily");
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
				
				node = editor.getSelectedElement("FONT");
				
				if (!data) {
					if (node && node.tagName == "FONT") {
						node.style.color = "";
						node.removeAttribute("color");
						
						if (this.cleanUpNode(node)) {
							editor._changed();
							editor.refresh(true);
						}
						return;
					}
				} else {
					if (node && node.tagName == "FONT") {
						//Update FONT element if we can
						node.setAttribute("color", data);
						return;
					}
				}
				
			} else if (command == "backcolor") {
				
				node = editor.getSelectedElement();
				
				if (!data) {
					var tmpNode = node,
						srcNode = this.htmleditor.get("srcNode").getDOMNode();
					
					//Find closest element with background color
					while(tmpNode && tmpNode !== srcNode) {
						if (tmpNode && tmpNode.style.backgroundColor) {
							node = tmpNode;
							tmpNode = null;
						} else {
							tmpNode = tmpNode.parentNode;
						}
					}
					
					if (node && node !== srcNode && node.style.backgroundColor) {
						if (node.tagName == "SPAN") {
							this.htmleditor.unwrapNode(node);
						} else {
							//FONT element without any styles will be removed in cleanUp
							node.style.backgroundColor = "";
						}
						
						this.cleanUp();
						
						editor._changed();
						editor.refresh(true);
						
						return;
					}
				} else {
					if (node && node.tagName == "FONT") {
						//Update FONT element if we can
						node.style.backgroundColor = data;
						return;
					}
				}
				
			}
			
			//Inserts <font> for color, fontsize, fontname andbackground color
			var targetNode = null;
			
			if (command == "backcolor") {
				var selection = editor.selection;
				if (selection.start === selection.end && selection.start_offset !== selection.end_offset) {
					node = this.htmleditor.replaceSelection(null, "FONT");
					if (node) {
						targetNode = node;
						node.style.backgroundColor = data;
					}
				} else {
					editor.get("doc").execCommand(command, null, data);
				}
			} else {
				editor.get("doc").execCommand(command, null, data);
			}
			
			if (targetNode) {
				editor.selectNode(targetNode);
				editor.refresh(true);
			} else {
				// If all text inside DIV, P, ... was selected, then selection didn't changed
				// (according to text), but new wrapper element was added, so need to reset
				editor.resetSelectionCache();
			}
			
			if (command == "fontsize") {
				//Get <font /> element
				node = editor.getSelectedElement();
				
				if (node) {
					//Remove "size" attribute, since we will be using classname
					node.removeAttribute("size");
					node.className = "";
					
					//We want to make sure classname if is actually needed
					realSize = parseInt(Y.Node(node).getStyle("fontSize"), 10);
					
					if (data && data != realSize) {
						//Fontsize set as classname
						node.className = "font-" + data;
					} else {
						node.className = "";
					}
				}
			} else if (command == "backcolor" || command == "forecolor") {
				//Get <span /> element
				node = editor.getSelectedElement();
				
				if (node && node.tagName == "SPAN" && (node.style.backgroundColor || node.style.color || node.getAttribute("color"))) {
					//Replace SPAN with FONT
					var tempNode = editor.get("doc").createElement("FONT");
					node.parentNode.insertBefore(tempNode, node);
					
					while(node.firstChild) {
						tempNode.appendChild(node.firstChild);
					}
					
					if (command == "backcolor") {
						tempNode.style.backgroundColor = node.style.backgroundColor;
					} else if (command == "forecolor") {
						if (node.style.color) {
							tempNode.setAttribute("color", node.style.color); // Gecko
						} else if (node.getAttribute("color")) {
							tempNode.setAttribute("color", node.getAttribute("color")); // WebKit
						}
					}
					
					node.parentNode.removeChild(node);
					editor.selectNode(tempNode);
				}
			}
			
			//Remove <font> which don't have font size and font family and color 
			this.cleanUp();
			
			editor._changed();
			editor.refresh(true);
		},
		
		/**
		 * Remove node if doesn't have any styles
		 * 
		 * @param {Object} node Node
		 * @return True if node was removed, otherwise false
		 */
		cleanUpNode: function (node) {
			node = node.getDOMNode ? node.getDOMNode() : node;
			node.removeAttribute("size");
			
			if (!node.getAttribute("face") && !node.className && !node.getAttribute("color") && !node.style.backgroundColor) {
				this.htmleditor.unwrapNode(node);
				return true;
			}
			return false;
		},
		
		/**
		 * Remove all <font> nodes which don't have any style
		 */
		cleanUp: function () {
			var nodes = this.htmleditor.get("srcNode").all("font");
			nodes.each(Y.bind(this.cleanUpNode, this));
		},
		
		/**
		 * Returns list of used fonts
		 * 
		 * @return List of font API ids
		 */
		getUsedFonts: function () {
			var editor = this.htmleditor,
				nodes = this.htmleditor.get("srcNode").all("font"),
				used = [];
			
			nodes.each(function (node) {
				var face  = node.getAttribute("face"),
					fonts = null,
					i     = 0,
					ii    = 0,
					safe  = Supra.GoogleFonts.SAFE_FONTS;
				
				if (face) {
					fonts = face.split(/\s*,\s*/g);
					for (ii=fonts.length; i<ii; i++) {
						if (Y.Array.indexOf(safe, fonts[i]) !== -1) {
							// Font is in the safe list, don't send it to server
							return;
						}
					}
					used.push(face);
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
			
			var form_config = {
					"inputs": [{
						"id": "font",
						"type": "Fonts",
						"label": "",
						"values": []
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
				action = Manager.getAction("PageContentSettings"),
				toolbarName = "htmleditor-plugin";
			
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
			
			if (!Manager.getAction('PageToolbar').hasActionButtons(toolbarName)) {
				Manager.getAction('PageToolbar').addActionButtons(toolbarName, []);
				Manager.getAction('PageButtons').addActionButtons(toolbarName, []);
			}
			
			action.execute(form, {
				"doneCallback": Y.bind(this.hideSidebar, this),
				"hideCallback": Y.bind(this.onSidebarHide, this),
				
				"title": Supra.Intl.get(["htmleditor", "fonts"]),
				"scrollable": true,
				"toolbarActionName": toolbarName
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
			
			//Find presets
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
				"inputs": [{
					"id": "color",
					"type": "Color",
					"label": "",
					"allowUnset": true,
					"presets": presets
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
				action = Manager.getAction("PageContentSettings"),
				toolbarName = "htmleditor-plugin",
				label = Supra.Intl.get(["htmleditor", this.colorType + "color"]);
			
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
			
			if (!Manager.getAction('PageToolbar').hasActionButtons(toolbarName)) {
				Manager.getAction('PageToolbar').addActionButtons(toolbarName, []);
				Manager.getAction('PageButtons').addActionButtons(toolbarName, []);
			}
			
			//Change color input label
			form.getInput('color').set('label', label) 
			
			//Show form
			action.execute(form, {
				"doneCallback": Y.bind(this.hideSidebar, this),
				"hideCallback": Y.bind(this.onSidebarHide, this),
				
				"title": label,
				"scrollable": true,
				"toolbarActionName": toolbarName
			});
			
			//Color toolbar button
			this.htmleditor.get("toolbar").getButton(this.colorType + "color").set("down", true);
			this.htmleditor.get("toolbar").getButton((this.colorType == "fore" ? "back" : "fore") + "color").set("down", false);
			
			//Update selected text/back color, because color picker could be showing for wrong one
			this.handleNodeChange({});
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
			} else if (this.color_settings_form && this.color_settings_form.get("visible")) {
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
			this.htmleditor.get("toolbar").getButton("forecolor").set("down", false);
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
			
			this.silentUpdating = true;
			this.listeners = [];
			
			htmleditor.addCommand("fonts", Y.bind(this.showFontSidebar, this));
			htmleditor.addCommand("forecolor", Y.bind(this.showTextColorSidebar, this));
			htmleditor.addCommand("backcolor", Y.bind(this.showBackColorSidebar, this));
			
			// Show inputs / buttons
			var inputs = ["fonts", "fontsize", "forecolor", "backcolor"],
				i = 0,
				ii = inputs.length;
			
			for (;i<ii; i++) {
				toolbar.getButton(inputs[i]).set("visible", true);
			}
			
			// Inputs
			this.fontFamilyInput = toolbar.getButton("fonts");
			this.foreColorInput  = toolbar.getButton("forecolor");
			this.backColorInput  = toolbar.getButton("backcolor");
			
			var input = this.fontSizeInput = toolbar.getButton("fontsize"),
				values = input.get('values');
			
			//Special style
			input.addClass("align-center");
			
			//On enable/disable add or remove listener 
			this.listeners.push(
				htmleditor.on("disabledChange", this.handleDisabledChange, this)
			);
			
			//When un-editable node is selected disable toolbar button
			this.listeners.push(
				htmleditor.on("editingAllowedChange", this.handleEditingAllowChange, this)
			);
			this.listeners.push(
				htmleditor.on("nodeChange", this.handleNodeChange, this)
			);
			
			this.silentUpdating = false;
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
	
}, YUI.version, {"requires": ["supra.htmleditor-base", "supra.template", "supra.google-fonts"]});