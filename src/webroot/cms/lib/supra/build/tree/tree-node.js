//Invoke strict mode
"use strict";

YUI.add('supra.tree-node', function(Y) {
	var C = Y.ClassNameManager.getClassName;
	
	function TreeNode (config) {
		var config = Y.mix(config || {}, {
			'label': '',
			'icon': '',
			'data': null,
			'selectable': true
		});
		
		TreeNode.superclass.constructor.apply(this, arguments);
		
		this.init.apply(this, arguments);
	}
	TreeNode.NAME = 'tree-node';
	Y.extend(TreeNode, Y.Widget);
	
	
	Supra.TreeNode = Y.Base.create('supra.tree-node', TreeNode, [Y.WidgetChild, Y.WidgetParent], {
		ROOT_TYPE: TreeNode,
		BOUNDING_TEMPLATE: '<li></li>',
		CONTENT_TEMPLATE: '<div class="tree-node">\
			  					<div><span class="toggle hidden"></span><span class="img"><img src="/cms/lib/supra/img/tree/none.png" /></span> <label></label></div>\
			  				</div>\
			  				<ul class="tree-children">\
			  				</ul>',
		
		_tree: null,
		
		_is_root: null,
		
		syncUI: function () {
			var data = this.get('data');
			var node = this.get('nodeToggle');
			
			if (data && 'children' in data && data.children.length) {
				node.removeClass('hidden');
			} else {
				node.addClass('hidden');
			}
		},
		
		toggle: function () {
			if (this.get('boundingBox').hasClass(C('tree-node', 'collapsed'))) {
				this.expand();
			} else {
				this.collapse();
			}
		},
		
		collapse: function (silent) {
			this.get('boundingBox').addClass(C('tree-node', 'collapsed'));
			if (silent !== true) this.getTree().fire('toggle', {node: this, data: this.get('data'), newVal: false});
		},
		
		collapseAll: function () {
			if (!this.isRoot() || this.getTree().get('rootNodeExpandable')) this.collapse();
			for(var i=0,ii=this.size(); i<ii; i++) {
				this.item(i).collapseAll();
			}
		},
		
		expand: function (silent) {
			this.get('boundingBox').removeClass(C('tree-node', 'collapsed'));
			if (silent !== true) this.getTree().fire('toggle', {node: this, data: this.get('data'), newVal: true});
		},
		
		expandAll: function () {
			if (!this.isRoot() || this.getTree().get('rootNodeExpandable')) this.expand();
			for(var i=0,ii=this.size(); i<ii; i++) {
				this.item(i).expandAll();
			}
		},
		
		/**
		 * On addChild update data
		 * Handle only sorting, new items should be handled by default handler
		 * 
		 * @param {Object} event
		 */
		onAddChild: function (event) {
			//-1 index is for new items, handle only sorting
			if (event.child.get('index') == -1) return;
			
			//Stop propagation, otherwise it will propagate to the root element and
			//child-parent association will be wrong
			event.stopPropagation();
			
			//Prevent default implementation, it is buggy!?
			event.preventDefault();
			
			var data = this.getTree().getIndexedData();			//all tree data
			var index = event.index;							//new index
			
			var child = event.child;							//TreeNode instance
			var child_data = child.get('data');					//drag element data
			
			var target = event.currentTarget;					//TreeNode instance
			var target_data = target.get('data');				//drop target data
			
			var parent = child.get('parent');					//TreeNode instance of old parent
			
			if (!('children' in target_data)) {
				target_data.children = [];
			}
			
			//Remove from parents children list
			if (parent) {
				parent.remove(child.get('index'));
			}
			
			//Update "parent" in data
			child_data.parent = target_data.id;
			
			//Insert into new parents data and new parents children list
			var children = target._items;
			if (Y.Lang.isNumber(index)) {
	            target_data.children.splice(index, 0, child_data);
				children.splice(index, 0, child);
	        }  else {
	            target_data.children.push(child_data);
				children.push(child);
	        }
			
			//Update child parent
			child._set("parent", target);
    		child.addTarget(target);
			event.index = child.get("index");
			
			//Insert node into correct position
			var sibling = null;
			if (Y.Lang.isNumber(index)) {
				sibling = target._childrenContainer.get('children').item(index);
			}
			
			if (sibling) {
				sibling.insert(child.get('boundingBox'), 'before');
			} else {
				target._childrenContainer.append(child.get('boundingBox'));
			}
			
			//Update UI
			if (parent) parent.syncUI();
			target.syncUI();
		},
		
		/**
		 * On remove child remove data from parent
		 */
		onRemoveChild: function (event) {
			var data = this.getTree().getIndexedData();			//all tree data
			
			var child = event.child;							//TreeNode instance
			var child_data = child.get('data');					//drag element data
			
			var parent = child.get('parent');					//TreeNode instance of old parent
			
			if (parent) {
				var parent_data = data[data[child_data.id].parent];	//Old parent data
				
				//Remove data from old parent
				if (parent_data && parent_data.children) {
					for(var i=0,ii=parent_data.children.length; i<ii; i++) {
						if (parent_data.children[i].id == child_data.id) {
							parent_data.children.splice(i,1);
							break;
						}
					}
				}
			}
		},
		
		bindUI: function () {
			//Handle click
			this.get('boundingBox').one('div').on('click', function (evt) {
				//If event was prevented then don't do anything
				if (evt.prevented) return;
				
				var event_name = 'node-click';
				if (evt.target.get('tagName') == 'A') {
					event_name = 'newpage-click';
					evt.preventDefault();
				}
				
				if (this.getTree().fire(event_name, {node: this, data: this.get('data')})) {
					//If event wasn't stopped then set this node as selected
					this.set('isSelected', true);
				}
			}, this);
			
			//Expand/collapse
			this.get('nodeToggle').on('click', function (event) {
				var data = this.get('data');
				if (data && data.children && data.children.length) {
					this.toggle();
					event.halt();
				}
			}, this);
			
			this.after('addChild', function (event) {
				var target = event.currentTarget;
				target.syncUI();
			});
			
			//On addChild update data
			//Handle only sorting, new items should be handled by default handler
			this.on('addChild', this.onAddChild, this);
			
			this.on('removeChild', this.onRemoveChild, this);
			
		},
		
		renderUI: function () {
			var data = this.get('data');
			
			this._childrenContainer = this.get('boundingBox').one('ul');
			
			//Data
			if (data.id) {
				this.get('boundingBox').setData('nodeId', data.id);
			}
			
			//Label
			this.setLabel(this.get('label'));
			
			//Icon
			this.get('boundingBox').one('img').set('src', '/cms/lib/supra/img/tree/' + data.icon + '.png');
			
			//Toggle
			this.set('nodeToggle', this.get('boundingBox').one('span.toggle'));
			
			if (this.isRoot() && this.getTree().get('rootNodeExpandable')) {
				this.get('boundingBox').one('div.tree-node').addClass('tree-node-root-expandable');
			}
			
			//Children
			if (data && 'children' in data) {
				for(var i=0, ii=data.children.length-1; i<=ii; i++) {
					var isDragable = ('isDragable' in data.children[i] ? data.children[i].isDragable : true);
					var isDropTarget = ('isDropTarget' in data.children[i] ? data.children[i].isDropTarget : true);
					this.add({
						'data': data.children[i],
						'label': data.children[i].title,
						'icon': data.children[i].icon,
						'isDropTarget': isDropTarget,
						'isDragable': isDragable
					}, i);
				}
				
				if (i==ii) {
					this.syncUI();
				}
			}
		},
		
		setLabel: function (label) {
			this.get('boundingBox').one('label').set('innerHTML', label);
		},
		
		setIcon: function (icon) {
			this.get('boundingBox').one('img').set('src', '/cms/lib/supra/img/tree/' + icon + '.png');
		},
		
		/**
		 * Returns true if this node is root node
		 * 
		 * @return True if root node, otherwise false
		 * @type {Boolean}
		 */
		isRoot: function () {
			if (this._is_root === null) {
				return this._is_root = this.get('parent') instanceof Supra.Tree;
			}
			return this._is_root;
		},
		
		getTree: function () {
			if (this._tree) return this._tree;
			
			var p = this.get('parent');
			while(p && !(p instanceof Supra.Tree)) {
				p = p.get('parent');
			}
			
			this._tree = p;
			return p;
		},
		
		/**
		 * Returns tree node by ID
		 * 
		 * @param {String} id
		 * @return TreeNode
		 * @type {Object}
		 */
		getNodeById: function (id) {
			var i = 0, node;
			while(node = this.item(i)) {
				if (node.get('data').id == id) {
					return node;
				} else if (node = node.getNodeById(id)) {
					return node;
				}
				i++;
			}
			return null;
		},
		
		/**
		 * Handle .set('selected', true|false)
		 */
		_setIsSelected: function (value) {
			if (!this.get('selectable')) return false;
			if (!!value == this.get('isSelected')) return value;
			
			var classname = C('tree-node', 'selected');
			if (value) {
				this.get('boundingBox').one('div').addClass(classname);
				
				setTimeout(Y.bind(function () {
					var tree = this.getTree();
					if (tree.get('selectedNode') !== this) {
						this.getTree().set('selectedNode', this);
					}
				}, this), 1);
			} else {
				this.get('boundingBox').one('div').removeClass(classname);
			}
			
			return !!value;
		},
		
		_setLoading: function (loading) {
			var classname = C('tree-node', 'loading');
			if (loading) {
				this.get('boundingBox').one('div').addClass(classname);
			} else {
				this.get('boundingBox').one('div').removeClass(classname);
			}
			
			return !!loading;
		}
		
	}, {
		HTML_PARSER: {
			srcNode: function (srcNode) {
				return this.get('boundingBox').one('ul');
			},
			contentBox: function (srcNode) {
				return this.get('boundingBox').one('ul');
			}
		},
		ATTRS: {
			'label': {
				value: '',
				setter: 'setLabel'
			},
			'icon': {
				value: '',
				setter: 'setIcon'
			},
			'isSelected': {
				value: false,
				setter: '_setIsSelected'
			},
			'selectable': {
				value: true
			},
			'loading': {
				value: false,
				setter: '_setLoading'
			},
			'nodeToggle': {
				value: null
			},
			'data': {
				value: null
			},
			'defaultChildType': {  
	            value: null
	        }
		},
		CLASS_NAME: C('tree-node'),
	});
	
	Supra.TreeNode.ATTRS.defaultChildType.value = Supra.TreeNode;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['substitute', 'widget', 'widget-parent', 'widget-child']});