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
		'dragSelector': {
			'value': null
		},
		'dragContainerSelector': {
			'value': null
		},
		'proxyClass': {
			'value': null
		},
		'dropSelector': {
			'value': null
		},
		'targetClass': {
			'value': null
		}
	};
	
	// Extend Plugin.Base
	Y.extend(DDPlugin, Y.Plugin.Base, {
		
		/**
		 * Constructor
		 */
		initializer: function (config) {
			var host = config.host,
				container = host.one();
			
			var del = this.del = new Y.DD.Delegate({
				'container': container.one(config.dragContainerSelector),	//The common container
				'nodes': config.dragSelector,								//The items to make draggable
				'target': {},
			});
			
			//Add drop targets
			container.all(config.dropSelector).each(function (item) {
				var drop = new Y.DD.Drop({
					'node': item
				});
			});
			
			//Add proxy to drag
			del.dd.plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,
				cloneNode: true
			});
			
			//Set special style to proxy node
			del.on('drag:start', this.ddDragStart, this);
			
			// When we leave drop target hide marker
			del.on('drag:exit', this.ddDragExit, this);
			
			// When we move mouse over drop target update marker
			del.on('drag:over', this.ddDragOver, this);
			
			del.on('drag:end', this.ddDragEnd, this);
		},
		
		/**
		 * Add drag node
		 */
		addDrag: function (node) {
			var dd = null;
			
			if (node.isInstanceOf('Drag')) {
				dd = node;
			} else {
				var dd = new Y.DD.Drag({
					'node': node,
					'target': false
				});
				dd.plug(Y.Plugin.DDProxy, {
					moveOnEnd: false,
					cloneNode: true
				});
			}
			
			//Set special style to proxy node
			dd.on('drag:start', this.ddDragStart, this);
			
			// When we leave drop target hide marker
			dd.on('drag:exit', this.ddDragExit, this);
			
			// When we move mouse over drop target update marker
			dd.on('drag:over', this.ddDragOver, this);
			
			dd.on('drag:end', this.ddDragEnd, this);
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
					drop_id = this.drop_target.ancestor('.userlist-group').getAttribute('data-group');
					
				if (!drag_id || !this.drop_target.compareTo(node.ancestor())) {
					this.fire('drop', {
						'drag_id': drag_id,
						'drop_id': drop_id,
						'drag_node': node,
						'drop_node': this.drop_target
					});
				}
				
				this.drop_target = null;
			}
			
			//Make sure node is not actually moved
			e.preventDefault();
		}
		
	});
	
	Supra.ListDD = DDPlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['dd', 'dd-delegate']});
