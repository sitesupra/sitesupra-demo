YUI.add("supra.footer", function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Button configuration defaults
	var BUTTON_DEFINITION = {
		"id": null,
		"srcNode": null,
		"type": "push",
		"label": "",
		"icon": null,
		"style": "small",
		"disabled": false
	};
	
	var BUTTON_STYLES = {
		"create": "small-blue",
		"save": "small-blue",
		"ok": "small-blue",
		"cancel": "small",
		"yes": "small-blue",
		"no": "small",
		"apply": "small-blue",
		"done": "small",
		"delete": "small-red",
		"reset": "small",
		"error": "small-red"
	};
	
	function bubbleEvent (event, event_name) {
		this.fire(event_name, event);
	}
	
	/**
	 * Class for handling buttons lists 
	 * 
	 * @alias Supra.Footer
	 * @param {Object} config Configuration
	 */
	function Footer (config) {
		Footer.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this.buttons = {};
		this.buttons_definition = {};
	}
	
	Footer.NAME = "footer";
	Footer.ATTRS = {
		"buttons": {
			value: null
		},
		"autoDiscoverButtons": {
			value: true
		},
		"style": {
			value: "default"
		}
	};
	
	Y.extend(Footer, Y.Widget, {
		
		/**
		 * Buttons
		 * @type Object
		 * @private
		 */
		buttons: {},
		
		/**
		 * Button definition
		 * @type {Object}
		 * @private
		 */
		buttons_definition: {},
		
		/**
		 * Index used to create ID if one is not set
		 * @type {Number}
		 * @private 
		 */
		button_index: 0,
		
		/**
		 * Render buttons
		 * @private
		 */
		renderUI: function () {
			Footer.superclass.renderUI.apply(this, arguments);
			
			var button_count = 0;
			var definitions = Supra.mix(this.buttons_definition || {}, this.get("buttons") || {}, true);
			
			//Find all buttons
			if (this.get("autoDiscoverButtons")) {
				definitions = Supra.mix(this.discoverButtons(), definitions, true);
			}
			
			//Create Inputs
			for(var i in definitions) {
				this._renderButton(definitions[i]);
				button_count++;
			}
			
			//Style
			this.get("srcNode").addClass(Y.ClassNameManager.getClassName(Footer.NAME, this.get("style")));
			
			//If there are no buttons to show, then hide panel
			if (button_count == 0) this.hide();
		},
		
		/**
		 * Bind to button events
		 * @private
		 */
		bindUI: function () {
			Footer.superclass.bindUI.apply(this, arguments);
			
			//When user will click on button, then event BUTTON_ID will be triggered on Footer
			var buttons = this.buttons;
			for(var i in buttons) {
				buttons[i].on("click", bubbleEvent, this, buttons[i].get("id"));
			}
		},
		
		/**
		 * Search for buttons in DOM
		 * 
		 * @private
		 * @return Object with button definitions
		 * @type {Object}
		 */
		discoverButtons: function () {
			var buttons = this.get("srcNode").all("input[type='button'],input[type='submit'],button"),
				config = {},
				styles = BUTTON_STYLES,
				style,
				button,
				id,
				disabled,
				label;

			for(var i=0,ii=buttons.size(); i<ii; i++) {
				button = buttons.item(i);
				
				id = button.getAttribute("id");
				if (!id) {
					for(style in styles) {
						if (button.hasClass(style)) {
							id = style;
							break;
						}
					}
				}
				
				if (!id) continue;
				
				disabled = button.getAttribute("disabled") ? true : false;
				label = button.test("input") ? button.get("value") : button.get("innerHTML");
				style = button.test("button") ? button.getAttribute('data-style') : null;
				
				config[id] = {
					"id": id,
					"label": label,
					"srcNode": button,
					"disabled": disabled,
					"style": style
				};
			}
			
			return config;
		},
		
		/**
		 * Normalize button config
		 * 
		 * @private
		 * @param {Object} config
		 * @return Normalized button configuration
		 * @type {Object}
		 */
		normalizeButtonConfig: function (config) {
			var style_definition = {
				"style": "small",
				"label": ""
			};
			
			if (config.id && config.id in BUTTON_STYLES && !config.style) {
				style_definition.style = BUTTON_STYLES[config.id];
			}
			
			if (config.id && !config.label) {
				var label = Supra.Intl.get(["buttons", config.id]);
				if (label) style_definition.label = label;
			}
			
			//Convert arguments into
			//[{}, INPUT_DEFINITION, argument1, argument2, ...]
			var args = [].slice.call(arguments,0);
				args = [{}, BUTTON_DEFINITION, style_definition].concat(args);
			
			//Mix them together
			return Supra.mix.apply(Supra, args);
		},
		
		/**
		 * Add button
		 * 
		 * @param {Object} config Button configuration, see Supra.Button 
		 * @see Supra.Button
		 */
		addButton: function (config) {
			if (this.get("rendered")) {
				this._renderButton(config);
				this.show();
			} else {
				var index = this.button_index++;
				var id = ("id" in config && config.id ? config.id : "button" + index);
				var conf = (id in this.buttons_definition ? this.buttons_definition[id] : {});
				this.buttons_definition[id] = Supra.mix(conf, config);
			}
			
			return this;
		},
		
		/**
		 * Render button
		 * @param {Object} definition
		 */
		_renderButton: function (definition) {
			var srcNode = this.get("srcNode"),
				contentBox = this.get("contentBox"),
				definition = this.normalizeButtonConfig(definition),
				id = definition.id,
				buttons = this.buttons = (this.buttons || {}),
				node = null;
			
			//Try finding input
			if (!definition.srcNode) {
				node = srcNode.one("#" + id);
				if (!node) {
					node = srcNode.one("input." + id + ", button." + id);
				}
				
				definition.srcNode = node;
			}
			
			//Remove old button if it already exist
			if (id in buttons) {
				this.removeButton(id);
			}
			
			//Create button, but without ID to prevent ID attribute collision
			var config = Supra.mix({}, definition);
			
			delete(config.id);
			buttons[id] = new Supra.Button(config);
			
			if (definition.srcNode) {
				buttons[id].render();
			} else {
				//If input doesn't exist, then create it
				buttons[id].render(contentBox);
			}
			
			return this;
		},
		
		/**
		 * Remove button
		 * Chainable
		 * 
		 * @param {String} id Button ID
		 */
		removeButton: function (id) {
			if (id in this.buttons) {
				var node = this.buttons[id].get("srcNode");
				this.buttons[id].destroy();
				delete(this.buttons[id]);
				node.destroy();
			}
			
			return this;
		},
		
		/**
		 * Returns button instance by ID. If button doesn't exist returns null
		 * 
		 * @return Button instance (Supra.Button)
		 * @type {Object}
		 */
		getButton: function (id) {
			if (id in this.buttons) {
				return this.buttons[id];
			} else {
				return null;
			}
		},
		
		/**
		 * Returns all buttons in object, where keys are button IDs 
		 * 
		 * @return Object with buttons
		 * @type {Object}
		 */
		getButtons: function () {
			return this.buttons;
		},
		
		/**
		 * Disable / enable button
		 * Chainable
		 * 
		 * @param {String} id Button ID
		 * @param {Boolean} disabled
		 */
		setButtonDisabled: function (id, disabled) {
			var button = this.getButton(id);
			if (button) {
				button.set("disabled", !!disabled);
			}
			return this;
		},
		
		/**
		 * Returns true if button is disabled, otherwise false
		 * 
		 * @param {String} id Button ID
		 * @return True if button is disabled
		 * @type {Boolean}
		 */
		getButtonDisabled: function (id) {
			var button = this.getButton(id);
			if (button) {
				return button.get("disabled");
			} else {
				return false;
			}
		},
		
		/**
		 * Show / hide button
		 * 
		 * @param {String} id Button ID
		 * @param {Boolean} visible Button visibility
		 */
		setButtonVisible: function (id, visible) {
			var button = this.getButton(id);
			if (button) {
				button.set("visible", !!visible);
			}
			return this;
		},
		
		/**
		 * Returns true if button is visible, otherwise false.
		 * If button is not found then returns false
		 * 
		 * @param {String} id
		 * @return True if button is visible
		 * @type {Boolean}
		 */
		getButtonVisible: function (id) {
			var button = this.getButton(id);
			if (button) {
				return button.get("visible");
			} else {
				return false;
			}
		},
		
		/**
		 * Enable/disable auto discover feature.
		 * If enabled, then will try to find buttons
		 */
		setAutoDiscoverButtons: function () {
			this.set("autoDiscoverButtons", true);
			return this;
		}
	});
	
	Supra.Footer = Footer;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);