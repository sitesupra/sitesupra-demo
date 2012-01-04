YUI().add('supra.htmleditor-plugin-table', function (Y) {
	
	//Constants
	var HTMLEDITOR_COMMAND = 'inserttable',
		HTMLEDITOR_BUTTON  = 'inserttable';
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [SU.HTMLEditor.MODE_SIMPLE, SU.HTMLEditor.MODE_RICH]
	};
	
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	SU.HTMLEditor.addPlugin('table', defaultConfiguration, {
		
		settings_form: null,
		selected_cell: null,
		selected_table: null,
		original_data: null,
		data: null,
		silent: false,
		
		buttons: null,
		
		/**
		 * Footer node
		 * @type {Object}
		 * @private
		 */
		footer: null,
		
		/**
		 * Generate settings form
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').one();
			if (!content) return;
			
			
			//Remove table from "style" plugin list, because this plugin will manage TABLE classnames
			var plugin = this.htmleditor.getPlugin('style');
			if (plugin) {
				plugin.excludeTag('table');
			}
			
			//Get styles
			var styles = this.getTableStyles();
			
			//Properties form
			var form_config = {
				'inputs': [
					{'id': 'style', 'type': 'Select', 'label': Supra.Intl.get(['htmleditor', 'table_style']), 'value': '', 'values': styles}
				],
				'style': 'vertical'
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.get('boundingBox').addClass('yui3-form-properties')
									   .addClass('yui3-sidebar-content')
									   .addClass('scrollable')
									   .addClass('has-footer');
				form.hide();
			
			//On style change update table
			for(var i=0,ii=form_config.inputs.length; i<ii; i++) {
				form.getInput(form_config.inputs[i].id).on('change', this.onPropertyChange, this);
			}
			
			//Form heading
			var heading = Y.Node.create('<h2>' + Supra.Intl.get(['htmleditor', 'table_properties']) + '</h2>');
			form.get('contentBox').insert(heading, 'before');
			
			//Insert row before, after, etc.
			var button_list = {};
			var node_group = Y.Node.create('<div class="yui3-button-group-list"></div>');
			form.get('contentBox').append(node_group);
			
			var btn = new Supra.Button({'label': Supra.Intl.get(['htmleditor', 'insert_row_before']), 'style': 'group', 'icon': '/cms/lib/supra/img/htmleditor/table-row-before.png'});
				btn.addClass('yui3-button-first');
				btn.render(node_group).on('click', this.cmdRowBefore, this);
				button_list.rowBefore = btn;
			
			var btn = new Supra.Button({'label': Supra.Intl.get(['htmleditor', 'delete_row']), 'style': 'group', 'icon': '/cms/lib/supra/img/htmleditor/table-row-delete.png'});
				btn.render(node_group).on('click', this.cmdRowDelete, this);
				button_list.rowDelete = btn;
			
			var btn = new Supra.Button({'label': Supra.Intl.get(['htmleditor', 'insert_row_after']), 'style': 'group', 'icon': '/cms/lib/supra/img/htmleditor/table-row-after.png'});
				btn.addClass('yui3-button-last');
				btn.render(node_group).on('click', this.cmdRowAfter, this);
				button_list.rowAfter = btn;
			
			var btn = new Supra.Button({'label': Supra.Intl.get(['htmleditor', 'merge_cells']), 'style': 'group', 'icon': '/cms/lib/supra/img/htmleditor/table-merge.png'});
				btn.addClass('yui3-button-first');
				btn.addClass('yui3-button-last');
				btn.render(node_group).on('click', this.cmdMergeCells, this);
				button_list.mergeCells = btn;
			
			node_group = Y.Node.create('<div class="yui3-button-group-list"></div>');
			form.get('contentBox').append(node_group);
			
			var btn = new Supra.Button({'label': Supra.Intl.get(['htmleditor', 'insert_col_before']), 'style': 'group', 'icon': '/cms/lib/supra/img/htmleditor/table-col-before.png'});
				btn.addClass('yui3-button-first');
				btn.render(node_group).on('click', this.cmdColBefore, this);
				button_list.colBefore = btn;
			
			var btn = new Supra.Button({'label': Supra.Intl.get(['htmleditor', 'delete_col']), 'style': 'group', 'icon': '/cms/lib/supra/img/htmleditor/table-col-delete.png'});
				btn.render(node_group).on('click', this.cmdColDelete, this);
				button_list.colDelete = btn;
			
			var btn = new Supra.Button({'label': Supra.Intl.get(['htmleditor', 'insert_col_after']), 'style': 'group', 'icon': '/cms/lib/supra/img/htmleditor/table-col-after.png'});
				btn.addClass('yui3-button-last');
				btn.render(node_group).on('click', this.cmdColAfter, this);
				button_list.colAfter = btn;
			
			//Footer
			var footer = Y.Node.create('<div class="yui3-sidebar-footer hidden"></div>');
			this.footer = footer;
			form.get('boundingBox').insert(footer, 'after');
			
			form.on('visibleChange', function (e) {
				if (this.footer && e.newVal != e.prevVal && !e.newVal) {
					this.footer.addClass('hidden');
				}
			}, this);
			
			//Delete button
			var btn = new Supra.Button({'label': Supra.Intl.get(['buttons', 'delete']), 'style': 'mid-red'});
				btn.render(footer);
				btn.addClass('yui3-button-delete');
				btn.on('click', this.removeSelectedTable, this);
			
			this.buttons = button_list;
			this.settings_form = form;
			return form;
		},
		
		cmdRowBefore: function () {
			this.cmdRow('before');
		},
		cmdRowAfter: function () {
			this.cmdRow('after');
		},
		cmdRow: function (where) {
			var sel_td = this.selected_cell,
				sel_tr = sel_td.ancestor(),
				all_td = sel_tr.get('children'),
				new_tr = '',
				colspan = 1;
			
			for(var i=0,ii=all_td.size(); i<ii; i++) {
				colspan = parseInt(all_td.item(i).getAttribute('colspan'), 10) || 1;
				new_tr += '<td' + (colspan > 1 ? ' colspan="' + colspan +'"' : '') + '><br /></td>';
			}
			
			sel_tr.insert('<tr>' + new_tr + '</tr>', where);
		},
		cmdRowDelete: function () {
			this.selected_cell.ancestor().remove();
			this.selected_cell = this.selected_table.one('th,td');
			this.selected_cell.addClass('yui3-cell-selected');
		},
		
		cmdColBefore: function () {
			var sel_td = Y.Node.getDOMNode(this.selected_cell),
				index = this.getCellIndex(sel_td),
				table = this.selected_table,
				trs = Y.Node.getDOMNode(table).getElementsByTagName('TR');
			
			if (index > 0) {
				var cell = null,
					prevCell = null;
				
				for(var i=0,ii=trs.length; i<ii; i++) {
					//Find current and previous cell (by index) in given TR
					cell = this.getCellAtIndex(trs[i], index);
					prevCell = this.getCellAtIndex(trs[i], index - 1);
					
					if (prevCell === cell) {
						//If they match, then update colspan
						cell.setAttribute('colspan', this.getCellSpan(cell) + 1);
					} else {
						this.insertCell(trs[i], index);
					}
				}
			} else {
				//If inserting first cell then ignore colspan
				for(var i=0,ii=trs.length; i<ii; i++) {
					this.insertCell(trs[i], index);
				}
			}
		},
		cmdColAfter: function () {
			var sel_td = Y.Node.getDOMNode(this.selected_cell),
				index = this.getCellIndex(sel_td),
				table = this.selected_table,
				trs = Y.Node.getDOMNode(table).getElementsByTagName('TR'),
				length = this.getRowLength(trs[0]),
				targetcell = null;
			
			if (index + 1 < length) {
				var cell = null,
					prevCell = null;
				
				for(var i=0,ii=trs.length; i<ii; i++) {
					//Find current and next cell (by index) in given TR
					cell = this.getCellAtIndex(trs[i], index + 1);
					prevCell = this.getCellAtIndex(trs[i], index);
					if (prevCell === cell) {
						//If they match, then update colspan
						cell.setAttribute('colspan', this.getCellSpan(cell) + 1);
					} else {
						this.insertCell(trs[i], index + 1);
					}
				}
			} else {
				//If inserting last cell then ignore colspan
				for(var i=0,ii=trs.length; i<ii; i++) {
					this.insertCell(trs[i], index + 1);
				}
			}
		},
		
		cmdColDelete: function () {
			var sel_td = Y.Node.getDOMNode(this.selected_cell),
				index = this.getCellIndex(sel_td),
				table = this.selected_table,
				trs = Y.Node.getDOMNode(table).getElementsByTagName('TR'),
				td = null,
				colspan = 0;
			
			for(var i=0,ii=trs.length; i<ii; i++) {
				td = this.getCellAtIndex(trs[i], index);
				colspan = this.getCellSpan(td);
				if (colspan > 1) {
					td.setAttribute('colspan', colspan - 1);
				} else {
					td.parentNode.removeChild(td);
				}
			}
		},
		
		fixRangeContainer: function (container, offset) {
			if (container.nodeType != 1) container = container.parentNode;
			if (container.tagName != 'TD' && container.tagName != 'TH' && container.tagName != 'TR') {
				var node = new Y.Node(container);
					node = node.closest('TD,TH');
				
				container = node ? Y.Node.getDOMNode(node) : null;
				if (!container) return null;
			}
			
			if (container.tagName == 'TR') {
				container = container.childNodes[offset];
			}
			
			return container;
		},
		
		cmdMergeCells: function () {
			var win = this.htmleditor.get('win'), tr = null;
			if (win.getSelection) {
				var sel = win.getSelection(),
					tds = [],
					colspansum = 0,
					range = null,
					td = null,
					start_container = null,
					end_container = null;
				
				for(var i=0,ii=sel.rangeCount; i<ii; i++) {
					range = sel.getRangeAt(i);
					start_container = range.startContainer;
					end_container = range.endContainer;
					
					start_container = this.fixRangeContainer(start_container, range.startOffset);
					
					if (range.startContainer !== range.endContainer) {
						end_container = this.fixRangeContainer(end_container, range.endOffset);
					} else {
						end_container = start_container;
					}
					
					if (!tr) {
						tr = start_container.parentNode;
					}
					
					if (start_container.parentNode === tr && end_container.parentNode === tr) {
						while(start_container) {
							if (start_container.nodeType == 1) {
								tds.push(start_container);
								colspansum += this.getCellSpan(start_container);
							}
							if (start_container === end_container) break;
							start_container = start_container.nextSibling;
						}
					}
				}
				
				for(var i=0,ii=tds.length; i<ii; i++) {
					if (i == 0) {
						tds[i].setAttribute('colspan', colspansum);
					} else {
						tds[i].parentNode.removeChild(tds[i]);
					}
				}
			}
		},
		
		/**
		 * Insert cell at specific index
		 * If row consists of THs then insert TH instead of TD
		 * 
		 * @param {Object} tr
		 * @param {Object} index
		 */
		insertCell: function (tr, index) {
			//Insert new cell
			var td = document.createElement(tr.childNodes.length ? tr.childNodes[0].tagName : 'TD');
				td.innerHTML = '<br />';
			
			var cell = this.getCellAtIndex(tr, index);
			if (cell) {
				tr.insertBefore(td, cell);
			} else {
				tr.appendChild(td);
			}
		},
		
		/**
		 * Returns cell index (all previous cell colspan summ)
		 * 
		 * @param {HTMLElement} cell
		 * @return Cell index
		 * @type {Number}
		 */
		getCellIndex: function (cell) {
			var index = 0;
			
			cell = cell.previousSibling;
			while(cell) {
				if (cell.nodeType == 1) {
					index += this.getCellSpan(cell);
				}
				cell = cell.previousSibling;
			}
			
			return index;
		},
		
		getRowLength: function (tr) {
			var tds = tr.childNodes,
				length = 0;
			
			for(var i=0,ii=tds.length; i<ii; i++) {
				if (tds[i].nodeType == 1) length++;
			}
			
			return length;
		},
		
		/**
		 * Returns cell at index
		 * 
		 * @param {HTMLElement} tr
		 * @param {Number} index
		 * @return Cell
		 * @type {HTMLElement}
		 */
		getCellAtIndex: function (tr, index) {
			var tds = tr.childNodes,
				curindex = 0;
			
			for(var i=0,ii=tds.length; i<ii; i++) {
				if (tds[i].nodeType == 1) {
					curindex += this.getCellSpan(tds[i]);
					if (curindex > index) return tds[i];
				}
			}
			
			return null;
		},
		
		/**
		 * Returns cells colspan
		 * 
		 * @param {HTMLElement} cell
		 * @return Cell colspan
		 * @type {Number}
		 */
		getCellSpan: function (cell) {
			return parseInt(cell.getAttribute('colspan'), 10) || 1;
		},
		
		/**
		 * Returns TD index
		 * 
		 * @param {HTMLElement} cell
		 * @return TD index
		 * @type {Number}
		 */
		getTDIndex: function (cell) {
			var index = 0;
			
			cell = cell.previousSibling;
			while(cell) {
				if (cell.nodeType == 1) {
					index++;
				}
				cell = cell.previousSibling;
			}
			
			return index;
		},
		
		/*
		getCellAtIndex: function () {
			
		},
		*/
		
		/**
		 * Handle property input value change
		 * Save data and update UI
		 * 
		 * @param {Object} event Event
		 */
		onPropertyChange: function (event) {
			if (this.silent || !this.selected_table) return;
			
			var target = event.target,
				id = target.get('id'),
				value = this.settings_form.getInput(id).get('value');
			
			this.setProperty(id, value);
		},
		
		/**
		 * Set property value, update UI
		 * 
		 * @param {String} id
		 * @param {String} value
		 */
		setProperty: function (id, value) {
			//Update table style
			if (id == 'style') {
				var styles = this.getTableStyles();
				for(var i=0,ii=styles.length; i<ii; i++) {
					this.selected_table.removeClass(styles[i].id);
				} 
				this.selected_table.addClass(value);
				this.data.style = value;
			}
		},
		
		/**
		 * Returns true if form is visible, otherwise false
		 */
		hideSettingsForm: function () {
			if (this.settings_form && this.settings_form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * Apply settings changes
		 */
		settingsFormApply: function () {
			if (this.selected_table) {
				
				this.selected_table.removeClass('yui3-table-selected');
				this.selected_cell.removeClass('yui3-cell-selected');
				
				this.selected_table = null;
				this.selected_cell = null;
				this.original_data = null;
				this.data = null;
				
				this.hideSettingsForm();
				
				//Property changed, update editor 'changed' state
				this.htmleditor._changed();
			}
		},
		
		/**
		 * Remove selected image
		 */
		removeSelectedTable: function () {
			if (this.selected_table) {
				this.selected_table.remove();
				this.selected_table = null;
				this.selected_cell = null;
				this.original_data = null;
				this.data = null;
				this.htmleditor.refresh(true);
				this.hideSettingsForm();
			}
		},
		
		/**
		 * Returns all table styles
		 */
		getTableStyles: function () {
			var style_plugin = this.htmleditor.getPlugin('style'),
				styles = style_plugin.getSelectors('table', true, true),	//Get all table styles
				list = [{'id': '', 'title': ''}];
			
			for(var i=0,ii=styles.length; i<ii; i++) {
				list.push({
					'id': styles[i].classname,
					'title': styles[i].attributes.title
				});
			}
			
			return list;
		},
		
		/**
		 * Show table settings bar
		 */
		showTableSettings: function (event) {
			Manager.executeAction('PageContentSettings', this.settings_form || this.createSettingsForm(), {
				'doneCallback': Y.bind(this.settingsFormApply, this)
			});
			
			this.footer.removeClass('hidden');
			
			this.selected_table = event.target.closest('table');
			this.selected_cell = event.target.closest('td,th');
			this.selected_cell.addClass('yui3-cell-selected');
			this.selected_table.addClass('yui3-table-selected');
			
			//Find current style
			var styles = this.getTableStyles();
			var	data = {
				'style': ''
			};
			for(var i=0,ii=styles.length; i<ii; i++) {
				if (this.selected_table.hasClass(styles[i].id)) {
					data.style = styles[i].id;
					break;
				}
			}
			
			//Reset form
			this.silent = true;
			this.settings_form.resetValues()
							  .setValues(data, 'id');
			this.silent = false;
			
			//Clone data because data properties will change and orginal properties should stay intact
			this.original_data = Supra.mix({}, data);
			this.data = data;
			
			if (event.halt) event.halt();
		},
		
		/**
		 * Insert table
		 */
		insertTable: function (values) {
			var htmleditor = this.htmleditor;
			
			if (!htmleditor.get('disabled') && htmleditor.isSelectionEditable(htmleditor.getSelection())) {
				var styles = this.getTableStyles(),	//Get all table styles
					classname = styles.length ? styles[0].id : '',
					
					html_row = '<tr><td><br /></td><td><br /></td><td><br /></td></tr>',
					html_table = '<table class="' + classname + '"><tbody><tr><th><br /></th><th><br /></th><th><br /></th></tr>' + html_row + html_row + '</tbody></table>';
				
				//Replace selection with table
				var node = htmleditor.replaceSelection(html_table);
				
				if (node) {
					node = (new Y.Node(node)).one('th,th');
					this.showTableSettings({'target': node});
				}
				
				//Set changed event
				htmleditor._changed();
			}
		},
		
		/**
		 * Disable table handles, FF
		 */
		disableInlineTableEditing: function () {
			window.htmleditor = this.htmleditor;
			try {
				this.htmleditor.get('doc').execCommand("enableInlineTableEditing", false, false);
			} catch (err) {}
		},
		
		/**
		 * On node change check if settings form needs to be hidden
		 */
		onNodeChange: function () {
			var element = this.htmleditor.getSelectedElement('td,th,table');
			
			if (element) {
				var element = new Y.Node(element),
					table = element.closest('table');
				
				if (this.selected_table && !table.compareTo(this.selected_table)) {
					this.settingsFormApply();
				}
				
				if (this.selected_table) {
					if (this.selected_cell) {
						this.selected_cell.removeClass('yui3-cell-selected');
					}
					this.selected_cell = element;
					this.selected_cell.addClass('yui3-cell-selected');
				} else {
					this.showTableSettings({'target': element});
				}
			} else if (this.selected_table) {
				this.settingsFormApply();
			}
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			
			// Add command
			htmleditor.addCommand(HTMLEDITOR_COMMAND, Y.bind(this.insertTable, this));
			
			var button = this.getButton();
			if (button) {
				//When un-editable node is selected disable toolbar button
				htmleditor.on('editingAllowedChange', function (event) {
					button.set('disabled', !event.allowed);
				});
			}
			
			//When image looses focus hide settings form
			htmleditor.on('nodeChange', this.onNodeChange, this);
			
			// When clicking on table show settings
			var container = htmleditor.get('srcNode');
			container.delegate('click', Y.bind(this.showTableSettings, this), 'table');
			
			//Disable inline table insert row/column, delete row/column
			this.disableInlineTableEditing();
			
			//On editing allowed change disable controls
			htmleditor.on('editingAllowedChange', this.disableInlineTableEditing, this);
		},
		
		getButton: function () {
			var toolbar = this.htmleditor.get('toolbar');
			return toolbar ? toolbar.getButton(HTMLEDITOR_BUTTON) : null;
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});