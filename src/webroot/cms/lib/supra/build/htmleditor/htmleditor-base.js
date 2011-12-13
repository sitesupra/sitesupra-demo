//Invoke strict mode
"use strict";

YUI().add('supra.htmleditor-base', function (Y) {
	
	function HTMLEditor () {
		HTMLEditor.superclass.constructor.apply(this, arguments);
	}
	
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
		'disabled': {
			value: false,
			setter: '_setDisabled'
		},
		'nativeSpellCheck': {
			value: false,
			setter: '_setNativeSpellCheck'
		},
		'toolbar': {
			value: null
		},
		/**
		 * HTMLEditor mode: SU.HTMLEditor.MODE_SIMPLE or SU.HTMLEditor.MODE_RICH
		 */
		'mode': {
			value: 3
		}
	};
	
	HTMLEditor.MODE_STRING	= 1;
	HTMLEditor.MODE_SIMPLE	= 2;
	HTMLEditor.MODE_RICH	= 3;
	
	
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
				this.get('srcNode').on('keypress', this._handleKeyPress, this)
			);
			this.events.push(
				this.get('srcNode').on('mousedown', this._handleNodeMouseDown, this)
			);
			this.events.push(
				this.get('srcNode').on('mouseup', this._handleNodeChange, this)
			);
			
			this.events.push(
				doc.on('click', this._handleNodeChange, this)
			);
			
			var toolbar = this.get('toolbar');
			if (toolbar) {
				this.events.push(
					toolbar.on('command', Y.bind(function (event) {
						this.exec(event.command);
					}, this))
				);
			}
		},
		
		renderUI: function () {
			var srcNode = this.get('srcNode');
			
			this.set('disabled', this.get('disabled'));
			this.set('nativeSpellCheck', this.get('nativeSpellCheck'));
		},
		
		render: function () {
			this.renderUI();
			this.bindUI();
			this.syncUI();
			
			this.data = {};
			this.commands = {};
			this.selection = null;
			this.initPlugins();
			
			this._changed = Y.throttle(Y.bind(this._changed, this), 1000);
			
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
		refresh: function (force) {
			return this._handleNodeChange({}, force);
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
				
				if (Y.UA.webkit) {
					//Focus and deselect all text
					/* @TODO
					this._setSelection(null); */
				} else {
					//Focus
					this.get('srcNode').focus();
				}
				
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
				if (this.fire('keyUp', event) !== false) {
					setTimeout(Y.bind(function () {
						this._handleNodeChange(event);
					}, this), 0);
					
					if (!navKey && !event.ctrlKey) {
						this._changed();
					}
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
		 */
		_handleNodeChange: function (event, force) {
			if (this.get('disabled') && !force) return false;
			
			var oldSel = this.selection;
			var newSel = this.getSelection();
			var fireSelectionEvent = false,
				fireNodeEvent = false;
			
			if (oldSel) {
				if (oldSel.start !== newSel.start || oldSel.end !== newSel.end) {
					fireSelectionEvent = true;
					fireNodeEvent = true;
				} else if (oldSel.start_offset !== newSel.start_offset || oldSel.end_offset !== newSel.end_offset) {
					fireSelectionEvent = true;
				} else {
					//Nothing at all changed, skip
					return false; 
				}
			} else {
				fireSelectionEvent = true;
				fireNodeEvent = true;
			}
			
			this._resetSelection(newSel);
			
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
				
				return true;
			}
			return false;
		},
		
		/**
		 * Update 'changed' state if needed
		 */
		_changed: function () {
			this.fire('change');
		}
		
	});
	
	//Plugins
	HTMLEditor.PLUGINS = {};
	
	
	Supra.HTMLEditor = HTMLEditor;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);
