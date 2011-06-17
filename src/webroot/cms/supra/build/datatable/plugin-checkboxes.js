YUI.add('supra.datatable-checkboxes', function (Y) {
	
	function CheckboxPlugin (config) {
		CheckboxPlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a node instance, the plugin will be 
	// available on the "checkboxes" property.
	CheckboxPlugin.NS = 'checkboxes';
	
	// Extend Plugin.Base
	Y.extend(CheckboxPlugin, Y.Plugin.Base, {
		
		/**
		 * Array of checkboxe nodes
		 * @type {Array}
		 */
		checkboxes: null,
		
		/**
		 * Format checkbox
		 * This keyword refers to DataTableRow
		 * 
		 * @param {Object} col
		 * @param {Object} val
		 * @param {Object} data
		 */
		formatCheckbox: function (col, val, data) {
			return '<input class="yui3-datatable-checkbox" type="checkbox" name="cb[' + this.index + ']" value="1" ' + (val ? 'checked="checked" ' : '') + '/>';
		},
		
		/**
		 * Check all rows
		 */
		checkAll: function () {
			var checkboxes = this.checkboxes;
			if (checkboxes) {
				checkboxes.set('checked', true);
			}
		},
		
		/**
		 * Uncheck all rows
		 */
		uncheckAll: function () {
			var checkboxes = this.checkboxes;
			if (checkboxes) {
				checkboxes.set('checked', false);
			}
		},
		
		/**
		 * Returns checked rows
		 * 
		 * @return Array of DataTableRow
		 * @rype {Array}
		 */
		getCheckedRows: function () {
			if (!this.checkboxes) return [];
			
			var host = this.get('host');
			var checkboxes = this.checkboxes.filter(':checked'),
				rows = [],
				index = 0;
			
			for(var i=0,ii=checkboxes.size(); i<ii; i++) {
				index = checkboxes.item(i).get('name');
				index = index.match(/\[(\d+)\]/)[1];
				index = parseInt(index);
				
				rows.push(host.getRowByIndex(index));
			}
			
			return rows;
		},
		
		/**
		 * Returns all checkboxes
		 * 
		 * @return NodeList of checkboxes
		 * @type {Object}
		 */
		getCheckboxes: function () {
			return this.checkboxes;
		},
		
		/**
		 * Constructor
		 */
		initializer: function () {
			var host = this.get('host');
			
			host.addColumn({
				'id': 'checkbox',
				'title': '',
				'formatter': this.formatCheckbox,
				'hasData': false
			});
			
			host.after('reset:success', function () {
				//Clean up
				if (this.checkboxes) {
					this.checkboxes.remove();
				}
				
				this.checkboxes = this.get('host').tableBodyNode.all('input[type="checkbox"]');
			}, this);
		},
		
		/**
		 * Destructor
		 */
		destructor: function () {
			if (this.checkboxes) {
				this.checkboxes.remove();
			}
		}
		
	});
	
	Supra.DataTable.CheckboxPlugin = CheckboxPlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.datatable']});