YUI.add("supra.input-color", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Color = Y.DataType.Color;
	
	var TEMPLATE = Supra.Template.compile('\
						{% if allowUnset or presets %}\
							<div class="presets">\
								{% if presets %}{% for preset in presets %}\
									<a class="preset" style="background-color: {{ preset }};" data-color="{{ preset|upper }}"></a>\
								{% endfor %}{% endif %}\
								{% if allowUnset %}\
									<a class="unset"></a><label>{{ labelUnset|escape }}</label>\
								{% endif %}\
							</div>\
						{% endif %}\
						<div class="input-content">\
							<div class="map"><div class="handle"></div><div class="cursor hidden"></div></div>\
							<div class="bar"><div class="handle"></div></div>\
							<div class="preview"></div>\
							<span>#</span>\
							<input type="text" name="hex" maxlength="6" /><br />\
							<span>{{ "{# inputs.red #}"|default("R") }}</span>\
							<input type="text" name="red" maxlength="3" class="rgb" /><br />\
							<span>{{ "{# inputs.green #}"|default("G") }}</span>\
							<input type="text" name="green" maxlength="3" class="rgb" /><br />\
							<span>{{ "{# inputs.blue #}"|default("B") }}</span>\
							<input type="text" name="blue" maxlength="3" class="rgb" />\
						</div>\
					');
	
	/**
	 * Color picker input
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		
		this.hex = "#000000";
		this.rgb = {"red": 0, "green": 0, "blue": 0};
		this.hsb = {"hue": 0, "saturation": 0, "brightness": 0};
		
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = "input-color";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		"value": {
			"value": ""
		},
		// Map node
		"nodeMap": {
			"value": null
		},
		// Map handle node
		"nodeMapHandle": {
			"value": null
		},
		// Map cursor node
		"nodeMapCursor": {
			"value": null
		},
		// Bar node
		"nodeBar": {
			"value": null
		},
		// Bar handle node
		"nodeBarHandle": {
			"value": null
		},
		// Preview node
		"nodePreview": {
			"value": null
		},
		//HEX input
		"nodeInputHEX": {
			"value": null
		},
		//Red input
		"nodeInputRed": {
			"value": null
		},
		//Green input
		"nodeInputGreen": {
			"value": null
		},
		//Blue input
		"nodeInputBlue": {
			"value": null
		},
		//Node to unset color
		"nodeUnset": {
			"value": null
		},
		//Allow to unset color
		"allowUnset": {
			"value": false
		},
		//Unset button text
		"labelUnset": {
			"value": "No color"
		},
		//Shim node
		"nodeShim": {
			"value": null
		},
		//Preset list of colors
		"presets": {
			"value": null
		},
		//Color preset nodes
		"nodePresets": {
			"value": null
		}
	};
	
	Input.HTML_PARSER = {
		"allowUnset": function (srcNode) {
			var input = this.get("inputNode"),
				unset = srcNode.getAttribute("suAllowUnset") == "true" || (input && input.getAttribute("suAllowUnset") == "true");
			
			return unset === true ? true : null;
		},
		"presets": function (srcNode) {
			var input = this.get("inputNode"),
				presets = srcNode.getAttribute("suPresets") || (input && input.getAttribute("suPresets"));
			
			return presets ? presets.split(',') : null;
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: "<input type=\"hidden\" value=\"\" />",
		LABEL_TEMPLATE: "<label></label>",
		
		/**
		 * Value is unset
		 * @type {Boolean}
		 * @private
		 */
		unset: false,
		
		/**
		 * Preset index which is choosen
		 * @type {Number}
		 * @private
		 */
		preset: -1,
		
		/**
		 * Value as HEX
		 * @type {String}
		 * @private
		 */
		hex: "#000000",
		
		/**
		 * Values as RGB
		 * @type {Object}
		 * @private
		 */
		rgb: null,
		
		/**
		 * Values as HSB
		 * @type {Object}
		 * @private
		 */
		hsb: null,
		
		/**
		 * Map node position relative to the page
		 * @type {Array}
		 * @private
		 */
		mapPosition: null,
		
		/**
		 * Map cursor style is dark
		 * @type {Boolean}
		 * @private
		 */
		mapCursorDark: true,
		mapHandleDark: true,
		
		/**
		 * Mouse is down on map
		 * @type {Boolean}
		 * @private
		 */
		mapCursorDown: false,
		
		/**
		 * Subscription object for document move
		 * @type {Object}
		 * @private
		 */
		cursorMoveEvent: null,
		cursorUpEvent: null,
		
		
		/**
		 * Bar node position relative to the page
		 * @type {Array}
		 * @private
		 */
		barPosition: null,
		
		/**
		 * Mouse is down on bar
		 * @type {Boolean}
		 * @private
		 */
		barCursorDown: false,
		
		/**
		 * While frozen UI will not be updated
		 * @type {Boolean}
		 * @private
		 */
		uiFrozen: false,
		
		
		
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var contentBox = this.get("contentBox"),
				template = Y.Node.create(TEMPLATE({
					"allowUnset": this.get("allowUnset"),
					"labelUnset": this.get("labelUnset"),
					"presets": this.get("presets")
				}));
			
			//Attributes
			this.set("nodePreview", template.one(".preview"));
			this.set("nodeMap", template.one(".map"));
			this.set("nodeMapHandle", template.one(".map .handle"));
			this.set("nodeMapCursor", template.one(".map .cursor"));
			this.set("nodeBar", template.one(".bar"));
			this.set("nodeBarHandle", template.one(".bar .handle"));
			this.set("nodeInputHEX", template.one("input[name=\"hex\"]"));
			this.set("nodeInputRed", template.one("input[name=\"red\"]"));
			this.set("nodeInputGreen", template.one("input[name=\"green\"]"));
			this.set("nodeInputBlue", template.one("input[name=\"blue\"]"));
			
			if (this.get("allowUnset")) {
				this.set("nodeUnset", template.one("div.presets a.unset"));
			}
			if (this.get("presets")) {
				this.set("nodePresets", template.all("div.presets a.preset"));
			}
			
			//Render template
			if (contentBox.test('input')) {
				contentBox.addClass('hidden');
				this.get('boundingBox').append(template.size ? template.get("children") : template);
			} else {
				contentBox.append(template.size ? template.get("children") : template);
			}
			
			//Value
			var value = this.get('value'),
				fixed = (value || "#000000").toUpperCase(),
				presets = this.get('presets');
			
			this.hex = fixed;
			this.rgb = Color.convert.HEXtoRGB(fixed);
			this.hsb = Color.convert.RGBtoHSB(this.rgb);
			
			if (this.get("allowUnset") && !value) {
				this.unset = true;
			}
			
			if (presets && presets.length) {
				var i   = 0,
					ii  = presets.length,
					hex = this.hex;
				
				for (; i<ii; i++) {
					if (presets[i].toUpperCase() == hex) {
						this.preset = i; break;
					}
				}
			}
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var nodeMap = this.get("nodeMap"),
				nodeBar = this.get("nodeBar");
			
			nodeMap.on("mouseenter", this._showMapCursor, this);
			nodeMap.on("mouseleave", this._hideMapCursor, this);
			nodeMap.on("mousemove", this._moveMapCursor, this);
			nodeMap.on("mousedown", this._downMapCursor, this);
			nodeMap.on("mouseup", this._upMapCursor, this);
			
			nodeBar.on("mousedown", this._downBarCursor, this);
			nodeBar.on("mouseup", this._upBarCursor, this);
			
			this.get("nodeInputHEX").on("blur", this._onBlurHEX, this);
			this.get("nodeInputRed").on("blur", this._onBlurRGB, this);
			this.get("nodeInputGreen").on("blur", this._onBlurRGB, this);
			this.get("nodeInputBlue").on("blur", this._onBlurRGB, this);
			
			this.get("nodeInputHEX").on("keyup", this._onKeyHEX, this);
			this.get("nodeInputRed").on("keyup", this._onKeyRGB, this);
			this.get("nodeInputGreen").on("keyup", this._onKeyRGB, this);
			this.get("nodeInputBlue").on("keyup", this._onKeyRGB, this);
			
			if (this.get("allowUnset")) {
				this.get("nodeUnset").on("mousedown", this._onUnset, this);
			}
			if (this.get("presets")) {
				this.get("nodePresets").on("mousedown", this._onPreset, this);
			}
			
			//Handle value attribute change
			this.on('valueChange', this._afterValueChange, this);
			
			this.syncUI();
		},
		
		syncUI: function () {
			this.syncUIMap();
			this.syncUIBar();
			this.syncUIRGB();
			this.syncUIHEX();
			this.syncUIPreview();
		},
		
		/**
		 * Update map UI
		 * @private
		 */
		syncUIMap: function () {
			if (this.get("nodeMap") && !this.uiFrozen) {
				//Background color
				var background = {'hue': this.hsb.hue, 'saturation': 100, 'brightness': 100};
				this.get("nodeMap").setStyle("backgroundColor", Color.convert.HSBtoHEX(background));
				
				//Handle position
				this.get("nodeMapHandle").setStyles({
					"left": Math.round(this.hsb.saturation / 100 * 110) + "px",
					"top": 110 - Math.round(this.hsb.brightness / 100 * 110) + "px"
				});
				
				//
				if (100 - this.hsb.brightness + this.hsb.saturation > 50) {
					this.get("nodeMapHandle").addClass("light");
					this.mapHandleDark = false;
				} else {
					this.get("nodeMapHandle").removeClass("light");
					this.mapHandleDark = true;
				}
			}
		},
		
		/**
		 * Update bar UI
		 * @private
		 */
		syncUIBar: function () {
			if (this.get("nodeBarHandle") && !this.uiFrozen) {
				var pos = 110 - Math.round(this.hsb.hue / 359 * 110),
					cur = parseInt(this.get("nodeBarHandle").getStyle("top"), 10);
				
				if (pos != cur) {
					this.get("nodeBarHandle").setStyle("top", pos + "px");
				}
			}
		},
		
		syncUIRGB: function () {
			if (this.get("nodeInputRed") && !this.uiFrozen) {
				this.get("nodeInputRed").set("value", this.rgb.red);
				this.get("nodeInputGreen").set("value", this.rgb.green);
				this.get("nodeInputBlue").set("value", this.rgb.blue);
			}
		},
		
		syncUIHEX: function () {
			var input = this.get("nodeInputHEX"),
				value = null;
			
			if (input && !this.uiFrozen) {
				value = this.hex.replace('#', '');
				if (input.get('value') != value) {
					input.set("value", value);
				}
			}
		},
		
		/**
		 * Update preview UI
		 * @private
		 */
		syncUIPreview: function () {
			if (this.get("nodePreview") && !this.uiFrozen) {
				var nodeUnset = this.get("nodeUnset"),
					nodePresets = this.get("nodePresets");
				
				if (this.unset) {
					if (nodeUnset) nodeUnset.addClass("active");
					this.get("nodePreview").addClass("preview-unset");
					this.get("nodePreview").setStyle("backgroundColor", this.hex);
				} else {
					if (nodeUnset) nodeUnset.removeClass("active");
					this.get("nodePreview").removeClass("preview-unset");
					this.get("nodePreview").setStyle("backgroundColor", this.hex);
				}
				
				if (nodePresets) {
					nodePresets.removeClass("active");
					if (this.preset >= 0) {
						nodePresets.item(this.preset).addClass("active");
					}
				}
			}
		},
		
		
		/**
		 * -------------------------------- INPUT CHANGE -----------------------------
		 */
		
		
		/**
		 * On HEX input blur update color
		 */
		_onBlurHEX: function () {
			var node = this.get("nodeInputHEX"),
				value = "#" + node.get("value").toUpperCase(),
				m = null;
			
			if (this.hex != value) {
				if (m = value.match(/^#([0-9ABCDEF]{3})?[0-9ABCDEF]{3}$/)) {
					//Convert from #ABC to #AABBCC
					if (!m[1]) value = "#" + value[1] + value[1] + value[2] + value[2] + value[3] + value[3];
					
					//Update value
					this.set("value", value);
				} else {
					//Error
					node.set("value", this.hex.replace('#', ''));
				}
			}
		},
		
		/**
		 * On HEX input change update color
		 */
		_onKeyHEX: function (e) {
			var node = this.get("nodeInputHEX"),
				value = "#" + node.get("value").toUpperCase();
			
			// if full color or return key pressed then apply color
			if (this.hex != value && (value.match(/^#[0-9ABCDEF]{6}$/) || e.keyCode == 13)) {
				this._onBlurHEX();
			}
		},
		
		/**
		 * On RGB input blur update color
		 */
		_onBlurRGB: function () {
			var nodeRed = this.get("nodeInputRed"),
				nodeGreen = this.get("nodeInputGreen"),
				nodeBlue = this.get("nodeInputBlue"),
				red = nodeRed.get("value"),
				green = nodeGreen.get("value"),
				blue = nodeBlue.get("value"),
				reg_num = /^[1-9][0-9]{0,2}$/;
			
			if (!red.match(reg_num) || parseInt(red) > 255) {
				nodeRed.set("value", this.rgb.red);
				red = this.rgb.red;
			}
			if (!green.match(reg_num) || parseInt(green) > 255) {
				nodeGreen.set("value", this.rgb.green);
				green = this.rgb.green;
			}
			if (!blue.match(reg_num) || parseInt(blue) > 255) {
				nodeBlue.set("value", this.rgb.blue);
				blue = this.rgb.blue;
			}
			
			red = parseInt(red, 10);
			green = parseInt(green, 10);
			blue = parseInt(blue, 10);
			
			if (this.rgb.red != red || this.rgb.green != green || this.rgb.blue != blue) {
				this.setRGB(red, green, blue);
				this.set("value", this.hex);
			}
		},
		
		/**
		 * On RGB input change update color
		 */
		_onKeyRGB: function (e) {
			var nodeRed = this.get("nodeInputRed"),
				nodeGreen = this.get("nodeInputGreen"),
				nodeBlue = this.get("nodeInputBlue"),
				red = nodeRed.get("value"),
				green = nodeGreen.get("value"),
				blue = nodeBlue.get("value"),
				reg_num = /^[1-9][0-9]{0,2}$/;
			
			if (red.match(reg_num) && parseInt(red) < 255 &&
				green.match(reg_num) && parseInt(green) < 255 &&
				blue.match(reg_num) && parseInt(blue) < 255) {
				
				this._onBlurRGB();
			}
		},
		
		/**
		 * On unset update color
		 * 
		 * @private
		 */
		_onUnset: function () {
			this.setRGB(255, 255, 255);
			this.set("value", "");
		},
		
		/**
		 * On preset update color
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_onPreset: function (e) {
			var target = Y.Node(e.target),
				color  = target.getAttribute("data-color");
			
			if (color) {
				this.set("value", color);
			}
		},
		
		
		/**
		 * -------------------------------- BAR MOUSE -----------------------------
		 */
		
		
		/**
		 * Mouse down on map
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_downBarCursor: function (e) {
			this.barCursorDown = true;
			this.unset = false;
			this.preset = -1;
			e.halt();
			
			var doc = Y.Node(document);
			this.barPosition = this.get("nodeBar").getY();
			
			if (this.cursorMoveEvent) this.cursorMoveEvent.detach();
			this.cursorMoveEvent = doc.on("mousemove", Supra.throttle(this._updateBarColor, 40, this));
			
			if (this.cursorUpEvent) this.cursorUpEvent.detach();
			this.cursorUpEvent = doc.on("mouseup", this._upBarCursor, this);
			
			this._showShim();
		},
		
		/**
		 * Mouse up on bar
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_upBarCursor: function (e) {
			if (this.barCursorDown) {
				this._updateBarColor(e);
			}
			
			this.barCursorDown = false;
			
			//Save HSB
			var hsb = this.hsb;
			
			this.uiFrozen = true;
			this.set("value", this.hex);
			this.uiFrozen = false;
			
			this.hsb = hsb;
			
			if (this.cursorUpEvent) {
				this.cursorUpEvent.detach();
				this.cursorUpEvent = null;
			}
			if (this.cursorMoveEvent) {
				this.cursorMoveEvent.detach();
				this.cursorMoveEvent = null;
			}
			
			this._hideShim();
		},
		
		/**
		 * Update color based on cursor position
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_updateBarColor: function (e) {
			var y = Math.min(110, Math.max(0, e.pageY - this.barPosition)),
				h = ~~(359 - (y / 110) * 359),
				node = this.get("nodeBarHandle");
			
			this.setHue(h);
			
			node.setStyles({
				"top": y
			});
			
			this.fire("input", {"newVal": this.hex});
		},
		
		/**
		 * Create element to prevent drag stopping when over iframe
		 * 
		 * @private
		 */
		_showShim: function () {
			var shim = this.get("nodeShim");
			if (!shim) {
				shim = Y.Node.create("<div></div>");
				shim.setStyles({
					"position": "absolute",
					"z-index": 1,
					"top": 0,
					"right": 0,
					"bottom": 0,
					"left": 0,
					"background": "#fff",
					"opacity": 0,
					"cursor": "none"
				});
				this.set("nodeShim", shim);
			}
			
			shim.appendTo(document.body);
		},
		
		/**
		 * Hide shim node
		 * 
		 * @private
		 */
		_hideShim: function () {
			var shim = this.get("nodeShim");
			if (shim) {
				shim.remove();
			}
		},
		
		
		/**
		 * -------------------------------- MAP MOUSE -----------------------------
		 */
		
		
		/**
		 * Show map cursor
		 * 
		 * @private
		 */
		_showMapCursor: function () {
			this.get("nodeMapCursor").removeClass("hidden");
			this.mapPosition = this.get("nodeMap").getXY();
		},
		
		/**
		 * Hide map cursor
		 * 
		 * @private
		 */
		_hideMapCursor: function () {
			this.get("nodeMapCursor").addClass("hidden");
		},
		
		/**
		 * Move map cursor to the mouse position
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_moveMapCursor: function (e) {
			if (!this.mapPosition) return;
			
			var x = Math.min(110, Math.max(0, e.pageX - this.mapPosition[0])),
				y = Math.min(110, Math.max(0, e.pageY - this.mapPosition[1])),
				dark = ((x + y) < 55),
				node = this.get("nodeMapCursor");
			
			if (dark != this.mapCursorDark) {
				this.mapCursorDark = dark;
				node.toggleClass("light", !dark);
			}
			
			node.setStyles({
				"left": x,
				"top": y
			});
		},
		
		/**
		 * Mouse down on map
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_downMapCursor: function (e) {
			this.mapCursorDown = true;
			this.unset = false;
			this.preset = -1;
			e.halt();
			
			var doc = Y.Node(document);
			
			if (this.cursorMoveEvent) this.cursorMoveEvent.detach();
			this.cursorMoveEvent = doc.on("mousemove", Supra.throttle(this._updateMapColor, 40, this));
			
			if (this.cursorUpEvent) this.cursorUpEvent.detach();
			this.cursorUpEvent = doc.on("mouseup", this._upMapCursor, this);
			
			this._showShim();
		},
		
		/**
		 * Mouse down on map
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_upMapCursor: function (e) {
			if (this.mapCursorDown) {
				this._updateMapColor(e);
			}
			
			this.mapCursorDown = false;
			
			//Save HSB
			var hsb = this.hsb;
			
			this.uiFrozen = true;
			this.set("value", this.hex);
			this.uiFrozen = false;
			
			//Restore HSB, because changing hex will invalidate HSB
			this.hsb = hsb;
			
			if (this.cursorUpEvent) {
				this.cursorUpEvent.detach();
				this.cursorUpEvent = null;
			}
			if (this.cursorMoveEvent) {
				this.cursorMoveEvent.detach();
				this.cursorMoveEvent = null;
			}
			
			this._hideShim();
		},
		
		/**
		 * Update color based on cursor position
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_updateMapColor: function (e) {
			if (!this.mapPosition) return;
			
			var x = Math.min(110, Math.max(0, e.pageX - this.mapPosition[0])),
				y = Math.min(110, Math.max(0, e.pageY - this.mapPosition[1])),
				dark = (x + y) < 55,
				
				s = x / 1.1,
				b = 100 - y / 1.1,
				
				node = this.get("nodeMapHandle");
			
			this.setSaturationBrightness(s, b);
			
			node.setStyles({
				"left": x,
				"top": y
			});
			
			if (dark != this.mapHandleDark) {
				node.toggleClass("light", !dark);
				this.mapHandleDark = dark;
			}
			
			this.fire("input", {"newVal": this.hex});
		},
		
		
		/**
		 * -------------------------------- SETTERS -----------------------------
		 */
		
		/**
		 * Set HSB colors hue component
		 * 
		 * @param {Number} hue Hue component
		 */
		setHue: function (hue) {
			this.hsb.hue = hue;
			this.rgb = Color.convert.HSBtoRGB(this.hsb);
			this.hex = Color.convert.RGBtoHEX(this.rgb);
			
			//HUE is set using a bar
			this.syncUIMap();
			this.syncUIRGB();
			this.syncUIHEX();
			this.syncUIPreview();
		},
		
		/**
		 * Set HSB colors saturation component
		 * 
		 * @param {Number} saturation Saturation component
		 */
		setSaturation: function (saturation) {
			this.hsb.saturation = saturation;
			this.rgb = Color.convert.HSBtoRGB(this.hsb);
			this.hex = Color.convert.RGBtoHEX(this.rgb);
			
			//Saturation is set using a map
			this.syncUIRGB();
			this.syncUIHEX();
			this.syncUIPreview();
		},
		
		/**
		 * Set HSB colors brightness component
		 * 
		 * @param {Number} brightness Brightness component
		 */
		setBrightness: function (brightness) {
			this.hsb.brightness = brightness;
			this.rgb = Color.convert.HSBtoRGB(this.hsb);
			this.hex = Color.convert.RGBtoHEX(this.rgb);
			
			//Brightness is set using a map
			this.syncUIRGB();
			this.syncUIHEX();
			this.syncUIPreview();
		},
		
		/**
		 * Set HSB colors saturation component
		 * 
		 * @param {Number} saturation Saturation component
		 * @param {Number} brightness Brightness component
		 */
		setSaturationBrightness: function (saturation, brightness) {
			this.hsb.saturation = saturation;
			this.hsb.brightness = brightness;
			this.rgb = Color.convert.HSBtoRGB(this.hsb);
			this.hex = Color.convert.RGBtoHEX(this.rgb);
			
			//Saturation and brightness is set using a map
			this.syncUIRGB();
			this.syncUIHEX();
			this.syncUIPreview();
		},
		
		/**
		 * Set RGB colors red component
		 * 
		 * @param {Number} red Red component
		 * @param {Number} green Green component
		 * @param {Number} blue Blue component
		 */
		setRGB: function (red, green, blue) {
			this.rgb.red = red;
			this.rgb.green = green;
			this.rgb.blue = blue;
			
			this.hsb = Color.convert.RGBtoHSB(this.rgb);
			this.hex = Color.convert.RGBtoHEX(this.rgb);
		},
		
		/**
		 * Set HEX color
		 * 
		 * @param {String} hex HEX color
		 */
		setHEX: function (hex) {
			this.hex = hex;
			this.rgb = Color.convert.HEXtoRGB(hex);
			this.hsb = Color.convert.RGBtoHSB(this.rgb);
			
			//HEX is set using an input
			this.syncUIMap();
			this.syncUIBar();
			this.syncUIRGB();
			this.syncUIPreview();
		},
		
		
		/**
		 * Returns value as RGB
		 * 
		 * @return Object with red, green and blue keys
		 * @type {Object}
		 */
		getValueAsRGB: function () {
			return this.rgb;
		},
		
		
		/**
		 * Returns value as HEX
		 * 
		 * @return String representing color in HEX format
		 * @type {String}
		 */
		getValueAsHEX: function () {
			return this.hex;
		},
		
		
		/**
		 * ---------------------------- ATTRIBUTES -------------------------
		 */
		
		
		/**
		 * Value attribute setter
		 * 
		 * @param {String} value New value
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setValue: function (value) {
			//Handle transparent CSS value, this is not a valid color
			if (value == "transparent") value = "";
			
			var fixed = (value || "#000000").toUpperCase();
			
			this.rgb = Color.parse(fixed) || {'red': 0, 'green': 0, 'blue': 0};
			this.hsb = Color.convert.RGBtoHSB(this.rgb);
			this.hex = Color.convert.RGBtoHEX(this.rgb);
			
			if (this.get("allowUnset") && !value) {
				fixed = "";
				this.unset = true;
			} else {
				fixed = this.hex;
				this.unset = false;
			}
			
			// Check if any preset is choosen
			this.preset = -1;
			
			var presets = this.get("presets"),
				hex     = this.hex,
				i       = 0,
				ii      = 0;
			
			if (presets && hex) {
				for (ii=presets.length; i<ii; i++) {
					if (presets[i].toUpperCase() == hex) {
						this.preset = i; break;
					}
				}
			}
			
			//Super
			Input.superclass._setValue.apply(this, [fixed]);
			
			//Update UI
			this.syncUI();
			
			return fixed;
		},
		
		/**
		 * After value change trigger event
		 * @param {Object} evt
		 */
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		}
		
	});
	
	Supra.Input.Color = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});