YUI.add('supra.tree', function(Y) {
	
	/*
	 * Shortcuts
	 */
	var C = Y.ClassNameManager.getClassName;
	
	/*
	 * Generated HTML:
	 * 		<ul class="tree">
	 * 			<li id="tree0_node0">
	 * 				<div class="tree-node tree-node-published">
	 * 					<span class="img"><img src="/cms/supra/img/tree/home.png" /></span> <label>Home</label>
	 * 				</div>
	 * 				<ul class="tree-children">
	 * 					<li id="tree0_node1">
	 *		 				<div class="tree-node tree-node-scheduled">
	 * 							<span class="img"><img src="/cms/supra/img/tree/page.png" /></span> <label>Page</label>
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
		_data: [],
		_data_indexed: {},
		
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
			
			this.get('boundingBox').addClass(C('tree', 'loading'));
		},
		
		bindUI: function () {
		},
		
		syncUI: function () {
		},
		
		renderTreeUI: function (data) {
			this.get('boundingBox').removeClass(C('tree', 'loading'));
			
			for(var i=0,ii=data.length; i<ii; i++) {
				this._renderTreeUIChild(data[i], i);
			}
		},
		
		_renderTreeUIChild: function (data, i) {
			var node = this.add({'data': data, 'label': data.title, 'icon': data.icon}, i);
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
		
		removeNode: function () {
			
		},
		
		/**
		 * Reload tree data
		 */
		reload: function () {
			var uri = this.get('requestUri');
			
			// Define a function to handle the response data.
			function complete(id, data, args) {
				// Remove all nodes and data
				for(var i=0,ii=this.size(); i<ii; i++) {
					this.item(i).destroy();
				}
				
				this._data = [];
				this._data_indexed = {};
				
				//Get new data
				var id = id; // Transaction ID.
				
				//Create data index
				var data_indexed = {};
				var tmp = [].concat(data), i=0;
				
				while(i < tmp.length) {
					
					data_indexed[tmp[i].id] = tmp[i];
					
					if ('children' in tmp[i] && tmp[i].children) {
						for(var k=0, kk=tmp[i].children.length; k<kk; k++) {
							Y.mix(tmp[i].children[k], {
								parent: tmp[i].id,
								fullpath: (tmp[i].fullpath || '') + '/' + tmp[i].children[k].path
							});
							tmp.push(tmp[i].children[k]);
						}
					}
					
					i++;
				}
				
				this._data = data;
				this._data_indexed = data_indexed;
				this.renderTreeUI(data);
				
				this.fire('render:complete');
			};
			
			var request = Supra.io(uri, complete, this);
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
			'rootNodeExpandable': {
				value: false
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