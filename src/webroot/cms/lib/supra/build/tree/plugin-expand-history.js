YUI.add('supra.tree-plugin-expand-history', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function ExpandHistoryPlugin (config) {
		ExpandHistoryPlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a tree instance, the plugin will be 
	// available on the "state" property.
	ExpandHistoryPlugin.NS = 'state';
	
	ExpandHistoryPlugin.ATTRS = {
		'idProperty': {
			value: 'id'
		}
	};
	
	// Extend Plugin.Base
	Y.extend(ExpandHistoryPlugin, Y.Plugin.Base, {
		
		state: [],
		restoring: false,
		
		saveState: function (event) {
			if (this.restoring) return;
			
			var id = String(event.data[this.get('idProperty')]);
			var index = Y.Array.indexOf(this.state, id);
			var changed = false;
			
			if (!event.newVal && index != -1) {
				this.state.splice(index, 1);
				changed = true;
			} else if (event.newVal && index == -1) {
				this.state.push(id);
				changed = true;
			}
			
			if (changed) {
				var state = this.state.join(',');
				Y.Cookie.set('tree-state', state);
			}
		},
		
		restoreState: function () {
			this.restoring = true;
			var host = this.get('host'), node;
			
			host.collapseAll();
			
			var state = Y.Cookie.get('tree-state');
			if (state) {
				this.state = state = state.split(',');
				for(var i=0,ii=state.length; i<ii; i++) {
					node = host.getNodeById(state[i]);
					if (node) node.expand();
				}
			}
			
			this.restoring = false;
		},
		
		/**
		 * Constructor
		 */
		initializer: function () {
			var host = this.get('host');
			
			host.on('toggle', this.saveState, this);
			host.on('render:complete', this.restoreState, this);
		}
		
	});
	
	Supra.Tree.ExpandHistoryPlugin = ExpandHistoryPlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'cookie', 'supra.tree']});