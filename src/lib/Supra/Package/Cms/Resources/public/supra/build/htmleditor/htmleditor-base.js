YUI().add('supra.htmleditor-base', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function HTMLEditor () {
		HTMLEditor.superclass.constructor.apply(this, arguments);
	}
	
	HTMLEditor.MODE_STRING	= 1;
	HTMLEditor.MODE_TEXT	= 4;
	HTMLEditor.MODE_BASIC	= 5;
	HTMLEditor.MODE_SIMPLE	= 2;
	HTMLEditor.MODE_RICH	= 3;
	
	HTMLEditor.MODE_NAMES   = {
		'string': HTMLEditor.MODE_STRING,
		'text':   HTMLEditor.MODE_TEXT,
		'basic':  HTMLEditor.MODE_BASIC,
		'simple': HTMLEditor.MODE_SIMPLE,
		'rich':   HTMLEditor.MODE_RICH
	};
	
	HTMLEditor.TYPE_STANDALONE = 1;
	HTMLEditor.TYPE_INLINE = 2;
	
	HTMLEditor.NAME = 'editor';
	HTMLEditor.CLASS_NAME = Y.ClassNameManager.getClassName(HTMLEditor.NAME);
	HTMLEditor.ATTRS = {
		'doc': {
			value: null
		},
		'win': {
			value: null
		},
		'srcNode': {
			value: null
		},
		'iframeNode': {
			value: null
		},
		'disabled': {
			value: false,
			setter: '_setDisabled'
		},
		'nativeSpellCheck': {
			value: true,
			setter: '_setNativeSpellCheck'
		},
		'toolbar': {
			value: null
		},
		/**
		 * Max content length in STRING or TEXT modes
		 */
		'maxLength': {
			value: 0
		},
		/**
		 * HTMLEditor mode: Supra.HTMLEditor.MODE_SIMPLE or Supra.HTMLEditor.MODE_RICH
		 */
		'mode': {
			value: HTMLEditor.MODE_RICH
		},
		/**
		 * Plugin configuration
		 */
		'plugins': {
			value: {}
		},
		/**
		 * HTMLEditor is in standalone mode
		 */
		'standalone': {
			value: false
		},
		/**
		 * Parent widget, usually input
		 */
		'parent': {
			value: null
		},
		/**
		 * Root parent input, could be form or block
		 */
		'root': {
			value: null
		},
		
		/**
		 * Stylesheet parser,
		 * Supra.IframeStylesheetParser instance
		 */
		'stylesheetParser': {
			value: null
		},
		
		/**
		 * Delayed initialization
		 * Used for performance reasons
		 */
		'delayedInitialization': {
			value: false
		}
	};
	
	Y.extend(HTMLEditor, Y.Base, {
		
		events: [],
		
		
		syncUI: function () {
			
		},
		
		bindUI: function () {
			var doc = new Y.Node(this.get('doc'));
			
			this.events.push(
				this.get('srcNode').on('keyup', this._handleKeyUp, this)
			);
			this.events.push(
				this.get('srcNode').on('keydown', this._handleKeyDown, this)
			);
			this.events.push(
				this.get('srcNode').on('keypress', this._handleKeyPress, this)
			);
			this.events.push(
				this.get('srcNode').on('mousedown', this._handleNodeMouseDown, this)
			);
			this.events.push(
				doc.on('mouseup', this._handleNodeChange, this)
			);
			
			this.events.push(
				doc.on('click', this._handleNodeChange, this)
			);
			
			var toolbar = this.get('toolbar');
			if (toolbar) {
				this.events.push(
					toolbar.on('command', function (event) {
						this.exec(event.command);
					}, this)
				);
			}
		},
		
		renderUI: function () {
			var srcNode = this.get('srcNode');
			
			this.set('disabled', this.get('disabled'));
			this.set('nativeSpellCheck', this.get('nativeSpellCheck'));
		},
		
		render: function () {
			this.events = [];
			this.data = {};
			this.commands = {};
			this.selection = null;
			
			// For performance reasons (if there is we must delay initialization
			if (this.get('delayedInitialization')) {
				Y.later(16, this, this.renderDelayed);
			} else {
				this.renderDelayed();
			}
		},
		
		/**
		 * Initialize everything
		 */
		renderDelayed: function () {
			if (!this.get("stylesheetParser")) {
				var root = this.get("root");
				if (root && root.getStylesheetParser) {
					//Root is block, we can take borrow from it
					this.set("stylesheetParser", root.getStylesheetParser());
				} else {
					//Create new parser
					this.set("stylesheetParser", new Supra.IframeStylesheetParser({
						"win": this.get("win"),
						"doc": this.get("doc")
					}));
				}
			}
			
			this.renderUI();
			this.bindUI();
			this.syncUI();
			
			this.initPlugins();
			
			this._changed = Supra.throttle(this._changed, 1000, this);
			
			this.setHTML(this.get('srcNode').get('innerHTML'));
		},
		
		/**
		 * Destroy editor
		 */
		destructor: function () {
			//Remove event listeners
			var events = this.events;
			for(var i=0,ii=events.length; i<ii; i++) events[i].detach();
			this.events = [];
			this.destroyPlugins();
		},
		
		/**
		 * Update selection, trigger necessary events
		 */
		refresh: function (force, delay) {
			if (delay) {
				//Delay is used after making modifications to the DOM
				Y.later(60, this, function () {
					this._handleNodeChange({}, force);
				});
			} else {
				return this._handleNodeChange({}, force) == 2;
			}
		},
		
		/**
		 * Set content HTML
		 * 
		 * @param {String} html
		 */
		setHTML: function (html) {
			//Make sure we have a string, not null or undefined
			var html = String(html || '');
			
			//editor:setHTML event
			var event = {html: this.uncleanHTML(html)};
			this.fire('setHTML', {}, event);
			
			//untagHTML
			html = event.html;
			
			var plugins = this.getAllPlugins(),
				data = this.getAllData(),
				id = null;
			
			for(id in plugins) {
				if (plugins[id].untagHTML) {
					html = plugins[id].untagHTML(html, data);
				}
			}
			
			//Replace with <p></p> if empty
			if (!Y.UA.ie && this.get('mode') == Supra.HTMLEditor.MODE_RICH) {
				if (!html) html = '<p></p>';
			}
			
			//Set HTML
			this.get('srcNode').set('innerHTML', html);
			this.restoreEditableStates();
			
			//Move cursor to the beginning of the content
			var srcNode = Y.Node.getDOMNode(this.get('srcNode')),
				selectionNode = srcNode.firstChild || srcNode;
			
			this.setSelection({'start': selectionNode, 'start_offset': 0, 'end': selectionNode, 'end_offset': 0});
			
			//Fire "nodeChange" event
			this.selection = null;
			this.refresh();
			
			//Fire event
			this.fire('afterSetHTML');
		},
		
		/**
		 * Returns cleaned up HTML
		 * 
		 * @return HTML
		 * @type {String}
		 */
		getHTML: function () {
			var html = this.get('srcNode').get('innerHTML');
				html = this.cleanHTML(html);
			
			var event = {'html': html};
			this.fire('getHTML', {}, event);
			
			return event.html || '';
		},
		
		/**
		 * Returns HTML with nodes converted into macros
		 * 
		 * @return HTML
		 * @type {String}
		 */
		getProcessedHTML: function () {
			var html = this.getHTML(),
				plugins = this.getAllPlugins();
			
			for(var id in plugins) {
				if (plugins[id].tagHTML) {
					html = plugins[id].tagHTML(html);
				}
			}
			
			return html;
		},
		
		/**
		 * Focus editor
		 */
		focus: function () {
			var srcNode = this.get('srcNode').getDOMNode(),
				node = srcNode.lastChild;
			
			// Find last text node
			while (node && node.nodeType == 1) {
				node = node.lastChild;
			}
			
			// Focus
			if (Y.UA.webkit) {
				this.get('win').focus();
				this.get('srcNode').focus();
			} else {
				this.get('srcNode').focus();
			}
			
			// Place cursor at the end
			Supra.immediate(this, function () {
				this.deselect();
			});
		},
		
		/**
		 * Enable/disable editor
		 * 
		 * @param {Boolean} value
		 * @private
		 */
		_setDisabled: function (value) {
			if (value) {
				this.get('srcNode').setAttribute('contentEditable', false);
				this.editingAllowed = false;
				
				this.fire('editingAllowedChange', {'allowed': false});
				
				this.selection = null;
				this.selectedElement = null;
				this.path = null;
				
				this.fire('disable');
			} else {
				this.get('srcNode').setAttribute('contentEditable', true);
				
				// Focus
				this.focus();
				
				//Prevent object resizing
				this.disableObjectResizing();
				
				//Update selection, etc.
				this.refresh(true);
				
				this.fire('enable');
			}
			
			return !!value;
		},
		
		/**
		 * Enable/disable spell-checking in supported browsers
		 * 
		 * @param {Boolean} value
		 */
		_setNativeSpellCheck: function (value) {
			value = !!value;
			this.get('doc').body.spellcheck = value;
			if (value) {
				this.get('doc').body.removeAttribute('spellcheck');
			} else {
				this.get('doc').body.setAttribute('spellcheck', 'false');
			}
			
			return value;
		},
		
		/**
		 * Prevent key press if content is not editable
		 * 
		 * @param {Object} event
		 */
		_handleKeyPress: function (event) {
			var charCode = event.charCode || event.keyCode,
				navKey = this.navigationCharCode(charCode);
			
			/* 
			 * Cancel key press if node is not editable and key wasn't "navigation" key.
			 * If original event charCode is not empty, then this key definitely changes
			 * text and should be canceled
			 */
			if (!event.stopped && !this.editingAllowed && (event._event.charCode || !navKey)) {
				event.preventDefault();
				return;
			} else if (!navKey && !event.ctrlKey) {
				this._changed();
			}
		},
		
		/**
		 * Trigger node change when key is released
		 * 
		 * @param {Object} event
		 */
		_handleKeyUp: function (event) {
			var charCode = event.charCode,
				navKey = this.navigationCharCode(charCode);
			
			if (this.editingAllowed || navKey) {
				if (this.fire('keyUp', event, event) !== false) {
					Supra.immediate(this, function () {
						this._handleNodeChange(event);
					});
					
					if (!navKey && !event.ctrlKey) {
						this._changed();
					}
				}
			}
		},
		
		/**
		 * Trigger keydown event on editor
		 * 
		 * @param {Object} event
		 */
		_handleKeyDown: function (event) {
			var charCode = event.charCode,
				navKey = this.navigationCharCode(charCode);
			
			if (this.editingAllowed || navKey) {
				if (this.fire('keyDown', event, event) === false) {
					event.preventDefault();
				}
			}
		},
		
		/**
		 * Handle mouse down
		 * If user clicks on uneditable element, prevent text selection
		 * 
		 * @param {Object} event
		 */
		_handleNodeMouseDown: function (event) {
			if (this.get('disabled')) return;
			
			var target = Y.Node.getDOMNode(event.target);
			if (!this.isEditable(target)) {
				event.preventDefault();
				return false;
			}
		},
		
		/**
		 * If cursor position changed fires nodeChange event
		 * If cursor entered/left un-editable content fires editingAllowedChange
		 * 
		 * @param {Object} event
		 * @return Returns 2 if selection changed and 1 if selection didn't changed. True/false is not used to prevent event stopping
		 * @type {Number}
		 */
		_handleNodeChange: function (event, force) {
			if (this.get('disabled') && !force) return 1;
			
			var oldSel = this.selection,
				newSel = this.getSelection(),
				fireSelectionEvent = false,
				fireNodeEvent = false,
				node = null;
			
			//On mouse click / mouse down check if user clicked on image
			if (event && event.type && (event.type == 'mouseup' || event.type == 'click')) {
				if (event.target.test('img')) {
					node = event.target;
				} else {
					node = event.target.closest('svg');
					
					if (!node) {
						node = event.target.closest('.supra-image, .supra-icon');
						if (node) {
							// Clicked on resize tool
							node = node.one('img, svg');
						}
					}
				}
				
				if (node) {
					newSel = this._handleSelectableClick(node);
				}
			}
			
			if (oldSel) {
				if (oldSel.start !== newSel.start || oldSel.end !== newSel.end) {
					fireSelectionEvent = true;
					fireNodeEvent = true;
				} else if (oldSel.start_offset !== newSel.start_offset || oldSel.end_offset !== newSel.end_offset) {
					fireSelectionEvent = true;
				} else {
					//Nothing at all changed, skip
					return 1; 
				}
			} else {
				fireSelectionEvent = true;
				fireNodeEvent = true;
			}
			
			this.resetSelectionCache(newSel);
			
			if (fireSelectionEvent) {
				event.selection = newSel;
				this.fire('selectionChange', event);
				
				if (fireNodeEvent) {
					this.fire('nodeChange', event);
				}
				
				var allowed = true;
				if (!this.isSelectionEditable(newSel)) {
					allowed = false;
				}
				
				if (this.editingAllowed != allowed) {
					this.editingAllowed = allowed;
					this.fire('editingAllowedChange', {'allowed': allowed});
				}
				
				return 2;
			}
			
			
			return 1;
		},
		
		/**
		 * Handle click on image
		 * @private
		 */
		_handleSelectableClick: function (target) {
			var node = target.getDOMNode(),
				selection = {
					'start': node,
					'start_offset': 0,
					'end': node,
					'end_offset': 0,
					'collapsed': true
				};
			
			return selection;
		},
		
		/**
		 * Update 'changed' state if needed
		 */
		_changed: function () {
			this.fire('change');
			Supra.session.triggerActivity();
		}
		
	});
	
	//Plugins
	HTMLEditor.PLUGINS = {};
	
	
	Supra.HTMLEditor = HTMLEditor;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);
