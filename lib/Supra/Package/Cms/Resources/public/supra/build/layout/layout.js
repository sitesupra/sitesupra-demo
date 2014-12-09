/*
 * Supra.PluginLayout
 */
YUI.add('supra.plugin-layout', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function PluginLayout () {
		PluginLayout.superclass.constructor.apply(this, arguments);
	}
	
	PluginLayout.NAME = 'plugin-playout';
	PluginLayout.NS = "layout";
	PluginLayout.ATTRS = {
		/**
		 * Offset margins
		 * [left, top, right, bottom]
		 */
		'offset': {
			'value': [0,0,0,0]
		},
		
		/**
		 * Layout is calculated for layout boundary element
		 * Meaning that width and height must be set in px
		 */
		'layoutBoundary': {
			'value': false
		},
		
		/**
		 * Number of ms to disable UI interactions for
		 * Usefull if layout is animated to increase performance
		 */
		'supressInteractions': {
			'value': 0
		},
		
		/**
		 * Number of ms for throttle
		 */
		'throttle': {
			'value': 0
		}
	};
	
	Y.extend(PluginLayout, Y.Plugin.Base, {
		
		/**
		 * List of widgets/nodes used for offset
		 * @type {Array}
		 */
		offsets: [],
		
		/**
		 * Last know offset position
		 * Used to trigger sync only if something actually changes
		 * @type {Object}
		 */
		last_offset_position: {},
		
		/**
		 * Sync function
		 * @type {Function}
		 */
		sync_function: null,
		
		/**
		 * Initialize plugin
		 * 
		 * @constructor
		 * @param {Object} config
		 */
		initializer: function (config) {
			//Reset value
			this.offsets = [];
			
			var throttle = this.get('throttle');
			if (throttle) {
				this.sync_function = Supra.throttle(this.syncUI, throttle, this);
			} else {
				this.sync_function = Y.bind(function () {
					// We still want small delay to prevent non-smooth animations when
					// two different components change layout, for example one block
					// calls to hide sidebar and another one right after that to show it
					Supra.immediate(this, this.syncUI);
				}, this);
			}
			
			this.sync_function();
			
			//On window resize sync position
			Y.on('resize', this.sync_function);
		},
		
		/**
		 * Update position
		 */
		syncUI: function () {
			// If layout is disabled for some reason, then
			if (this.get('host').get('layoutDisabled') === true) return;
			
			var config = this.get('offset'),
				changed = {'left': false, 'top': false, 'right': false, 'bottom': false},
				offset = {'left': config[0], 'top': config[1], 'right': config[2], 'bottom': config[3]},
				offsets = this.offsets,
				pos = null,
				size = null,
				xy = null,
				node = null,
				win_w = Math.max(980, Y.DOM.winWidth()),
				win_h = Y.DOM.winHeight(),
				last_offset_position = this.last_offset_position,
				has_changes = false;
			
			for(var i=0,ii=offsets.length; i<ii; i++) {
				pos = offsets[i].pos;
				changed[pos] = true;
				
				//Only visible nodes
				if (!offsets[i].widget || offsets[i].widget.get('visible')) {
					node = offsets[i].node;
					xy = node.getXY();
					size = [node.get('offsetWidth'), node.get('offsetHeight')];
					pos = offsets[i].pos;
					
					switch(pos) {
						case 'top':
							offset[pos] = Math.max(offset[pos], xy[1] + size[1] + offsets[i].margin); break;
						case 'bottom':
							offset[pos] = Math.max(offset[pos], win_h - xy[1] + offsets[i].margin); break;
						case 'left':
							offset[pos] = Math.max(offset[pos], xy[0] + size[0] + offsets[i].margin); break;
						case 'right':
							offset[pos] = Math.max(offset[pos], win_w - xy[0] + offsets[i].margin); break;
					}
				}
			}
			
			if (last_offset_position) {
				if (last_offset_position.top !== offset.top || last_offset_position.left !== offset.left || last_offset_position.bottom !== offset.bottom || last_offset_position.right !== offset.right) {
					has_changes = true;
				}
			} else {
				has_changes = true;
			}
			
			if (has_changes) {
				var host = this.get('host'),
					node = host.get('boundingBox'),
					style = {},
					info = {},
					duration = this.get('supressInteractions');
				
				if (!node && host.isInstanceOf('Node')) node = host;
				if (!node) node = host.get('srcNode');
				
				if (node) {
					if (this.get('layoutBoundary')) {
						for(var pos in changed) {
							if (changed[pos]) {
								info[pos] = offset[pos];
							}
						}
						
						style.transform = 'translate(' + offset.left + 'px, ' + offset.top + 'px)';
						style.width = win_w - offset.right - offset.left + 'px';
						style.height = win_h - offset.top - offset.bottom + 'px';
						
						node.setStyles(style);
					} else {
						for(var pos in changed) {
							if (changed[pos]) {
								info[pos] = offset[pos];
								style[pos] = offset[pos] + 'px';
							}
						}
						
						node.setStyles(style);
					}
					
					if (node.isInstanceOf('NodeList')) node = node.item(0);
					if (node) node.fire('contentresize');
					
					if (duration) {
						this.disableUserInteraction(node, duration);
					}
					
					this.last_offset_position = offset;
					this.fire('sync', {'offset': info});
				}
			}
		},
		
		/**
		 * Add offset widget
		 * 
		 * @param {Object} node Widget or node
		 * @param {String} pos
		 * @param {Object} margin
		 */
		addOffset: function (widget, node, pos, margin) {
			if (widget.after) {
				widget.after('contentresize', this.sync_function, this);
				widget.after('visibleChange', function (evt) {
					if (evt.prevVal != evt.newVal) {
						this.sync_function(evt);
					}
				}, this);
				
				if (widget.isInstanceOf('Tabs')) widget.after('activeTabChange', this.sync_function, this);
			}
			
			var node = node.isInstanceOf('Node') ? node : (widget ? widget.get('boundingBox') : null);
			
			if (widget || node) {
				this.offsets.push({
					'node': node,
					'widget': widget,
					'pos': pos,
					'margin': margin
				});
			}
		},
		
		
		/* ------------------------------ Interactions ------------------------------ */
		
		
		/**
		 * Disable all user interactions for number of milliseconds
		 * 
		 * @param {Object} node Node for which to disable
		 * @param {Number} duration Number of milliseconds
		 * @private
		 */
		disableUserInteraction: function (node, duration) {
			node.addClass('no-pointer-events');
			
			Supra.later(duration, this, function () {
				node.removeClass('no-pointer-events');
			});
		}
	
	});
	
	PluginLayout.TOP = 'top';
	PluginLayout.BOTTOM = 'bottom';
	PluginLayout.LEFT = 'left';
	PluginLayout.RIGHT = 'right';
	
	Supra.PluginLayout = PluginLayout;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'plugin']});
