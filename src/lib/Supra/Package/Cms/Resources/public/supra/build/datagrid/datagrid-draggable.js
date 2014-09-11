/**
 * Continuous loader plugin
 */
YUI.add('supra.datagrid-draggable', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function DraggablePlugin (config) {
		DraggablePlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a DataGrid instance, the plugin will be 
	// available on the "loader" property.
	DraggablePlugin.NS = 'draggable';
	
	// Attributes
	DraggablePlugin.ATTRS = {
		/**
		 * Allow sorting elements using drag and drop
		 */
		'dd-sort': {
			value: false
		},
		
		/**
		 * Allow deleting elements using drag and drop
		 */
		'dd-delete': {
			value: false
		},
		
		/**
		 * Allow insert new item using drag and drop
		 */
		'dd-insert': {
			value: false
		}
	};
	
	// Extend Plugin.Base
	Y.extend(DraggablePlugin, Y.Plugin.Base, {
		
		/**
		 * Drag and drop delegation instance
		 * @type {Object}
		 * @private
		 */
		delegate: null,
		
		/**
		 * Table body node drop instance
		 * @type {Object}
		 * @private
		 */
		drop: null,
		
		/**
		 * Old previous node
		 */
		old_prev_node: null,
		
		/**
		 * Old next node
		 */
		old_next_node: null,
		
		/**
		 * Temporary new item node
		 */
		temp_node: null,
		
		
		
		/**
		 * Update drop targets
		 */
		refresh: function () {
			if (this.delegate) {
				this.delegate.syncTargets();
			}
		},
		
		/**
		 * Add class to proxy element for custom style
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		decorateProxy: function (e) {
			var row = this.get('host').getRowByNode(e.target.get('node'));
			if (!row) return;
			
			var td = row.getTitleColumnNode();
			if (!td) return;
			
			var content = td.get('childNodes').item(0).cloneNode(true),
				drag_node = e.target.get('dragNode');
			
			//Set offset from mouse
			e.target.deltaXY = [-16, 16];
			
			drag_node.empty().append(content);
			drag_node.setStyles({'width': td.get('offsetWidth') - 26 + 'px'});
			drag_node.addClass('su-datagrid-proxy');
		},
		
		/**
		 * Remove class from proxy to make sure we don't break
		 * proxy for other drag and drops
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		undecorateProxy: function (e) {
			e.target.get('dragNode').removeClass('su-datagrid-proxy');
		},
		
		/**
		 * Constructor
		 * 
		 * @private
		 */
		initializer: function () {
			var host = this.get('host');
			host.after('render', this.bindUI, this);
		},
		
		/**
		 * On drag start save which is previous node to compare
		 * on drag end if anything changed
		 * 
		 * @private
		 */
		sortableDragStart: function (e) {
			this.old_prev_node = e.target.get('node').previous();
			this.old_next_node = e.target.get('node').next();
		},
		
		/**
		 * On drag over swap elements if sorting is enabled
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		sortableDragOver: function (e, ignore) {
			//New item is not handled by sortable
			if (e.drag._groups['new-item'] && ignore !== true) return;
			
			var drag = e.drag.get('node'),
				drop = e.drop.get('node'),
				from = null,
				to   = null;
			
			//Same element
			if (drag.compareTo(drop)) {
				return;
			}
			
			//Move element
			if (e.drag.region.top < e.drop.region.top) {
				from = drag.next();
				to = drag;
				drop.insert(drag, 'after');
			} else {
				to = drag.previous();
				from = drag;
				drop.insert(drag, 'before');
			}
			
			this.refreshDropElements(from, to);
		},
		
		/**
		 * If sorting is enabled, then on drop trigger event
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		sortableDrop: function (e) {
			var drag = e.target.get('node'),
				record = null,
				new_prev = drag.previous(),
				new_next = drag.next(),
				old_prev = this.old_prev_node,
				old_next = this.old_next_node;
			
			//If both are empty (first childs before and now) or the same node, then nothing changed
			if ((!new_prev && !old_prev) || (new_prev && old_prev && new_prev.compareTo(old_prev))) {
				return;
			}
			
			//Find records from nodes
			record = this.get('host').getRowByNode(drag);
			old_prev = old_prev ? this.get('host').getRowByNode(old_prev) : null;
			old_next = old_next ? this.get('host').getRowByNode(old_next) : null;
			new_prev = new_prev ? this.get('host').getRowByNode(new_prev) : null;
			new_next = new_next ? this.get('host').getRowByNode(new_next) : null;
			
			//If droped not on datagrid, then restore position
			if (e.drop && !e.drop.inGroup('datagrid')) {
				this.get('host').add(record, old_next);
				return;
			}
			
			this.get('host').fire('drag:sort', {
				'record': record,
				'newRecordPrevious': new_prev,
				'newRecordNext': new_next,
				'oldRecordPrevious': old_prev,
				'oldRecordNext': old_next
			});
			
			//Clean up
			this.old_prev_node = null;
			this.old_next_node = null;
		},
		
		/**
		 * On drag over insert "New item" element into data grid
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		newItemDragOver: function (e) {
			//Only if 'new-item' is in dragged item group list
			if (!e.drag._groups['new-item']) return;
			
			var drag = this.temp_node,
				drop = e.drop.get('node'),
				from = null,
				to   = null;
			
			//Create element
			if (!drag) {
				this.temp_node = drag = Y.Node.create('<tr class="yui3-dd-dragging"><td colspan="' + this.get('host').getColumns().length + '">' + Supra.Intl.get(['crud', 'new_item']) + '</td></tr>');
			}
			
			//Move element
			if (e.drag.region.top < e.drop.region.top) {
				from = drag.next();
				to = drag;
				drop.insert(drag, 'after');
			} else {
				to = drag.previous();
				from = drag;
				drop.insert(drag, 'before');
			}
			
			this.refreshDropElements(from, to);
		},
		
		/**
		 * On drag over main element add item to the top
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		newItemDragOverMain: function (e) {
			//Only if 'new-item' is present in draggable items groups
			if (!e.drag._groups['new-item']) return;
			
			var drag = this.temp_node,
				drop = e.drop.get('node').one('tbody');
			
			if (drop) {
				//Create element
				if (!drag) {
					this.temp_node = drag = Y.Node.create('<tr class="yui3-dd-dragging"><td colspan="' + this.get('host').getColumns().length + '">' + Supra.Intl.get(['crud', 'new_item']) + '</td></tr>');
				}
				
				drop.prepend(drag);
				this.refreshDropElements();
			}
		},
		
		
		/**
		 * On drag exit remove "New item" element from data grid
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		newItemDragExitMain: function (e) {
			if (this.temp_node) {
				this.temp_node.remove();
				this.refreshDropElements();
			}
		},
		
		/**
		 * Handle new item drop
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		newItemDrop: function (e) {
			if (!e.drag._groups['new-item'] || !this.temp_node) return;
			
			if (this.get('dd-sort') && this.get('host').rows.length) {
				var drag = this.temp_node,
					prev = drag.previous(),
					next = drag.next();
				
				//Find records from nodes
				prev = prev ? this.get('host').getRowByNode(prev) : null;
				next = next ? this.get('host').getRowByNode(next) : null;
				
				this.get('host').fire('drag:insert', {
					'recordPrevious': prev,
					'recordNext': next
				});	
			} else {
				this.get('host').fire('drag:insert');	
			}
			
			//Remove element from DOM
			this.temp_node.remove();
		},
		
		/**
		 * Update drop target cached position. It will be updated for all nodes between
		 * from and to
		 * 
		 * @param {Y.Node} from Node from which to update
		 * @param {Y.Node} to Node to which to update
		 */
		refreshDropElements: function (from, to) {
			if (!from) from = this.get('host').tableBodyNode.get('firstChild');
			
			//Update drop regions for all elements between drag previous position and now
			while(from && (!to || !from.compareTo(to))) {
				if (from.drop) {
					from.drop.sizeShim();
				}
				from = from.next();
			}
			
			if (to && to.drop) {
				to.drop.sizeShim();
			}
			
			this.get('host').handleChange();
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			var host = this.get('host'),
				container = host.tableBodyNode,
				del = null;
			
			//Items can be dragged only if sorting is allowed to drag and drop deleting
			//items is allowed
			if (this.get('dd-sort') || this.get('dd-delete')) {
				//Delegation is faster and easier
				this.delegate = del = new Y.DD.Delegate({
					container: container,
					nodes: 'tr',
					target: this.get('dd-sort') || this.get('dd-insert'),
					dragConfig: {
						//Data grid elements can be dropped on other data grid items
						//and in recycle bin
						groups: ['datagrid', 'recycle-bin']
					}
				});
				
				//Use proxy
				del.dd.plug(Y.Plugin.DDProxy, {
					moveOnEnd: false
				});
				
				//Style proxy element
				del.on('drag:start', this.decorateProxy, this);
				del.on('drag:end', this.undecorateProxy, this);
			}
			
			//
			if (this.get('dd-sort')) {
				//On drag start save previous element
				del.on('drag:start', this.sortableDragStart, this);
				
				//On drag over swap elements
				del.on('drop:over', this.sortableDragOver, this);
				
				//On drop trigger event
				del.on('drag:drophit', this.sortableDrop, this);
				del.on('drag:dropmiss', this.sortableDrop, this);
			}
			
			if (this.get('dd-insert')) {
				this.drop = new Y.DD.Drop({
					'node': container.closest('.su-datagrid-content'),
					'groups': ['new-item']
				});
				
				this.drop.on('drop:hit', this.newItemDrop, this);
				this.drop.on('drop:exit', this.newItemDragExitMain, this);
				this.drop.on('drop:over', this.newItemDragOverMain, this);
				
				if (del) {
					del.on('drop:hit', this.newItemDrop, this);
					
					if (this.get('dd-sort')) {
						//On drag move element
						del.on('drop:over', this.newItemDragOver, this);
					} else {
						//On drag over add element to the table
						del.on('drop:over', this.newItemDragOverMain, this);
					}
				} else {
					this.drop.on('drop:over', this.newItemDragOverMain, this);
				}
			}
			
			//When data is reloaded update drop targets
			host.on('load:success', this.refresh, this);
		},
		
		/**
		 * Destructor
		 * 
		 * @private
		 */
		destructor: function () {
			
		}
		
	});
	
	Supra.DataGrid.DraggablePlugin = DraggablePlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'dd-delegate', 'dd-drop', 'supra.datagrid']});