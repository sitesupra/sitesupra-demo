YUI.add('gallerymanager.itemlist-order', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	/*
	 * Editable content
	 */
	function ItemListOrder (config) {
		ItemListOrder.superclass.constructor.apply(this, arguments);
	}
	
	ItemListOrder.NAME = 'gallerymanager-itemlist-order';
	ItemListOrder.NS = 'order';
	
	ItemListOrder.ATTRS = {
		'disabled': {
			value: false
		}
	};
	
	Y.extend(ItemListOrder, Y.Plugin.Base, {
		
		/**
		 * Drag delegation
		 * @type {Object}
		 * @private
		 */
		dragDelegate: null,
		
		/**
		 * Drag and drop direction
		 * @type {Boolean}
		 * @private
		 */
		dragGoingUp: false,
		
		/**
		 * Last known drag node index
		 * @type {Number}
		 * @private
		 */
		lastDragIndex: 0,
		
		
		/**
		 * 
		 */
		initializer: function () {
			var itemlist = this.get('host'),
				container = itemlist.get('listNode');
			
			this.listeners = [];
			this.listeners.push(itemlist.after('listNodeChange', this.reattachListeners, this));
			
			if (container) {
				this.reattachListeners();
			}
		},
		
		destructor: function () {
			this.resetAll();
			
			// Listeners
			var listeners = this.listeners,
				i = 0,
				ii = listeners.length;
			
			for (; i<ii; i++) listeners[i].detach();
			this.listeners = null;
		},
		
		/**
		 * Attach drag and drop listeners
		 */
		reattachListeners: function () {
			if (this.get('disabled')) return;
			
			var itemlist = this.get('host'),
				container = itemlist.get('listNode');
			
			if (!container) {
				// Nothing to attach listeneres to
				return;
			}
			
			var childSelector = itemlist.getChildSelector(),
				
				fnDragDrag = Y.bind(this.onDragDrag, this),
				fnDragStart = Y.bind(this.onDragStart, this),
				fnDropOver = Y.bind(this.onDropOver, this),
				
				del = null;
			
			// Set iframe document as main one
			Y.DD.DDM.regDoc(itemlist.getDocument());
			
			// Faster and easier than create separate dragables
			del = this.dragDelegate = new Y.DD.Delegate({
				container: container,
				nodes: childSelector + ':not(.supra-gallerymanager-new)',
				target: {},
				dragConfig: {
					haltDown: false
				}
			});
			
			// There is most likely overflow:hidden set on the list, need to use proxy
			del.dd.plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,
				cloneNode: true
			});
			
			// Inline editable shouldn't trigger drag
			del.dd.addInvalid('.yui3-input-string-inline-focused');
			del.dd.addInvalid('.yui3-input-text-inline-focused');
			del.dd.addInvalid('.yui3-imageeditor-focused');
			
			del.on('drag:drag', fnDragDrag);
			del.on('drag:start', fnDragStart);
			del.on('drag:over', fnDropOver);
			
			// On new item add or remove sync targets
			itemlist.on('addItem', this.dragDelegate.syncTargets, this.dragDelegate);
			itemlist.on('removeItem', this.dragDelegate.syncTargets, this.dragDelegate);
		},
		
		/**
		 * Reset all iframe content bindings, etc.
		 */
		resetAll: function () {
			var dragDelegate = this.dragDelegate;
			
			if (dragDelegate) {
				dragDelegate.destroy(true);
				this.dragDelegate = null;
			}
		},
		
		
		/* -------------- Drag and drop --------------- */
		
		
		/**
		 * Handle drag:start event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDragStart: function (evt) {
			//Get our drag object
	        var drag = evt.target,
	        	proxy = drag.get('dragNode'),
	        	node = drag.get('node');
			
	        //Set proxy styles
	        proxy.addClass('supra-gallerymanager-proxy');
	        proxy.removeClass('su-gallerymanager-focused');
	        
	        //Move proxy to body
	       	Y.Node(this.get('host').getDocument().body).append(proxy);
	        
	        this.lastDragIndex = node.get('parentNode').get('children').indexOf(node);
	        this.fire('dragStart');
		},
		
		/**
		 * Handle drag:drag event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDragDrag: function (evt) {
			/*
			var x = evt.target.lastXY[0];
			
			this.dragGoingUp = (x < this.lastDragX);
		    this.lastDragX = x;
		    */
		},
		
		/**
		 * Handle drop:over event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDropOver: function (evt) {
			//Get a reference to our drag and drop nodes
		    var drag = evt.drag.get('node'),
		        drop = evt.drop.get('node'),
		        selector = this.get('host').getChildSelector(),
		        index = 0,
		        dragGoingUp = false,
		        indexFrom = 0,
		        indexTo = 0;
			
		    //Are we dropping on a li node?
		    if (drop.test(selector)) {
			    index = drop.get('parentNode').get('children').indexOf(drop);
			    dragGoingUp = index < this.lastDragIndex;
			    
			    indexFrom = Math.min(index, this.lastDragIndex);
			    indexTo = Math.max(index, this.lastDragIndex);
			    this.lastDragIndex = index;
			    
			    //Are we not going up?
		        if (!dragGoingUp) {
		            drop = drop.get('nextSibling');
		        }
		        
				if (!dragGoingUp && !drop) {
			        evt.drop.get('node').get('parentNode').append(drag);
				} else {
			        evt.drop.get('node').get('parentNode').insertBefore(drag, drop);
				}
				
		        //Resize node shims, so we can drop on them later since position may
		        //have changed
		        var nodes = drop.get('parentNode').get('children'),
		        	dropObj = null;
		        
		        for (var i=indexFrom; i<= indexTo; i++) {
		        	dropObj = nodes.item(i).drop;
		        	if (dropObj) {
		        		dropObj.sizeShim();
		        	}
		        }
		    }
		}
		
	});
	
	Supra.GalleryManagerItemListOrder = ItemListOrder;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'dd-delegate']});