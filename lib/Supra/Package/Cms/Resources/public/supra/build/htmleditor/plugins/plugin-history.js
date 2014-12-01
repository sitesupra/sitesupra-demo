YUI().add('supra.htmleditor-plugin-history', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/* Maximum number of states which should be saved */
		maxHistorySize: 30
	};
	
	var KEY_Y = 89,
		KEY_Z = 90;
		
	var REGEX_HTML_IDS = /\s+id="yui_[^"]*"/g,
		REGEX_HTML_NEWLINES = /[r\n]*/g,
		REGEX_HTML_WHITESPACE = /[\s\t]{2,}/g;
	
	Supra.HTMLEditor.addPlugin('history', defaultConfiguration, {
		
		/**
		 * List of all history states
		 * @type {Array}
		 * @private
		 */
		history: [],
		
		/**
		 * Current history state
		 * @type {Number}
		 */
		index: -1,
		
		/**
		 * Text has changed
		 * @type {Boolean}
		 * @private
		 */
		has_text_changes: false,
		
		
		/**
		 * Undo changes
		 */
		undo: function () {
			this.pushTextState();
			this.setState(this.index - 1);
		},
		
		/**
		 * Redo changes
		 */
		redo: function () {
			this.pushTextState();
			this.setState(this.index + 1);
		},
		
		/**
		 * Set history state
		 */
		setState: function (index) {
			var htmleditor = this.htmleditor,
				history = this.history,
				state_index = null,
				srcNode;
			
			if (typeof index !== 'number') {
				index = history.length;
			} else {
				index = Math.max(0, Math.min(history.length - 1, index));
			}
			
			if (index === history.length - 1) {
				if (this.index !== history.length - 1) {
					state_index = index;
				}
			} else if (index !== this.index) {
				state_index = index;
			}
			
			if (state_index !== null) {
				srcNode = htmleditor.get('srcNode')
				htmleditor.data = history[state_index].data;
				srcNode.getDOMNode().innerHTML = history[state_index].html;
			}
			
			this.index = index;
		},
		
		/**
		 * Push state if there has been text changes
		 * This should be used before any non-text operation,
		 * for example: insert link, delete image, format text
		 */
		pushTextState: function () {
			if (this.has_text_changes) {
				this.pushState();
			}
		},
		
		/**
		 * Add state to the history
		 */
		pushState: function (forced) {
			var history = this.history,
				htmleditor = this.htmleditor,
				srcNode = htmleditor.get('srcNode'),
				index = this.index,
				html;
			
			if (!srcNode.getDOMNode()) {
				return;
			}
			
			html = srcNode.getDOMNode().innerHTML.replace(REGEX_HTML_IDS, '');
			
			if (history.length) {
				// Check if content actually changed
				// @TODO What about data, does all data changes affect visual represenation?
				if (this._comparHTMLs(history[index].html, html) && forced !== true) {
					this.has_text_changes = false;
					return;
				}
			}
			
			if (index != history.length - 1) {
				// There has been undo, remove remaining states
				history.splice(index + 1);
			}
			
			history.push({
				'html': html,
				'data': Supra.mix({}, htmleditor.data, true)
			});
			
			if (history.length > this.configuration.maxHistorySize) {
				history.shift();
			}
			
			this.index = history.length - 1;
			this.has_text_changes = false;
		},
		
		/**
		 * Remove all history states
		 */
		reset: function () {
			this.history = [];
		},
		
		/**
		 * Compare two HTMLs
		 * 
		 * @param {String} a HTML 1
		 * @param {String} b HTML 2
		 * @returns {Boolean} True if both htmls are basically the same, otherwise false
		 */
		_comparHTMLs: function (a, b) {
			a = a.replace(REGEX_HTML_NEWLINES, '').replace(REGEX_HTML_WHITESPACE, ' ');
			b = b.replace(REGEX_HTML_NEWLINES, '').replace(REGEX_HTML_WHITESPACE, ' ');
			
			return a == b;
		},
		
		/**
		 * Prevent keys if new content will be added and maxlength has been reached
		 * 
		 * @private
		 */
		_onKey: function (event) {
			if (event.metaKey === true || event.ctrlKey === true) {
				if (event.keyCode === KEY_Y || (event.keyCode === KEY_Z && event.shiftKey)) {
					// Redo: Ctrl + Y , Ctrl + Shift + Z (osx)
					this.redo();
					event.halt();
					return;
				}
				if (event.keyCode === KEY_Z) {
					// Undo: Ctrl + Z
					this.undo();
					event.halt();
					return;
				}
			}
			
			// Some other key
			var charCode = event.charCode,
				navKey = this.htmleditor.navigationCharCode(charCode);
			
			if (!event.stopped && this.htmleditor.editingAllowed && !navKey) {
				this.has_text_changes = true;
			}
		},
		
		/**
		 * Handle node change
		 * 
		 * @private
		 */
		_onNodeChange: function () {
			this.pushTextState();
		},
		
		/**
		 * Handle selection change
		 * 
		 * @private
		 */
		_onSelectionChange: function (e) {
			// If selection changed by more than 2 characters
			/*if (e.oldSelection && e.oldSelection.start === e.selection.start && e.oldSelection.end == e.selection.end && Math.abs(e.selection.start_offset - e.selection.end_offset) > 2) {
				this.pushTextState();
			}*/
		},
		
		/**
		 * Handle disabled state change
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		_onDisabledChange: function (event) {
			if (event.newVal != event.prevVal) {
				if (event.newVal) {
					// Disabled
					this.reset();
				} else {
					// Enabled, save initial state
					this.pushState();
				}
			}
		},
		
		/**
		 * After HTML is set reset states
		 * 
		 * @private
		 */
		_afterSetHTML: function () {
			if (!this.htmleditor.get('disabled')) {
				Supra.immediate(this, this.pushState);
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
			this.history = [];
			htmleditor.on('disabledChange', this._onDisabledChange, this);
			htmleditor.on('afterSetHTML', this._afterSetHTML, this);
			
			htmleditor.on('keyDown', this._onKey, this);
			
			htmleditor.on('nodeChange', this._onNodeChange, this);
			htmleditor.on('selectionChange', this._onSelectionChange, this);
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			this.reset();
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});