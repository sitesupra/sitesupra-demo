//Invoke strict mode
"use strict";

SU('supra.htmleditor', function (Y) {

	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	//Add as left bar child
	Manager.getAction('LayoutTopContainer').addChildAction('EditorToolbar');
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'EditorToolbar',
		
		/**
		 * No template for toolbar
		 * @type {Boolean}
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Style toolbar
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * List of buttons
		 * @type {Object}
		 */
		buttons: {},
		
		/**
		 * Tab instance
		 * @type {Object}
		 * @see Supra.Tabs
		 */
		tabs: {},
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			
			this.toolbar = new Supra.HTMLEditorToolbar();
			
		},
		
		/**
		 * Returns Supra.HTMLEditorToolbar instance
		 * 
		 * @return Toolbar instance
		 * @type {Object}
		 */
		getToolbar: function () {
			return this.toolbar;
		},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		render: function () {
			
			this.toolbar.render(this.getPlaceHolder());
			this.toolbar.hide();
			
			this.on('visibleChange', function (evt) {
				if (evt.prevVal != evt.newVal) {
					this.toolbar.set('visible', evt.newVal);
				}
			}, this);
			
			this.on('disabledChange', function (evt) {
				if (this.toolbar.get('disabled') != evt.newVal) {
					this.toolbar.set('disabled', evt.newVal);
				}
			}, this);
			
			
			//Add "Apply", "Close" buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'apply',
				'callback': Y.bind(function () {
					/* @TODO Apply changes */
					Manager.PageContent.getIframe().contents.set('activeContent', null);
				}, this)
			}, {
				'id': 'close',
				'callback': Y.bind(function () {
					/* @TODO Revert */
					Manager.PageContent.getIframe().contents.set('activeContent', null);
				}, this)
			}]);
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			Manager.getAction('LayoutTopContainer').unsetActiveAction(this.NAME);
			
			//Toggle classnames
			var nodes = this.toolbar.groupNodes;
			for(var id in nodes) {
				nodes[id].addClass('yui3-editor-toolbar-' + id + '-hidden');
			}
			
			//Hide "Apply", "Close" buttons
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function (dontShow) {
			if (dontShow) return;
			
			Manager.getAction('LayoutTopContainer').setActiveAction(this.NAME);
			
			//Toggle classnames
			var nodes = this.toolbar.groupNodes;
			for(var id in nodes) {
				nodes[id].removeClass('yui3-editor-toolbar-' + id + '-hidden');
			}
			
			//Show "Apply", "Close" buttons
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
		}
	});
	
});