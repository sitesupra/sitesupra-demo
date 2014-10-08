YUI().add('supra.htmleditor-toolbar', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var CONTROLS = {
		groups: [
			{
				"id": "main",
				"autoVisible": true, // always visible
				"animate": false, // never animate slide in/slide out
				"height": 48,
				"controls": [
					{"id": "source", "type": "button", "buttonType": "button", "icon": "/public/cms/supra/img/htmleditor/icon-source.png", "command": "source", "visible": false},
					{"type": "separator"},
					//{"id": "fullscreen", "type": "button", "buttonType": "toggle", "icon": "/public/cms/supra/img/htmleditor/icon-fullscreen.png", "command": "fullscreen"},
					//{"type": "separator"},
					{"id": "settings", "type": "button", "buttonType": "toggle", "icon": "/public/cms/supra/img/htmleditor/icon-settings.png", "command": "settings"}
				]
			},
			{
				"id": "text",
				"autoVisible": true, // always visible
				"animate": true,
				"height": 42,
				"controls": [
						{"id": "style", "type": "button", "command": "style", "icon": "/public/cms/supra/img/htmleditor/icon-style.png", "visible": false},
					{"type": "separator"},
						{"id": "fonts", "type": "button", "command": "fonts", "icon": "/public/cms/supra/img/htmleditor/icon-fonts.png", "visible": false},
					{"type": "separator"},
						{"id": "fontsize", "type": "dropdown", "command": "fontsize", "visible": false, "values": [
							{"id": 6, "title": "6"},
							{"id": 8, "title": "8"},
							{"id": 9, "title": "9"},
							{"id": 10, "title": "10"},
							{"id": 11, "title": "11"},
							{"id": 12, "title": "12"},
							{"id": 13, "title": "13"},
							{"id": 14, "title": "14"},
							{"id": 15, "title": "15"},
							{"id": 16, "title": "16"},
							{"id": 18, "title": "18"},
							{"id": 24, "title": "24"},
							{"id": 30, "title": "30"},
							{"id": 36, "title": "36"},
							{"id": 48, "title": "48"},
							{"id": 60, "title": "60"},
							{"id": 72, "title": "72"}
						]},
					{"type": "separator"},
						{"id": "forecolor", "type": "button", "command": "forecolor", "icon": "/public/cms/supra/img/htmleditor/icon-forecolor.png", "visible": false},
						{"id": "backcolor", "type": "button", "command": "backcolor", "icon": "/public/cms/supra/img/htmleditor/icon-backcolor.png", "visible": false},
					{"type": "separator"},
						{"id": "bold", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-bold.png", "command": "bold"},
						{"id": "italic", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-italic.png", "command": "italic"},
						{"id": "underline", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-underline.png", "command": "underline"},
						{"id": "strikethrough", "type": "button", "title": "Strike-through", "icon": "/public/cms/supra/img/htmleditor/icon-strikethrough.png", "command": "strikethrough"},
					{"type": "separator"},
						{"id": "align", "type": "dropdown", "command": "align", "style": "icons-text", "visible": false, "values": [
							{"id": "left", "title": "Left", "icon": "/public/cms/supra/img/htmleditor/align-left.png"},
							{"id": "center", "title": "Center", "icon": "/public/cms/supra/img/htmleditor/align-center.png"},
							{"id": "right", "title": "Right", "icon": "/public/cms/supra/img/htmleditor/align-right.png"},
							{"id": "justify", "title": "Justify", "icon": "/public/cms/supra/img/htmleditor/align-justify.png"}
						]},
					{"type": "separator"},
						{"id": "ul", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-ul.png", "command": "ul", "visible": false},
						{"id": "ol", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-ol.png", "command": "ol", "visible": false},
					{"type": "separator"},
						{"id": "indent",  "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-indent-in.png",  "command": "indent",  "visible": false},
						{"id": "outdent", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-indent-out.png", "command": "outdent", "visible": false},
					{"type": "separator"},
						{"id": "insertlink", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-insertlink.png", "command": "insertlink", "visible": false},
					{"type": "separator"},
						{"id": "insert", "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-insert.png", "command": "insert", "visible": false}
				]
			},
			{
				"id": "insert",
				"autoVisible": false, // visible only when needed
				"visible": false,
				"animate": true,
				"height": 42,
				"controls": [
						{"id": "insertimage", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-image.png", "command": "insertimage", "visible": false},
					{"type": "separator"},
						{"id": "inserticon", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-icon.png", "command": "inserticon", "visible": false},
					{"type": "separator"},
						{"id": "insertvideo", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-video.png", "command": "insertvideo", "visible": false},
					{"type": "separator"},
						{"id": "inserttable", "type": "button", "icon": "/public/cms/supra/img/htmleditor/icon-table.png", "command": "inserttable"}
				]
			},
			{
				"id": "table",
				"autoVisible": false, // visible only when needed
				"visible": false,
				"animate": true,
				"height": 42,
				"controls": [
						{"id": "row-before",  "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-row-before.png",  "command": "row-before"},
						{"id": "row-delete",  "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-row-delete.png",  "command": "row-delete"},
						{"id": "row-after",   "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-row-after.png",  "command": "row-after"},
					{"type": "separator"},
						{"id": "merge",  "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-cell-merge.png",  "command": "merge-cells"},
					{"type": "separator"},
						{"id": "column-before",  "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-column-before.png",  "command": "column-before"},
						{"id": "column-delete",  "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-column-delete.png",  "command": "column-delete"},
						{"id": "column-after",   "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-column-after.png",  "command": "column-after"},
					{"type": "separator"},
						{"id": "table-settings",   "type": "button", "buttonType": "push", "command": "table-settings", "style": "small"}
				]
			},
			{
				"id": "itemlist",
				"autoVisible": false, // visible only when needed
				"visible": false,
				"animate": true,
				"height": 42,
				"controls": [
					{"id": "itemlist-row-before",  "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-row-before.png",  "command": "itemlist-before"},
					{"id": "itemlist-row-delete",  "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-row-delete.png",  "command": "itemlist-delete"},
					{"id": "itemlist-row-after",   "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-row-after.png",  "command": "itemlist-after"},
					{"id": "itemlist-column-before",  "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-column-before.png",  "command": "itemlist-before", "visible": false},
					{"id": "itemlist-column-delete",  "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-column-delete.png",  "command": "itemlist-delete", "visible": false},
					{"id": "itemlist-column-after",   "type": "button", "buttonType": "push", "icon": "/public/cms/supra/img/htmleditor/icon-table-column-after.png",  "command": "itemlist-after", "visible": false}
				]
			}
		]
	};
	
	var TEMPLATE_GROUP = '<div class="yui3-editor-toolbar-{id} hidden">\
							<div class="yui3-editor-toolbar-{id}-content"></div>\
						  </div>';
	
	var ANIMATION_DURATION = 0.3;
	
	function HTMLEditorToolbar (config) {
		this.controls = {};
		this.groups = {};
		this.groupOrder = [];
		
		HTMLEditorToolbar.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	HTMLEditorToolbar.NAME = 'editor-toolbar';
	HTMLEditorToolbar.CLASS_NAME = Y.ClassNameManager.getClassName(HTMLEditorToolbar.NAME);
	HTMLEditorToolbar.ATTRS = {
		'editor': {
			value: null
		},
		'disabled': {
			value: false,
			setter: '_setDisabled'
		},
		'controls': {
			value: CONTROLS
		}
	};
	
	HTMLEditorToolbar.CONTROLS = CONTROLS;
	
	Y.extend(HTMLEditorToolbar, Y.Widget, {
		
		/**
		 * List of groups
		 * @type {Object}
		 * @private
		 */
		groups: {},
		
		/**
		 * Visible group order
		 * @type {Object}
		 * @private
		 */
		groupOrder: [],
		
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
		
		
		/* ---------------------------- Height ------------------------------ */
		
		
		/**
		 * Force leyout update
		 */
		syncLayout: function () {
			var action = Supra.Manager.getAction('LayoutTopContainer');
			if (action.get('executed')) action.syncLayout();
		},
		
		/**
		 * Calculate content height
		 * 
		 * @return Toolbar content height
		 * @type {Number}
		 * @private
		 */
		calculateContentHeight: function () {
			var groups = this.groups,
				height = 0,
				id = null;
			
			for (var id in groups) {
				if (groups[id].visible && id != 'main') { // "main" is outside content
					height += groups[id].height;
				}
			}
			
			return height;
		},
		
		/**
		 * Update content height
		 * 
		 * @private
		 */
		updateContentHeight: function () {
			var height = this.calculateContentHeight(),
				node = this.get('contentBox');
			
			node.setStyle('height', height + 'px');
			
			this.syncLayout();
			this.fire('contentResize');
		},
		
		/**
		 * Returns group node top position in which it should be
		 * 
		 * @param {String} group_id Group ID
		 * @return Group top position
		 * @type {Number}
		 */
		getGroupPosition: function (group_id) {
			var groups = this.groups,
				groupOrder = this.groupOrder,
				i = 0,
				ii = groupOrder.length,
				top = 0;
			
			for (; i<ii; i++) {
				if (groupOrder[i] == group_id) return top;
				if (groupOrder[i] != 'main') { // "main" is outside content
					top += groups[groupOrder[i]].height;
				}
			}
			
			return top;
		},
		
		
		/* ---------------------------- Controls ------------------------------ */
		
		
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
		 * Returns all controls in group
		 * 
		 * @param {String} group_id Group ID
		 * @returns {Array} List of group controls
		 */
		getControlsInGroup: function (group_id) {
			var groups = this.get('controls').groups,
				i = 0,
				ii = groups.length,
				controls = null,
				result = [];
				
			for (; i<ii; i++) {
				if (groups[i].id === group_id) {
					controls = groups[i].controls;
					
					if (controls) {
						for (i=0,ii=controls.length; i<ii; i++) {
							if (controls[i].type !== 'separator') {
								result.push(controls[i]);
							}
						}
					}
					
					break;
				}
			}
			
			return result;
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
			if (typeof group_id == 'string' && !(group_id in this.groups)) return;
			
			var cont = typeof group_id == 'string' ? this.groups[group_id].node.one('div') : group_id,
				label,
				title,
				node,
				node_source,
				node_previous,
				first = options && 'first' in options ? options.first : true,
				last = options && 'last' in options ? options.last : true,
				visible = data.visible === false ? false : true;
			
			switch(data.type) {
				case 'separator':
					node = Y.Node.create('<div class="yui3-toolbar-separator"></div>');
					cont.append(node);
					break;
				case 'dropdown':
					node = Y.Node.create('<select></select>');
					cont.append(node);
					node = new Supra.Input.Select({
						'srcNode': node,
						'scrollable': false,
						'values': data.values,
						'textRenderer': data.style == 'icons-text' ? function (item) { return '<span class="icon-crop"><img src="' + item.icon + '" /></span>'; } : null,
						'visible': visible
					});
					node.render();
					break;
				case 'button':
				default:
					title = data.title || Y.Escape.html(Supra.Intl.get(['htmleditor', data.id]));
					node = new Supra.Button({"label": title, "icon": data.icon, "type": data.buttonType || "toggle", "style": data.style || "toolbar", "visible": visible});
					node.ICON_TEMPLATE = '<span class="img"><img src="" alt="" /></span>';
					node.render(cont);
					
					node.on('click', function (evt, data) {
						if (!node.get('disabled')) {
							this.fire('command', data);
						}
						
						evt.preventDefault();
						evt.stopPropagation();
						return false;
					}, this, data);
					
					if (!options || !('first' in options)) {
						//Check if this is first button
						node_previous = node.get('boundingBox').previous();
						if (node_previous) {
							if (node_previous.hasClass('su-button-last')) {
								node_previous.removeClass('su-button-last');
								first = false;
							}
						}
					}
					
					if (first) node.addClass('su-button-first');
					if (last) node.addClass('su-button-last');
					
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
			HTMLEditorToolbar.superclass.renderUI.apply(this, arguments);
			
			var groups = this.get('controls').groups,
				groupList = this.groups = {},
				id = null,
				index = 0;
			
			for(var i=0,ii=groups.length; i<ii; i++) {
				id = groups[i].id;
				
				groupList[id] = {
					'node': null,
					'visible': false,
					'height': groups[i].height,
					'animate': groups[i].animate,
					'autoVisible': groups[i].autoVisible,
					'index': null
				};
				
				//Create tab
				var template = Y.substitute(TEMPLATE_GROUP, {'id': id}),
					cont = groupList[id].node = Y.Node.create(template),
					first = true,
					nextFirst = false,
					last = false;
					
				this.get('contentBox').prepend(cont);
				
				//Use content
				cont = cont.one('div');
				
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
		},
		
		
		/* ---------------------------- Visibility ------------------------------ */
		
		
		/**
		 * Returns true if group is visible, otherwise false
		 * 
		 * @param {String} group_id Group ID
		 * @returns {Boolean} True if group is visible, otherwise false
		 */
		isGroupVisible: function (group_id) {
			var group = this.groups[group_id];
			
			if (group) {
				return group.visible;
			} else {
				return false;
			}
		},
		
		/**
		 * Show group
		 * 
		 * @param {String} group_id Group ID
		 * @param {Boolean} silent In silent mode content height is not updated
		 */
		showGroup: function (group_id, silent) {
			var group = this.groups[group_id],
				groupOrder = this.groupOrder,
				position = null;
			
			if (group && !group.visible) {
				
				group.visible = true;
				group.node.removeClass('hidden');
				group.index = groupOrder.length;
				groupOrder.push(group_id);
				
				if (group.animate) {
					position = this.getGroupPosition(group_id);
					group.node.setStyle('top', position - 48 + 'px');
					group.node.transition({
						'top': position + 'px',
						'easing': 'ease-out',
						'duration': ANIMATION_DURATION
					});
				} else if (group_id == 'main') {
					//Main slide has special animation
					group.node.one('div').transition({
						'duration': ANIMATION_DURATION,
						'easing': 'ease-out',
						'marginTop': '0px'
					});
				}
				
				if (!silent) this.updateContentHeight();
			}
		},
		
		/**
		 * Hide group
		 * 
		 * @param {String} group_id Group name
		 * @param {Boolean} silent In silent mode content height is not updated
		 */
		hideGroup: function (group_id, silent, after) {
			var groups = this.groups,
				group = groups[group_id],
				groupOrder = this.groupOrder,
				position = null;
			
			if (group && group.visible) {
				groupOrder.splice(group.index, 1);
				
				group.index = null;
				group.visible = false;
				
				if (group.animate) {
					position = this.getGroupPosition(group_id);
					
					group.node.transition({
						'top': position - 48 + 'px',
						'easing': 'ease-out',
						'duration': ANIMATION_DURATION
					}, Y.bind(function () {
						group.node.addClass('hidden');
						if (!silent) this.updateContentHeight();
						if (Y.Lang.isFunction(after)) after();
					}, this));
					
				} else if (group_id == 'main') {
					
					//Main slide has special animation
					group.node.one('div').transition({
						'duration': ANIMATION_DURATION,
						'easing': 'ease-out',
						'marginTop': '50px'
					}, Y.bind(function () {
						group.node.addClass('hidden');
						if (!silent) this.updateContentHeight();
						if (Y.Lang.isFunction(after)) after();
					}, this));
					
				} else {
					
					if (!silent) this.updateContentHeight();
					if (Y.Lang.isFunction(after)) after();
					
				}
			}
		},
		
		/**
		 * Handle visibility change
		 * @param {Object} visible
		 */
		_uiSetVisible: function (visible) {
			if (visible) {
				this.get('boundingBox').removeClass(this.getClassName('hidden'));
				
				var group_proto = this.get('controls').groups,
					groups = this.groups,
					id = null,
					i = 0,
					ii = group_proto.length,
					show = [];
				
				for (; i<ii; i++) {
					id = group_proto[i].id;
					if (groups[id].autoVisible) show.push(id);
				}
				
				for (i=0,ii=show.length; i<ii; i++) {
					this.showGroup(show[i], i != ii - 1);
				}
			} else {
				var groupOrder = this.groupOrder,
					i = groupOrder.length - 1,
					fn = Y.bind(function () { 
							this.get('boundingBox').addClass(this.getClassName('hidden'));
						 }, this);
				
				for (; i >= 0; i--) {
					this.hideGroup(groupOrder[i], i != 0, i == 0 ? fn : null);
				}
			}
		}
		
	});
	
	Supra.HTMLEditorToolbar = HTMLEditorToolbar;
	
	
}, YUI.version, {requires:['widget', 'supra.panel', 'supra.button', 'transition']});