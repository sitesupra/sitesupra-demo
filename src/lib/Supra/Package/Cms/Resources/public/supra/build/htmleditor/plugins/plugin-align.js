/**
 * Font sidebar
 */
YUI().add("supra.htmleditor-plugin-align", function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	var ALIGN_TAG_NAMES = {
		"DIV": true, "P": true, "BLOCKQUOTE": true, "Q": true, 
		"LI": true, "TD": true, "TH": true, "DL": true, "DD": true,
		"H1": true, "H2": true, "H3": true, "H4": true, "H5": true,
		"ARTICLE": true, "ASIDE": true, "DETAILS": true, "FIGCAPTION": true, "FIGURE": true, "FOOTER": true, "HEADER": true, "NAV": true, "SECTION": true
	};
	
	/*
	 * Font plugin handles font selection
	 */
	Supra.HTMLEditor.addPlugin("align", defaultConfiguration, {
		
		// Align input
		alignInput: null,
		
		// Updating input to reflect selected element styles
		silentUpdating: false,
		
		
		/**
		 * When node changes update align input value
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleNodeChange: function (event) {
			var htmleditor = this.htmleditor,
				allowEditing = htmleditor.editingAllowed,
				element = this.getElement(),
				align = null;
			
			this.silentUpdating = true;
			
			if (element) {
				align = element.getAttribute("align");
			}
			if (!align) {
			 	element = element || Y.Node(htmleditor.getSelectedElement());
			 	if (element) {
			 		align = element.getStyle("textAlign");
			 		if (align != "left" && align != "right" && align != "center" && align != "justify") {
			 			align = "left";
			 		}
			 	}
			}
			
			this.alignInput.set("value", align || "left");
			this.alignInput.set("opened", false);
			this.silentUpdating = false;
		},
		
		/**
		 * Returns closest element to selection to which align can be applied
		 * 
		 * @return Element to which align can be applied
		 * @type {Object}
		 * @private
		 */
		getElement: function () {
			var htmleditor = this.htmleditor,
				selected = htmleditor.getSelectedElement('img,svg');
			
			if (selected) {
				// Image and icons have their own controls, dont allow changing anything
				return null;
			}
			
			var element = htmleditor.getSelectedElement(),
				tagName = "",
				container = htmleditor.get("srcNode").getDOMNode();
			
			if (element) {
				element = Y.Node(element);
			} else {
				return null;
			}
			
			while (element) {
				tagName = element.get("tagName");
				
				if (tagName in ALIGN_TAG_NAMES) {
					//Found it
					break;
				}
				
				element = element.ancestor();
				if (element && element.compareTo(container)) {
					element = null;
				}
			}
			
			return element;
		},
		
		/**
		 * When editing allowed changes update UI
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleEditingAllowChange: function (event) {
			if (!event.allowed) {
				
			}
		},
		
		/**
		 * Handle align input value change
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleAlignChange: function (event) {
			this.exec(event.newVal, "align");
		},
		
		
		/* -------------------------------------- API ---------------------------------------- */
		
		
		/**
		 * Set text align position
		 */
		exec: function (data, command) {
			if (!this.silentUpdating && command === "align") {
				var element = this.getElement();
				if (element) {
					element.setAttribute("align", data);
				}
			}
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
			
			htmleditor.addCommand("align", Y.bind(this.exec, this));
			
			// Font size input
			var input = this.alignInput = toolbar.getButton("align");
			input.set("visible", true);
			input.set("value", "left");
			input.after("valueChange", this.handleAlignChange, this);
			
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
			this.alignInput = null;
		}
		
	});
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {"requires": ["supra.htmleditor-base"]});