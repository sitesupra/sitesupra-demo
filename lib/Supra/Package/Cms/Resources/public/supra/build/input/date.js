YUI.add("supra.input-date", function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Date picker input
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = "input-date";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		// If set, then new slide with calendar will be added
		"slideshow": {
			"value": null
		},
		
		// Maximal selectable date
		"maxDate": {
			"value": null,
			"setter": "_setMaxDate"
		},
		
		// Minimal selectable date
		"minDate": {
			"value": null,
			"setter": "_setMinDate"
		},
		
		// Allow to set also time
		"time": {
			"value": true
		},
		
		// Label for button when no date is selected
		"labelSet": {
			"value": "Select a date"
		},
		
		// Label for button to clear selected date
		"labelClear": {
			"value": "Clear all"
		}
	};
	
	Input.HTML_PARSER = {
		// data-min-date attribute for minDate
		"minDate": function (srcNode) {
			var date = srcNode.getAttribute("data-min-date");
			if (date) return date;
		},
		
		// data-max-date attribute for maxDate
		"maxDate": function (srcNode) {
			var date = srcNode.getAttribute("data-max-date");
			if (date) return date;
		},
		
		// Label when no date is selected
		"labelSet": function (srcNode) {
			var label = srcNode.getAttribute("data-label-set");
			if (label) return label;
		},
		
		// Label to clear selection
		"labelClear": function (srcNode) {
			var label = srcNode.getAttribute("data-label-clear");
			if (label) return label;
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: "<input type=\"hidden\" value=\"\" />",
		LABEL_TEMPLATE: "<label></label>",
		
		
		/**
		 * Widgets list
		 * @private
		 */
		widgets: {},
		
		/**
		 * Value will not be changed while silent
		 * @private
		 */
		silent: false,
		
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			this.widgets = {
				"button": null,
				
				"calendar": null,
				
				"time": null,
				"hours": null,
				"minutes": null,
				"clear": null,
				
				"slide": null,
				"slideId": null,
				
				"popup": null
			};
			
			// Try finding slideshow which is above this node in DOM, but still inside form
			var slideshow = this.get("slideshow");
			if (!slideshow) {
				slideshow = this.get("boundingBox").closest(".su-slideshow");
				if (slideshow) {
					var form = slideshow.closest(".yui3-form");
					if (form) {
						slideshow = Y.Widget.getByNode(slideshow);
						this.set("slideshow", slideshow);
					}
				}
			}
			
			//Create button widget
			var contentBox = this.get("contentBox");
			var button = this.widgets.button = new Supra.Button({
				"style": "small-gray",
				"label": ""
			});
			
			button.addClass("button-section");
			button.on("click", this.openCalendar, this);
			
			if (contentBox.test("input")) {
				contentBox.addClass("hidden");
				button.render(this.get("boundingBox"));
			} else {
				button.render(contentBox);
			}
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			//Handle value attribute change
			this.after('valueChange', this._afterValueChange, this);
		},
		
		syncUI: function () {
			if (this.widgets.button) {
				var value = this.get("inputNode").get("value"),
					date = value ? Y.DataType.Date.reformat(value, this.getFormat("in"), this.getFormat("out")) : "";
				
				if (!date) {
					date = this.get("labelSet");
				}
			
				this.widgets.button.set("label", date);
			}
		},
		
		destructor: function () {
			var widgets = this.widgets,
				key = null;
			
			for(key in widgets) {
				if (widgets[key] && widgets[key].destroy) {
					widgets[key].destroy();
				}
			}
			
			delete(this.widgets);
		},
		
		
		/**
		 * ---------------------------- API -------------------------
		 */
		
		
		/**
		 * Open calendar in popup or in slide
		 */
		openCalendar: function () {
			this.silent = true;
			
			if (this.get("slideshow")) {
				this.openCalendarSlide();
			} else {
				this.openCalendarPopup();
			}
			
			this.widgets.calendar.set("date", this.get("value"));
			this.setCalendarTime(this.get("value"));
			this.silent = false;
		},
		
		/**
		 * Close calendar slide or popup
		 */
		closeCalendar: function () {
			if (this.get("slideshow")) {
				this.closeCalendarSlide();
			} else {
				this.closeCalendarPopup();
			}
		},
		
		/**
		 * Reset value
		 */
		resetCalendar: function () {
			this.silent = true;
			this.closeCalendar(true);
			this.silent = false;
			this.set("value", "");
		},
		
		
		/**
		 * ---------------------------- SLIDESHOW -------------------------
		 */
		
		
		/**
		 * Open calendar slide
		 * 
		 * @private
		 */
		openCalendarSlide: function () {
			var slideshow = this.get("slideshow");
			if (slideshow) {
				if (!this.widgets.slide) {
					this.renderCalendarSlide();
				}
				
				slideshow.set("slide", this.widgets.slideId);
			}
		},
		
		/**
		 * Close calendar slide
		 * 
		 * @private
		 */
		closeCalendarSlide: function () {
			var slideshow = this.get("slideshow");
			if (slideshow) {
				slideshow.scrollBack();
			}
		},
		
		/**
		 * Render calendar in a slide
		 * 
		 * @private
		 */
		renderCalendarSlide: function () {
			//Create slide
			this.widgets.slideId = Y.guid();
			
			var slideshow = this.get("slideshow"),
				slide = this.widgets.slide = slideshow.addSlide({
					'id': this.widgets.slideId,
					'title': this.get('label')
				});
			
			//If slideshow is in sidebar we want an icon and title changed
			slide.setAttribute("data-icon", "/public/cms/supra/img/sidebar/icons/settings-schedule.png");
			slide.setAttribute("data-title", this.get("label"));
			
			//Create calendar
			var calendar = this.renderCalendar(slide.one(".su-slide-content, .su-multiview-slide-content"));
			
			//On slide change update value
			slideshow.on("slideChange", this.handleSlideChange, this);
		},
		
		/**
		 * When slide is hidden set input value
		 * 
		 * @private
		 */
		handleSlideChange: function (evt) {
			var slide		= evt.prevVal,
				calendar	= this.widgets.calendar,
				date		= null;
			
			if (calendar && slide == this.widgets.slideId) {
				var date = this.getCalendarDate();
				if (date != this.get("value")) {
					this.set("value", date);
				}
			}
		},
		
		
		/**
		 * ---------------------------- POPUP -------------------------
		 */
		
		
		/**
		 * Open calendar popup
		 * 
		 * @private
		 */
		openCalendarPopup: function () {
			if (!this.widgets.popup) {
				this.renderCalendarPopup();
			}
			
			this.widgets.popup.fadeIn();
		},
		
		/**
		 * Close calendar popup
		 * 
		 * @private
		 */
		closeCalendarPopup: function () {
			if (this.widgets.popup) {
				this.widgets.popup.hide();
			}
		},
		
		/**
		 * Render calendar in a popup
		 * 
		 * @private
		 */
		renderCalendarPopup: function () {
			var popup,
				calendar;
			
			popup = new Supra.Panel({
				'bodyContent': '<div></div>',
				'arrowVisible': true,
				'alignTarget': this.widgets.button.get('contentBox'),
				'alignPosition': 'T',
				'width': 220,
				'zIndex': 10,
				'autoClose': true
			});
			
			popup.render(this.get('boundingBox'));
			//popup.set('useMask', true);
			
			//Create calendar
			calendar = this.renderCalendar(popup.get("bodyContent").item(0));
			
			calendar.after('dateChange', this.afterCalendarPopupChange, this);
			
			if (this.get("time")) {
				this.widgets.hours.after('valueChange', this.afterCalendarPopupChange, this);
				this.widgets.minutes.after('valueChange', this.afterCalendarPopupChange, this);
			}
			
			this.widgets.popup = popup;
		},
		
		/**
		 * After calendar popup change update input valie
		 */
		afterCalendarPopupChange: function (e) {
			if (this.silent || (e.type == "calendar:dateChange" && e.newVal == e.prevVal)) return;
			
			var date = this.getCalendarDate();
			if (!this.compareDates(date, this.get("value"))) {
				this.set("value", date);
			}
		},
		
		
		/**
		 * ---------------------------- CALENDAR -------------------------
		 */
		
		
		/**
		 * Render calendar widget
		 * 
		 * @private
		 */
		renderCalendar: function (container) {
			var calendar = this.widgets.calendar = new Supra.Calendar({
				"date": this.get("value"),
				"minDate": this.get("minDate"),
				"maxDate": this.get("maxDate")
			});
			
			
			calendar.render(container);
			
			if (this.get("time")) {
				var html = '<div class="yui3-input-date-time">\
								<input type="text" name="hours" value="00" data-value-mask="^([0-1][0-9]|2[0-4]|[0-9])$" maxlength="2" />\
								<span>:</span>\
								<input type="text" name="minutes" value="00" data-value-mask="^([0-5][0-9]|60|[0-9])$" maxlength="2" />\
								\
								<br />\
								\
								<button type="button" data-style="small"><p>' + this.get("labelClear") + '</p></button>\
							</div>';
				
				var node	= this.widgets.time    = Y.Node.create(html),
					hours	= this.widgets.hours   = new Supra.Input.Text({"srcNode": node.one("input[name='hours']")}),
					minutes	= this.widgets.minutes = new Supra.Input.Text({"srcNode": node.one("input[name='minutes']")});
				
				container.append(node);
				
				hours.render();
				minutes.render();
				
			} else {
				var html = '<div class="yui3-input-date-time">\
								<button type="button" data-style="small"><p>' + this.get("labelClear") + '</p></button>\
							</div>';
				
				var node	= this.widgets.time    = Y.Node.create(html);
				container.append(node);
			}
			
			//Clear button
			var clear = this.widgets.clear = new Supra.Button({
				"srcNode": node.one("button"),
				"style": "small",
				"label": this.get("labelClear")
			});
			
			clear.render();
			clear.on("click", this.resetCalendar, this);
			
			return calendar;
		},
		
		/**
		 * Set calendar time
		 * 
		 * @private
		 */
		setCalendarTime: function (date) {
			var time = (date ? date.match(/(\d{1,2}):(\d{1,2})/) : null) || ['', 0, 0],
				hours = parseInt(time[1], 10),
				minutes = parseInt(time[2], 10);
			
			this.widgets.hours.set('value', hours < 10 ? "0" + hours : hours);
			this.widgets.minutes.set('value', minutes < 10 ? "0" + minutes : minutes);
		},
		
		/**
		 * Returns calendar date and time
		 * This is not inputs value
		 * 
		 * @private
		 */
		getCalendarDate: function () {
			var calendar = this.widgets.calendar,
				date = "";
			
			if (calendar) {
				date = new Date(calendar.get("rawDate"));
				
				if (this.get("time")) {
					var hours = parseInt(this.widgets.hours.get("value"), 10) || 0,
						minutes = parseInt(this.widgets.minutes.get("value"), 10) || 0;
					
					date.setHours(hours);
					date.setMinutes(minutes);
				}
				
				date = Y.DataType.Date.reformat(date, "raw", this.getFormat("in"));
				return date || "";
			} else {
				return this.get("value");
			}
		},
		
		
		/**
		 * ---------------------------- DATE AND TIME -------------------------
		 */
		
		
		/**
		 * Returns format
		 * 
		 * @param {String} prefix Format prefix
		 * @return Date format
		 * @private
		 */
		getFormat: function (prefix) {
			return prefix + (this.get("time") ? "_datetime_short" : "_date");
		},
		
		/**
		 * Compare two date strings
		 * 
		 * @param {String} a Date one
		 * @param {String} b Date two
		 * @returns {Boolean} True if dates are equal, otherwise false
		 */
		compareDates: function (a, b) {
			//YYYY-MM-DD HH:MM
			var len_min = Math.min(a.length, b.length),
				len_max = Math.max(a.length, b.length);
			
			if (!len_min && len_max) return false;
			if (!len_min && !len_max) return true;
			
			a = a.substr(0, len_min);
			b = b.substr(0, len_min);
			
			return a == b;
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
			if (typeof value === 'object' && value && 'date' in value) {
				// Object {date: ..., timzone: ..., timezone_type: ...}
				value = value.date;
			}
			
			if (this.widgets.calendar) {
				this.widgets.calendar.set("date", value);
				
				if (this.get("time")) {
					this.setCalendarTime(value); 
				}
				
				//For validation we use calendar to get value
				value = value ? this.getCalendarDate() : "";
			}
			
			//Super
			Input.superclass._setValue.apply(this, [value]);
			
			//Update UI
			this.syncUI();
			
			return value;
		},
		
		/**
		 * Set min-date
		 * 
		 * @param {Date} date
		 * @return Date
		 * @type {Date}
		 * @private
		 */
		_setMinDate: function (minDate) {
			if (this.widgets.calendar) {
				this.widgets.calendar.set("minDate", minDate);
				return this.widgets.calendar.get("minDate");
			}
			
			return minDate;
		},
		
		/**
		 * Set max-date
		 * 
		 * @param {Date} date
		 * @return Date
		 * @type {Date}
		 * @private
		 */
		_setMaxDate: function (maxDate) {
			if (this.widgets.calendar) {
				this.widgets.calendar.set("maxDate", maxDate);
				return this.widgets.calendar.get("maxDate");
			}
			
			return maxDate;
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
	
	Supra.Input.Date = Input;
	
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "supra.calendar"]});