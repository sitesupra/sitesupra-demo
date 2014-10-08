YUI.add('supra.tree', function(Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Shortcuts
	 */
	var C = Y.ClassNameManager.getClassName;
	
	/*
	 * Generated HTML:
	 * 		<ul class="tree">
	 * 			<li id="tree0_node0">
	 * 				<div class="tree-node tree-node-published">
	 * 					<span class="img"><img src="/cms/lib/supra/img/tree/home.png" /></span> <label>Home</label>
	 * 				</div>
	 * 				<ul class="tree-children">
	 * 					<li id="tree0_node1">
	 *		 				<div class="tree-node tree-node-scheduled">
	 * 							<span class="img"><img src="/cms/lib/supra/img/tree/page.png" /></span> <label>Page</label>
	 * 
	 * 						</div>
	 *					</li>
	 * 				</ul>
	 * 			</li>
	 * 		</ul>
	 * 
	 */
	
	/**
	 * Tree view
	 * Events:
	 *   node-click  -  when one of the nodes is clicked
	 * 
	 * @param {Object} config
	 */
	function Tree (config) {
		Tree.superclass.constructor.apply(this, arguments);
	}
	Tree.NAME = 'Tree';
	Y.extend(Tree, Y.Widget);
	
	
	Supra.Tree = Y.Base.create('tree', Tree, [Y.WidgetParent], {
		
		CONTENT_TEMPLATE: '<ul></ul>',
		
		/** 
		 * First level page data 
		 * @type {Array} 
		 * @private 
		 */ 
		_data: [],
		
		/** 
		 * All data indexed by page ID 
		 * @type {Object} 
		 * @private 
		 */ 
		_data_indexed: {},
		
		/** 
		 * XHR request object 
		 * @type {Object} 
		 * @private 
		 */ 
		_xhr: null,
		
		
		initializer: function () {
		},
		
		destructor: function () {
			delete(this._data);
			delete(this._data_indexed);
		},
		
		renderUI: function() {
			//Get unique ID
			var guid = Supra.Tree.GUID++;
			this.set('guid', guid);
			var node_id = 'tree' + guid;
			
			this.get('srcNode').set('id', node_id);
			
			this.reload();
			
			this.set('loading', true);
		},
		
		bindUI: function () {
		},
		
		syncUI: function () {
		},
		
		renderTreeUI: function (data) {
			this.set('loading', false);
			
			for(var i=0,ii=data.length; i<ii; i++) {
				this._renderTreeUIChild(data[i], i);
			}
		},
		
		_renderTreeUIChild: function (data, i) {
			this.add({'data': data, 'label': data.title, 'icon': data.icon}, i);
		},
		
		/**
		 * Returns all tree data
		 * 
		 * @return Data array
		 * @type {Array}
		 */
		getData: function () {
			return this._data;
		},
		
		/**
		 * Returns all tree data as object where keys are items ids
		 * 
		 * @return Data object
		 * @type {Object}
		 */
		getIndexedData: function () {
			return this._data_indexed;
		},
		
		/**
		 * Returns TreeNode by ID
		 * 
		 * @param {Object} id ID
		 * @return Tree node
		 * @type {Object}
		 */
		getNodeById: function (id) {
			return this.getNodeBy('id', id);
		},
		
		/**
		 * Returns TreeNode by ID
		 * 
		 * @param {Object} key field name
		 * @param {Object} value field value
		 * @return Tree node
		 * @type {Object}
		 */
		getNodeBy: function (key, value) {
			var i = 0, node;
			while(node = this.item(i)) {
				if (node.get('data')[key] == value) {
					return node;
				} else if (node = node.getNodeBy(key, value)) {
					return node;
				}
				i++;
			}
			return null;
		},
		
		/**
		 * Returns ID by Y.Node element
		 * 
		 * @param {Object} node Node
		 * @return Node ID
		 * @type {String}
		 */
		getIdByNode: function (node) {
			node = node.closest('LI');
			if (node) {
				return node.getData('nodeId');
			}
		},
		
		/**
		 * Handle .set('selectedNode')
		 * @param {Object} node
		 */
		_setSelectedNode: function (node) {
			var old = this.get('selectedNode');
			if (node === old) return node;
			
			var id = null;
			var data = null;
			var treenode = null;
			
			if (Y.Lang.isNumber(node) || Y.Lang.isString(node)) {
				id = node;
				treenode = this.getNodeById(id);
			} else if (node instanceof Supra.TreeNode) {
				id = node.get('data').id;
				treenode = node;
			}
			
			if ((!old || old.get('data').id != id) && id && id in this._data_indexed && treenode.get('selectable')) {
				if (old) {
					old.set('isSelected', false);
				}
				
				data = this._data_indexed[id];
				
				if (!treenode.get('isSelected')) {
					treenode.set('isSelected', true);
				}
				
				return treenode;
			} else if (!id && old) {
				old.set('isSelected', false);
				return null;
			}
		},
		
		collapseAll: function () {
			for(var i=0,ii=this.size(); i<ii; i++) {
				this.item(i).collapseAll();
			}
		},
		
		expandAll: function () {
			for(var i=0,ii=this.size(); i<ii; i++) {
				this.item(i).expandAll();
			}
		},
		
		/**
		 * Reload tree data
		 */
		reload: function () {
			var uri = this.get('requestUri');
			
			if (this._xhr) {
				this._xhr.abort();
			}
			
			this._xhr = Supra.io(uri, this.onDataLoad, this);
		},
		
		/**
		 * Load children data
		 * 
		 * @param {String} parent_id Item ID for which to load children
		 */
		loadPartial: function (parent_id) {
			var uri = this.get('requestUri'),
				params = {
					'data': {'parent_id': parent_id}
				};
			
			return Supra.io(uri, params, function (data, status) {
				this.onPartialDataLoad(data, status, parent_id);
			}, this)
		},
		
		/**
		 * Handle data load
		 * 
		 * @private
		 */
		onDataLoad: function (data, status) {
			//On failure assume nothing was returned
			if (!status) data = [];
			
			// Remove all nodes and data
			var item = null;
			for(var i=this.size() - 1; i >= 0; i--) {
				item = this.item(i);
				this.remove(i);
				item.destroy();
			}
			
			this._xhr = null;
			this._data = data;
			this._data_indexed = {};
			
			//Create data index
			this.onPartialDataLoad(data, status);
			
			this.renderTreeUI(data);
			
			this.fire('render:complete');
		},
		
		/**
		 * Handle partial data load,
		 * data is loaded for a parent
		 * 
		 * @private
		 */
		onPartialDataLoad: function (data, status, parent_id) {
			//On failure assume nothing was returned
			if (!status) data = [];
			
			//Create data index
			var data_indexed = this._data_indexed;
			var tmp = [].concat(data), i=0;
			
			//Add items to parent children list
			if (parent_id) {
				data_indexed[parent_id].children = data;
			}
			
			//Update new children parent attribute and index them
			while(i < tmp.length) {
				
				if (parent_id) {
					tmp[i].parent = parent_id;
				}
				
				data_indexed[tmp[i].id] = tmp[i];
				
				if ('children' in tmp[i] && tmp[i].children) {
					for(var k=0, kk=tmp[i].children.length; k<kk; k++) {
						tmp[i].children[k].parent = tmp[i].id;
						tmp.push(tmp[i].children[k]);
					}
				}
				
				i++;
			}
		},
		
		/**
		 * Remove all children
		 */
		empty: function () {
			var item = null;
			for(var i=this.size() - 1; i >= 0; i--) {
				this.item(i).destroy();
				this.remove(i);
			}
		},
		
		/**
		 * Remove all children and data
		 */
		resetAll: function () {
			this.empty();
			this._data = null;
			this._data_indexed = null;
		},
		
		/**
		 * Set loading style
		 */
		_setLoading: function (value) {
			var node = this.get('boundingBox'),
				classname = C('tree', 'loading');
			
			node.toggleClass(classname, value);
			
			return !!value;
		}
	}, {
		
		ATTRS: {
			'guid': {
				value: 0
			},
			'requestUri': {
				value: ''
			},
			'defaultChildType': {  
	            value: Supra.TreeNode
	        },
			'selectedNode': {
				value: null,
				setter: '_setSelectedNode'
			},
			'groupNodesSelectable': {
				value: true
			},
			'rootNodeExpandable': {
				value: false
			},
			'loading': {
				value: false,
				setter: '_setLoading'
			}
		},
		
		GUID: 1,
		
		CLASS_NAME: C('tree'),
		
		HTML_PARSER: {}
	});
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['substitute', 'widget', 'widget-parent', 'widget-child', 'supra.tree-node']});