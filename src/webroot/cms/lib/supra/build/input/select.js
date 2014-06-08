YUI.add("supra.input-select", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var LOOKUP_BLACKLIST = {
		9: true,  // Tab
		13: true, // Returns
		16: true, // Shift
		27: true, // Escape
		37: true, // Arrows
		38: true,
		39: true,
		40: true 
	};
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = "input-select";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		/**
		 * List of values
		 * @type {Array}
		 */
		"values": {
			value: [],
			setter: "_setValues"
		},
		
		/**
		 * Loading icon
		 * @type {Boolean}
		 */
		"loading": {
			value: false,
			setter: "_setLoading"
		},
		
		/**
		 * Show empty value in the list
		 * @type {Boolean}
		 */
		"showEmptyValue": {
			value: true
		},
		
		/**
		 * Don"t use replacement
		 * @type {Boolean}
		 */
		"useReplacement": {
			readOnly: true,
			value: false
		},
		
		/**
		 * Custom select container node
		 * @type {Object}
		 */
		"innerNode": {
			value: null
		},
		
		/**
		 * Node for loading icon
		 * @type {Object}
		 */
		"loadingNode": {
			value: null
		},
		
		/**
		 * Text node
		 * @type {Object}
		 */
		"textNode": {
			value: null
		},
		/**
		 * Dropdown node
		 * @type {Object}
		 */
		"dropdownNode": {
			value: null
		},
		/**
		 * Dropdown content node
		 * @type {Object}
		 */
		"contentNode": {
			value: null
		},
		
		/**
		 * Dropdown is opened
		 * @type {Boolean}
		 */
		"opened": {
			value: false,
			setter: "_setDropdownOpened"
		},
		
		/**
		 * Dropdown is scrollable
		 */
		"scrollable": {
			value: true,
			writeOnce: true
		},
		
		/**
		 * Item renderer, allows to create
		 * custom styled items
		 * @type {Function}
		 */
		"itemRenderer": {
			value: null
		},
		
		/**
		 * Text renderer, allows to create
		 * different selected item text
		 * @type {Function}
		 */
		"textRenderer": {
			value: null
		}
	};
	
	Input.HTML_PARSER = {
		"values": function (srcNode) {
			var input = this.get("inputNode"),
				values = [];
			
			if (input && input.test("select")) {
				var options = Y.Node.getDOMNode(input).options;
				for(var i=0,ii=options.length; i<ii; i++) {
					values.push({
						"id": options[i].value,
						"title": options[i].text
					});
				}
			} else {
				values = this.get("values") || [];
			}
			
			return values;
		}
	};
	
	Y.extend(Input, Supra.Input.String, {
		CONTENT_TEMPLATE: "<div></div>",
		INPUT_TEMPLATE: "<select></select>",
		LABEL_TEMPLATE: "<label></label>",
		
		/**
		 * Dropdown opening animation
		 * @type {Object}
		 * @private
		 */
		anim_open: null,
		
		/**
		 * Dropdown close animation
		 * @type {Object}
		 * @private
		 */
		anim_close: null,
		
		/**
		 * Highlighted item id
		 * @type {String}
		 * @private
		 */
		highlight_id: 0,
		
		/**
		 * Scrollable instance
		 * @type {Object}
		 * @private
		 */
		scrollable: null,
		
		/**
		 * Add nodes needed for widget
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			//Add inner node
			var node = Y.Node.create(this.CONTENT_TEMPLATE);
			node.addClass(this.getClassName("inner"));
			node.setAttribute("tabindex", "0");
			this.get("inputNode").insert(node, "after");
			this.set("innerNode", node);
			
			//Input doesn't need to be visible
			this.get("inputNode").addClass("hidden");
			
			//Text node
			var text_node = Y.Node.create("<p></p>");
			this.get("innerNode").append(text_node);
			this.set("textNode", text_node);
			
			//Dropdown node
			var dropdown_node = Y.Node.create('\
					<div class="' + this.getClassName('dropdown') + '">\
						<div class="' + this.getClassName('arrow') + '"></div>\
						<div class="' + this.getClassName('dropdown-content') + '"></div>\
					</div>');
			
			this.set("dropdownNode", dropdown_node);
			this.set("contentNode", dropdown_node.one("." + this.getClassName("dropdown-content")));
			this.get("innerNode").append(dropdown_node);
			
			//Sync values
			this.set("values", this.get("values"));
			this.set("value", this.get("value"));
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var inner_node = this.get("innerNode");
			inner_node.on("mouseenter", this._onMouseOver, this);
			inner_node.on("mouseleave", this._onMouseOut, this);
			inner_node.on("mousedown", this._onMouseDown, this);
			inner_node.on("keydown", this._onKeyPress, this);
			
			//Handle list item  click
			inner_node.delegate("click", this._onItemClick, "a[data-id]", this);
		},
		
		/**
		 * Overwrite focus UI event to prevent styling if input
		 * is disabled
		 */
		_uiSetFocused: function () {
			if (this.get("disabled")) return;
			Input.superclass._uiSetFocused.apply(this, arguments);
		},
		
		/**
		 * On mouse over set style
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_onMouseOver: function (e) {
			if (this.get("disabled")) return;
			this.get("boundingBox").addClass(this.getClassName("mouse-over"));
		},
		
		/**
		 * On mouse out remove style
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_onMouseOut: function (e) {
			if (this.get("disabled")) return;
			this.get("boundingBox").removeClass(this.getClassName("mouse-over"));
		},
		
		/**
		 * On mouse down open dropdown
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_onMouseDown: function (e) {
			//If user clicked inside dropdown then ignore
			var target = e.target.closest("." + this.getClassName("dropdown"));
			if (target) return;
			
			this.set("opened", !this.get("opened"));
		},
		
		/**
		 * On return/escape key down open/close dropdown and allow up/down key navigation
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_onKeyPress: function (e) {
			if (this.get("disabled")) return;
			
			var key = e.keyCode;
			
			if (!this.get("opened")) {
				if (key == 13 || key == 40) {
					//Return key or arrow down, open dropdown
					this.set("opened", true);;
				}
			} else {
				if (key == 27 || key == 9) {
					//Escape key or tab key, close dropdown
					this.set("opened", false);
				} else if (key == 40 || key == 38) {
					//Arrow down or up
					var dir = key == 40 ? "next" : "previous",
						node = this.get("dropdownNode"),
						item = node.one(".selected"),
						prev = item;
					
					if (!item) {
						item = node.one("." + this.getClassName("item"));
					} else {
						item = item[dir]();
					}
					
					//Find visible item
					while(item && item.hasClass("hidden")) {
						item = item[dir]();
					}
					
					//Style
					if (item) {
						this._highlightItem(item);
					}
				} else if (key == 13) {
					if (this.highlight_id !== null) {
						this.set("value", this.highlight_id);
					}
					
					this.set("opened", false);
				}
			}
			
			if (!(key in LOOKUP_BLACKLIST)) {
				var character = String.fromCharCode((96 <= key && key <= 105)? key-48 : key);
				if (character) {
					this._updateLookupString(character);
				}
			}
		},
		
		/**
		 * Highlight list item
		 * 
		 * @param {String} id Item id
		 * @private
		 */
		_highlightItem: function (id) {
			var node = this.get("dropdownNode"),
				prev_item = node.one(".selected"),
				new_item = null;
			
			if (id instanceof Y.Node) {	
				new_item = id;
				id = new_item.getAttribute('data-id');
			} else {
				new_item = node.one('[data-id="' + id + '"]');
			}
			
			if (new_item) {
				if (prev_item) {
					prev_item.removeClass('selected');
				}
				new_item.addClass('selected');
				
				this.highlight_id = id;
				
				//Update scroll position
				if (this.scrollable) {
					this.scrollable.scrollInView(new_item);
				}
				
				return id;
			} else {
				return false;
			}
		},
		
		/**
		 * On item click change value and close dropdown
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_onItemClick: function (e) {
			if (this.get("disabled")) return;
			
			var item = e.target.closest("a"),
				value = item.getAttribute("data-id");
			
			this.set("value", value);
			this.set("opened", false);
		},
		
		/**
		 * Open dropdown
		 * 
		 * @private
		 */
		_openDropDown: function () {
			if (this.get("disabled")) return;
			
			var inner_node = this.get("innerNode"),
				dropdown_node = this.get("dropdownNode"),
				bounding_node = this.get("boundingBox");
			
			bounding_node.addClass(this.getClassName("open"));
			dropdown_node.setStyles({
				"opacity": 0,
				"marginTop": -15,
				"minWidth": inner_node.get("offsetWidth") + "px"
			});
			
			//Animations
			if (!this.anim_open) {
				this.anim_open = new Y.Anim({
					node: dropdown_node,
				    duration: 0.25,
				    easing: Y.Easing.easeOutStrong,
					from: {opacity: 0, marginTop: -15},
					to: {opacity: 1, marginTop: 0}
				});
			}
			if (this.anim_close) {
				this.anim_close.stop();
			}
			
			this.anim_open
					.stop()
					.run();
			
			//Listeners
			this.close_event_listener = Y.one(document).on("mousedown", this._closeDropDownAttr, this);
			
			//Scrollable
			if (this.scrollable) {
				this.scrollable.syncUI();
			} else if (this.get("scrollable")) {
				//Scrollable content
				this.get("boundingBox").addClass(this.getClassName("scrollable"));
				
				this.scrollable = new Supra.Scrollable({
					"srcNode": this.get("contentNode"),
					"axis": "y"
				});
				
				this.scrollable.render();
				
				this.set("contentNode", this.scrollable.get("contentBox"));
			}
			
			//Highlight selected value
			this._highlightItem(this.get('value'));
		},
		
		/**
		 * Close dropdown by setting attribute
		 */
		_closeDropDownAttr: function (e) {
			//Check validity
			if (e && e.target) {
				var node = e.target.closest("." + this.getClassName("inner"));
				if (node && node === this.get("innerNode")) {
					return;
				}
			}
			
			if (this.get("opened")) {
				this.set("opened", false);
			}
		},
		
		/**
		 * Close dropdown without updating attribute value
		 */
		_closeDropDown: function () {
			//Remove listener
			if (this.close_event_listener) {
				this.close_event_listener.detach();
				this.close_event_listener = null;
			}
			
			//Animations
			if (!this.anim_close) {
				this.anim_close = new Y.Anim({
					node: this.get("dropdownNode"),
				    duration: 0.25,
				    easing: Y.Easing.easeOutStrong,
					from: {opacity: 1, marginTop: 0},
					to: {opacity: 0, marginTop: -15}
				});
				this.anim_close.on("end", function () {
					this.get("boundingBox").removeClass(this.getClassName("closing"));
				}, this);
			}
			if (this.anim_open) {
				this.anim_open.stop();
			}
			
			this.get("boundingBox").addClass(this.getClassName("closing"));
			this.get("boundingBox").removeClass(this.getClassName("open"));
			
			this.anim_close
					.stop()
					.run();
			
			//Remove item highlighting
			this.highlight_id = null;
			var item = this.get("dropdownNode").one(".selected");
			if (item) {
				item.removeClass("selected");
			}
		},
		
		/**
		 * 'Opened' attribute value setter
		 * 
		 * @param {Boolean} value Opened state
		 * @private
		 */
		_setDropdownOpened: function (value) {
			var prev = !!this.get("opened"),
				value = !!value;
			
			if (this.get("disabled")) {
				return false;
			}
			
			if (value != prev) {
				if (value) {
					this._openDropDown();
				} else {
					this._closeDropDown();
				}
			}
			
			return value;
		},
		
		/**
		 * Disabled attribute setter
		 * 
		 * @param {Array} values
		 * @return New values
		 * @type {Array}
		 * @private
		 */
		_setDisabled: function (value) {
			value = Input.superclass._setDisabled.apply(this, arguments);
			
			if (value && this.get("opened")) {
				this.set("opened", false);
			}
			
			return value;
		},
		
		/**
		 * Loading attribute setter
		 * 
		 * @param {Boolean} loading Loading attribute value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setLoading: function (loading) {
			var box = this.get("innerNode");
			
			if (box) {
				if (loading && !this.get("nodeLoading")) {
					var node = Y.Node.create('<span class="loading-icon"></span>');
					box.append(node);
					this.set("nodeLoading", node);
				}
				
				box.toggleClass(this.getClassName("loading"), loading);
			}
			
			this.set("disabled", loading);
			return loading;
		},
		
		/**
		 * Values attribute setter
		 * 
		 * @param {Array} values
		 * @return New values
		 * @type {Array}
		 * @private
		 */
		_setValues: function (values) {
			if (!Y.Lang.isArray(values)) values = [];
			
			var inputNode = this.get("inputNode"),
				contentNode = this.get("contentNode"),
				item_class = this.getClassName("item"),
				renderer = this.get("itemRenderer"),
				textRenderer = this.get("textRenderer"),
				show_empty_value = this.get("showEmptyValue"),
				html = null,
				title = null;
			
			if (inputNode) {
				var domNode = Y.Node.getDOMNode(inputNode),
					value = this.get("value"),
					text_node = this.get("textNode"),
					has_value = false;
				
				//Remove all options
				for(var i = domNode.options.length - 1; i>=0; i--) {
					domNode.remove(i);
				}
				
				if (contentNode) {
					contentNode.empty();
				}
				
				//Check if value is in new values list
				for(var i=0,ii=values.length; i<ii; i++) {
					if (values[i].id == value) {
						has_value = true;
						break;
					}
				}
				
				if (values.length && !has_value) {
					value = values[0].id;
				}
				
				//Render
				for(var i=0,ii=values.length; i<ii; i++) {
					
					//Check if title is localized
					title = values[i].title;
					title = (title || title === 0 ? String(title) : '');
					
					if (title.indexOf("{#") != -1) {
						title = Supra.Intl.replace(title);
					}
					
					domNode.options[i] = new Option(title, values[i].id, values[i].id == value);
					
					if (values[i].id == value && text_node) {
						if (textRenderer) {
							text_node.set("innerHTML", textRenderer(values[i], i));
						} else {
							text_node.set("text", title);
						}
					}
					
					if (contentNode && (show_empty_value || values[i].id !== "")) {
						if (renderer) {
							html = renderer(values[i], i);
						} else {
							html = '<a class="' + item_class + '" data-id="' + values[i].id + '">' + Supra.Y.Escape.html(title) + '</a>';
						}
						if (html) {
							contentNode.append(html);
						}
					}
				}
				
				// Set correct value
				inputNode.set('value', value);
			}
			
			return values;
		},
		
		/**
		 * Returns object with dropdown elements by value id
		 * 
		 * @return Dropdown elements
		 * @type {Object}
		 */
		getValueNodes: function () {
			var nodes = this.get("contentNode").all("." + this.getClassName("item")),
				node = null,
				obj = {},
				i = 0,
				ii = nodes.size();
			
			for(; i<ii; i++) {
				node = nodes.item(i);
				obj[node.getAttribute("data-id")] = node;
			}
			
			return obj;
		},
		
		/**
		 * Returns element for item with id
		 * 
		 * @return Dropdown element
		 * @type {Object}
		 */
		getValueNode: function (id) {
			return this.get("contentNode").one("." + this.getClassName("item") + "[data-id=\"" + id + "\"]") || null;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {String} value
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setValue: function (value) {
			value = Input.superclass._setValue.apply(this, arguments);
			
			//If not rendered yet, then textNode will not exist
			if (!this.get("textNode")) return value;
			
			var values = this.get("values"),
				title  = null,
				renderer = this.get("textRenderer");
			
			for(var i=0,ii=values.length; i<ii; i++) {
				if (values[i].id == value) {
					if (renderer) {
						this.setText(renderer(values[i], i) || "", false);
					} else {
						this.setText(values[i].title);
					}
					break;
				}
			}
			
			return value;
		},
		
		/**
		 * Returns input value
		 * 
		 * @returns {String} Value
		 * @private
		 */
		_getValue: function () {
			if (this.get('rendered')) {
				return Input.superclass._getValue.apply(this, arguments);
			} else {
				return this._original_value;
			}
		},
		
		/**
		 * Set dropdown text
		 * Can be used when value is not in the list or for custom text
		 * 
		 * @param {String} title Text
		 */
		setText: function (title, escape) {
			var node = this.get("textNode");
			if (!node) return this;
			
			//Check if title is localized
			title = String(title || "");
			if (title.indexOf("{#") != -1) {
				title = Supra.Intl.replace(title);
			}
			
			if (escape === false) {
				node.set("innerHTML", title);
			} else {
				node.set("text", title);
			}
			return this;
		},
		
		/**
		 * Returns full data for value
		 * If value is an array of values then returns array of data
		 * 
		 * @param {String} value Optional, value for which to return full data
		 * @returns {Object} Value data
		 */
		getValueData: function (value) {
			var value  = value === null || typeof value === 'undefined' ? this.get('value') : value,
				values = this.get('values'),
				i = 0,
				ii = values.length;
			
			// Single value
			for (; i<ii; i++) {
				if (values[i].id == value) {
					return values[i];
				}
			}
			
			return null;
		},
		
		/**
		 * Returns true if list has options with given id
		 * 
		 * @param {String} id Option ID
		 * @return True if has option with given id, otherwise false
		 * @type {Boolean}
		 */
		hasValue: function (id) {
			var values = this.get("values"),
				i = 0,
				ii = values.length;
			
			 for (; i<ii; i++) if (values[i].id == id) return true;
			 return false
		},
		
		/**
		 * Reset value to default
		 */
		resetValue: function () {
			var value = this.get("defaultValue"),
				values = this.get("values");
			
			this.set("value", value !== null ? value : (values.length ? values[0].id : ""));
			return this;
		},
		
		/**
		 * On destroy remove animations and listeners
		 */
		destructor: function () {
			if (this.anim_open) this.anim_open.destroy();
			if (this.anim_close) this.anim_close.destroy();
			if (this.close_event_listener) {
				this.close_event_listener.detach();
				this.close_event_listener = null;
			}
			
			this.get("innerNode").destroy(true);
		},
		
		
		/* -------------------------- Find item matching entered string  -------------------------- */
		
		
		/**
		 * Timer to clear lookup string
		 * @type {Object}
		 * @private
		 */
		_lookupCooldownTimer: null,
		
		/**
		 * Lookup string
		 * @type {String}
		 * @private
		 */
		_lookupString: '',
		
		
		/**
		 * Find matching value
		 * 
		 * @param {String} str
		 */
		_findMatchingValue: function (str) {
			var values = this.get('values'),
				i = 0,
				ii = values.length;
			
			str = str.toUpperCase();
			
			for (; i<ii; i++) {
				if (values[i].title.toUpperCase().indexOf(str) === 0) {
					return values[i].id;
				}
		 	}
		 	
		 	// Nothing matched, try partial
		 	for (i=0; i<ii; i++) {
				if (values[i].title.toUpperCase().indexOf(str) !== -1) {
					return values[i].id;
				}
		 	}
		 	
		 	return null;
		},
		
		/**
		 * Update lookup string
		 * @param {Object} character
		 */
		_updateLookupString: function (character) {
			if (this._lookupCooldownTimer) {
				this._lookupCooldownTimer.cancel();
			}
			this._lookupCooldownTimer = Y.later(1000, this, this._clearLookupString);
			
			this._lookupString += character;
			
			var value = this._findMatchingValue(this._lookupString);
			if (value) {
				if (this.get('opened')) {
					this._highlightItem(value);
				} else {
					this.set('value', value);
				}
			}
		},
		
		/**
		 * Clear lookup string
		 * 
		 * @private
		 */
		_clearLookupString: function () {
			if (this._lookupCooldownTimer) {
				this._lookupCooldownTimer.cancel();
				this._lookupCooldownTimer = null;
			}
			
			this._lookupString = '';
		}
		
	});
	
	Supra.Input.Select = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-string", "anim", "supra.scrollable"]});
