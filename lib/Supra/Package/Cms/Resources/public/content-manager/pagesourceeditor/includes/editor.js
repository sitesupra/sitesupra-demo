/*
 * Supra.Manager.PageContent.Iframe
 */
YUI.add('supra.source-editor', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	/*
	 * Source code editor
	 */
	function Editor (config) {
		Editor.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Editor.NAME = 'page-source-editor';
	Editor.CLASS_NAME = Editor.CSS_PREFIX = 'su-' + Editor.NAME;
	
	Editor.ATTRS = {
		'html': {
			setter: 'setHTML',
			getter: 'getHTML'
		}
	};
	
	Y.extend(Editor, Y.Widget, {
		/**
		 * Content template
		 */
		CONTENT_TEMPLATE: '<textarea></textarea>',
		
		
		
		
		/**
		 * Line count
		 * @type {Number}
		 * @private
		 */
		/*
		line_count: 0,
		*/
		
		/**
		 * Line count node
		 * @type {Object}
		 * @private
		 */
		/*
		node_line_numbers: null,
		*/
		
		
		
		/**
		 * Set HTML which will be editable
		 * 
		 * @param {String} html HTML code
		 */
		setHTML: function (html) {
			this.get('contentBox').set('value', this.beautifyHTML(html));
			//this.adjustHeight();
			return html;
		},
		
		/**
		 * Returns current HTML
		 * 
		 * @return HTML code which is currently being edited
		 * @type {String}
		 */
		getHTML: function () {
			return this.uglifyHTML(this.get('contentBox').get('value'));
		},
		
		/**
		 * Beautify HTML
		 * 
		 * @param {String} html HTML code
		 * @return Beautified HTML code
		 * @type {String}
		 */
		beautifyHTML: Supra.HTMLEditor.prototype.beautifyHTML,
		
		/**
		 * Uglify HTML
		 * 
		 * @param {String} html HTML code
		 * @return Uglified HTML code
		 * @type {String}
		*/
		uglifyHTML: Supra.HTMLEditor.prototype.uglifyHTML,
		
		/**
		 * Adjust textarea height
		 */
		/*
		adjustHeight: function () {
			var content_box 		= this.get('contentBox'),
				bounding_box 		= this.get('boundingBox'),
				content_box_h 		= Math.ceil(parseFloat(content_box.getStyle('height'))),
				bounding_box_h 		= Math.ceil(parseFloat(bounding_box.getStyle('height'))),
				content_box_scroll	= Math.ceil(parseFloat(content_box.get('scrollHeight'))),
				line_count 			= Math.ceil(Math.max(content_box_h, bounding_box_h, content_box_scroll) / 16);
			
			if (content_box_h < bounding_box_h || content_box_scroll > content_box_h) {
				content_box.setStyle('minHeight', Math.max(bounding_box_h, content_box_scroll) + 'px');
			}
			
			if (this.line_count < line_count) {
				for(var i = this.line_count+1, ii = line_count; i <= ii; i++) {
					this.node_line_numbers.append(Y.Node.create('<i>' + i + '</i>'));
				}
				
				this.line_count = line_count;
				return true;
			}
			
			return false;
		},
		*/
		
		/**
		 * Handle key press
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		handleKey: function (e) {
			/*if (e.keyCode == 13) {
				//Return key
				this.adjustHeight();
			} else*/
			if (e.keyCode == 9) {
				//Tab key insert 4 spaces
				this.insertAtCaret("    ");
				e.halt();
			}
		},
		
		/**
		 * Handle window resize
		 * Alias of adjustHeight, only throttled and proxied
		 * 
		 * @private
		 */
		handleResize: null,
		
		/**
		 * Insert string at caret position
		 * 
		 * @param {String} str String to insert
		 */
		insertAtCaret: function (str) {
			var area = this.get('contentBox').getDOMNode(),
				sel = null,
				start_pos = 0,
				end_pos = 0,
				scroll_top = 0;
			
			if (document.selection) {
				area.focus();
				sel = document.selection.createRange();
				sel.text = str;
				area.focus();
			} else if (area.selectionStart || area.selectionStart == '0') {
				start_pos = area.selectionStart;
				end_pos = area.selectionEnd;
				scroll_top = area.scrollTop;
				
				area.value = area.value.substring(0, start_pos) + str + area.value.substring(end_pos, area.value.length);
				area.focus();
				area.selectionStart = start_pos + str.length;
				area.selectionEnd = start_pos + str.length;
				area.scrollTop = scroll_top;
			} else {
				area.value += str;
			}
		},
		
		/**
		 * Focus input
		 */
		focus: function () {
			Editor.superclass.focus.apply(this, arguments);
			
			var node = this.get('contentBox');
			if (node) {
				node.getDOMNode().focus();
			}
		},
		
		/**
		 * Add needed elements
		 * 
		 * @private
		 */
		renderUI: function () {
			this.node_line_numbers = Y.Node.create('<div class="line-numbers"></div>');
			this.get('boundingBox').prepend(this.node_line_numbers);
			
			//Update height
			/*
			setTimeout(Y.bind(function () {
				this.syncUI();
			}, this), 16);
			*/
		},
		
		/**
		 * Add event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			var keyEventSpec = (!Y.UA.opera) ? "down:" : "press:";
			var content_box = this.get('contentBox');	//textarea
			
			/*
			this.handleResize = Y.throttle(Y.bind(this.adjustHeight, this), 50);
			Y.on('resize', this.handleResize, window);
			*/
			
			Y.on("key", Y.bind(this.handleKey, this), content_box, keyEventSpec);
		},
		
		/**
		 * Update position, etc.
		 * 
		 * @private
		 */
		syncUI: function () {
			//this.adjustHeight();
		}
	});
	
	Manager.PageSourceEditor.Editor = Editor;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
});