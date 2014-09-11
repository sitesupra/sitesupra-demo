/**
 * Manager Action plugin to automatically hide container when
 * action 'visible' attribute changes
 */
YUI.add('supra.manager-action-plugin-container', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Action = Supra.Manager.Action;
	
	function PluginContainer () {
		PluginContainer.superclass.constructor.apply(this, arguments);
		this.children = {};
	};
	
	PluginContainer.NAME = 'PluginContainer';
	
	Y.extend(PluginContainer, Action.PluginBase, {
		
		initialize: function () {
			//On visibility change show/hide container
			this.host.on('visibleChange', function (evt) {
				var node = this.one();
				if (node && evt.newVal != evt.prevVal) {
					node.toggleClass('hidden', !evt.newVal);
					
					if (evt.newVal) {
						this.fire('show');
					} else {
						this.fire('hide');
					}
				}
			});
		}
		
	});
	
	Action.PluginContainer = PluginContainer;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base']});