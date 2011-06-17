YUI().add('supra.editor-toolbar', function (Y) {
	
	var BUTTONS_DEFAULT = {
		groups: [
			{
				"id": "text",
				"title": "Text",
				"icon": "/cms/content-manager/editortoolbar/img/icon-text.png",
				"controls": [
					{"id": "bold", "type": "button", "title": "Bold", "icon": "/cms/content-manager/editortoolbar/img/icon-bold.png", "command": "bold"},
					{"id": "italic", "type": "button", "title": "Italic", "icon": "/cms/content-manager/editortoolbar/img/icon-italic.png", "command": "italic"},
					{"id": "underline", "type": "button", "title": "Underline", "icon": "/cms/content-manager/editortoolbar/img/icon-underline.png", "command": "underline"},
					{"id": "strikethrough", "type": "button", "title": "Strike-through", "icon": "/cms/content-manager/editortoolbar/img/icon-strikethrough.png", "command": "strikethrough"},
					{"type": "separator"},
					{"id": "insertlink", "type": "button", "title": "Insert link", "icon": "/cms/content-manager/editortoolbar/img/icon-insertlink.png", "command": "insertlink"},
					{"type": "separator"},
					{"id": "p", "type": "button", "title": "Paragraph", "icon": "/cms/content-manager/editortoolbar/img/icon-p.png", "command": "p"},
					{"id": "h1", "type": "button", "title": "Heading 1", "icon": "/cms/content-manager/editortoolbar/img/icon-h1.png", "command": "h1"},
					{"id": "h2", "type": "button", "title": "Heading 2", "icon": "/cms/content-manager/editortoolbar/img/icon-h2.png", "command": "h2"},
					{"id": "h3", "type": "button", "title": "Heading 3", "icon": "/cms/content-manager/editortoolbar/img/icon-h3.png", "command": "h3"},
					{"id": "h4", "type": "button", "title": "Heading 4", "icon": "/cms/content-manager/editortoolbar/img/icon-h4.png", "command": "h4"},
					{"id": "h5", "type": "button", "title": "Heading 5", "icon": "/cms/content-manager/editortoolbar/img/icon-h5.png", "command": "h5"},
					{"type": "separator"},
					{"id": "ul", "type": "button", "title": "Ordered list", "icon": "/cms/content-manager/editortoolbar/img/icon-ul.png", "command": "ul"},
					{"id": "ol", "type": "button", "title": "Ordered list", "icon": "/cms/content-manager/editortoolbar/img/icon-ol.png", "command": "ol"},
					{"type": "separator"},
					{"id": "style", "type": "dropdown", "title": "Style", "command": "style"}
				]
			},
			{
				"id": "insert",
				"title": "Insert",
				"icon": "/cms/content-manager/editortoolbar/img/icon-insert.png",
				"controls": [
					{"id": "image", "type": "button", "title": "Image", "icon": "/cms/content-manager/editortoolbar/img/icon-image.png", "command": "image"}/*,
					{"id": "table", "type": "button", "title": "Table", "icon": "/cms/content-manager/editortoolbar/img/icon-table.png", "command": "table"},
					{"id": "link-file", "type": "button", "title": "Link to file", "command": "link-file"},
					{"id": "link-page", "type": "button", "title": "Link to page", "command": "link-page"}*/
				]
			},{
				"id": "settings",
				"title": "Settings",
				"icon": "/cms/content-manager/editortoolbar/img/icon-settings.png"
			}
		]
	};
	
	function EditorToolbar (config) {
		this.tabs = null;
		
		EditorToolbar.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	EditorToolbar.NAME = 'editor-toolbar';
	EditorToolbar.CLASS_NAME = Y.ClassNameManager.getClassName(EditorToolbar.NAME);
	EditorToolbar.ATTRS = {
		'editor': null
	};
	
	Y.extend(EditorToolbar, Y.Widget, {
		
		tabs: null,
		controls: {},
		
		bindUI: function () {
			var r = EditorToolbar.superclass.renderUI.apply(this, arguments);
			
			this.tabs.on('activeTabChange', function () {
				this.fire('resize');
			}, this);
			
			this.on('visibleChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					this.tabs.set('visible', evt.newVal);
				}
			}, this);
			
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
		 * @param {String} tab_id Tab ID
		 * @param {Object} data Control element data
		 * @return Y.Node or Supra.Button instance for created element 
		 * @type {Object}
		 */
		addControl: function (tab_id, data, options) {
			if (!data || !tab_id) return;
			if (typeof tab_id == 'string' && !this.tabs.hasTab(tab_id)) return;
			
			var cont = typeof tab_id == 'string' ? this.tabs.getTabContent(tab_id) : tab_id,
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
					node = new Supra.Button({"label": data.title, "icon": data.icon, "type": "button", "style": "group"});
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
		addButton: function (tab_id, data, options) {
			return this.addControl(tab_id, data, options);
		},
		
		/**
		 * Render all groups and controls
		 */
		renderUI: function () {
			var r = EditorToolbar.superclass.renderUI.apply(this, arguments);
			
			this.tabs = new Supra.Tabs({"style": "dark", "buttonStyle": "mid-large"});
			this.tabs.render(this.get('contentBox'));
			
			this.set('tabs', this.tabs);
			
			var groups = BUTTONS_DEFAULT.groups;
			for(var i=0,ii=groups.length; i<ii; i++) {
				//Create tab
				var cont = this.tabs.addTab({"id": groups[i].id, "title": groups[i].title, "icon": groups[i].icon, "visible": groups[i].visible}),
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
		}
		
	});
	
	Supra.EditorToolbar = EditorToolbar;
	
	
}, YUI.version, {requires:['widget', 'supra.panel', 'supra.button', 'supra.tabs', 'supra.editor-toolbar-css']});