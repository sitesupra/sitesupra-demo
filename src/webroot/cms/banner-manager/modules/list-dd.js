//Invoke strict mode
"use strict";

YUI.add('website.list-dd', function (Y) {
	
	/**
	 * New page tree plugin allows adding new page using drag & drop
	 */	
	function DDPlugin (config) {
		DDPlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a tree instance, the plugin will be 
	// available on the "state" property.
	DDPlugin.NS = 'dd';
	
	DDPlugin.ATTRS = {
		'dragContainerSelector': {
			'value': null
		},
		'proxyClass': {
			'value': null
		},
		'targetClass': {
			'value': null
		}
	};
	
	// Extend Plugin.Base
	Y.extend(DDPlugin, Y.Plugin.Base, {
		
		/**
		 * Drop instances
		 * @type {Array}
		 * @private
		 */
		drops: null,
		
		/**
		 * Constructor
		 */
		initializer: function (config) {
			this.drops = [];
		},
		
		addDrop: function (node) {
			var drop = new Y.DD.Drop({
				'node': node
			});
			
			this.drops.push(drop);
			
			return drop;
		},
		
		removeDrops: function () {
			for(var i=0,ii=this.drops.length; i<ii; i++) {
				this.drops[i].destroy();
			}
			this.drops = [];
		},
		
		/**
		 * Add drag node
		 */
		addDrag: function (node) {
			var dd = new Y.DD.Drag({
				'node': node,
				'target': false
			});
			dd.plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,
				cloneNode: true
			});
			
			//Set special style to proxy node
			dd.on('drag:start', this.ddDragStart, this);
			
			// When we leave drop target hide marker
			dd.on('drag:exit', this.ddDragExit, this);
			
			// When we move mouse over drop target update marker
			dd.on('drag:over', this.ddDragOver, this);
			
			dd.on('drag:end', this.ddDragEnd, this);
			
			return dd;
		},
		
		/**
		 * Handle drag start event
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		ddDragStart: function (e /* Event */) {
			var target = e.target.get('dragNode'),
				container = this.get('host').one(this.get('dragContainerSelector'));
			
			target.addClass(this.get('proxyClass'));
			container.append(target);
		},
		
		/**
		 * Handle mouse exit from drop target
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		ddDragExit: function (e /* Event */) {
			var target = e.drop.get('node');
			
			target.ancestor().removeClass(this.get('targetClass'));
			this.drop_target = null;
		},
		
		/**
		 * Handle mouse over drop target
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		ddDragOver: function (e /* Event */) {
			var target = e.drop.get('node');
			
			target.ancestor().addClass(this.get('targetClass'));
			this.drop_target = target;
		},
		
		/**
		 * Handle drag end event
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		ddDragEnd: function (e /* Event */) {
			if (this.drop_target) {
				//Remove drop target style
				this.drop_target.ancestor().removeClass(this.get('targetClass'));
				
				var node = e.target.get('node'),
					drag_id = node.getAttribute('data-id'),
					drop_id = this.drop_target.ancestor().getAttribute('data-group');
				
				if (!drag_id || !this.drop_target.compareTo(node.ancestor())) {
					this.fire('drop', {
						'drag_id': drag_id,
						'drop_id': drop_id,
						'drop_node': this.drop_target
					});
					
					if (drag_id) {
						//Move node
						this.drop_target.append(node);
					}
				}
			}
			
			//Make sure node is not actually moved
			e.preventDefault();
		},
		
	});
	
	Supra.ListDD = DDPlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['dd', 'dd-delegate']});