YUI.add("supra.input-html", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Manager = Supra.Manager;
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = "input-html";
	
	Input.ATTRS = {
		'doc': {
			value: null
		},
		'win': {
			value: null
		},
		'toolbarInline': {
			value: false
		},
		'nodeIframe': {
			value: null
		},
		// HTML plugin information
		'plugins': {
			value: null
		},
		// HTML editor mode
		'mode': {
			value: null
		},
		// Default value
		'defaultValue': {
			value: ''
		}
	};
	
	/**
	 * Parse
	 */
	Input.HTML_PARSER = {
		'mode': function (srcNode) {
			var mode = srcNode.getAttribute('data-mode'),
				names = Supra.HTMLEditor.MODE_NAMES;
			
			if (mode && mode in names) {
				return names[mode];
			}
		},
		'toolbarInline': function (srcNode) {
			var inline = srcNode.getAttribute('data-toolbar-inline');
			if (inline === 'true' || inline === '1') {
				return true;
			} else if (inline === 'false' || inline === '0') {
				return false;
			}
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		/**
		 * Constants
		 */
		INPUT_TEMPLATE: '<textarea spellcheck="false"></textarea>',
		LABEL_TEMPLATE: '<label></label>',
		
		/**
		 * HTMLEditor instance
		 * @type {Object}
		 * @private
		 */
		htmleditor: null,
		
		
		
		
		/**
		 * ----------------------------------- PRIVATE --------------------------------------
		 */
		
		
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			if (this.htmleditor) {
				this.htmleditor.on('change', function (evt) {
					this.fire('change');
				}, this);
			}
			
			this.after('disabledChange', this._afterDisabledChange, this);
		},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			this.set('boundingBox', this.get('srcNode'));
			
			if (!this.get('toolbarInline')) {
				Manager.Loader.loadActions(['EditorToolbar', 'PageContentSettings']);
			} else {
				Manager.Loader.loadActions(['PageContentSettings']);
			}
			
			this.createIframe();
		},
		
		/**
		 * Clean up
		 * 
		 * @private
		 */
		destructor: function () {
			if (this.htmleditor) {
				this.htmleditor.detach('change');
				this.htmleditor.destroy();
				this.htmleditor = null;
			}
		},
		
		/**
		 * Create HTML toolbar and load PageContentSettings before creating editor
		 * 
		 * @private
		 */
		loadDependancies: function () {
			if (this.get('toolbarInline')) {
				this.createEditor();
			} else {
				// We need EditorToolbar action
				var action = Manager.getAction('EditorToolbar');
				if (!action.get('created')) {
					action.once('executed', function () {
						var action = Manager.getAction('PageContentSettings');
						if (!action.get('loaded')) {
							action.once('loaded', this.createEditor, this);
						} else {
							this.createEditor();
						}
					}, this);
					
					Manager.executeAction('EditorToolbar', true);
				} else {
					this.createEditor();
				}
			}
		},
		
		createEditorToolbar: function () {
			if (this.get('toolbarInline')) {
				var iframe   = this.get('nodeIframe'),
					toolbar  = null,
					controls = Supra.mix([], Supra.HTMLEditorToolbar.CONTROLS, true),
					i = controls.groups.length-1;
				
				for (; i>=0; i--) {
					if (controls.groups[i].id == 'main') {
						// We can't have main group, while toolbar is inline
						controls.groups.splice(i, 1);
					}
				}
				
				toolbar = new Supra.HTMLEditorToolbar({'controls': controls});
				toolbar.render(this.get('boundingBox'));
				iframe.insert(toolbar.get('boundingBox'), 'before');
				
				return toolbar;
			} else {
				return Manager.EditorToolbar.getToolbar();
			}
		},
		
		/**
		 * Create HTML editor
		 * 
		 * @private
		 */
		createEditor: function () {
			if (this.htmleditor) return;
			
			var doc = this.get('doc'),
				win = this.get('win'),
				src = this.get('srcNode'),
				toolbar = this.createEditorToolbar(),
				value = this.get('value'),
				iframe = this.get('nodeIframe'),
				controls = null;
			
			if (doc && win && src) {
				this.htmleditor = new Supra.HTMLEditor({
					'doc': doc,
					'win': win,
					'srcNode': Y.Node(doc).one('.editing'),
					'iframeNode': iframe,
					'toolbar': toolbar,
					'mode': this.get('mode') || Supra.HTMLEditor.MODE_RICH,
					'standalone': true,
					'parent': this,
					'root': this.get('root') || this,
					'plugins': this.get('plugins')
				});
				this.htmleditor.render();
				
				if (this.get('disabled') || !this.get('toolbarInline')) {
					// Disable
					this.htmleditor.set('disabled', true);
				} else {
					// Enable
					Y.Node(this.get('doc')).one('html').removeClass('standalone-disabled');
				}
				
				// Normalize value
				if (typeof value === 'string') {
					value = {
						'html': value,
						'data': {}
					};
				}
				
				if (value && 'html' in value) {
					this.htmleditor.setAllData(value.data);
					this.htmleditor.setHTML(value.html);
				}
				
				Y.Node(doc).one('html').on('click', this.onIframeClick, this);
			}
		},
		
		/**
		 * Wait till stylesheets are loaded
		 * 
		 * @private
		 */
		waitTillStyleSheetsAreLoaded: function (links) {
			var fn = Y.bind(function () {
				var loaded = true;
				for(var i=0,ii=links.length; i<ii; i++) {
					if (!links[i].sheet) {
						loaded = false;
						break;
					} else {
					}
				}
				
				if (loaded) {
					Y.later(50, this, function () {
						this.loadDependancies();
					});
				} else {
					setTimeout(fn, 50);
				}
			}, this);
			setTimeout(fn, 50);
		},
		
		/**
		 * Create iframe for HTMLEditor
		 * 
		 * @private
		 */
		createIframe: function () {
			var textarea = this.get('inputNode'),
				iframe = Y.Node.create('<iframe />'),
				html = '';
			
			textarea.insert(iframe, 'after');
			textarea.addClass('hidden');
			
			html = Supra.data.get(['supra.htmleditor', 'standalone', 'doctype'], '<!DOCTYPE html>');
			html+= '<html lang="en" class="standalone standalone-disabled"><head>';
			
			//Add stylesheets to iframe
			if (!Supra.data.get(['supra.htmleditor', 'stylesheets', 'skip_default'], false)) {
				var uri = Manager.Loader.getStaticPath() + Manager.Loader.getActionBasePath('Header') + '/pagecontent/iframe.css';
				html+= '<link rel="stylesheet" type="text/css" href="' + uri + '" />';
			}
			
			var stylesheets = Supra.data.get(['supra.htmleditor', 'standalone', 'stylesheets'], []),
				i = 0,
				ii = stylesheets.length;
			
			for(; i<ii; i++) {
				html+= '<link rel="stylesheet" type="text/css" href="' + stylesheets[i] + '" />';
			}
			
			html+= '</head><body>';
			html+= this.selectorToHTML(Supra.data.get(['supra.htmleditor', 'standalone', 'wrap'], ''));
			html+= '</body></html>';
			
			this.writeHTML(iframe, html);
			this.set('nodeIframe', iframe);
			
			//Save document & window instances
			var win = this.get('nodeIframe').getDOMNode().contentWindow;
			var doc = win.document;
			this.set('win', win);
			this.set('doc', doc);
			
			//Wait till all stylesheets are loaded before creating editor
			this.waitTillStyleSheetsAreLoaded(Y.Node(doc).all('link[rel="stylesheet"]'));
		},
		
		/**
		 * On iframe click enable editing
		 * 
		 * @private
		 */
		onIframeClick: function () {
			if (!this.get('disabled')) {
				if (this.htmleditor.get('disabled')) {
					if (this.get('toolbarInline')) {
						Y.Node(this.get('doc')).one('html').removeClass('standalone-disabled');
						this.htmleditor.set('disabled', false);
					} else if (!Manager.EditorToolbar.get('visible')) {
						Y.Node(this.get('doc')).one('html').removeClass('standalone-disabled');
						
						//Show toolbar without "Settings" button
						Manager.EditorToolbar.execute();
						Manager.EditorToolbar.getToolbar().getButton('settings').set('visible', false);
						
						this.htmleditor.set('disabled', false);
						
						Manager.EditorToolbar.once('afterVisibleChange', this.onIframeBlur, this);
					}
				}
			}
		},
		
		/**
		 * On blur disable editor
		 * if source editor in not opened, because it's part of HTML editor 
		 */
		onIframeBlur: function (e) {
			var source_editor = Manager.getAction('PageSourceEditor'),
				link_manager = Manager.getAction('LinkManager'),
				link_manager_loading = Supra.Manager.Loader.isLoading('LinkManager');
			
			if (!link_manager.get('visible') && !link_manager_loading && !source_editor.get('visible') && !this.htmleditor.get('disabled') && !e.silent) {
				Y.Node(this.get('doc')).one('html').addClass('standalone-disabled');
				
				this.htmleditor.set('disabled', true);
				this.fire('change', {'value': this.get('value')});
			}
		},
		
		/**
		 * Create HTML which would match given selector
		 * 
		 * @param {String} selector CSS selector
		 * @return HTML
		 * @type {String}
		 * @private
		 */
		selectorToHTML: function (selector) {
			var attrs = {},
				attr = null,
				tag = 'div',
				
				start = [],
				end = [],
				index = 0;
			
			selector = selector.split(/\s+/g);
			
			for(var i=0,ii=selector.length; i<ii; i++) {
				attrs = {'class': 'yui3-box-reset'};
				tag = 'div';
				
				selector[i].replace(/(\.|#)?([a-z0-9\-\_]+)/g, function (all, type, value) {
					if (type == '.') { //Classname
						attrs['class'] += ' ' + value;
					} else if (type == '#') { //ID attribute
						attrs['id'] = value;
					} else if (!type) { //Node
						tag = value;
					}
				});
				
				//To last item add class
				if (i == ii-1) {
					attrs['class'] = attrs['class'] ? attrs['class'] + ' editing' : 'editing';
				}
				
				//Create tags
				end.unshift('</' + tag + '>');
				tag = '<' + tag;
				
				for(attr in attrs) {
					tag+= ' ' + attr + '="' + attrs[attr] + '"';
				}
				
				start.push(tag + '>');
			}
			
			var value = this.get('value'),
				content = '';
			
			if (value && value.html) {
				content = value.html;
			}
						
			return start.join('') + content + end.join('');
		},
		
		/**
		 * Write HTML into iframe
		 * 
		 * @param {String} html HTML
		 * @private
		 */
		writeHTML: function (iframe, html) {
			var win = iframe.getDOMNode().contentWindow;
			var doc = win.document;
			var scripts = [];
			
			doc.open('text/html', 'replace');
			doc.writeln(html);
			doc.close();
			
			Y.Node(doc).one('html').addClass('supra-cms');
		},
		
		
		
		
		/**
		 * ----------------------------------- API --------------------------------------
		 */
		
		
		
		/**
		 * Returns HTMLEditor instance
		 * 
		 * @return HTMLEditor instance
		 * @type {Object}
		 */
		getEditor: function () {
			return this.htmleditor;
		},
		
		
		
		
		/**
		 * ----------------------------------- ATTRIBUTES --------------------------------------
		 */
		
		
		
		/**
		 * Disabled attribute setter
		 * Disable / enable HTMLEditor
		 * 
		 * @param {Boolean} value New state value
		 * @return New state value
		 * @type {Boolean}
		 * @private
		 */
		_setDisabled: function (value) {
			//HTMLEditor is already disabled while used is not editing
			return !!value;
		},
		
		/**
		 * Value attribute getter
		 * Returns value, object with 'html' and 'data' keys
		 * 
		 * @param {Object} value Previous value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_getValue: function (value) {
			if (this.htmleditor) {
				//Remove data which is not bound to anything
				this.htmleditor.removeExpiredData();
				
				return {
					'html': this.htmleditor.getHTML(),
					'data': this.htmleditor.getAllData(),
					'fonts': this.htmleditor.getUsedFonts()
				};
			} else {
				return value;
			}
		},
		
		/**
		 * saveValue attribute getter
		 * Returns value for sending to server, object with 'html' and 'data' keys
		 * 
		 * @param {Object} value Previous value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_getSaveValue: function (value) {
			if (this.htmleditor) {
				return {
					'html': this.htmleditor.getProcessedHTML(),
					'data': this.htmleditor.getProcessedData(),
					'fonts': this.htmleditor.getUsedFonts()
				};
			} else {
				return value;
			}
		},
		
		/**
		 * Value attribute setter
		 * Set HTMLEdtior html and data
		 * 
		 * @param {Object} value New value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_setValue: function (value) {
			if (typeof value === 'string') {
				value = {
					'html': value,
					'data': {}
				};
			}
			
			if (this.htmleditor) {
				this.htmleditor.setAllData(value.data);
				this.htmleditor.setHTML(value.html);
			}
			
			return value;
		},
		
		/**
		 * Handle disabled change event
		 * 
		 * @param {Object} e
		 */
		_afterDisabledChange: function (e) {
			this.htmleditor.set('disabled', e.newVal);
		}
		
	});
	
	Input.lipsum = function () {
		return {
			'data': {},
			'html': Supra.Lipsum.html()
		};
	};
	
	Supra.Input.Html = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "supra.htmleditor"]});