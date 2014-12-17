YUI.add('supra.tree-multiselect', function(Y) {
	//Invoke strict mode
	"use strict";
	
	function TreeMultiSelect (config) {
		TreeMultiSelect.superclass.constructor.apply(this, arguments);
	}
	
	TreeMultiSelect.NAME = 'tree-multiselect';
	
	TreeMultiSelect.ATTRS = {
		/**
		 * Default children class
		 * @type {Function}
		 */
		'defaultChildType': {  
            value: Supra.TreeNodeMultiSelect
		},
		
		/**
		 * List of all selected nodes
		 * @type {Array}
		 */
		'value': {
			value: null
		},
		
		/**
		 * Allow selecting multiple items
		 */
		'multiple': {
			value: false
		}
	};
	
	Y.extend(TreeMultiSelect, Supra.Tree, {
		_valuesChanging: false,
		
		bindUI: function () {
			TreeMultiSelect.superclass.bindUI.apply(this, arguments);
			
			this.on('valueChange', this._handleValueChange, this);
			this.after('valueChange', this._afterValueChange, this);
		},
		
		_handleValueChange: function (e) {
			// Make sure we don't go into loop
			if (this._valuesChanging) return;
			this._valuesChanging = true;
			
			var prev_val = e.prevVal,
				prev_ids = {},
				i = 0,
				ii = prev_val ? prev_val.length : 0,
				
				new_val = e.newVal,
				new_ids = {},
				id, node;
			
			for (; i<ii; i++) {
				id = this._getValueId(prev_val[i]);
				prev_ids[id] = 1;
			}
			
			// Find which are now selected, but wasn't before
			if (new_val && new_val.length) {
				for (i=0,ii=new_val.length; i<ii; i++) {
					id = this._getValueId(new_val[i]);
					
					new_ids[id] = new_val[i];
					
					if (!(id in prev_ids)) {
						// New item selected
						node = this.getNodeById(id);
						if (node && !node.get('isSelected')) {
							node.set('isSelected', true); 
						}
						
						this.fire('value-add', new_val[i]);
					}
				}
			}
			
			// Find which are not selected, but was before
			if (prev_val.length) {
				for (i=0, ii=prev_val.length; i<ii; i++) {
					id = this._getValueId(prev_val[i]);
					
					if (!(id in new_ids)) {
						// Item selection removed
						node = this.getNodeById(id);
						if (node && node.get('isSelected')) {
							node.set('isSelected', false); 
						}
						
						this.fire('value-remove', prev_val[i]);
					}
				}
			}
		},
		
		_afterValueChange: function () {
			// Reset state
			this._valuesChanging = false;
		},
		
		valueAdd: function (data) {
			if (this._valuesChanging) return;
			
			var id = this._getValueId(data),
				index = this._getValueIndex(id),
				value;
			
			if (index === -1) {
				value = [].concat(this.get('value') || []);
				value.push(data);
				this.set('value', value);
			}
		},
		
		valueRemove: function (data) {
			if (this._valuesChanging) return;
			
			var id = this._getValueId(data),
				index = this._getValueIndex(id),
				value;
			
			if (index !== -1) {
				value = [].concat(this.get('value'));
				value.splice(index, 1);
				this.set('value', value);
			} 
		},
		
		valueHas: function (data) {
			return this._getValueIndex(this._getValueId(data)) !== -1;
		},
		
		_getValueIndex: function (data) {
			var id = this._getValueId(data),
				value = this.get('value') || [],
				i = 0,
				ii = value.length;
			
			for (; i<ii; i++) {
				if (value[i] === id || (typeof value[i] === 'object' && value[i].id === id)) {
					return i;
				}
			}
			
			return -1;
		},
		
		_getValueId: function (node) {
			var type = typeof node;
			
			if (type === 'string' || type === 'number') {
				return node;
			} else if (node && node.isInstanceOf) {
				return node.get('data').id;
			} else if (node && type === 'object' && 'id' in node) {
				return node.id;
			} else {
				return null;
			}
		}
		
	});
	
	Supra.TreeMultiSelect = TreeMultiSelect;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.tree', 'supra.tree-node-multiselect']});
