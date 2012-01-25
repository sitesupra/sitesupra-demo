//Invoke strict mode
"use strict";

YUI.add('supra.manager-action-plugin-layout-sidebar', function (Y) {
	
	var Manager = Supra.Manager,
		Action = Manager.Action;
	
	function PluginSidebar () {
		PluginSidebar.superclass.constructor.apply(this, arguments);
	};
	
	PluginSidebar.NAME = 'PluginSidebar';
	
	Y.extend(PluginSidebar, Action.PluginBase, {
		
		/**
		 * Initialize plugin
		 */
		initialize: function () {
			var node = null;
			
			node = this.host.one('.sidebar-header');
			this.host.addAttr('headerNode', {
				value: node
			});
			this.host.addAttr('headerVisible', {
				value: node && !node.hasClass('hidden'),
				setter: Y.bind(this._setHeaderVisible, this)
			});
			
			node = this.host.one('.sidebar-footer');
			this.host.addAttr('footerNode', {
				value: node
			});
			this.host.addAttr('footerVisible', {
				value: node && !node.hasClass('hidden'),
				setter: Y.bind(this._setFooterVisible, this)
			});
			
			node = this.host.one('.sidebar-buttons');
			this.host.addAttr('buttonsNode', {
				value: node
			});
			this.host.addAttr('buttonsVisible', {
				value: node && !node.hasClass('hidden'),
				setter: Y.bind(this._setButtonsVisible, this)
			});
			
			/*
			 * In frozen state if sidebar is hidden then toolbar buttons
			 * will not be removed
			 */
			this.host.addAttr('toolbarButtonsFrozen', {
				value: false
			});
			
			this.host.after('visibleChange', this._afterVisibleChange, this);
		},
		
		/**
		 * Set header visibility
		 * 
		 * @param {Boolean} val Visibility state
		 * @return New visibility state
		 * @type {Boolean}
		 */
		_setHeaderVisible: function (val) {
			var node = this.host.one('.sidebar-header');
			var cont = this.host.one('.sidebar-content');
			
			if (val && node) {
				node.removeClass('hidden');
				if (cont) cont.addClass('has-header');
			} else {
				if (node) node.addClass('hidden');
				if (cont) cont.removeClass('has-header');
				val = false;
			}
			
			return !!val;
		},
		
		/**
		 * Set footer visibility
		 * 
		 * @param {Boolean} val Visibility state
		 * @return New visibility state
		 * @type {Boolean}
		 */
		_setFooterVisible: function (val) {
			var node = this.host.one('.sidebar-footer');
			var cont = this.host.one('.sidebar-content');
			
			if (val && node) {
				node.removeClass('hidden');
				if (cont) cont.addClass('has-footer');
			} else {
				if (node) node.addClass('hidden');
				if (cont) cont.removeClass('has-footer');
				val = false;
			}
			
			return !!val;
		},
		
		/**
		 * Set buttons visibility
		 * 
		 * @param {Boolean} val Visibility state
		 * @return New visibility state
		 * @type {Boolean}
		 */
		_setButtonsVisible: function (val) {
			var node = this.host.one('.sidebar-buttons');
			var cont = this.host.one('.sidebar-content');
			
			if (val && node) {
				node.removeClass('hidden');
				if (cont) cont.addClass('has-buttons');
			} else {
				if (node) node.addClass('hidden');
				if (cont) cont.removeClass('has-buttons');
				val = false;
			}
			
			return !!val;
		},
		
		/**
		 * On visibility change show/hide toolbar buttons
		 */
		_afterVisibleChange: function (evt) {
			if (evt.newVal != evt.prevVal) {
				var toolbar = Manager.getAction('PageToolbar'),
					buttons = Manager.getAction('PageButtons'),
					container = this.host.one();
				
				if (evt.newVal) {
					//Show container
					if (container) container.removeClass('hidden');
					
					//Show action
					if (this.host.LAYOUT_CONTAINER) {
						Manager.getAction(this.host.LAYOUT_CONTAINER).setActiveAction(this.host.NAME);
					}
					
					//Show buttons
					toolbar.setActiveAction(this.host.NAME);
					buttons.setActiveAction(this.host.NAME);
				} else {
					if (!this.host.get('toolbarButtonsFrozen')) {
						//Hide buttons
						toolbar.unsetActiveAction(this.host.NAME);
						buttons.unsetActiveAction(this.host.NAME);
					}
					
					//Hide action
					if (this.host.LAYOUT_CONTAINER) {
						Manager.getAction(this.host.LAYOUT_CONTAINER).unsetActiveAction(this.host.NAME);
					}
					
					//Hide container
					if (container) container.addClass('hidden');				
				}
				
			}
		},
		
		/**
		 * Render
		 */
		render: function () {
			PluginSidebar.superclass.render.apply(this, arguments);
		},
		
		/**
		 * Execute
		 */
		execute: function () {
			PluginSidebar.superclass.execute.apply(this, arguments);
			this.host.show();
		}
		
	});
	
	Action.PluginLayoutSidebar = PluginSidebar;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base', 'supra.input']});