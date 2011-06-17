YUI().add('supra.htmleditor-toolbar', function (Y) {
	
	var BUTTONS_DEFAULT = {
		groups: [
			{
				"id": "main",
				"controls": [
					{"id": "insertimage", "type": "button", "buttonType": "toggle", "title": "Image", "icon": "/cms/supra/img/htmleditor/icon-image.png", "command": "insertimage"},
					{"type": "separator"},
					{"id": "insertlink", "type": "button", "buttonType": "toggle", "title": "Link", "icon": "/cms/supra/img/htmleditor/icon-insertlink.png", "command": "insertlink"},
					{"type": "separator"},
					{"id": "inserttable", "type": "button", "buttonType": "toggle", "title": "Table", "icon": "/cms/supra/img/htmleditor/icon-table.png", "command": "inserttable"},
					{"type": "separator"},
					{"id": "settings", "type": "button", "buttonType": "toggle", "title": "Settings", "icon": "/cms/supra/img/htmleditor/icon-settings.png", "command": "settings"}
				]
			},
			{
				"id": "text",
				"controls": [
					{"id": "bold", "type": "button", "title": "Bold", "icon": "/cms/supra/img/htmleditor/icon-bold.png", "command": "bold"},
					{"id": "italic", "type": "button", "title": "Italic", "icon": "/cms/supra/img/htmleditor/icon-italic.png", "command": "italic"},
					{"id": "underline", "type": "button", "title": "Underline", "icon": "/cms/supra/img/htmleditor/icon-underline.png", "command": "underline"},
					{"id": "strikethrough", "type": "button", "title": "Strike-through", "icon": "/cms/supra/img/htmleditor/icon-strikethrough.png", "command": "strikethrough"},
					{"type": "separator"},
					{"id": "p", "type": "button", "title": "Paragraph", "icon": "/cms/supra/img/htmleditor/icon-p.png", "command": "p"},
					{"id": "h1", "type": "button", "title": "Heading 1", "icon": "/cms/supra/img/htmleditor/icon-h1.png", "command": "h1"},
					{"id": "h2", "type": "button", "title": "Heading 2", "icon": "/cms/supra/img/htmleditor/icon-h2.png", "command": "h2"},
					{"id": "h3", "type": "button", "title": "Heading 3", "icon": "/cms/supra/img/htmleditor/icon-h3.png", "command": "h3"},
					{"id": "h4", "type": "button", "title": "Heading 4", "icon": "/cms/supra/img/htmleditor/icon-h4.png", "command": "h4"},
					{"id": "h5", "type": "button", "title": "Heading 5", "icon": "/cms/supra/img/htmleditor/icon-h5.png", "command": "h5"},
					{"type": "separator"},
					{"id": "ul", "type": "button", "title": "Ordered list", "icon": "/cms/supra/img/htmleditor/icon-ul.png", "command": "ul"},
					{"id": "ol", "type": "button", "title": "Ordered list", "icon": "/cms/supra/img/htmleditor/icon-ol.png", "command": "ol"},
					{"type": "separator"},
					{"id": "style", "type": "dropdown", "title": "Style", "command": "style"}
				]
			}
		]
	};
	
	function HTMLEditorToolbar (config) {
		this.controls = {};
		this.groupNodes = {};
		
		HTMLEditorToolbar.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	HTMLEditorToolbar.NAME = 'editor-toolbar';
	HTMLEditorToolbar.CLASS_NAME = Y.ClassNameManager.getClassName(HTMLEditorToolbar.NAME);
	HTMLEditorToolbar.ATTRS = {
		'editor': null,
		'disabled': {
			value: false,
			setter: '_setDisabled'
		}
	};
	
	Y.extend(HTMLEditorToolbar, Y.Widget, {
		
		/**
		 * List of group nodes
		 * @type {Object}
		 * @private
		 */
		groupNodes: {},
		
		/**
		 * List of all controls
		 * @type {Object}
		 * @private
		 */
		controls: {},
		
		/**
		 * List of controls and their disabled states
		 * before settings 'disabled' on toolbar
		 */
		previousControlStates: null,
		
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			var r = HTMLEditorToolbar.superclass.bindUI.apply(this, arguments);
			
			return r;
		},
		
		/**
		 * Returns control
		 * 
		 * @param {String} id Control ID
		 * @return Control element or null if not found
		 * @type {Object}
		 */
		getControl: function (id) {
			return (id in this.controls ? this.controls[id] : null);
		},
		
		/**
		 * Returns button, alias of getControl
		 */
		getButton: function (id) {
			return this.getControl(id);
		},
		
		/**
		 * Add control to toolbar
		 * 
		 * @param {String} group_id Tab ID
		 * @param {Object} data Control element data
		 * @return Y.Node or Supra.Button instance for created element 
		 * @type {Object}
		 */
		addControl: function (group_id, data, options) {
			if (!data || !group_id) return;
			if (typeof group_id == 'string' && !(group_id in this.groupNodes)) return;
			
			var cont = typeof group_id == 'string' ? this.groupNodes(group_id) : group_id,
				label,
				node,
				node_source,
				first = options && 'first' in options ? options.first : true,
				last = options && 'last' in options ? options.last : true;
			
			switch(data.type) {
				case 'separator':
					node = Y.Node.create('<div class="yui3-toolbar-separator"></div>');
					cont.append(node);
					break;
				case 'dropdown':
					label = Y.Node.create('<label class="yui3-toolbar-label">' + Y.Lang.escapeHTML(data.title) + ':</label>');
					node = Y.Node.create('<select></select>');
					cont.append(label);
					cont.append(node);
					
					break;
				case 'button':
				default:
					node = new Supra.Button({"label": data.title, "icon": data.icon, "type": data.buttonType || "push", "style": "group"});
					node.render(cont);
					
					node.on('click', function (evt, data) {
						this.fire('command', data);
						
						evt.preventDefault();
						evt.stopPropagation();
						return false;
					}, this, data);
					
					if (!options || !('first' in options)) {
						//Check if this is first button
						node_previous = node.get('boundingBox').previous();
						if (node_previous) {
							if (node_previous.hasClass('yui3-button-last')) {
								node_previous.removeClass('yui3-button-last');
								first = false;
							}
						}
					}
					
					if (first) node.addClass('yui3-button-first');
					if (last) node.addClass('yui3-button-last');
					
					break;
			}
			
			if (node && data.id) {
				this.controls[data.id] = node;
			}
			
			return node;
		},
		
		/**
		 * Add control to toolbar, alias of addControl
		 */
		addButton: function (group_id, data, options) {
			return this.addControl(group_id, data, options);
		},
		
		/**
		 * Render all groups and controls
		 */
		renderUI: function () {
			var r = HTMLEditorToolbar.superclass.renderUI.apply(this, arguments);
			
			var groups = BUTTONS_DEFAULT.groups;
			for(var i=0,ii=groups.length; i<ii; i++) {
				//Create tab
				var cont = this.groupNodes[groups[i].id] = Y.Node.create('<div class="yui3-editor-toolbar-' + groups[i].id + ' yui3-editor-toolbar-' + groups[i].id + '-hidden"></div>').appendTo(this.get('contentBox')),
					first = true,
					nextFirst = false,
					last = false;
				
				if (!('controls' in groups[i])) continue;
				
				//Create controls
				for(var k=0,kk=groups[i].controls.length; k<kk; k++) {
					var data = groups[i].controls[k];
					
					switch (data.type) {
						case 'separator':
							first = true;
							nextFirst = true;
							break;
						case 'dropdown':
							first = true;
							nextFirst = true;
							break;
						case 'button':
						default:
							last = k < kk-1 ? (groups[i].controls[k+1].type != 'button' ? true : false ) : true;
							nextFirst = false;
							break;
					}
					
					this.addControl(cont, data, {
						'first': first,
						'last': last
					});
					
					first = nextFirst;
				}
				
			}
			
			this.fire('resize');
			
			return r;
		},
		
		/**
		 * Enable or disable all buttons
		 */
		_setDisabled: function (disabled) {
			var controls = this.controls,
				states = {};
			
			if (disabled) {
				for(var id in controls) {
					if (controls[id].get('disabled')) {
						states[id] = true;
					} else {
						controls[id].set('disabled', true);
						states[id] = false;
					}
				}
				
				this.previousControlStates = states;
			} else {
				states = this.previousControlStates;
				
				for(var id in states) {
					if (id in controls) {
						controls[id].set('disabled', states[id]);
					}
				}
				
				this.previousControlStates = {};
			}
		}
		
	});
	
	Supra.HTMLEditorToolbar = HTMLEditorToolbar;
	
	
}, YUI.version, {requires:['widget', 'supra.panel', 'supra.button', 'supra.editor-toolbar-css']});