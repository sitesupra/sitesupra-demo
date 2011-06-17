/*
 * SU.PluginLayout
 */
YUI.add('supra.plugin-layout', function (Y) {
	
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
		 * Use throttle
		 */
		'throttle': {
			'value': 100
		}
	};
	
	Y.extend(PluginLayout, Y.Plugin.Base, {
		
		/**
		 * List of widgets/nodes used for offset
		 * @type {Array}
		 */
		offsets: [],
		
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
				this.sync_function = this._sync_fn = this.throttle(this.syncUI, throttle, this);
			} else {
				this.sync_function = this.syncUI();
			}
			
			this.sync_function();
		},
		
		/**
		 * Throttle function call
		 * 
		 * @param {Function} fn
		 * @param {Number} ms
		 * @param {Object} context
		 */
		throttle: function (fn, ms, context) {
			ms = (ms) ? ms : 150;
			
			if (true || ms === -1) {
				return (function() {
					fn.apply(context, arguments);
				});
			}
			
			var last = (new Date()).getTime();
			var t = null;
			
			return (function() {
				var now = (new Date()).getTime();
				if (now - last > ms) {
					last = now;
					fn.apply(context, arguments);
					clearTimeout(t);
				} else {
					clearTimeout(t);
					t = setTimeout(arguments.callee, ms);
				}
			});
		},
		
		/**
		 * Update position
		 */
		syncUI: function () {
			var config = this.get('offset');
			var changed = {'left': false, 'top': false, 'right': false, 'bottom': false};
			var offset = {'left': config[0], 'top': config[1], 'right': config[2], 'bottom': config[3]},
				offsets = this.offsets,
				pos = null,
				xy = null,
				node = null,
				win_w = Y.DOM.winWidth(),
				win_h = Y.DOM.winHeight();
			
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
			
			var host = this.get('host');
			var node = host.get('boundingBox');
			var style = {};
			
			if (!node) node = host.get('srcNode');
			
			if (node) {
				for(var pos in changed) {
					if (changed[pos]) {
						style[pos] = offset[pos] + 'px';
					}
				}
				node.setStyles(style);
			}
		},
		
		/**
		 * Add offset widget
		 * 
		 * @param {Object} node Widget or node
		 * @param {String} pos
		 * @param {Object} margin
		 */
		addOffset: function (node, pos, margin) {
			if (node.after) {
				node.after('resize', this.sync_function, this);
				node.after('visibleChange', this.sync_function, this);
				
				if (node.isInstanceOf('Tabs')) node.after('activeTabChange', this.sync_function, this);
			}
			
			var widget = node.isInstanceOf('Widget') ? node : null;
				node = node.isInstanceOf('Node') ? node : (widget ? widget.get('boundingBox') : null);
			
			if (widget || node) {
				this.offsets.push({
					'node': node,
					'widget': widget,
					'pos': pos,
					'margin': margin
				});
			}
		}
	
	});
	
	PluginLayout.TOP = 'top';
	PluginLayout.BOTTOM = 'bottom';
	PluginLayout.LEFT = 'left';
	PluginLayout.RIGHT = 'right';
	
	SU.PluginLayout = PluginLayout;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'plugin']});