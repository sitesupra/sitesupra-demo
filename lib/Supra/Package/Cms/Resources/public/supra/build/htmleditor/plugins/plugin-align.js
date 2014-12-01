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
		"div": true, "p": true, "blockquote": true, "q": true, 
		"li": true, "td": true, "th": true, "dl": true, "dd": true,
		"h1": true, "h2": true, "h3": true, "h4": true, "h5": true,
		"article": true, "aside": true, "details": true, "figcaption": true, "figure": true, "footer": true, "header": true, "nav": true, "section": true
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
				elements = this.getElements(),
				align = null,
				
				valid_target = false,
				button = this.htmleditor.get('toolbar').getButton('align');
			
			this.silentUpdating = true;
			
			if (elements.length) {
				align = elements[0].getAttribute('align');
				valid_target = true;
				
				if (!align) {
					align = Y.DOM.getStyle(elements[0], 'textAlign');
					
					if (align != "left" && align != "right" && align != "center" && align != "justify") {
			 			align = "left";
			 		}
				}
			}
			
			// Update button in the toolbar
			if (button) {
				if (valid_target) {
					if (button.get('disabled')) {
						button.set('disabled', false);
					}
				} else {
					if (!button.get('disabled')) {
						button.set('disabled', true);
					}
				}
			}
			
			this.alignInput.set("value", align || "left");
			this.alignInput.set("opened", false);
			this.silentUpdating = false;
		},
		
		getElements: function () {
			var htmleditor = this.htmleditor,
				selected   = htmleditor.getSelectedElement('img, svg');
			
			if (selected) {
				// Image and icons have their own controls, dont allow changing anything
				return [];
			}
			
			return htmleditor.getSelectedNodes().find({'filter': ALIGN_TAG_NAMES}, true /* to_array */);
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
				var history = this.htmleditor.getPlugin('history'),
					elements = this.getElements(),
					i = 0,
					ii = elements.length;
				
				if (elements.length) {
					if (history) {
						history.pushTextState();
					}
					
					for (; i<ii; i++) {
						elements[i].setAttribute("align", data);
					}
					
					if (history) {
						history.pushState();
					}
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