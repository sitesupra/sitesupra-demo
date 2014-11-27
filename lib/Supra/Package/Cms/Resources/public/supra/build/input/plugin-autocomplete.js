/**
 * Plugin to show autocomplete
 */
YUI.add("supra.input-plugin-autocomplete", function (Y) {
	//Invoke strict mode
	"use strict";
	
	Supra.Template.compile('<div class="su-input-autocomplete"></div>', 'autoCompleteOverlay');
	Supra.Template.compile('<li data-id="{{ id }}">{{ title }}</li>', 'autoCompleteOverlayItem');
	Supra.Template.compile('<li>{{ message }}</li>', 'autoCompleteOverlayItemEmpty');
	
	/**
	 * Folder rename plugin
	 * Saves item properties when they change
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = "autocomplete";
	Plugin.NS = "autocomplete";
	
	Plugin.ATTRS = {
		
		/*
		 * Input node
		 * @type {Object}
		 */
		"inputNode": {
			value: null
		},
		
		/**
		 * Element inside which to render autocomplete, optional
		 * @type {Object|Null}
		 */
		"targetNode": {
			value: null
		},
		
		/*
		 * Data source
		 * Array of values or URL from where to load values
		 * @type {Array|String}
		 */
		"values": {
			value: ""
		},
		
		/**
		 * Input which value is binded with this 
		 * @type {Object}
		 */
		"input": {
			value: null
		},
		
		"templateOverlay": {
			value: "autoCompleteOverlay"
		},
		
		"templateItem": {
			value: "autoCompleteOverlayItem"
		},
		
		"templateItemEmpty": {
			value: "autoCompleteOverlayItemEmpty"
		},
		
		"messageNoResults": {
			value: "{# inputs.autocomplete_empty #}"
		}
	};
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * List of values
		 * @type {Array}
		 */
		values: null,
		
		/**
		 * Source type, either "array" or "url"
		 * @type {String}
		 */
		sourceType: null,
		
		/**
		 * If source is URL then url where to load values from
		 * @type {String}
		 */
		sourceUrl: "",
		
		/**
		 * Widgets
		 * @type {Object}
		 */
		nodes: null,
		
		/**
		 * Overlay visibility state
		 * @type {Boolean}
		 */
		overlayVisible: false,
		
		/**
		 * Currently focused autocomplete item index
		 * @type {Number}
		 */
		overlayIndex: -1,
		
		/**
		 * Currently focused autocomplete item node
		 * @type {Object}
		 */
		overlayIndexNode: null,
		
		/**
		 * Last used filter values
		 * @type {String}
		 */
		filterValue: "",
		
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			var input = this.get("inputNode") || this.get("host").get("inputNode"),
				filter = Supra.throttle(this._autoCompleteFilter, 150, this, true);
			
			this.after("valuesChange", this._attrHandleSourceChange, this);
			
			input.on("keyup", filter);
			input.on("change", filter);
			input.on("keydown", this._autoCompleteKeyPress, this);
			input.on("focus", this._showDelayed, this);
			input.on("blur", this._hideDelayed, this);
			
			this.nodes = {
				datalist: null,
				autocomplete: null
			};
			
			this._attrHandleSourceChange({"newVal": this.get("values")});
		},
		
		/**
		 * Teardown plugin
		 */
		destructor: function () {
			this.hide();
			
			var nodes = this.nodes;
			
			if (nodes) {
				if (nodes.datalist) nodes.datalist.destroy();
				if (nodes.autocomplete) nodes.autocomplete.remove(true);
				
				this.nodes = null;
			}
			
			if (this.anim_close) {
				this.anim_close.destroy();
				this.anim_close = null;
			}
			if (this.anim_open) {
				this.anim_open.destroy();
				this.anim_open = null;
			}
			
			this.values = null;
		},
		
		
		_fireAdd: function (data) {
			this.fire("add", {"data": data});
		},
		
		_handleItemAdd: function (e) {
			if (this.get("disabled")) return;
			var item = this.nodes.datalist.getItemDataByNode(e.target);
			
			if (item) {
				this._fireAdd(item);
			}
			
			e.halt();
		},
		
		
		/* ---------------------------- Overlay ---------------------------- */
		
		
		_autoCompleteFilter: function (e) {
			if (this.get("host").get("disabled")) return;
			
			var input = this.get("inputNode") || this.get("host").get("inputNode");
			var query = input.get("value").toLowerCase();
			
			if (!this.overlayVisible) {
				if (query) {
					this._showDelayed();
				} else {
					return;
				}
			}
			
			var source_url = this.sourceUrl,
				source_type = this.sourceType,
				
				values = this.values,
				filtered_values,
				
				datalist = this.nodes.datalist;
			
			if (this.filterValue == query) {
				// Already filtered
				return;
			}
			
			if (source_type === "array") {
				filtered_values = Y.Array.map(values, function (item, index) {
					var title = "";
					if (item && typeof item === "object" && "title" in item) {
						title = item.title;
					}  else if (typeof item === "string") {
						title = item;
					}
					if (title && title.toLowerCase().indexOf(query) !== -1) return item;
				});
				
				datalist.set("data", filtered_values);
			} else {
				source_url += (source_url.indexOf("?") !== -1 ? "&" : "?") + "q=" + encodeURIComponent(query);
				datalist.set("dataSource", source_url);
			}
			
			this.filterValue = query;
			
			// Reset selection
			this.overlayIndex = -1;
			this._autoCompleteUpdateFocusedItem();
		},
		
		_overlayRender: function () {
			if (!this.nodes.overlay) {
				var overlay = Y.Node.create(Supra.Template(this.get("templateOverlay"), {})),
					target = this.get("targetNode") || this.get("host").get("contentBox"),
					datalist;
				
				datalist = new Supra.DataList({
					"data": this.sourceType === "array" ? this.values : null,
					"dataSource": this.sourceUrl,
					"scrollable": true,
					"listSelector": "ul",
					"itemTemplate": this.get("templateItem"),
					"itemEmptyTemplate": this.get("templateItemEmpty"),
					"messageNoResults": this.get("messageNoResults")
				});
				
				target.append(overlay);
				datalist.render(overlay);
				datalist.on("redraw", this._autoCompleteUpdateFocusedItem, this);
				
				this.nodes.overlay = overlay;
				this.nodes.datalist = datalist;
				
				this.nodes.overlay.delegate("mousedown", this._handleItemAdd, "li", this);
			}
		},
		
		/**
		 * Open overlay
		 */
		show: function () {
			if (this.hideTimer) {
				// Called .show right after .hide
				this.hideTimer.cancel();
				this.hideTimer = null;
			}
			
			if (this.overlayVisible || this.get("host").get("disabled")) return;
			if (!this.nodes.overlay) this._overlayRender();
			var overlay = this.nodes.overlay;
			
			this.showTimer = null;
			this.overlayVisible = true;
			this.overlayIndex = -1;
			this.overlayIndexNode = null;
			this._autoCompleteUpdateFocusedItem();
			
			//Animations
			if (this.anim_close) {
				this.anim_close.stop();
			}
			
			overlay.addClass("opening").setStyles({"opacity": 0, "marginTop": -15}).removeClass("hidden");
			
			if (!this.anim_open) {
				this.anim_open = new Y.Anim({
					node: overlay,
				    duration: 0.25,
				    easing: Y.Easing.easeOutStrong,
					from: {opacity: 0, marginTop: -15},
					to: {opacity: 1, marginTop: 0}
				});
			}
			
			this.anim_open.run();
			
			// Scroll to top
			var datalist = this.nodes.datalist;
			if (datalist.nodes.scrollable) {
				datalist.nodes.scrollable.setScrollPosition(0);
			}
			
			// Remove filter
			if (this.filterValue) {
				this.filterValue = "";
				
				var source_url = this.sourceUrl,
					source_type = this.sourceType,
					datalist = this.nodes.datalist;
				
				if (source_type === "array") {
					datalist.set("data", this.values);
				} else {
					datalist.set("dataSource", source_url);
				}
			}
		},
		
		/**
		 * Close overlay
		 */
		hide: function () {
			if (this.showTimer) {
				// Called .hide right after .show
				this.showTimer.cancel();
				this.showTimer = null;
			}
			
			if (!this.overlayVisible) return;
			var input = this.get("inputNode") || this.get("host").get("inputNode"),
				overlay = this.nodes.overlay;
			
			if (overlay) {
				//Animations
				if (this.anim_open) {
					this.anim_open.stop();
				}
				if (!this.anim_close) {
					this.anim_close = new Y.Anim({
						node: overlay,
					    duration: 0.25,
					    easing: Y.Easing.easeOutStrong,
						to: {opacity: 0, marginTop: -15},
						from: {opacity: 1, marginTop: 0}
					});
					this.anim_close.on("end", function () {
						this.nodes.overlay.addClass("hidden");
						this.overlayVisible = false;
					}, this);
				}
				
				this.anim_close.run();
			}
			
			if (this.overlayIndexNode) {
				this.overlayIndexNode.removeClass("selected");
				this.overlayIndexNode = null;
			}
			
			this.overlayVisible = false;
			this.hideTimer = null;
			
			input.set("value", "");
		},
		
		/**
		 * Close overlay after small delay
		 * This is done to prevent click event loosing target if input "blur"
		 * event closes overlay immediatelly
		 * 
		 * @private
		 */
		_hideDelayed: function () {
			if (this.showTimer) {
				this.showTimer.cancel();
				this.showTimer = null;
			}
			if (!this.hideTimer) {
				this.hideTimer = Supra.immediate(this, this.hide);
			}
		},
		
		/**
		 * We can't control how inputs are focused / blurred, so we have to 
		 * tollerate fast focus / blur switching
		 *
		 * @private
		 */
		_showDelayed: function () {
			if (this.hideTimer) {
				this.hideTimer.cancel();
				this.hideTimer = null;
			}
			if (!this.showTimer) {
				this.showTimer = Supra.immediate(this, this.show);
			}
		},
		
		/**
		 * Handle key press
		 * Up and down arrows changes selected item, return key adds selected item
		 *
		 * @private
		 */
		_autoCompleteKeyPress: function (e) {
			if (this.overlayVisible) {
				var index = this.overlayIndex,
					length = this.nodes.datalist.size();
				
				if (e.which === 40) {
					// Arrow down -> next item
					if (index < length - 1) {
						this.overlayIndex++;
						this._autoCompleteUpdateFocusedItem();
						e.preventDefault();
					}
				} else if (e.which == 38) {
					// Arrow up -> previous item
					if (index > 0) {
						this.overlayIndex--;
						this._autoCompleteUpdateFocusedItem();
						e.preventDefault();
					}
				} else if (e.which == 13) {
					// Return key -> add item
					var datalist = this.nodes.datalist,
						item = datalist.getItemDataByProperty("index", this.overlayIndex);
					
					if (item) {
						this._fireAdd(item);
						e.preventDefault();
					}
				}
			} else if (e.which === 40) {
				// Arrow down -> open list
				this.show();
				e.preventDefault();
			}
		},
		
		/**
		 * Update focused item style
		 *
		 * @private
		 */
		_autoCompleteUpdateFocusedItem: function () {
			var datalist = this.nodes.datalist,
				length = datalist.size(),
				index = Math.max(-1, Math.min(this.overlayIndex, length - 1)),
				node_prev = this.overlayIndexNode,
				node_new = index !== -1 ? datalist.getItemNodeByProperty("index", index) : null;
			
			if (node_prev && !node_prev.getDOMNode()) {
				// Could be that it's already removed from DOM
				node_prev = null;
			}
			
			if (node_new && node_prev && node_new.compareTo(node_prev)) {
				// Same node, don"t do anything
				return;
			}
			
			if (node_prev) {
				node_prev.removeClass("selected");
			}
			if (node_new) {
				// Node will be missing if there are no items
				node_new.addClass("selected");
				
				if (datalist.nodes.scrollable) {
					datalist.nodes.scrollable.scrollInView(node_new);
				}
			}
			
			this.overlayIndexNode = node_new;
		},
		
		
		/* ---------------------------- Attributes ---------------------------- */
		
		
		/**
		 * On source attribute change update datalist and revalidate values
		 *
		 * @param {Object} evt Event facade object
		 * @private
		 */
		_attrHandleSourceChange: function (evt) {
			var datalist = (this.nodes ? this.datalist : null);
			
			// Reset selection
			if (this.get('rendered') && this.overlayIndex !== -1) {
				this.overlayIndex = -1;
				this._autoCompleteUpdateFocusedItem();
			}
			
			if (evt.newVal) {
				if (Y.Lang.isArray(evt.newVal)) {
					// Array of values
					this.values = evt.newVal;
					this.sourceType = "array";
					this.sourceUrl = "";
					
					if (datalist) {
						datalist.set("data", evt.newVal);
						datalist.set("dataSource", null);
					}
					
					return;
				} else if (typeof evt.newVal === "string") {
					// URL or CRUD id
					var url = "";
					this.values = null;
					this.sourceType = "url";
					this.sourceUrl = evt.newVal;
					
					if (datalist) {
						datalist.set("data", null);
						datalist.set("dataSource", evt.newVal);
					}
					
					return;
				}
			}
			
			this.values = [];
			this.sourceType = "array";
			this.sourceUrl = null;
			
			if (datalist) {
				datalist.set("data", []);
				datalist.set("dataSource", null);
			}
		}
		
	});
	
	
	Supra.Input.PluginAutoComplete = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {"requires": ["plugin", "supra.datalist"]});
