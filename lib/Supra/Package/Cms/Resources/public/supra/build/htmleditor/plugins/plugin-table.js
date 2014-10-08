YUI().add('supra.htmleditor-plugin-table', function (Y) {
	
	//Constants
	var HTMLEDITOR_COMMAND = 'inserttable',
		HTMLEDITOR_SETTINGS_COMMAND = 'table-settings',
		HTMLEDITOR_BUTTON  = 'inserttable';
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	Supra.HTMLEditor.addPlugin('table', defaultConfiguration, {
		
		settings_form: null,
		selected_cell: null,
		selected_table: null,
		original_data: null,
		data: null,
		silent: false,
		
		/**
		 * Generate settings form
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').get('contentInnerNode');
			if (!content) return;
			
			
			//Remove table from "style" plugin list, because this plugin will manage TABLE classnames
			var plugin = this.htmleditor.getPlugin('style');
			if (plugin) {
				plugin.excludeTags(['table', 'tr', 'td', 'th']);
			}
			
			//Form
			var form_config = {
				'style': 'vertical',
				'inputs': [
					{'id': 'rows', 'label': 'Rows', 'type': 'Select', 'value': '3', 'values': [
							{'title': '1', 'id': '1'}, {'title': '2', 'id': '2'}, {'title': '3', 'id': '3'}, {'title': '4', 'id': '4'}, {'title': '5', 'id': '5'}, {'title': '6', 'id': '6'}, {'title': '7', 'id': '7'}, {'title': '8', 'id': '8'}, {'title': '9', 'id': '9'}
						]},
					{'id': 'columns', 'label': 'Columns', 'type': 'Select', 'value': '3', 'values': [
							{'title': '1', 'id': '1'}, {'title': '2', 'id': '2'}, {'title': '3', 'id': '3'}, {'title': '4', 'id': '4'}, {'title': '5', 'id': '5'}, {'title': '6', 'id': '6'}, {'title': '7', 'id': '7'}, {'title': '8', 'id': '8'}, {'title': '9', 'id': '9'}
						]},
				]
			};

			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
			
			//On style change update table
			for(var i=0,ii=form_config.inputs.length; i<ii; i++) {
				form.getInput(form_config.inputs[i].id).after('valueChange', this.onPropertyChange, this);
			}
			
			//Delete button
			var btn = new Supra.Button({'label': Supra.Intl.get(['buttons', 'delete']), 'style': 'small-red'});
				btn.render(form.get('contentBox'));
				btn.addClass('su-button-delete');
				btn.on('click', this.removeSelectedTable, this);
			
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
				colspan = 1,
				cell_html = '<br />';
			
			if (Y.UA.ie) {
				cell_html = '';
			}
			
			for(var i=0,ii=all_td.size(); i<ii; i++) {
				colspan = parseInt(all_td.item(i).getAttribute('colspan'), 10) || 1;
				new_tr += '<td' + (colspan > 1 ? ' colspan="' + colspan +'"' : '') + '>' + cell_html + '</td>';
			}
			
			sel_tr.insert('<tr>' + new_tr + '</tr>', where);
		},
		cmdRowDelete: function () {
			this.selected_cell.ancestor().remove();
			
			if (this.selected_table.all('tr').size() == 0) {
				//Table last row was removed
				return this.removeSelectedTable();
			}
			
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
			
			if (table.all('td').size() == 0 && table.all('th').size() == 0) {
				//Table last column was removed
				this.removeSelectedTable();
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
			var cell_html = '<br />';
			if (Y.UA.ie) {
				cell_html = '';
			}
			
			//Find tag name
			var children = Y.Node(tr).get('children'),
				tag = children.size() ? children.item(0).get('tagName') : 'TD';
			
			//Create new cell
			var td = document.createElement(tag);
				td.innerHTML = cell_html;
			
			var cell = this.getCellAtIndex(tr, index);
			if (cell) {
				tr.insertBefore(td, cell);
			} else {
				tr.appendChild(td);
			}
		},
		
		/**
		 * Returns number of columns in the table
		 * 
		 * @return Column count
		 * @type {Number}
		 */
		getColCount: function () {
			if (!this.selected_table) return 0;
			
			var trs = this.selected_table.all('tr'),
				i = 0,
				ii = trs.size(),
				max = 0;
			
			for (; i<ii; i++) {
				max = Math.max(max, this.getRowLength(trs.item(i).getDOMNode()));
			}
			
			return max;
		},
		
		/**
		 * Returns number of rows in the table
		 * 
		 * @return Row count
		 * @type {Number}
		 */
		getRowCount: function () {
			return this.selected_table ? this.selected_table.all('tr').size() : 0;
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
		
		/**
		 * Returns number of cells in a row
		 * 
		 * @param {HTMLElement} tr Row element
		 * @return Number of cells in a row
		 * @type {Number}
		 */
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
		
		/**
		 * Handle property input value change, update UI
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
			if (id == 'rows') {
				var old_value = this.getRowCount(),
					i = 0, ii = 0,
					trs = this.selected_table.all('tr');
				
				//Remove selection classname
				if (this.selected_cell) {
					this.selected_cell.removeClass('yui3-cell-selected');
				}
				
				value = parseInt(value, 10);
				this.selected_cell = trs.item(trs.size() - 1).one('th, td');
				
				if (value > old_value) {
					//Increase column count
					for (i=old_value+1, ii=value+1; i<ii; i++) {
						this.cmdRowAfter();
						
						trs = this.selected_table.all('tr');
						this.selected_cell = trs.item(trs.size() - 1).one('th, td');
					}
				} else if (value < old_value) {
					//Remove columns
					for (i=old_value, ii=value; i>ii; i--) {
						this.cmdRowDelete();
						
						trs = this.selected_table.all('tr');
						this.selected_cell = trs.item(trs.size() - 1).one('th, td');
					}
				}
				
				//Set selection and add classname
				trs = this.selected_table.all('tr');
				this.selected_cell = trs.item(trs.size() - 1).one('th, td');
				this.selected_cell.addClass('yui3-cell-selected');
				
				//Property changed, update editor 'changed' state
				this.htmleditor._changed();
				
			} else if (id == 'columns') {
				var old_value = this.getColCount(),
					i = 0, ii = 0,
					tds = this.selected_table.one('tr').all('th, td');
				
				//Remove selection classname
				if (this.selected_cell) {
					this.selected_cell.removeClass('yui3-cell-selected');
				}
				
				value = parseInt(value, 10);
				this.selected_cell = tds.item(tds.size() - 1);
				
				if (value > old_value) {
					//Increase column count
					for (i=old_value+1, ii=value+1; i<ii; i++) {
						this.cmdColAfter();
						
						tds = this.selected_table.one('tr').all('th, td');
						this.selected_cell = tds.item(tds.size() - 1);
					}
				} else if (value < old_value) {
					//Remove columns
					for (i=old_value, ii=value; i>ii; i--) {
						this.cmdColDelete();
						
						tds = this.selected_table.one('tr').all('th, td');
						this.selected_cell = tds.item(tds.size() - 1);
					}
				}
				
				//Set selection and add classname
				tds = this.selected_table.one('tr').all('th, td');
				this.selected_cell = tds.item(tds.size() - 1);
				this.selected_cell.addClass('yui3-cell-selected');
				
				//Property changed, update editor 'changed' state
				this.htmleditor._changed();
				
			}
		},
		
		/**
		 * Returns true if form is visible, otherwise false
		 */
		hideSettingsForm: function () {
			if (this.settings_form && this.settings_form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
			
			//Button style
			this.getButton(HTMLEDITOR_SETTINGS_COMMAND).set('down', false);
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
				this.hideToolbar();
				this.hideSettingsForm();
			}
		},
		
		/**
		 * Returns all table styles
		 */
		getTableStyles: function () {
			var style_plugin = this.htmleditor.getPlugin('style'),
				list = [{'id': '', 'title': ''}];
			
			if (style_plugin) {
				var styles = style_plugin.getSelectors('table', true, true);	//Get all table styles
				
				for(var i=0,ii=styles.length; i<ii; i++) {
					list.push({
						'id': styles[i].classname,
						'title': styles[i].attributes.title
					});
				}
			}
			
			return list.length == 1 ? [] : list;
		},
		
		showToolbar: function () {
			var toolbar = this.htmleditor.get('toolbar');
			toolbar.getButton(HTMLEDITOR_BUTTON).set('down', true);
			toolbar.showGroup('table');
		},
		
		hideToolbar: function () {
			var toolbar = this.htmleditor.get('toolbar');
			toolbar.getButton(HTMLEDITOR_BUTTON).set('down', false);
			toolbar.hideGroup('table');
		},
		
		/**
		 * Show table settings bar
		 */
		showTableSettings: function () {
			if (!this.selected_table) return;
			
			//Make sure PageContentSettings is rendered
			var form = this.settings_form || this.createSettingsForm(),
				action = Manager.getAction('PageContentSettings');
			
			if (!form) {
				if (action.get('loaded')) {
					if (!action.get('created')) {
						action.renderAction();
						this.showTableSettings();
					}
				} else {
					action.once('loaded', function () {
						this.showTableSettings();
					}, this);
					action.load();
				}
				return false;
			}
			
			//Button style
			this.getButton(HTMLEDITOR_SETTINGS_COMMAND).set('down', true);
			
			if (!Manager.getAction('PageToolbar').hasActionButtons("htmleditor-plugin")) {
				Manager.getAction('PageToolbar').addActionButtons("htmleditor-plugin", []);
				Manager.getAction('PageButtons').addActionButtons("htmleditor-plugin", []);
			}
			
			action.execute(form, {
				'doneCallback': Y.bind(this.hideSettingsForm, this),
				'title': Supra.Intl.get(['htmleditor', 'table_properties']),
				'scrollable': true,
				'toolbarActionName': 'htmleditor-plugin'
			});
			
			this.silent = true;
			form.getInput('rows').set('value', this.getRowCount());
			form.getInput('columns').set('value', this.getColCount());
			this.silent = false;
		},
		
		/**
		 * Focus table
		 */
		focusTable: function (element) {
			var table = element ? element.closest('table') : null,
				cell = element ? element.closest('td,th') : null;
			
			if (this.selected_table && (!table || !table.compareTo(this.selected_table))) {
				this.selected_table.removeClass('yui3-table-selected');
			}
			if (table) {
				table.addClass('yui3-table-selected');
			}
			
			if (this.selected_cell && (!cell || !cell.compareTo(this.selected_cell))) {
				this.selected_cell.removeClass('yui3-cell-selected');
			}
			if (cell) {
				cell.addClass('yui3-cell-selected');
			}
			
			this.selected_table = table;
			this.selected_cell = cell;
		},
		
		/**
		 * Insert table
		 */
		insertTable: function (values) {
			var htmleditor = this.htmleditor;
			
			if (this.selected_table) {
				//Focus already on table, don't allow creating table inside another table
				return;
			}
			
			if (!htmleditor.get('disabled') && htmleditor.isSelectionEditable(htmleditor.getSelection())) {
				var cell_html = '<br />';
				if (Y.UA.ie) {
					cell_html = '';
				}
				
				var html_row = '<tr><td>' + cell_html + '</td><td>' + cell_html + '</td><td>' + cell_html + '</td></tr>',
					html_table = '<table class="desktop"><tbody><tr><th>' + cell_html + '</th><th>' + cell_html + '</th><th>' + cell_html + '</th></tr>' + html_row + html_row + '</tbody></table>';
				
				//Replace selection with table
				var node = htmleditor.replaceSelection(html_table);
				
				if (node) {
					node = (new Y.Node(node)).one('th,th');
					
					this.showToolbar();
					this.focusTable(node);
				}
				
				//Set changed event
				htmleditor._changed();
			}
		},
		
		/**
		 * On node change check if settings form needs to be hidden
		 */
		onNodeChange: function () {
			var element = this.htmleditor.getSelectedElement('img,svg,td,th,table'),
				button = htmleditor.get("toolbar").getButton(HTMLEDITOR_BUTTON),
				allowEditing = this.htmleditor.editingAllowed,
				isSpecial = element ? Y.Node(element).test('img,svg') : false;
			
			if (element && !isSpecial) {
				var element = new Y.Node(element),
					table = element.closest('table');
				
				if (this.selected_table && !table.compareTo(this.selected_table)) {
					this.hideSettingsForm();
				}
				
				if (!this.selected_table) {
					this.showToolbar();
				}
				
				this.focusTable(element);
			} else if (this.selected_table) {
				this.focusTable(null);
				this.hideSettingsForm();
				this.hideToolbar();
			}
						
			if (isSpecial) {
				button.set('disabled', true);
			} else {
				button.set('disabled', !allowEditing);
			}
		},
		
		
		/* --------------------------------- Key board input --------------------------------- */
		
		
		/**
		 * On tab key navigate between table cells
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_onTabKey: function (event) {
			var table   = this.selected_table,
				cell    = this.selected_cell,
				node    = cell,
				offset  = 0,
				test    = null,
				filter  = null,
				KEY_TAB = 9;
			
			test = function (node) {
				return node.get('nodeType') == 1;
			};
			
			if (table && cell && !event.stopped && event.keyCode == KEY_TAB && !event.altKey && !event.ctrlKey) {
				
				if (event.shiftKey) {
					node = node.previous(test);
					
					if (!node) {
						node = cell.ancestor().previous(test);
						
						if (node) {
							// last child
							node = node.get('childNodes').filter('td,th');
							node = node.item(node.size() - 1);
						}
					}
				} else {
					node = node.next(test);
					
					if (!node) {
						node = cell.ancestor().next(test);
						
						if (node) {
							// first child
							node = node.get('childNodes').filter('td,th');
							node = node.item(0);
						}
					}
				}
				
				if (node) {
					cell   = node;
					node   = node.getDOMNode();
					offset = node.childNodes.length;
					
					// Focus that node
					this.htmleditor.setSelection({
						'start': node,
						'start_offset': offset,
						'end': node,
						'end_offset': offset
					});
					
					this.focusTable(cell);
				}
					
				event.halt();
			}
		},
		
		/**
		 * On CTRL+A / Command+A select only cell content, not all content in HTMLEditor
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		_onSelectAllKey: function (event) {
			var table  = this.selected_table,
				cell   = this.selected_cell,
				KEY_A  = 65;
			
			if (table && cell && !event.stopped && event.keyCode == KEY_A && (event.ctrlKey || event.metaKey) && !event.altKey) {
				cell   = cell.getDOMNode();
				offset = cell.childNodes.length;
				
				// Focus that node
				this.htmleditor.setSelection({
					'start': cell,
					'start_offset': 0,
					'end': cell,
					'end_offset': offset
				});
					
				event.halt();
			}
		},
		
		
		/* --------------------------------- Initialize --------------------------------- */
		
		
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
			htmleditor.addCommand(HTMLEDITOR_SETTINGS_COMMAND, Y.bind(this.showTableSettings, this));
			
			htmleditor.addCommand('row-before', Y.bind(this.cmdRowBefore, this));
			htmleditor.addCommand('row-after', Y.bind(this.cmdRowAfter, this));
			htmleditor.addCommand('row-delete', Y.bind(this.cmdRowDelete, this));
			htmleditor.addCommand('merge-cells', Y.bind(this.cmdMergeCells, this));
			htmleditor.addCommand('column-before', Y.bind(this.cmdColBefore, this));
			htmleditor.addCommand('column-after', Y.bind(this.cmdColAfter, this));
			htmleditor.addCommand('column-delete', Y.bind(this.cmdColDelete, this));
			
			var button = this.getButton();
			if (button) {
				//When un-editable node is selected disable toolbar button
				htmleditor.on('editingAllowedChange', function (event) {
					if (!event.allowed) {
						this.hideToolbar();
					}
					button.set('disabled', !event.allowed);
				}, this);
			}
			
			// On tab key navigate between cells
			htmleditor.on('keyDown', Y.bind(this._onTabKey, this));
			
			// On select-all key selec only cell content
			htmleditor.on('keyDown', Y.bind(this._onSelectAllKey, this));
			
			//When table looses focus hide settings form
			htmleditor.on('nodeChange', this.onNodeChange, this);
		},
		
		getButton: function (id) {
			var toolbar = this.htmleditor.get('toolbar');
			return toolbar ? toolbar.getButton(id || HTMLEDITOR_BUTTON) : null;
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {},
		
		/**
		 * Process HTML
		 * Called before HTML is saved
		 * 
		 * @param {String} html
		 * @return Processed HTML
		 * @type {HTML}
		 */
		tagHTML: function (html) {
			return html;
		},
		
		/**
		 * Process HTML
		 * Called before HTML is set
		 * 
		 * @param {String} html HTML
		 * @param {Object} data Data
		 * @return Processed HTML
		 * @type {String}
		 */
		untagHTML: function (html, data) {
			return html;
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});