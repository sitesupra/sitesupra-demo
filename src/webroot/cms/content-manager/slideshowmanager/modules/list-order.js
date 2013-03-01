YUI.add('slideshowmanager.list-order', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Slide list ordering
	 */
	function SlideListOrder (config) {
		SlideListOrder.superclass.constructor.apply(this, arguments);
	}
	
	SlideListOrder.NAME = 'slideshowmanager-order';
	SlideListOrder.NS = 'order';
	
	SlideListOrder.ATTRS = {
	};
	
	Y.extend(SlideListOrder, Y.Plugin.Base, {
		
		/**
		 * Drag and drop delegate object
		 * @type {Object}
		 */
		del: null,
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			
			var container = this.get('host').get('listNode'),
				del = null,
				
				fnDragDrop = Y.bind(this.onDragDrop, this),
				fnDragStart = Y.bind(this.onDragStart, this),
				fnDropOver = Y.bind(this.onDropOver, this);
			
			this.del = del = new Y.DD.Delegate({
				'container': container,
				'nodes': 'li',
				'target': true,
				'invalid': '.new-item',
				'dragConfig': {
					'haltDown': false,
					'clickTimeThresh': 1000
				}
			});
			
			del.dd.addInvalid('.new-item');
			
			del.dd.plug(Y.Plugin.DDProxy, {
				'moveOnEnd': false,
				'cloneNode': true,
				'resizeFrame': false
			});
			
			del.on('drag:start', fnDragStart);
			del.on('drag:over', fnDropOver);
			del.on('drag:end', fnDragDrop);
		},
		
		/**
		 * Update targets
		 */
		update: function () {
			if (this.del) {
				this.del.syncTargets();
			}
		},
		
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
	        proxy.addClass('proxy');
	        
	        this.originalDragIndex = node.get('parentNode').get('children').indexOf(node);
	        this.lastDragIndex = this.originalDragIndex;
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
		        selector = 'li',
		        invalid = this.del.get('invalid'),
		        index = 0,
		        dragGoingUp = false,
		        indexFrom = 0,
		        indexTo = 0;
			
		    //Are we dropping on a li node?
		    if (drop.test(selector) && !drop.test(invalid)) {
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
			        //evt.drop.get('node').get('parentNode').append(drag);
			        evt.drop.get('node').get('parentNode').insertBefore(drag, this.get('host').get('newItemControl'));
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
		},
		
		/**
		 * Handle drag:drop event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDragDrop: function () {
			if (this.originalDragIndex != this.lastDragIndex) {
				this.get('host').fire('order', {
					'indexDrag': this.originalDragIndex,
					'indexDrop': this.lastDragIndex
				});
			}
		}
		
	});
	
	Supra.SlideshowManagerListOrder = SlideListOrder;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'dd']});