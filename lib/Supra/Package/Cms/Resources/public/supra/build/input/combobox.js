/**
 * Combo-box input
 */
YUI.add("supra.input-combobox", function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Templates
	 */
	Supra.Template.compile('\
			<div class="input-list clearfix">\
				<div class="input-list-items clearfix hidden"></div>\
			</div>', 'comboBoxList');
	
	Supra.Template.compile('\
			<span data-value="{{ id|escape("attr") }}" class="input-list-item">\
				{{ title|escape }}\
				<a></a>\
			</span>', 'comboBoxListItem');
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = "input-combobox";
	Input.CLASS_NAME = Input.CSS_PREFIX = "su-" + Input.NAME;
	
	Input.ATTRS = {
		
		/*
		 * Allow selecting multiple values
		 */
		"multiple": {
			value: false
		},
		
		/*
		 * Data source
		 * Array of values, URL from where to load values or CRUD id
		 */
		"values": {
			value: null
		},
		
		/*
		 * Allow opening list in sidebar
		 */
		"sidebar": {
			value: true
		}
	};
	
	Input.HTML_PARSER = {
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		/**
		 * Current value as array
		 * @type {Array}
		 * @private
		 */
		value: null,
		
		/**
		 * Source type, either "array", "url" or "crud"
		 * @type {String}
		 * @private
		 */
		sourceType: null,
		
		/**
		 * List of all values ir source type is "array"
		 * @type {Array|Null}
		 * @private
		 */
		values: null,
		
		/**
		 * Nodes and widgets
		 * @type {Object}
		 * @private
		 */
		nodes: null,
		
		/**
		 * Overlay visibility state
		 * @type {Boolean}
		 * @private
		 */
		overlayVisible: false,
		
		/**
		 * Last known filter value
		 */
		filterValue: "",
		
		
		/**
		 * Render nodes, etc.
		 * 
		 * @private
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			// Initialize values list
			this._attrHandleSourceChange({"newVal": this.get("values")});
			this.value = this._normalizeValue(this.value);
			
			var content = this.get("contentBox"),
				
				list = Y.Node.create(Supra.Template("comboBoxList", {})),
				input,
				button;
			
			content.append(list);
			
			// Button to open sidebar
			if (this.get("sidebar")) {
				button = new Supra.Button();
				button.addClass("button-section");
				button.render(list);
				
				this.get("boundingBox").addClass(this.getClassName("sidebar"));
			}
			
			// Input for autocomplete lookup
			input = new Supra.Input.String();
			input.render(list);
			input.addClass("search");
			
			this.nodes = {
				// Inner node
				list: list,
				
				// Input node
				input: input,
				
				// Sidebar button
				button: button
			};
			
			this.plug(Supra.Input.PluginAutoComplete, {
				"inputNode": input.get("inputNode"),
				"targetNode": list,
				"values": this._transformSource(this.get("values"))
			});
		},
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			this.after('valueChange', this._afterValueChange, this);
			this.after("valuesChange", this._attrHandleSourceChange, this);
			this.after("valueChange", this.syncUI, this);
			this.after("disabledChange", this._attrHandleDisabledChange, this);
			
			this.autocomplete.on("add", this._handleItemAdd, this);
			this.nodes.list.delegate("click", this._handleItemRemove, ".input-list-item a", this);
			
			if (this.nodes.button) {
				this.nodes.button.on("click", this.sidebarOpen, this);
			}
		},
		
		/**
		 * Update UI based on current attribute states
		 */
		syncUI: function () {
			this._rerenderValue();
		},
		
		/**
		 * Clean up
		 */
		destructor: function () {
			this.sidebarClose();
			
			var nodes = this.nodes;
			
			if (nodes) {
				if (nodes.input) nodes.input.destroy();
				if (nodes.button) nodes.button.destroy();
				
				this.nodes = null;
			}
			
			this.values = null;
			this.value = null
		},
		
		
		/* ---------------------------- Value list ---------------------------- */
		
		
		/**
		 * Handle item remove click
		 *
		 * @param {Object} e Event facade object
		 */
		_handleItemRemove: function (e) {
			if (this.get("disabled")) return;
			var id = e.target.get("parentNode").getAttribute("data-value");
			
			if (id) {
				this.remove(id);
			}
			
			e.preventDefault();
		},
		
		_handleItemAdd: function (e) {
			if (this.get("disabled")) return;
			var item = e.data;
			
			if (item) {
				this.nodes.input.set("value", "");
				this.add(item);
				
				// Blur if only 1 item can be added
				if (!this.get("multiple")) {
					this.nodes.input.get("inputNode").blur();
				}
			}
		},
		
		_rerenderValue: function () {
			var value = this.value,
				value_ids = {},
				i = 0,
				ii = value.length,
				
				list  = this.nodes.list.one(".input-list-items"),
				items = list.all("span"),
				items_ids = {},
				
				item,
				id,
				
				changed = false;
			
			// Find all ids
			for (; i<ii; i++) {
				value_ids[value[i].id] = true;
			}
			
			// Go through items, remove old ones and index existing
			for (i=0, ii=items.size(); i<ii; i++) {
				item = items.item(i);
				id = item.getAttribute("data-value");
				
				if (!(id in value_ids)) {
					// Remove item
					item.remove(true);
					changed = true;
				} else {
					// Index
					items_ids[id] = item;
				}
			}
			
			// Fix order and create new items
			for (i=0, ii=value.length; i<ii; i++) {
				item = items_ids[value[i].id];
				
				if (!item) {
					item = Y.Node.create(Supra.Template("comboBoxListItem", value[i]));
					changed = true;
				}
				
				list.append(item);
			}
			
			if (value.length) {
				list.removeClass("hidden");
			} else {
				list.addClass("hidden");
			}
			
			if (changed) {
				this.get("boundingBox").fire("contentresize");
			}
		},
		
		
		/* ---------------------------- Sidebar ---------------------------- */
		
		
		/**
		 * Open sidebar
		 */
		sidebarOpen: function () {
			var source_type = this.sourceType;
			
			Supra.Manager.executeAction("ComboSidebar", {
				// values is either array or crud ID or request url
				"values": this.get("values"),
				
				// currently selected value
				"value": [].concat(this.value),
				
				// allow selecting multiple items
				"multiple": this.get("multiple"),
				
				"title": this.get("label"),
				
				"onupdate": Y.bind(this._handleSidebarValueUpdate, this),
				
				"onclose": Y.bind(this._handeSidebarClose, this)
			});
			
			// Disable to prevent any changes while sidebar is opened
			this.set("disabled", true);
		},
		
		/**
		 * Close sidebar
		 */
		sidebarClose: function () {
			var action = Supra.Manager.getAction("ComboSidebar");
			if (action.get("visible")) {
				action.close();
				action.hide();
			}
		},
		
		/**
		 * Handle sidebar closing
		 * 
		 * @private
		 */
		_handeSidebarClose: function () {
			this.set("disabled", false);
		},
		
		/**
		 * When sidebar value changes update this
		 * 
		 * @param {String} operation Either "add" or "remove"
		 * @param {Object} value Value which was added or removed
		 * @private
		 */
		_handleSidebarValueUpdate: function (operation, value) {
			if (operation === "add") {
				this.add(value);
			} else if (operation === "remove") {
				this.remove(value);
			}
		},
		
		
		/* ---------------------------- Value ---------------------------- */
		
		
		add: function (item) {
			if (this.indexOf(item) !== -1) return this;
			
			var value = this.value.concat(item);
			
			if (this.get("multiple")) {
				this.set("value", this.value.concat(item));
			} else {
				this.set("value", item);
			}
			
			return this;
		},
		
		indexOf: function (item) {
			var id = (item && typeof item === "object" && "id" in item ? item.id : item),
				value = this.value,
				i = 0,
				ii = value.length;
			
			for (; i<ii; i++) {
				if (value[i].id == id) return i;
			}
			
			return -1;
		},
		
		has: function (item) {
			return this.indexOf(item) !== -1;
		},
		
		remove: function (item) {
			var value = this.value,
				index = this.indexOf(item);
			
			if (index !== -1) {
				value = [].concat(value);
				value.splice(index, 1);
				
				this.set("value", value);
			}
			
			return this;
		},
		
		/**
		 * Convert value into an array of values, where each value is an
		 * object with at least "id" key.
		 *
		 * @param {Array|String} _value Value
		 * @param {Array} [_values] Optional, list of all values
		 * @returns {Array} Normalized array
		 */
		_normalizeValue: function (_value, _values) {
			var value,
				i = 0,
				ii,
				
				values = _values || this.values,
				v,
				vv = values ? values.length : 0,
				
				normalized = [];
			
			if (!Y.Lang.isArray(_value)) {
				value = _value ? [_value] : [];
			} else {
				value = _value;
			}
			
			for (ii=value.length; i<ii; i++) {
				if (value[i] || value[i] === 0) {
					if (typeof value[i] !== "object") {
						// ID only, find a value
						if (values) {
							for (v=0; v<vv; v++) {
								if (values[v].id == value[i]) {
									normalized.push(values[v]);
									break;
								}
							}
						}
					} else {
						normalized.push(value[i]);
					}
				}
			}
			
			return normalized;
		},
		
		
		/* ---------------------------- Attributes ---------------------------- */
		
		
		/**
		 * Change attribute value
		 */
		_applyValue: function (value) {
			if (this.get("multiple")) {
				this.set("value", this.value);
			} else {
				this.set("value", this.value[0] || null);
			}
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {String} value New value
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setValue: function (value) {
			if (this.get("rendered")) {
				this.value = this._normalizeValue(value);
			} else {
				this.value = value;
			}
			
			return this.value;
		},
		
		/**
		 * Returns current value
		 * 
		 * @returns {Object|Array} Returns selected value
		 */
		_getValue: function () {
			var value = this.value;
			
			if (this.get("multiple")) {
				return value;
			} else {
				if (Y.Lang.isArray(value)) {
					return value.length ? value[0] : null;
				} else {
					// It won"t be an array before render
					return value;
				}
			}
		},
		
		/**
		 * Returns current value for sending to server
		 * 
		 * @returns {Number|String|Array} Returns selected value id or ids
		 */
		_getSaveValue: function () {
			var value = this.value,
				result = [],
				i = 0, ii;
			
			if (this.get("multiple")) {
				for (ii=value.length; i<ii; i++) {
					result.push(value[i].id);
				}
				return result;
			} else {
				if (value.length) {
					return value[0].id;
				} else {
					return null;
				}
			}
		},
		
		/**
		 * On source attribute change update autocomplete
		 *
		 * @param {Object} evt Event facade object
		 * @private
		 */
		_attrHandleSourceChange: function (evt) {
			if (this.autocomplete) {
				this.autocomplete.set("values", this._transformSource(evt.newVal));
			}
		},
		
		/**
		 * Transform source attribute by replacing CRUD id with url
		 * 
		 * @param {String|Array} source Source
		 * @returns {String|Array} Transformed source
		 * @private
		 */
		_transformSource: function (source) {
			if (typeof source === "string") {
				var url = source;
				
				if (source[0] !== "/") {
					url = Supra.Crud.getDataPath(source, "datalist");	
				}
				
				url += (url.indexOf('?') !== -1 ? '&' : '?') + 'autocomplete=1';
				
				return url;
			} else {
				return source;
			}
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire("change", {"value": evt.newVal});
			}
		},
		
		_attrHandleDisabledChange: function (evt) {
			if (this.nodes.input) this.nodes.input.set("disabled", evt.newVal);
			if (this.nodes.button) this.nodes.button.set("disabled", evt.newVal);
		}
		
	});
	
	Supra.Input.ComboBox = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn);this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "supra.input-string", "supra.button", "supra.input-plugin-autocomplete", "supra.crud"]});

		
		
