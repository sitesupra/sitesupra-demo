/**
 * Dropdown widget
 * 
 * Replaces standard <select> element with custom dropdown
 * Supports <option> and <optgroup> elements
 * If content exceed maxHeight option value, then scrollbar is shown
 * 
 * @param {Object} node SELECT element
 * @param {Object} options Dropdown widget options
 * @version 1.1.0
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'plugins/widgets/scrollbar'], function ($) {
            return factory($);
        });
	} else if (typeof module !== "undefined" && module.exports) {
		// CommonJS
		module.exports = factory(jQuery);
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	"use strict";
	
	//Save if this is old IE browser or not
	var msieOld = false;
	
	if ($.browser) {
		msieOld = ($.browser.msie && parseInt($.browser.version) <= 9);
	} else {
		var userAgent = navigator.userAgent.toLowerCase();
		// Test if the browser is IE and check the version number is lower than 9
		if (/msie/.test(userAgent) && parseFloat((userAgent.match(/.*(?:rv|ie)[\/: ](.+?)([ \);]|$)/) || [])[1]) < 9) {
			msieOld = true;
		}
	}
	
	function DropDown (node, _options) {
		var attr_options = $.extend({}, node.data()),
			default_options = this.DEFAULT_OPTIONS,
			key = null;
		
		for (key in attr_options) {
			// Remove option if it's not in defaults
			if (!(key in default_options)) {
				delete(attr_options[key]);
			}
		}
		
		var options = this.options = $.extend({}, default_options, attr_options, _options || {});
		
		this.nodeInput = null;
		this.values = [];
		this.valuesObject = {};
		this.itemsOutput = [];
		
		//Update class names
		//replace %c in classnames with options 'classname' value
		var classnames = ['classnameFocus', 'classnameTouch', 'classnameOpen', 'classnameDisabled', 'classnameReadonly', 'classnameItem', 'classnamePopup', 'classnamePopupScrollable', 'classnamePopupList', 'classnamePopupItem', 'classnamePopupGroup', 'classnameSelectedItem', 'classnameActiveItem'];
		for(var i=0,ii=classnames.length; i<ii; i++) {
			options[classnames[i]] = options[classnames[i]].split('%c').join(options.classname);
		}
		
		this._renderUI($(node));
		this._bindUI();
	}
	
	DropDown.prototype = {
		/**
		 * Default options for class
		 */
		DEFAULT_OPTIONS: {
			'classname': 'select',
			'classnameTouch': '%c-touch',
			'classnameFocus': '%c-focus',
			'classnameOpen': '%c-open',
			'classnameDisabled': '%c-disabled',
			'classnameReadonly': '%c-readonly',
			'classnameItem': '%c-item',
			'classnamePopup': '%c-popup',
			'classnamePopupScrollable': '%c-scrollable',
			'classnamePopupList': '%c-list',
			'classnamePopupItem': '%c-list-item',
			'classnamePopupGroup': '%c-list-group',
			'classnameSelectedItem': '%c-list-item-selected',
			'classnameActiveItem': '%c-list-item-active',
			
			'classnameInput': 'invisible',
			'classnameHidden': 'hidden',
			
			'maxHeight': 260,
			'allowScrollbar': true,
			'scrollbarOptions': null,				//Options for scrollbar
			'showEmptyValue': false,				//Show empty value in popup
			
			'preventDefault': true,					//Prevent default behaviour 
			
			'renderer': null						//HTML Renderer
		},
		
		/**
		 * Input element (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeInput: null,
		
		/**
		 * Dropdown element (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeDropdown: null,
		
		/**
		 * Selected item element (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeItem: null,
		
		/**
		 * Popup element (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodePopup: null,
		
		/**
		 * List element (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeList: null,
		
		/**
		 * Current value
		 * @type {String}
		 * @private
		 */
		value: '',
		
		/**
		 * Current value index / selected index
		 * @type {Number}
		 * @private
		 */
		index: 0,
		
		/**
		 * All dropdown values
		 * @type {Array}
		 * @private
		 */
		values: [],
		
		/**
		 * All dropdown values indexed
		 * @type {Object}
		 * @private
		 */
		valuesObject: {},
		
		/**
		 * All dropdown items: options and option groups
		 */
		itemsOutput: [],
		
		/**
		 * Focus state
		 * @type {Boolean}
		 * @private
		 */
		hasFocus: false,
		
		/**
		 * Events won't be fired in silent mode
		 * @type {Boolean}
		 * @private
		 */
		silent: false,
		
		/**
		 * Popup visibility state
		 * @type {Boolean}
		 * @private
		 */
		popupVisible: false,
		
		/**
		 * Focused popup item index
		 * @type {Number}
		 * @private
		 */
		popupIndex: -1,
		
		/**
		 * Touch event activated popup
		 * @type {Boolean}
		 * @private
		 */
		touchActivated: false,
		
		/**
		 * Distance content was scrolled while in touch, this is to prevent
		 * item selecting after scroll
		 * @type {Boolean}
		 * @private
		 */
		touchScrolled: false,
		
		/**
		 * User clicked on item and preventDefault is false,
		 * this is used to make sure blur doesn't close popup
		 * @type {Boolean}
		 * @private
		 */
		clickActivatedApplyDefault: false,
		
		/**
		 * Key stroke line
		 * @type {String}
		 * @private
		 */
		keystrokeLine: '',
		
		/**
		 * Key stroke timer
		 * @type {Number}
		 * @private
		 */
		keystrokeTimer: null,
		
		/**
		 * Timer callback which clears keystroke
		 * @type {Function}
		 * @private
		 */
		keystrokeCallback: null,
		
		/**
		 * Disabled state
		 * @type {Boolean}
		 * @private
		 */
		disabled: false,
		
		/**
		 * Read only state
		 * @type {Boolean}
		 * @private
		 */
		readonly: false,
		
		/**
		 * Scrollbar instance
		 * @type {Object}
		 * @private
		 */
		scrollbar: null,
		
		/**
		 * Returns index by value
		 * 
		 * @param {String} value
		 * @return Index of value
		 * @type {Number}
		 */
		getIndexByValue: function (value) {
			var values = this.values;
			for(var i=0,ii=values.length; i<ii; i++) {
				if (values[i] == value) return i;
			}
			return -1;
		},
		
		/**
		 * Returns dropdown value
		 * 
		 * @return Selected value
		 * @type {String}
		 */
		getValue: function () {
			return this.value;
		},
		
		/**
		 * Set dropdown value
		 * 
		 * @param {String} value
		 */
		setValue: function (value, skipScroll) {
			if (this.value != value && value in this.valuesObject) {
				this.silent = true;
				
				this.value = value;
				var index = this.index = this.getIndexByValue(value);
				
				if (this.nodeInput) {
					this.nodeInput.val(value).change();
				}
				
				this.nodeItem.html(this.render('item'));
				
				this._setPopupIndex(index, skipScroll, true);
				
				this.silent = false;
			}
			
			return this;
		},
		
		/**
		 * Returns selected item index
		 * 
		 * @return Selected index
		 * @type {Number}
		 */
		getIndex: function () {
			return this.index;
		},
		
		/**
		 * Set dropdown index
		 * 
		 * @param {Number} index
		 */
		setIndex: function (index) {
			if (this.index != index && index >=0 && index < this.values.length) {
				this.silent = true;
				
				var value = this.value = this.values[index];
				this.index = index;
				
				if (this.nodeInput) {
					this.nodeInput.val(value).change();
				}
				
				this.nodeItem.html(this.render('item'));
				
				this._setPopupIndex(index, false, true);
				
				this.silent = false;
			}
			
			return this;
		},
		
		/**
		 * Enable/disable dropdown
		 * 
		 * @param {Boolean} disabled
		 */
		setDisabled: function (disabled) {
			if (this.disabled != disabled) {
				this.disabled = !!disabled;
				
				if (disabled) {
					this._hidePopup();
					this.nodeDropdown.removeAttr('tabIndex');
					this.nodeDropdown.addClass(this.options.classnameDisabled);
					
					if (this.nodeInput) {
						this.nodeInput.attr('disabled', 'disabled');
					}
				} else {
					this.nodeDropdown.attr('tabIndex', 0);
					this.nodeDropdown.removeClass(this.options.classnameDisabled);
					
					if (this.nodeInput) {
						this.nodeInput.removeAttr('disabled');
					}
				}
			}
			
			return this;
		},
		
		/**
		 * Returns if dropdown is disabled
		 * 
		 * @return True if dropdown is disabled, otherwise false
		 * @type {Boolean}
		 */
		getDisabled: function () {
			return this.disabled;
		},
		
		/**
		 * Set readonly/normal state
		 * 
		 * @param {Boolean} readonly
		 */
		setReadOnly: function (readonly) {
			if (this.readonly != readonly) {
				this.readonly = !!readonly;
				
				if (readonly) {
					this._hidePopup();
					this.nodeDropdown.addClass(this.options.classnameReadonly);
					
					if (this.nodeInput) {
						this.nodeInput.attr('readonly', 'readonly');
					}
				} else {
					this.nodeDropdown.removeClass(this.options.classnameReadonly);
					
					if (this.nodeInput) {
						this.nodeInput.removeAttr('readonly');
					}
				}
			}
			
			return this;
		},
		
		/**
		 * Returns if dropdown is read-only
		 * 
		 * @return True if dropdown is read-only, otherwise false
		 * @type {Boolean}
		 */
		getReadOnly: function () {
			return this.readonly;
		},
		
		/**
		 * Returns dropdown focus state
		 * 
		 * @return True if dropdown is focused, otherwise false
		 * @type {Boolean}
		 */
		getHasFocus: function () {
			return this.hasFocus;
		},
		
		/**
		 * Update values, readonly and disabled style
		 */
		update: function () {
			if (!this.nodeInput) {
				// Was created from HTML, not SELECT element. Nothing to update 
				return;
			}
			
			var options = this.nodeInput.get(0).options,
				optionGroups = this.nodeInput.find('optgroup'),
				val = '',
				showEmptyValue = this.options.showEmptyValue,
				values = [],
				valuesObject = {},
				curValuesObject = this.valuesObject,
				itemsOutput = [],
				curItemsOutput = this.itemsOutput,
				hasChanges = false;
			
			//Traverse options and collect values
			function traverseOptions () {
				for(var i=0,ii=options.length; i<ii; i++) {
					val = options[i].value;
					
					if (val || showEmptyValue) values.push(options[i].value);
					if (val || showEmptyValue) itemsOutput.push({'type': 'item', 'value': options[i].value});
					valuesObject[val] = options[i].text;
					
					if (!(val in curValuesObject) || (curValuesObject[val] != options[i].text)) {
						hasChanges = true;
					}
				}
								
				if (curItemsOutput.length !== itemsOutput.length) {
					hasChanges = true;
				}
			}
			
			//Traverse groups
			if (optionGroups.length) {
				for(var i=0,ii=optionGroups.length; i<ii; i++) {
					group = optionGroups.eq(i);
					label = group.attr('label');
					options = group.find('option');
					
					itemsOutput.push({'type': 'group', 'label': label});
					traverseOptions.call(this);
					
					if (curItemsOutput.length < i || curItemsOutput[i].type != 'group' || curItemsOutput[i].label != label) {
						hasChanges = true;
					}
				}
			} else {
				traverseOptions.call(this);
			}
			
			//If there are any changes in groups, values or labels then redraw list
			if (hasChanges) {
				var value = this.nodeInput.val();
				this.values = values;
				this.valuesObject = valuesObject;
				this.itemsOutput = itemsOutput;
				
				//Redraw list and value, event if it didn't changed, because
				//label may have changed
				this.nodeList.html(this.render('popupList'));
				
				this.value = null;
				this.setValue(value);
			}
			
			//Check input 'disabled' and 'readonly' attributes and apply to DropDown
			var disabled = this.nodeInput.attr('disabled'),
				readonly = this.nodeInput.attr('readonly');
			
			disabled = !(disabled === false || disabled === undefined);
			readonly = !(readonly === false || readonly === undefined);
			
			this.setDisabled(disabled);
			this.setReadOnly(readonly);
		},

		/**
		 * Escape html special characters or quotes in string
		 * 
		 * @param {String} str String to escape
		 * @param {String} type Type: 'html' (default) or 'quotes'
		 * @private
		 */
		 escape: function (str, type) {
			str = String(str || '');
			
			if (type == 'quotes') {
				return str.replace(/\\/g, '\\\\')
						  .replace(/"/g, '\\"')
						  .replace(/'/g, "\\'");
			} else {
				return str.replace(/&/g, '&amp;')
						  .replace(/"/g, '&quot;')
				  		  .replace(/</g, '&lt;')
						  .replace(/>/g, '&gt;');
			}
		},

		/**
		 * Unescape html special characters or quotes in string
		 * 
		 * @param {String} str String to escape
		 * @param {String} type Type: 'html' (default) or 'quotes'
		 * @private
		 */
		 unescape: function (str, type) {
			str = String(str || '');
			
			if (type == 'quotes') {
				return str.replace(/\\"/g, '"')
						  .replace(/\\'/g, "'")
						  .replace(/\\\\/g, '\\');
			} else {
				return str.replace(/&lt;/g, '<')
						  .replace(/&gt;/g, '>')
						  .replace(/&guot;/g, '"')
						  .replace(/&amp;/g, '&');
			}
		},
		
		/**
		 * Convert classname string into selector
		 * 
		 * @param {String} classname Classname
		 * @returns {String} CSS selector
		 * @private
		 */
		classnameToSelector: function (classname) {
			return '.' + classname.split(' ').join('.');
		},
		
		/**
		 * Render specific block
		 * 
		 * @param {String} block
		 * @private
		 */
		render: function (block) {
			var renderer = this.options.renderer;
			
			if (block in renderer) {
				var args = [this.options];
				
				if (block == 'item') {
					args = [this.options, this.value, this.valuesObject[this.value]];
				} else if (block == 'popupListItem') {
					return '';
				}
				
				return renderer[block].apply(this, args);
			}
			
			return '';
		},
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		_renderUI: function (node) {
			if (node.is('select')) {
				this.nodeInput = node;
				this._renderUIFromSelect();
			} else {
				this.nodeDropdown = node;
				this._renderUIFromHTML();
			}
		},
		
		/**
		 * Render UI from <select> element
		 * 
		 * @private
		 */
		_renderUIFromSelect: function () {
			//Get values
			var optionGroups = this.nodeInput.find('optgroup'),
				options = (optionGroups.length ? null : this.nodeInput.get(0).options),
				group = null,
				val = '',
				label = '',
				showEmptyValue = this.options.showEmptyValue,
				value,
				values = this.values,
				itemsOutput = this.itemsOutput,
				valuesObject = this.valuesObject,
				className;
			
			//Traverse options and collect values
			function traverseOptions () {
				for(var i=0,ii=options.length; i<ii; i++) {
					val = options[i].value;
					
					if (val || showEmptyValue) {
						values.push(options[i].value);
						itemsOutput.push({'type': 'item', 'value': options[i].value});
					}
					 
					valuesObject[val] = options[i].text;
				}
			}
			
			if (optionGroups.length) {
				for(var i=0,ii=optionGroups.length; i<ii; i++) {
					group = optionGroups.eq(i);
					label = group.attr('label');
					options = group.find('option');
					
					itemsOutput.push({'type': 'group', 'label': label});
					traverseOptions.call(this);
				}
			} else {
				traverseOptions.call(this);
			}
			
			value = this.value = this.nodeInput.val();
			if (!(value in valuesObject)) {
				value = this.value = values[0];
			}
			
			this.index = this.getIndexByValue(value);
			this.popupIndex = -1;
			
			//Create HTML
			var html = $(this.render('dropdown'));
				html.find(this.classnameToSelector(this.options.classnamePopupList)).html(this.render('popupList'));
			
			//Style
			className = $.trim(this.nodeInput.attr('class'));
			if (className) {
				html.addClass(className);
			}
			
			//Hide input
			this.nodeInput.addClass(this.options.classnameInput);
			
			//Custom input can be focused, so no need to "tab" into input
			this.nodeInput.attr('tabIndex', '-1');
			
			//Add dropdown
			html.insertAfter(this.nodeInput);
			
			this.nodeDropdown = html;
			this.nodePopup = html.find(this.classnameToSelector(this.options.classnamePopup));
			this.nodeItem  = html.find(this.classnameToSelector(this.options.classnameItem));
			this.nodeList = html.find(this.classnameToSelector(this.options.classnamePopupList));
			
			//Set max-height if dropdown should be scrollable
			if (this.options.maxHeight) {
				this.nodeList.css('max-height', this.options.maxHeight + 'px');
			}
			
			//Check input 'disabled' and 'readonly' attributes and apply to DropDown
			var disabled = this.nodeInput.attr('disabled'),
				readonly = this.nodeInput.attr('readonly');
				
			if (!(disabled === false || disabled === undefined)) {
				this.setDisabled(true);
			}
			if (!(readonly === false || readonly === undefined)) {
				this.setReadOnly(true);
			}
			
			// Touch scrolling supported?
			if ('ontouchstart' in document) {
				this.nodeDropdown.addClass(this.options.classnameTouch);
			}
		},
		
		/**
		 * Check existing DOM for elements
		 * 
		 * @private
		 */
		_renderUIFromHTML: function () {
			var node = this.nodeDropdown,
				input = node.prev();
			
			this.nodePopup = node.find(this.classnameToSelector(this.options.classnamePopup));
			this.nodeItem  = node.find(this.classnameToSelector(this.options.classnameItem));
			this.nodeList = node.find(this.classnameToSelector(this.options.classnamePopupList));
			
			this.nodeInput = input.size() ? input : null;
			
			//Set max-height if dropdown should be scrollable
			if (this.options.maxHeight) {
				this.nodeList.css('max-height', this.options.maxHeight + 'px');
			}
			
			//Find values
			var values = this.values = [],
				values_obj = this.valuesObject = {},
				
				value = null,
				text = null,
				
				li = this.nodePopup.find('li'),
				i  = 0,
				ii = li.size(),
				
				item;
			
			for (; i<ii; i++) {
				item = li.eq(i).find('a').eq(0);
				if (!item.size()) {
					item = li.eq(i);
				}
				
				value = this.unescape(li.eq(i).attr('data'));
				text  = this.unescape(item.text());
				values.push(value);
				values_obj[value] = text;
				
				if (li.eq(i).hasClass(this.options.classnameActiveItem)) {
					this.value = value;
					this.index = i;
					this.popupIndex = -1;
				}
			}
			
			if (!this.index) {
				this.index = 0;
				this.value = values[0];
			}
			
			//Check for disabled and readonly classnames
			if (node.hasClass(this.options.classnameDisabled)) {
				this.disabled = true;
			}
			if (node.hasClass(this.options.classnameReadonly)) {
				this.readonly = true;
			}
			
			// Touch scrolling supported?
			if ('ontouchstart' in document) {
				this.nodeDropdown.addClass(this.options.classnameTouch);
			}
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		_bindUI: function () {
			//Proxy
			//Using click to observe when blur happens for IE7 and IE8
			var _onBlurOriginal = this._onBlur;
			this._onBlur = $.proxy(function () { return _onBlurOriginal.apply(this, arguments); }, this);
			
			if (!msieOld) {
				this.nodeDropdown.blur(this._onBlur);
			}
			
			this.nodeDropdown.focus($.proxy(this._onFocus, this))
							  .keydown($.proxy(this._onKey, this));
			
			//On destroy input is not removed, so will need to unbind
			//on destroy and to do that need to save proxy function
			this._onChange = $.proxy(this._onChange, this);
			
			if (this.nodeInput) {
				this.nodeInput.change(this._onChange);
			}
			
			// Bind to touch or mouse event which evecutes before blur, other
			this.nodeDropdown.bind('touchstart', $.proxy(this._resetTouchScroll, this));
			this.nodeDropdown.bind('mousedown touchend', $.proxy(this._onClick, this));
			
			var listItemTag = this.nodeList.children().prop('tagName') || '',
				listItemInnerTag = this.nodeList.children().eq(0).children().eq(0).prop('tagName') || '',
				self = this,
				selector = listItemTag + this.classnameToSelector(this.options.classnamePopupItem) + ' ' + listItemInnerTag;
			
			this.nodeList.delegate(selector, 'mouseenter', function (e) {
				var target = $(e.target).closest(selector),
					index = self.nodeList.find(selector).index(target);
				
				self._setPopupIndex(index, true);
			});
			
			//On scroll (if scrollbar exists) update list scroll offset
			this.nodePopup.bind('scroll', $.proxy(function (evt, data) {
				if (data) {
					this.nodeList.scrollTop(~~data.value);
				}
			}, this));
			
			// Touch scrolling
			if ('ontouchstart' in document) {
				this.nodeList.bind('scroll', $.proxy(function (e) {
					var scroll = this.nodeList.scrollTop();
					
					this.touchScrolled = true;
					this.scrollbar.setValue(scroll);
				}, this));
			}
			
			this.keystrokeCallback = $.proxy(function () { this.keystrokeLine = ''; }, this);
		},
		
		/**
		 * Find item which text matches string, returns item index or -1
		 * if not found 
		 * 
		 * @private
		 * @param {String} search
		 * @return Item index
		 * @type {Number}
		 */
		_findValue: function (_search) {
			var values = this.values,
				valuesObject = this.valuesObject,
				value,
				search = _search.toUpperCase();
				
			for(var i=0,ii=values.length; i<ii; i++) {
				value = values[i];
				if (valuesObject[value].toUpperCase().indexOf(search) === 0) return i;
			}
			
			return -1;
		},
		
		/**
		 * Set popup index
		 * 
		 * @param {Number} index
		 * @param {Boolean} skipScroll Do not scroll to item if true, used by mouseenter event
		 * @param {Boolean} active Set item to active state
		 * @private
		 */
		_setPopupIndex: function (index, skipScroll, active) {
			if (index >= -1 && index < this.values.length) {
				var children = this.nodeList.find(this.classnameToSelector(this.options.classnamePopupItem)),
					childrenSelected = children.eq(index),
					classname = this.options.classnameSelectedItem;
				
				if (active) {
					children.removeClass(this.options.classnameActiveItem);
					if (index != -1) childrenSelected.addClass(this.options.classnameActiveItem);
				}
				
				if (index != this.popupIndex) {
					if (this.popupIndex != -1) {
						children.eq(this.popupIndex).removeClass(classname);
					}
					if (index != -1) {
						childrenSelected.addClass(classname);
					}
					
					this.popupIndex = index;
				}
				
				if (index > -1 && !skipScroll) this._scrollToItem(childrenSelected);
			}
			
			return this;
		},
		
		/**
		 * Scroll to item in popup
		 * @param {Object} item
		 */
		_scrollToItem: function (item) {
			if (!item.get(0)) return;
			
			var height = this.nodeList.height(),
				scroll = this.nodeList.scrollTop(),
				offset = item.get(0).offsetTop,
				itemHeight = item.height();
			
			var newScroll = Math.min(offset, scroll);
			if (offset < scroll) {
				newScroll = offset;
			} else if (offset + itemHeight > height + scroll) {
				newScroll = offset - height + itemHeight;
			}
			
			this.nodeList.scrollTop(newScroll);
			
			if (this.scrollbar) {
				this.scrollbar.setValue(newScroll);
			}
			
			this.touchScrolled = false;
		},
		
		/**
		 * Hide popup
		 * 
		 * @private
		 */
		_hidePopup: function () {
			if (this.popupVisible) {
				this.nodeDropdown.removeClass(this.options.classnameOpen);
				this.nodePopup.addClass(this.options.classnameHidden);
				this.popupVisible = false;
				this.keystrokeLine = '';
			}
			
			return this;
		},
		
		/**
		 * Show popup
		 * 
		 * @private
		 */
		_showPopup: function () {
			if (!this.disabled && !this.readonly && !this.popupVisible) {
				this.nodeDropdown.addClass(this.options.classnameOpen);
				this.nodePopup.removeClass(this.options.classnameHidden);
				this.popupVisible = true;
				this.keystrokeLine = '';
				
				if (this.options.maxHeight && this.options.allowScrollbar) {
					var elementList = this.nodeList.get(0),
						scrollHeight = -elementList.offsetHeight + elementList.scrollHeight;
					
					// Show scrollbar if there are at least 3px to scroll
					if (scrollHeight >= 3) {
						this.nodePopup.addClass(this.options.classnamePopupScrollable);
						
						if (!this.scrollbar) {
							this.scrollbar = new $.Scrollbar(this.nodePopup, $.extend(this.options.scrollbarOptions || {}, {
								'maxValue': scrollHeight,
								'value': elementList.scrollTop
							}));
						} else {
							this.scrollbar.setMax(scrollHeight);
							this.scrollbar.show();
						}
					} else {
						if (this.scrollbar) {
							this.scrollbar.hide();
						}
						this.nodePopup.removeClass(this.options.classnamePopupScrollable);
					}
				}
				
				this._setPopupIndex(this.index, false, true);
				
				if (msieOld) {
					//If user clicks outside dropdown, consider it's a blur event
					$(document).on('mousedown', this._onBlur);
				}
			}
			
			return this;
		},
		
		/**
		 * Handle dropdown click
		 * 
		 * @param {Event} event Event object
		 * @private
		 */
		_onClick: function (event) {
			if (event.type == 'touchend') {
				this.touchActivated = true;
			} else if (event.type == 'mousedown' && this.touchActivated) {
				this.touchActivated = false;
				return;
			}
			
			if (event.type == 'mousedown' && event.which != 1) {
				// Wrong mouse button
				return;
			}
			
			if (this.popupVisible) {
				var target = $(event.target),
					item = target.closest(this.classnameToSelector(this.options.classnamePopupItem)),
					value = this.unescape(item.attr('data')),
					touch_scrolled = this.touchScrolled;
				
				this.touchScrolled = false;
				
				if (item.size()) {
					if (typeof value != 'undefined') {
						if (this.options.preventDefault) {
							// We want to skip scroll, because if user clicked on item it
							// is already in view and changing scroll may lead to clicking
							// on incorrect item, because event is 'mousedown'
							
							if (this.touchActivated && touch_scrolled) {
								// User scrolled, don't select item
								return false;
							} else {
								this.setValue(value, true /* skip scroll */);
								this._hidePopup();
							}
							
							return false;
						} else {
							this.clickActivatedApplyDefault = true;
						}
					} else if (!this.options.preventDefault) {
						this.clickActivatedApplyDefault = true;
					}
				} else if (target.closest(this.classnameToSelector(this.options.classnameItem).length)) {
					// Click on dropdown title
					this._hidePopup();
				}
			} else {
				this._showPopup();
			}
		},
		
		/**
		 * Reset touch scroll state
		 * 
		 * @private
		 */
		_resetTouchScroll: function () {
			this.touchScrolled = false;
		},
		
		/**
		 * Handle input value change
		 * 
		 * @private
		 */
		_onChange: function () {
			if (!this.silent && this.nodeInput) {
				this.setValue(this.nodeInput.val());
			}
		},
		
		/**
		 * Handle focus
		 * 
		 * @private
		 */
		_onFocus: function () {
			this.nodeDropdown.addClass(this.options.classnameFocus);
			this.hasFocus = true;
			this.keystrokeLine = '';
		},
		
		/**
		 * Handle blur
		 * 
		 * @private
		 */
		_onBlur: function (e) {
			if (this.clickActivatedApplyDefault) {
				this.clickActivatedApplyDefault = false;
				return;
			}
			
			if (msieOld) {
				//If click originated inside this dropdown, then don't do anything
				var target = $(e.target).closest(this.classnameToSelector(this.options.classname));
				if (target.get(0) === this.nodeDropdown.get(0)) return;
				
				//Since onBlur is proxied for each instance individually, then click will be
				//unbinded only for this instance
				$(document).unbind('mousedown', this._onBlur);
			}
			
			this.nodeDropdown.removeClass(this.options.classnameFocus);
			this.hasFocus = false;
			this._hidePopup();
		},
		
		/**
		 * Handle key press
		 * 
		 * @param {Event} event Event objects
		 * @private
		 */
		_onKey: function (event) {
			if (this.disabled || this.readonly || !this.hasFocus) return;
			var key = event.keyCode || event.which,
				index,
				link,
				href,
				target,
				character;
			
			switch (key) {
				case 40: //Arrow down
					if (this.popupVisible) {
						this._setPopupIndex(this.popupIndex + 1);
					} else {
						this.setIndex(this.index + 1);
					}
					this.keystrokeCallback();
					return false;
				case 38: //Arrow up
					if (this.popupVisible) {
						//Index shouldn't be less than 0, unless it already is -1
						index = (this.popupIndex == -1 ? -1 : Math.max(0, this.popupIndex - 1));
						this._setPopupIndex(index);
					} else {
						this.setIndex(this.index - 1);
					}
					this.keystrokeCallback();
					return false;
				case 13: //Return
					if (this.popupVisible && this.popupIndex != -1) {
						this.setIndex(this.popupIndex);
						this._hidePopup();
						
						if (!this.options.preventDefault) {
							// Find link
							link = this.nodeList.find('[data="' + this.value + '"] a');
							href = link.attr('href');
							target = link.attr('target');
							
							if (href) {
								if (target == '_blank') {
									window.open(href);
								} else {
									document.location = href;
								}
							}
						}
					} else if (!this.popupVisible) {
						this._showPopup();
					}
					return false;
				case 27: //Escape
					this._hidePopup();
					break;
				default: //Characters
					//Only non-whitespace characters
					character = String.fromCharCode(key).replace(/[^\x20-\x7E]/, '');
					if (character) {
						this.keystrokeLine += character;
						
						//After 1 second forget what user entered before
						clearTimeout(this.keystrokeTimer);
						this.keystrokeTimer = setTimeout(this.keystrokeCallback, 1000);
						
						//Find matching item
						index = this._findValue(this.keystrokeLine);
						if (index != -1) {
							if (this.popupVisible) {
								this._setPopupIndex(index);
							} else {
								this.setIndex(index);
							}
						}
					}
					break;
			}
		},
		
		/**
		 * Destroy dropdown widget
		 */
		destroy: function () {
			if (this.scrollbar) this.scrollbar.destroy();
			
			//Show input
			if (this.nodeInput) {
				this.nodeInput.removeClass(this.options.classnameInput);
				
				this.nodeInput.removeData('select');
				this.nodeInput.unbind('change', this._onChange);
			}
			
			this.nodeDropdown.remove();
			
			delete(this.scrollbar);
			delete(this.nodeInput);
			delete(this.nodeDropdown);
			delete(this.options);
			delete(this.nodeItem);
			delete(this.nodePopup);
			delete(this.nodeList);
			delete(this.values);
			delete(this.valuesObject);
			delete(this.itemsOutput);
			delete(this.keystrokeCallback);
			
			//Proxied functions
			delete(this._onChange);
		}
	};
	
	/*
	 * Renderer
	 * 
	 * Generates following HTML:
	 * 		<div class="select" tabindex="0">
	 * 			<div class="select-popup hidden">
	 * 				<ul class="select-list">
	 * 					<li data="ITEM_ID" class="select-list-item">ITEM_NAME</li>
	 * 					...
	 * 				</ul>
	 * 			</div>
	 * 			<a class="select-item">ITEM_NAME</a>
	 *		</div>
	 */
	DropDown.prototype.DEFAULT_OPTIONS.renderer = DropDown.RENDERER = {
		/**
		 * Render dropdown
		 * 
		 * @param {Object} options
		 * @return HTML
		 * @type {String}
		 */
		'dropdown': function (options) {
			var classnameDisabled = this.disabled ? ' ' + options.classnameDisabled : '';
			var classnameReadonly = this.readonly ? ' ' + options.classnameReadonly : '';
			return	'<div tabindex="0" class="' + options.classname + classnameDisabled + classnameReadonly + '">' +
					'	' + this.render('popup') +
					'	<a class="' + options.classnameItem + '">' + this.render('item') + '</a>' +
					'</div>';
		},
		
		/**
		 * Render dropdown item
		 * 
		 * @param {Object} options
		 * @param {String} value Selected item value
		 * @param {String} text Selected item title
		 * @return HTML
		 * @type {String}
		 */
		'item': function (options, value, text) {
			return this.escape(text) || '&nbsp;';
		},
		
		/**
		 * Render dropdown popup
		 * 
		 * @param {Object} options
		 * @return HTML
		 * @type {String}
		 */
		'popup': function (options) {
			return '<div class="' + options.classnamePopup + ' ' + options.classnameHidden + '"><ul class="' + options.classnamePopupList + '"></ul></div>';
		},
		
		/**
		 * Render dropdown popup item
		 * 
		 * @param {Object} options
		 * @param {String} value Selected item value
		 * @param {String} text Selected item title
		 * @param {Boolean} first If this is first value in dropdown
		 * @param {Bollean} last If this is last value in dropdown
		 * @return HTML
		 * @type {String}
		 */
		'popupItem': function (options, value, text, first, last) {
			var evalue = this.escape(value),
				etext = this.escape(text),
				href = '';
			
			if (!options.preventDefault) {
				href = evalue;
			}
			
			return '<li class="' + options.classnamePopupItem + '" data="' + evalue + '"><a' + (href ? ' href="' + href + '"' : '') + '>' + etext + '</a></li>';
		},
		
		/**
		 * Render dropdown popup group label
		 * 
		 * @param {Object} options
		 * @param {String} value Selected item value
		 * @param {String} text Selected item title
		 * @param {Boolean} first If this is first value in dropdown
		 * @param {Bollean} last If this is last value in dropdown
		 * @return HTML
		 * @type {String}
		 */
		'popupGroup': function (options, text, first, last) {
			var etext = this.escape(text);
			
			return '<li class="' + options.classnamePopupGroup + '">' + etext + '</li>';
		},
		
		/**
		 * Render dropdown popup list items
		 * 
		 * @param {Object} options
		 * @return HTML
		 * @type {String}
		 */
		'popupList': function (options) {
			var html = '',
				items = this.itemsOutput,
				showEmptyValue = options.showEmptyValue,
				fnItem = options.renderer.popupItem,
				fnGroup = options.renderer.popupGroup,
				args = null;
			
			for(var i=0,ii=items.length-1; i<=ii; i++) {
				if (items[i].type == 'item') {
					if (items[i] || showEmptyValue) {
						args = [options, items[i].value, this.valuesObject[items[i].value], i === 0, i === ii];
						html += fnItem.apply(this, args);
					}
				} else {
					args = [options, items[i].label, i === 0, i === ii];
					html += fnGroup.apply(this, args);
				}
			}
			
			return html;
		}
	};
	
	/**
	 * jQuery DropDown plugin
	 * 
	 * @param {Object} options
	 * @return DropDown instance for node
	 */
	$.fn.dropdown = function (options) {
		var inputs = this.find('select').add(this.filter('select')),
			fn;
		
		if (typeof options === 'string' && typeof DropDown.prototype[options] === 'function') {
			fn = options;
			options = {};
		} else if (typeof options !== 'object') {
			options = {};
		}
		
		inputs.each(function () {
			var node = $(this),
				instance = node.data('select');
			
			if (!instance) {
				instance = new DropDown(node, options);
				node.data('select', instance);
			}
			
			if (fn) {
				instance[fn]();
			}
		});
		
		return this;
	};
	
	$.DropDown = DropDown;
	return DropDown;
	
}));