//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-node', function (Y) {

	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');


	function TreeNode(config) {
		TreeNode.superclass.constructor.apply(this, arguments);
	}

	TreeNode.NAME = 'TreeNode';
	TreeNode.CSS_PREFIX = 'su-tree-node';

	TreeNode.TEMPLATE = Supra.Template.compile('\
				<div class="background"></div>\
				<div class="marker"></div>\
				<div class="item">\
					<div class="edit">\
						<button type="button">Open</button>\
						<button type="button" class="button-edit"></button>\
					</div>\
					<div class="translate">\
						<button type="button">Translate</button>\
					</div>\
					<img src="{{ preview }}" onerror="this.src=\'/public/cms/supra/img/sitemap/preview/blank.jpg\';" alt="" />\
					<div class="highlight"></div>\
					{% if ! localized %}\
						<div class="status-not-localized">{{ "sitemap.status_not_created"|intl }}</div>\\n\
					\
					{# NB! is/not active actually means published/unpublished #}\
					\
					{% elseif type == "page" and ! published %}\\n\
						<div class="status-special status-not-published">{{ "sitemap.status_not_published"|intl }}</div>\
					{% elseif type == "page" and ! published_latest %}\\n\
						<div class="status-special status-draft">{{ "sitemap.status_draft"|intl }}</div>\
					{% elseif type == "page" and scheduled %}\\n\
						<div class="status-special status-scheduled">{{ "sitemap.status_scheduled"|intl }}</div>\
					{% endif %}\
				</div>\
				<label>{{ label|escape }}</label>\
				<div class="arrow"></div>\
				<div class="children"><div class="new-item-fake-preview"><div></div></div></div>\
			');

	TreeNode.ATTRS = {
		'tree': {
			'value': null
		},
		'view': {
			'value': null
		},

		'identifier': {
			'value': null
		},
		'label': {
			'value': null,
			'setter': '_setLabel'
		},
		'preview': {
			'value': null,
			'setter': '_setPreview'
		},
		'data': {
			'value': null
		},

		'draggable': {
			'value': false,
			'setter': '_setDraggable'
		},
		'droppable': {
			'value': false,
			'setter': '_setDroppable'
		},
		//Places where items can be dropped
		'droppablePlaces': {
			'value': {'inside': true, 'before': true, 'after': true}
		},
		//All children drag and drop is locked, used when draging this node
		'dndLocked': {
			'value': false,
			'setter': '_setDndLocked'
		},
		//Drag and drop mark position
		'dndMarker': {
			'value': false,
			'setter': '_setDndMarker'
		},
		// Drag and drop group ID for this node
		'groupId': {
			'value': null
		},

		'depth': {
			'value': 0
		},
		'index': {
			'value': 0,
			'getter': '_getIndex'
		},
		'parent': {
			'value': null,
			'setter': '_setParent'
		},
		'root': {
			'value': true,
			'setter': '_setAttributeClass'
		},

		'type': {
			'value': 'page'
		},
		'highlighted': {
			'value': null,
			'setter': '_setAttributeClass'
		},
		'expanded': {
			'value': false,
			'setter': '_setExpanded'
		},
		'expandable': {
			'value': false,
			'setter': '_setAttributeClass'
		},
		'selected': {
			'value': false,
			'setter': '_setAttributeClass'
		},
		'selectable': {
			'value': false,
			'setter': '_setAttributeClass'
		},
		'editable': {
			'value': false,
			'setter': '_setAttributeClass'
		},
		'publishable': {
			'value': false,
			'setter': '_setAttributeClass'
		},
		'global': {
			'value': false,
			'setter': '_setAttributeClass'
		},
		'localized': {
			'value': true,
			'setter': '_setAttributeClass'
		},
		'state': {
			'value': 'draft',
			'setter': '_setStateAttributeClass'
		},

		'children': {
			'value': null,
			'getter': '_getChildren'
		},
		'childrenRendered': {
			'value': false
		},

		'loading': {
			'value': false,
			'setter': '_setLoading'
		},
		'loadingNode': {
			'value': null
		},

		'childrenBox': {
			'value': null
		},
		'itemBox': {
			'value': null
		},

		'fullPath': {
			'value': null,
			'getter': '_getFullPath'
		},

		/**
		 * Child of this element is visible, but not siblings or ancestors
		 */
		'visibilityRoot': {
			'value': true,
			'setter': '_setVisibilityRoot'
		},

		'published': {
			'value': false
		},
		'active': {
			'value': false
		},
		'scheduled': {
			'value': false
		}

	};

	Y.extend(TreeNode, Y.Widget, {
		/**
		 * Node width constant, used to calculate
		 * children offset
		 * @type {Number}
		 */
		'WIDTH': 120,

		/**
		 * Drag and drop groups, all
		 * @type {Array}
		 */
		'DND_GROUPS': [
			'default',
			'new-page', 'restore-page',
			'new-template', 'restore-template',
			'new-group', 'new-application',
			'delete'
		],

		/**
		 * Group which is actually this one
		 * @type {String}
		 */
		'DND_GROUP_ID': 'default',

		/**
		 * Groups which are allowed to be dropped
		 * @type {Array}
		 */
		'DND_GROUPS_ALLOW': [
			'default',
			'new-page', 'restore-page',
			'new-template', 'restore-template',
			'new-group', 'new-application',
		],

		/**
		 * Additional properties for new item
		 * @type {Object}
		 */
		'NEW_CHILD_PROPERTIES': {},

		/**
		 * Root level children list
		 * @type {Array}
		 * @private
		 */
		'_children': [],

		/**
		 * Widget list
		 * @type {Array}
		 * @private
		 */
		'_widgets': {},

		/**
		 * Drag and drop object
		 * @type {Object}
		 * @private
		 */
		'_dnd': null,

		/**
		 * Drag and drop target
		 * @type {Object}
		 * @private
		 */
		'_dndTarget': null,
		'_dndTargetNode': null,
		'_dndTargetPlace': null,

		/**
		 * Currently expanding sibling, avoid doing some
		 * things twice
		 * @type {Boolean}
		 * @private
		 */
		'_expandingSibling': false,


		/**
		 * Render UI
		 *
		 * @private
		 */
		'renderUI': function () {
			var boundingBox = this.get('boundingBox'),
				contentBox = this.get('contentBox'),
				childrenBox = null,
				itemBox = null,
				buttons = null,
				button = null;


			//Render template
				contentBox.set('innerHTML', TreeNode.TEMPLATE(this.getAttrs()));


			//Render "Open" and "Edit" buttons
				this._widgets = {};

				buttons = contentBox.all('button');
				this._widgets['buttonEdit'] = new Supra.Button({'srcNode': buttons.item(1), 'style': 'sitemap-gray'});
				this._widgets['buttonOpen'] = new Supra.Button({'srcNode': buttons.item(0), 'style': 'sitemap-blue'});
				this._widgets['buttonTranslate'] = new Supra.Button({'srcNode': buttons.item(2), 'style': 'sitemap-blue'});

			//Move child container
				childrenBox = contentBox.one('div.children');

				boundingBox.append(childrenBox);
				this.set('childrenBox', childrenBox);

				itemBox = contentBox.one('div.item');
				this.set('itemBox', itemBox);


			//Render children
				this._children = [];

				if (this.get('expanded')) {
					this._renderChildren();
				} else {
					//If there are no children then set childrenRendered state
					//to true to prevent render being called on page move
					var children = this.get('data').children,
						count    = this.get('data').children_count;

					if (!count) {
						this.set('childrenRendered', true);
					}
				}

			//After small timeout render widgets
				Y.later(16, this, this._afterRenderUI);
		},

		/**
		 * Attach event listeners
		 *
		 * @private
		 */
		'bindUI': function () {
			this.get('itemBox').on('click', this.handleToggle, this);

			//"Open" button
			this._widgets.buttonOpen.on('click', this.handleSelect, this);

			//"Translate" button
			this._widgets.buttonTranslate.on('click', this.handleSelect, this);

			//"Edit" button
			this._widgets.buttonEdit.on('click', this.edit, this);

			//Context menu / left mouse button click
			this.get('itemBox').on('contextmenu', this.handleContentMenu, this);

			//Child change
			this.on('child:add', this.syncChildrenPosition, this);
			this.on('child:remove', this.syncChildrenPosition, this);
			//this.on('child:move', this.syncChildrenPosition, this);

			//Highlight change
			this.on('highlightedChange', this._setHighlighted, this);

			//Drag and drop
			this._dndBind();
		},

		/**
		 * Apply widget state to UI
		 *
		 * @private
		 */
		'syncUI': function () {
			var boundingBox = this.get('boundingBox'),
				attrClasses = ['root', 'editable', 'publishable', 'selectable', 'selected', 'expanded', 'expandable', 'global', 'localized'],
				i = 0,
				ii = attrClasses.length;

			for(; i<ii; i++) {
				if (this.get(attrClasses[i])) boundingBox.addClass(attrClasses[i]);
			}

			boundingBox.addClass('animate');
			boundingBox.addClass(this.get('type'));
			boundingBox.addClass(this.get('state'));
		},

		/**
		 * Clean up
		 * @private
		 */
		'destructor': function () {
			//Remove drag and drop
			this._dnd.destroy();

			//Destroy children
			var children = this._children;
			for(var i=0,ii=children.length; i<ii; i++) {
				children[i].destroy();
			}

			var widgets = this._widgets;
			for(var i in widgets) {
				widgets[i].destroy();
			}

			//Clean up references
			this._children = null;
			this._widgets = null;

			this.set('tree', null);
			this.set('view', null);
		},



		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */



		/**
		 * After UI render do same for widgets
		 * Render buttons
		 *
		 * @private
		 */
		'_afterRenderUI': function () {
			var widgets = this._widgets;
			for(var i in widgets) {
				widgets[i].render();
			}
		},

		/**
		 * Handle element toggle click
		 * Show or hide children
		 *
		 * @param {Event} e Event facade object
		 * @private
		 */
		'handleToggle': function (e) {
			if (!e.target.closest('.translate') && !e.target.closest('.edit') && !e.target.closest('.highlight')) {

				var view = this.get('tree').get('view');

				//Prevent overflow check, otherwise it will be done
				//on toggle and on center, while we need only on center
				view.set('disabled', true);

				//Toggle item
				this.toggle();

				view.set('disabled', false);
			}
		},

		/**
		 * On context menu open edit window
		 *
		 * @param {Event} e Event facade object
		 * @private
		 */
		'handleContentMenu': function (e) {
			this.edit();
			e.preventDefault();
		},

		/**
		 * Handle page select click
		 * Fire page:select event on tree
		 *
		 * @param {Event} e Event facade object
		 * @private
		 */
		'handleSelect': function (e) {
			if (this.get('selectable') && this.get('editable') && this.get('state') != 'temporary') {
				var params = {
					'data': this.get('data'),
					'node': this
				};

				if (this.get('tree').fire('page:select', params)) {
					this.set('selected', true);
				}
			}
		},

		/**
		 * Render children tree nodes
		 *
		 * @private
		 */
		'_renderChildren': function () {
			if (this.get('childrenRendered')) return;

			var data     = this.get('data'),
				children = data.children;

			if (children && children.length) {
				var tree = this.get('tree'),
					item = null,
					node = null,
					view = this.get('view'),
					depth = this.get('depth'),
					childrenBox = this.get('childrenBox');

				//Since permissions are not loaded yet, we assume that page
				//can't be edited
				for(var i=0,ii=children.length; i<ii; i++) {
					item = children[i];

					node = tree._createNode({
						'identifier': item._id,
						'data': item,

						'depth': depth + 1,
						'index': i,
						'parent': this
					});

					tree._index[item._id] = node;
					this._children.push(node);

					node.render(childrenBox);
				}

				this.syncChildrenPosition();
			}

			this.set('childrenRendered', true);
		},

		/**
		 * ------------------------------ DRAG & DROP ------------------------------
		 */



		/**
		 * Add drag and drop functionality
		 *
		 * @private
		 */
		'_dndBind': function () {
			var dnd = this._dnd = new Y.DD.Drag({
				node: this.get('boundingBox'),
				dragMode: 'point',
				target: true,
				groups: this.DND_GROUPS
			}).plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,			// Don't move original node at the end of drag
				cloneNode: true
			});

			dnd.set('treeNode', this);
			dnd.set('groupId', this.get('groupId') || this.DND_GROUP_ID);
			dnd.set('groupsAllow', this.DND_GROUPS_ALLOW);

			//Target config
			dnd.target.set('groupId', this.get('groupId') || this.DND_GROUP_ID);
			dnd.target.set('groupsAllow', this.DND_GROUPS_ALLOW);

			dnd.target.set('padding', '0px 10px 0px 10px')
			dnd.target.set('node', this.get('itemBox'));
			dnd.target.set('treeNode', this);

			if (!this.get('draggable')) {
				dnd.set('lock', true);
			}
			if (!this.get('droppable')) {
				dnd.target.set('lock', true);
			}

			//Set special style to proxy node
			dnd.on('drag:start', this._dndStart);

			//When starting drag all children must be locked to prevent
			//parent drop inside children
			dnd.on('drag:start', this._dndLockChildren, this);

			// When we leave drop target hide marker
			dnd.on('drag:exit', this.hideDropMarker, this);

			// When we move mouse over drop target update marker
			dnd.on('drag:over', this._dndOver, this);

			dnd.on('drag:end', this._dndEnd, this);
		},

		/**
		 * Disable drag and drop on children nodes
		 *
		 * @private
		 */
		'_dndLockChildren': function () {
			if (this.get('draggable')) {
				this.children().forEach(function (child) {
					child.set('dndLocked', true);
				});
			}
		},

		/**
		 * Adjust proxy position
		 *
		 * @param {Object} e Event facade object
		 * @private
		 */
		'_dndStart': function (e) {
			this._setStartPosition([ this.realXY[0] , this.nodeXY[1] + 6 ]);
			this.get('dragNode').addClass('su-tree-node-proxy');

			var node = this.get('treeNode');
			if (node) {
				var proxy_parent = node.get('tree').get('boundingBox').closest('.su-sitemap');
				proxy_parent.append(this.get('dragNode'));
			}
		},

		/**
		 * Find marker position
		 *
		 * @param {Object} e Event facade object
		 * @private
		 */
		'_dndOver': function(e) {
			var dragNode = e.drag.get('node'),
				dropNode = e.drop.get('node').closest('.su-tree-node'),
				view     = this.get('view'),

				group_id = e.drag.get('groupId'),
				groups   = e.drop.get('groupsAllow'),
				in_group = true;

			if (group_id && groups) {
				if (Y.Array.indexOf(groups, group_id) == -1) {
					in_group = false;
				}
			}

			if (!view.get('animating') && in_group) {
				if (!dropNode && !this.get('tree').size()) {
					//If tree doesn't have any children
					var target = this.get('tree');
					dropNode = e.drop.get('node').closest('.su-tree-content');

					this._dndTarget = target;
					this._dndTargetPlace = 'inside';
					this._dndTargetNode = Y.Node.getDOMNode(dropNode);

				} else if (dropNode && !dragNode.compareTo(dropNode)) {
					var target = e.drop.get('treeNode');
					if (!target || target.get('dndLocked')) return;

					var place = 'inside',
						node = Y.Node.getDOMNode(dropNode),
						siblingsAllowed = (!target.get('root') || this.get('tree').get('mode') != 'pages'),
						padding = 10,
						droppablePlaces = target.get('droppablePlaces'),

						dragMouse = e.drag.mouseXY,
						dropRegion = e.drop.region;

					if (!dropRegion) {
						return this.hideDropMarker(e);
					}

					if (siblingsAllowed) {
						if (droppablePlaces.before && dragMouse[0] < (dropRegion.left + padding)) {
							place = 'before';
						} else if (droppablePlaces.after && dragMouse[0] > (dropRegion.right - padding)) {
							place = 'after';
						}
					}

					if (place == 'inside'  && !droppablePlaces.inside) {
						return this.hideDropMarker(e);
					}

					if (node != this._dndTargetNode || place != this._dndTargetPlace) {
						if (this._dndTarget && this._dndTarget != target) {
							this._dndTarget.set('dndMarker', false);
						}
						if (target) {
							target.set('dndMarker', place);
						}

						this._dndTarget = target;
						this._dndTargetPlace = place;
						this._dndTargetNode = node;
					}
				}
			}
		},

		/**
		 * Drop
		 *
		 * @param {Object} e Event facade object
		 * @private
		 */
		'_dndEnd': function (e) {
			//Unlock children to allow them being draged
			if (this.get('draggable')) {
				this.children().forEach(function (child) {
					child.set('dndLocked', false);
				});
			}

			var target = this._dndTarget;
			if (!target) return;

			var tree = this.get('tree');

			//Drop was canceled
			if (this._dndTargetPlace === null) return;

			//Move page
			tree.insert(this, target, this._dndTargetPlace);

			//Fire event
			tree.fire('page:move', {'node': this, 'reference': target, 'position': this._dndTargetPlace});

			//Hide marker and cleanup data
			target.set('dndMarker', false);

			//Make sure node is not actually moved
			e.preventDefault();

			//Clean up
			this._dndTarget = null;
			this._dndTargetPlace = null;
			this._dndTargetNode = null;
		},

		/**
		 * Draw line from new item to its parent
		 *
		 * @private
		 */
		'_drawNewItemMakerLine': function (visible) {
			var target	= this.get('boundingBox'),
				node	= this.get('childrenBox').get('children').filter('.new-item-fake-preview').item(0),
				line	= node.one('div'),

				tpos	= target.getX(),
				npos	= node.getX(),

				diff	= npos - tpos - 59;

			if (!npos || diff <= 0 || !visible) {
				line.setStyle('display', 'none');
			} else {
				line.setStyles({
					'left': -diff + 'px',
					'width': diff + 'px',
					'display': 'block'
				});
			}
		},



		/**
		 * ------------------------------ API ------------------------------
		 */



		/**
		 * Hide marker node
		 *
		 * @private
		 */
		'hideDropMarker': function () {
			if (this._dndTarget) {
				this._dndTarget.set('dndMarker', false);
				this._dndTargetPlace = null;
				this._dndTargetNode = null;
				this._dndTarget = null;
			}
		},

		/**
		 * Update children container position
		 */
		'syncChildrenPosition': function () {
			var children = this.get('data').children,
				children_count = this.get('data').children_count,
				size = children.length,
				position = null,

				i = 0,
				count = this._children.length,
				self_width = this.WIDTH,
				width = null;

			if (count) {
				for(; i<count; i++) {
					width = this._children[i].WIDTH;
					if (width != self_width) {
						size += width / self_width - 1;
					}
				}
			}

			position = - (size - 1) * 50 + '%';

			this.get('childrenBox').setStyle('left', position);

			if (this.get('expanded') && !children.length) {
				this.set('expanded', false);
			}

			if (this.get('expandable') && !children_count) {
				this.set('expandable', false);
			} else if (!this.get('expandable') && children_count) {
				this.set('expandable', true);
			}
		},

		/**
		 * Start editing page
		 *
		 * @return TreeNode for call chaining
		 * @type {Object}
		 */
		'edit': function (e) {
			if (this.get('editable')) {
				var params = {
					'data': this.get('data'),
					'node': this
				};

				this.get('tree').fire('page:edit', params);
			}
		},

		/**
		 * Returns TreeNode by ID or index
		 *
		 * @param {String} id Tree node ID or child index
		 * @param {Boolean} strict If strict then will return null for invalid index
		 * @return Tree node
		 * @type {Object}
		 */
		'item': function (id, strict) {
			//By index
			if (typeof id === 'number') {
				//Allow negative as indexes
				var children = this._children,
					count = children.length;
				id = strict ? id : ((id < 0 ? (id % count + count) % count : id) % count);

				return children[id];
			}

			//By id or node
			var item = this.get('tree').item(id),
				parent = null;

			while(parent = item.get('parent')) {
				//Check if this is ancestor of node
				if (parent === this) return item;
			}

			return null;
		},

		/**
		 * Returns next sibling
		 *
		 * @return Tree node
		 * @type {Object}
		 */
		'next': function () {
			return this.get('parent').item(this.get('index') + 1, true);
		},

		/**
		 * Returns previous sibling
		 *
		 * @return Tree node
		 * @type {Object}
		 */
		'previous': function () {
			return this.get('parent').item(this.get('index') - 1, true);
		},

		/**
		 * Returns Y.Array with all children nodes
		 *
		 * @return Y.Array with children nodes
		 * @type {Object}
		 */
		'children': function (filter, one) {
			var children = new Y.Array(this._children);

			if (filter) {
				if (Y.Lang.isFunction(filter)) {
					children = children.filter(filter);
				} else if (Y.Lang.isObject(filter)) {
					children = children.filter(function (item) {
						for(var i in filter) {
							if (item.get(i) != filter[i]) return false;
						}
						return true;
					});
				}
			}

			return one ? children[0] || null : children;
		},

		/**
		 * Returns index of tree node or -1 if not found
		 *
		 * @param {Object} node TreeNode or tree node ID
		 * @return Tree node index
		 * @type {Number}
		 */
		'indexOf': function (node) {
			node = this.item(node);
			return node ? node.get('index') : -1;
		},

		/**
		 * Returns children count
		 *
		 * @return Children count
		 * @type {Number}
		 */
		'size': function () {
			return this._children.length;
		},

		/**
		 * Returns node index
		 *
		 * @return Node index in parents children list
		 * @type {Number}
		 * @private
		 */
		'index': function () {
			return this._getIndex();
		},

		/**
		 * Insert node 'before', 'after' or 'inside'
		 *
		 * @param {Object} node TreeNode, TreeNode ID or data for new TreeNdoe
		 * @param {String} where Point of reference
		 * @return TreeNode for call chaining
		 * @type {Object}
		 */
		'insert': function (node, where) {
			this.get('tree').insert(node, this, where);
			return this;
		},

		/**
		 * Alias of insert, inserts node 'before', 'after' or 'inside'
		 *
		 * @param {Object} node TreeNode, TreeNode ID or data for new TreeNdoe
		 * @param {String} where Point of reference
		 * @return TreeNode for call chaining
		 * @type {Object}
		 */
		'add': function (node, where) {
			this.get('tree').insert(node, this, where);
			return this;
		},

		/**
		 * Removes child node
		 * Data is not removed
		 *
		 * @param {Object} node TreeNode or tree node ID
		 * @return TreeNode for call chaining
		 * @type {Object}
		 */
		'remove': function (node) {
			var node = this.item(node);
			if (node) {
				this.get('tree').remove(node);
			}
			return this;
		},

		/**
		 * Removes all children nodes recursively
		 *
		 * @param {Boolean} destroy If true, then nodes will be destroyed instead of just removing them from tree
		 * @return TreeNode for call chaining
		 * @type {Object}
		 */
		'removeAll': function (destroy) {
			this.get('tree').removeAll(this, destroy);
			return this;
		},

		/**
		 * Expand children
		 *
		 * @return TreeNode for call chaining
		 * @type {Object}
		 */
		'expand': function () {
			this.set('expanded', true);
			return this;
		},

		/**
		 * Collapse children
		 *
		 * @return TreeNode for call chaining
		 * @type {Object}
		 */
		'collapse': function () {
			this.set('expanded', false);
			return this;
		},

		/**
		 * Toggle children
		 *
		 * @return TreeNode for call chaining
		 * @type {Object}
		 */
		'toggle': function () {
			this.set('expanded', !this.get('expanded'));
			return this;
		},

		/**
		 * Returns tree node child widget by ID
		 * Child widgets are not child tree nodes
		 *
		 * @param {String} id Widget ID
		 * @return Widget
		 * @type {Object}
		 */
		'getWidget': function (id) {
			return this._widgets[id] || null;
		},

		/**
		 * Update page full path
		 */
		'updateFullPath': function () {
			var data = this.get('data'),
				parent = this.get('parent');

			//Not using this.get('fullPath') because it itterates through all ancestors
			if (data.path) {
				while(parent && !parent.get('data').full_path) {
					parent = parent.get('parent');
				}

				if (parent) {
					data.full_path = parent.get('data').full_path + (data.path ? data.path + '/' : '');
				} else {
					data.full_path = '/' + (data.path ? data.path + '/' : '');
				}
			}

			//Update all children full paths
			var children = this.children(),
				i = 0,
				ii = children.length;

			for (; i<ii; i++) {
				children[i].updateFullPath();
			}
		},


		/**
		 * ------------------------------ ATTRIBUTES ------------------------------
		 */


		/**
		 * Label attribute setter
		 *
		 * @param {String} label New label value
		 * @return Label attribute value
		 * @type {String}
		 * @private
		 */
		'_setLabel': function (label) {
			//Do anything only if already rendered
			if (!this.get('rendered')) return label;

			this.get('contentBox').one('label').set('text', label);
			return label;
		},

		/**
		 * Preview attribute setter
		 *
		 * @param {String} draggable New preview value
		 * @return Preview attribute value
		 * @type {String}
		 * @private
		 */
		'_setPreview': function (preview) {
			//Do anything only if already rendered
			if (!this.get('rendered')) return preview;

			if (!preview) {
				preview = '/public/cms/supra/img/sitemap/preview/blank.jpg';
			}

			this.get('itemBox').one('img').setAttribute('src', preview);
			return preview;
		},

		/**
		 * Draggable attribute setter
		 *
		 * @param {Boolean} draggable New draggable value
		 * @return Draggable attribute value
		 * @type {Boolean}
		 * @private
		 */
		'_setDraggable': function (draggable) {
			//Page root node can't be dragged
			if (this.get('root') && this.get('tree').get('mode') == 'pages') {
				return false;
			}
			if (this._dnd && !this.get('dndLocked')) {
				this._dnd.set('lock', !draggable);
			}

			return !!draggable;
		},

		/**
		 * Droppable attribute setter
		 *
		 * @param {Boolean} droppable New droppable value
		 * @return Droppable attribute value
		 * @type {Boolean}
		 * @private
		 */
		'_setDroppable': function (droppable) {
			if (this._dnd && this._dnd.target && !this.get('dndLocked')) {
				this._dnd.target.set('lock', !droppable);
			}

			return !!droppable;
		},

		/**
		 * dndLocked attribute setter
		 *
		 * @param {Boolean} locked Lock all children
		 * @return New dndLocked attribute value
		 * @type {Boolean}
		 * @private
		 */
		'_setDndLocked': function (locked, attr) {
			var children = new Y.Array(this._children);

			children.forEach(function (child) {
				child.set(attr, locked);
			});

			if (this._dnd) {
				if (this.get('draggable') || locked) {
					this._dnd.set('lock', locked);
				}
				if (this.get('droppable') || locked) {
					if (this._dnd.target) {
						this._dnd.target.set('lock', locked);
					}
				}
			}

			return !!locked;
		},

		/**
		 * Set marker position, dndMarker attribute setter
		 *
		 * @param {String} marker Marker position
		 * @return New marker position
		 * @type {String}
		 * @private
		 */
		'_setDndMarker': function (marker) {
			var node = this.get('boundingBox'),
				tree = this.get('tree');

			if (marker != 'inside') {
				tree.stopExpand();
			}

			if (marker == 'inside') {

				//Expand item to allow drop on children
				if (this.get('expandable') && !this.get('expanded')) {
					// Expand after 300ms
					tree.expand(this, 300);
				}

				node.removeClass('marker-before').removeClass('marker-after').addClass('marker-inside');

				// Draw line
				if (this.get('expandable')) {
					this._drawNewItemMakerLine(true);
				} else {
					this._drawNewItemMakerLine(false)
				}

			} else if (marker == 'before') {
				node.removeClass('marker-inside').removeClass('marker-after').addClass('marker-before');
			} else if (marker == 'after') {
				node.removeClass('marker-before').removeClass('marker-inside').addClass('marker-after');
			} else {
				node.removeClass('marker-before').removeClass('marker-after').removeClass('marker-inside');
			}

			return marker;
		},

		/**
		 * Parent attribute setter
		 *
		 * @param {Object} parent New parent TreeNode or Tree if root node
		 * @return New parent attribute value
		 * @type {Object}
		 * @private
		 */
		'_setParent': function (parent) {
			parent = parent ? this.get('tree').item(parent) : null;

			//Update depth
			this.set('depth', parent ? (parent.get('depth') || 0) + 1 : 0);

			//Reset path
			this.set('fullPath', null);

			return parent;
		},

		/**
		 * Children attribute getter
		 *
		 * @return Y.Array of all children nodes
		 * @type {Object}
		 * @private
		 */
		'_getChildren': function () {
			return new Y.Array(this._children);
		},

		/**
		 * Index attribute getter
		 *
		 * @return Node index in parents children list
		 * @type {Number}
		 * @private
		 */
		'_getIndex': function () {
			var parent = this.get('parent'),
				children = null;

			if (parent) {
				children = parent._children;

				for(var i=0,ii=children.length; i<ii; i++) {
					if (children[i] === this) return i;
				}
			}

			return -1;
		},

		/**
		 * Expanded attribute setter
		 *
		 * @param {Boolean} expanded Expanded state value
		 * @return New expanded state value
		 * @type {Boolean}
		 */
		'_setExpanded': function (expanded) {
			//Do anything only if already rendered
			if (!this.get('rendered')) return !!expanded;
			if (!this.get('expandable')) return false;

			if (expanded != this.get('expanded')) {
				var result = null;

				if (expanded) {
					var data = this.get('data'),
						tree = this.get('tree'),
						dataObject = tree.get('data');

					//Wait till data is loaded
					if (dataObject.isLoading(data.id)) return false;

					//Load data
					if (!dataObject.isLoaded(data.id)) {
						return this._setExpandedLoad();
					}

					if (data.children.length) {
						this._collapseSiblings();

						//Visibility root node
						if (!this.get('root')) {
							tree.set('visibilityRootNode', this.get('parent'));
						}

						result = this._setExpandedExpand();
					} else {
						result = false;
					}

				} else {
					//Update visibility root node
					var tree = this.get('tree'),
						parent = this.get('parent');

					if (!this.get('root') && tree.get('visibilityRootNode') === parent) {
						//If none of the siblings is being expanded then set visibility root
						//node one level up
						if (!this._expandingSibling) {
							tree.visibilityRootNodeUp();
						}
					}

					//Collapse
					result = this._setExpandedCollapse();
				}

				//on second level nodes expand hide all other roots
				if( ! this.get('root') && this.get('parent').get('root')) {
					//second level nodes
					var rootNodes = this.get('parent').get('parent').children();

					for (var i in rootNodes) {
						if (rootNodes[i] !== this) {
							rootNodes[i].get('boundingBox').toggleClass('visibility-root', expanded);
						}
					}
				}

				Y.later(16, this, this._afterToggle);

				return result;
			}

			return !!expanded;
		},

		/**
		 * Collapse all expanded siblings
		 *
		 * @private
		 */
		'_collapseSiblings': function () {
			//Find expanded sibling and collapse it
			var parent = this.get('parent');

			if (parent) {
				parent.children().some(function (item) {
					if (item.get('expanded')) {
						item._expandingSibling = true;
						item.set('expanded', false);
						item._expandingSibling = false;
						return true;
					}
				}, this);
			}
		},

		/**
		 * Load children data
		 *
		 * @private
		 */
		'_setExpandedLoad': function () {
			var id = this.get('data').id,
				tree = this.get('tree'),
				dataObject = tree.get('data');

			this.set('loading', true);
			tree.once('load:success:' + id, this._setExpandedAfterLoad, this);
			dataObject.load(id);

			return false;
		},

		/**
		 * After data is loaded expand item
		 *
		 * @private
		 */
		'_setExpandedAfterLoad': function () {
			this.set('loading', false);
			this.set('expanded', true);
		},

		/**
		 * Expand children
		 *
		 * @private
		 */
		'_setExpandedExpand': function () {
			//Render children
			if (!this.get('childrenRendered')) {
				this._renderChildren();
			}

			//Expand
			this.get('boundingBox').removeClass('children-hidden');
			Y.later(16, this, this._afterExpand);

			//"expanded" new state
			return true;
		},

		/**
		 * Expand children
		 *
		 * @private
		 */
		'_setExpandedCollapse': function () {
			this.get('boundingBox').removeClass('expanded');
			Y.later(250, this, this._afterCollapse);

			//"expanded" new state
			return false;
		},

		/**
		 * After expand add class which will animate node
		 *
		 * @private
		 */
		'_afterExpand': function () {
			//Check if nothing changed
			if (this.get('expanded')) {
				this.get('boundingBox').addClass('expanded');

				//Add all children as targets
				this.children().forEach(function (item) {
					if (item._dnd) {
						item._dnd.target.addToGroup('default');

						//Unlock
						if (Y.DD.DDM.activeDrag) {
							item._dnd.target.set('lock', false);
						}
					}
				}, this);

				//Make sure drop on children works if expanded during drag
				this.get('view').resetDropCache(true);

				//Update arrows
				this.get('view').checkOverflow();

				//Fire event
				this.fire('expanded');
			}
		},

		/**
		 * After toggle center the sitemap
		 *
		 * @private
		 */
		'_afterToggle': function() {
			this.get('tree').get('view').set('disabled', false);

			//Only if has children
			if (this.size()) {

				if (this.get('expanded')) {
					this.get('tree').get('view').center(this);
				} else {
					this.get('tree').get('view').center(this.get('parent'));
				}
			}
		},

		/**
		 * After collapse hide children
		 *
		 * @private
		 */
		'_afterCollapse': function () {
			//Check if nothing changed
			if (!this.get('expanded')) {
				this.get('boundingBox').addClass('children-hidden');

				//Collapse all children and remove them from drop targets
				this.children().forEach(function (item) {
					if (item._dnd) {
						item._dnd.target.removeFromGroup('default');
					}
					if (item.get('expanded')) {
						item.set('expanded', false);
					}
				}, this);

				//Make sure drop on children works if expanded during drag
				this.get('view').resetDropCache();

				//Update arrows
				this.get('view').checkOverflow();

				//Fire event
				this.fire('collapsed');
			}
		},

		/**
		 * Highlighted attribute change event handler
		 *
		 * @param {Event} e Event facade object
		 * @private
		 */
		'_setHighlighted': function (e) {
			if (!e.silent) {
				if (e.newVal) {
					this.get('tree').set('highlighted', this, {'silent': true});
				} else {
					this.get('tree').set('highlighted', false, {'silent': true});
				}
			}
		},

		/**
		 * On attribute change add or remove classname
		 *
		 * @param {Boolean} value New attribute value
		 * @param {String} key Attribute name
		 * @return New attribute value
		 * @type {Boolean}
		 * @private
		 */
		'_setAttributeClass': function (value, key) {
			//Do anything only if already rendered
			if (!this.get('rendered')) return !!value;

			if (value) {
				this.get('boundingBox').addClass(key);
			} else {
				this.get('boundingBox').removeClass(key);
			}

			return !!value;
		},

		/**
		 * On state attribute change add or remove classname
		 *
		 * @param {String} value New attribute value
		 * @return New attribute value
		 * @type {String}
		 * @private
		 */
		'_setStateAttributeClass': function (value) {
			//Do anything only if already rendered
			if (!this.get('rendered')) return value;
			var prevValue = this.get('state');

			if (value != prevValue) {
				if (prevValue) {
					this.get('boundingBox').removeClass(prevValue);
				}
				if (value) {
					this.get('boundingBox').addClass(value);
				}
			}

			return value;
		},

		/**
		 * Full path attribute getter
		 *
		 * @param {String} value Previous value
		 * @return Full path to page
		 * @type {String}
		 * @private
		 */
		'_getFullPath': function (value) {
			if (value) return value;
			var parent = this.get('parent'),
				path = this.get('data').path || '',
				fullPath = '/';

			if (parent) {
				fullPath = parent.get('fullPath') || '/';
			}
			if (path) {
				fullPath += path + '/';
			}

			return fullPath;
		},

		/**
		 * Loading attribute setter
		 *
		 * @param {Boolean} loading Loading state
		 * @return Loading state value
		 * @type {Boolean}
		 * @private
		 */
		'_setLoading': function (loading) {
			var node = this.get('loadingNode'),
				box = this.get('itemBox');

			if (loading) {
				if (!node) {
					node = Y.Node.create('<div class="loading-icon"></div>');
					this.set('loadingNode', node);
					box.append(node);
				}
				box.addClass('loading');
			} else if (node) {
				box.removeClass('loading');
			}

			return !!loading;
		},

		/**
		 * visibilityRoot attribute setter
		 *
		 * @param {Boolean} is_visibility_root New value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		'_setVisibilityRoot': function (is_visibility_root) {
			if (this.get('rendered')) {

				//Hide content node and show arrow
				this.get('boundingBox').toggleClass('visibility-root', is_visibility_root);

				//Hide siblings
				this.get('boundingBox').get('parentNode').toggleClass('visibility-siblings', is_visibility_root);

				return !!is_visibility_root;
			} else {
				return false;
			}
		}
	});


	Action.TreeNode = TreeNode;


	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};

}, YUI.version, {'requires': ['website.sitemap-tree', 'supra.template', 'dd']});