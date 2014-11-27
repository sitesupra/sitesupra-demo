YUI.add('supra.tree-node-multiselect', function (Y) {
	//Invoke strict mode
	"use strict";
	
	
	var C = Y.ClassNameManager.getClassName;
	
	function TreeNodeMultiSelect (config) {
		TreeNodeMultiSelect.superclass.constructor.apply(this, [config]);
	};
	
	TreeNodeMultiSelect.NAME = 'tree-node-multiselect';
	TreeNodeMultiSelect.CLASS_NAME = Supra.TreeNode.CLASS_NAME;
	TreeNodeMultiSelect.CSS_PREFIX = Supra.TreeNode.CSS_PREFIX;
	
	TreeNodeMultiSelect.ATTRS = {
		'defaultChildType': {
			'value': TreeNodeMultiSelect
		}
	};
	
	Y.extend(TreeNodeMultiSelect, Supra.TreeNode, {
		CONTENT_TEMPLATE: '<div class="tree-node">\
			  					<div><span class="toggle hidden"></span><span class="remove"></span><span class="img"><img src="/public/cms/supra/img/tree/none.png" /></span> <label></label></div>\
			  				</div>\
			  				<ul class="tree-children">\
			  				</ul>',
		
		renderUI: function () {
			TreeNodeMultiSelect.superclass.renderUI.apply(this, arguments);
			
			if (this.getTree().valueHas(this.get('data'))) {
				this.set('isSelected', true);
			} else if (this.get('isSelected')) {
				this._syncUIisSelected(true);
			}
		},
		
		bindUI: function () {
			TreeNodeMultiSelect.superclass.bindUI.apply(this, arguments);
			
			this.get('boundingBox').one('span.remove').on('click', this.unsetSelectedState, this);
			this.after('isSelectedChange', this._handleSelectedStateChange, this);
		},
		
		_setIsSelected: function (value) {
			if (!this.get('selectable')) return false;
			this._syncUIisSelected(value);
			
			return !!value;
		},
		
		_syncUIisSelected: function (value) {
			var classname = C('tree-node', 'selected'),
				box = this.get('boundingBox'),
				node;
			
			if (box) {
				node = box.one('div');
				if (node) node.toggleClass(classname, value);
			}
		},
		
		_handleSelectedStateChange: function (evt) {
			if (evt.target !== this) return;
			
			if (evt.prevVal !== evt.newVal) {
				var tree = this.getTree();
				
				// Only TreeMultiSelect has values
				if (tree.valueAdd) {
					if (evt.newVal) {
						tree.valueAdd(this.get('data'));
						tree.fire('');
					} else {
						tree.valueRemove(this.get('data'));
					}
				}
			}
		},
		
		/**
		 * Unset selected state
		 */
		unsetSelectedState: function (evt) {
			this.set('isSelected', false);
			
			// Stop propagation to prevent from beeing selected again
			evt.halt();
		}
	});
	
	
	Supra.TreeNodeMultiSelect = TreeNodeMultiSelect;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};

}, YUI.version, {'requires': ['supra.tree', 'supra.tree-node']});
