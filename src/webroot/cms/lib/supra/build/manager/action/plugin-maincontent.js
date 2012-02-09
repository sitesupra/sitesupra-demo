//Invoke strict mode
"use strict";

/**
 * Manager Action plugin to automatically set container as main
 * content and resize if left, right or top (Header) containers changes
 */
YUI.add('supra.manager-action-plugin-maincontent', function (Y) {
	
	var Action = Supra.Manager.Action;
	
	function PluginMainContent () {
		PluginMainContent.superclass.constructor.apply(this, arguments);
		this.children = {};
	};
	
	PluginMainContent.NAME = 'PluginMainContent';
	
	Y.extend(PluginMainContent, Action.PluginBase, {
		
		initialize: function () {
			var layoutTopContainer = SU.Manager.getAction('LayoutTopContainer'),
				layoutLeftContainer = SU.Manager.getAction('LayoutLeftContainer'),
				layoutRightContainer = SU.Manager.getAction('LayoutRightContainer');
			
			//Container position sync with other actions
			this.host.plug(SU.PluginLayout, {
				'offset': [0, 0, 0, 0]	//Default offset from page viewport
			});
			
			//Offsets from other containers 
			this.host.layout.addOffset(layoutTopContainer, layoutTopContainer.one(), 'top', 0);
			this.host.layout.addOffset(layoutLeftContainer, layoutLeftContainer.one(), 'left', 0);
			this.host.layout.addOffset(layoutRightContainer, layoutRightContainer.one(), 'right', 0);
			
			//On visible change and execute reposition container
			this.host.after('execute', function (e) {
				//Update position
				this.host.layout.syncUI();
			}, this);
			
			this.host.on('visibleChange', function (e) {
				if (e.newVal) {
					//Update position
					this.host.layout.syncUI();
				}
			}, this);
			
			this.host.layout.syncUI();
		}
		
	});
	
	Action.PluginMainContent = PluginMainContent;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base']});