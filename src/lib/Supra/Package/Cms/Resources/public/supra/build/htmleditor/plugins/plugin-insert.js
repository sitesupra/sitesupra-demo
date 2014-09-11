YUI().add("supra.htmleditor-plugin-insert", function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	/*
	 * Plugin to control insert panel
	 */
	Supra.HTMLEditor.addPlugin("insert", defaultConfiguration, {
		
		/**
		 * Insert toolbar is visible
		 */
		visible: false,
		
		/**
		 * Toggle insert toolbar
		 */
		toggleInsertToolbar: function () {
			var toolbar = this.htmleditor.get("toolbar"),
				button = toolbar.getButton("insert");
			
			if (button) {
				if (!this.visible) {
					this.visible = true;
					toolbar.showGroup("insert");
					button.set("down", true);
				} else {
					this.visible = false;
					toolbar.hideGroup("insert");
					button.set("down", false);
				}
			}
		},
		
		/**
		 * Hide insert toolbar
		 */
		hideInsertToolbar: function () {
			if (this.visible) {
				var toolbar = this.htmleditor.get("toolbar"),
					button = toolbar.getButton("insert");
				
				toolbar.hideGroup("insert");
				button.set("down", false);
				
				this.visible = false;
			}
		},
		
		/**
		 * When editable/uneditable content is selected enable/disable button and hide toolbar
		 * 
		 * @private
		 */
		onEditingAllowedChange: function (event) {
			this.htmleditor.get("toolbar").getButton("insert").set("disabled", !event.allowed);
			
			if (!event.allowed) {
				this.hideInsertToolbar();
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
			var toolbar = htmleditor.get("toolbar"),
				button = toolbar ? toolbar.getButton("insert") : null;
			
			// Show button
			button.set("visible", true);
			
			// Add command
			htmleditor.addCommand("insert", Y.bind(this.toggleInsertToolbar, this));
			
			// When one of the insert controls is clicked hide toolbar
			var controls = toolbar.getControlsInGroup("insert"),
				i = 0,
				ii = controls.length;
			
			for (; i<ii; i++) {
				if (controls[i].command) {
					htmleditor.addCommand(controls[i].command, Y.bind(this.hideInsertToolbar, this));
				}
			}
			
			if (button) {
				//When un-editable node is selected disable toolbar button and hide toolbar
				htmleditor.on("editingAllowedChange", this.onEditingAllowedChange, this);
			}
			
			//Hide media library when editor is closed
			htmleditor.on("disable", this.hideInsertToolbar, this);
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {"requires": ["supra.htmleditor-base"]});