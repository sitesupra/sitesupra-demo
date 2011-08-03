//Invoke strict mode
"use strict";

SU('transition', 'supra.htmleditor', function (Y) {

	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
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
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Dependancy list
		 * @type {Array}
		 */
		DEPENDANCIES: ['LayoutContainers'],
		
		
		
		
		/**
		 * List of buttons
		 * @type {Object}
		 * @private
		 */
		buttons: {},
		
		/**
		 * Tab instance
		 * @type {Object}
		 * @see Supra.Tabs
		 * @private
		 */
		tabs: {},
		
		/**
		 * Toolbar instance
		 * @type {Object}
		 * @see Supra.HTMLEditorToolbar
		 * @private
		 */
		toolbar: null,
		
		
		
		
		
		/**
		 * On action create it's possible to change place holder
		 * @private
		 */
		create: function () {
			//Add action as top bar child
			Manager.getAction('LayoutTopContainer').addChildAction('EditorToolbar');
		},
		
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
			this.toolbar.get('boundingBox').addClass('yui3-editor-toolbar-html');
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
				'id': 'done',
				'callback': Y.bind(function () {
					var active_content = Manager.PageContent.getActiveContent();
						active_content.fire('block:save');
				}, this)
			}/*, {
				'id': 'close',
				'callback': Y.bind(function () {
					var active_content = Manager.PageContent.getActiveContent();
						active_content.fire('block:cancel');
				}, this)
			}*/]);
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			if (!this.get('created')) return;
			
			//Hide toolbar
			var group_node = this.toolbar.get('contentBox').one('div.yui3-editor-toolbar-main-content');
			group_node.transition({
				'duration': 0.35,
				'easing': 'ease-out',
				'marginTop': '50px'
			}, Y.bind(function () {
				this.toolbar.set('visible', false);
				Manager.getAction('LayoutTopContainer').fire('resize');
			}, this));
			
			//Show button toolbar
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			
			//Toggle classnames
			var nodes = this.toolbar.groupNodes;
			for(var id in nodes) {
				nodes[id].addClass('yui3-editor-toolbar-' + id + '-hidden');
			}
			
			//Hide "Done", "Close" buttons
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function (dontShow) {
			if (dontShow) return;
			
			//Show toolbar and resize container
			this.toolbar.set('visible', true);
			Manager.getAction('LayoutTopContainer').fire('resize');
			
			//Add empty button set to PageToolbar to hide buttons
			var pagetoolbar = Manager.getAction('PageToolbar');
			if (!pagetoolbar.hasActionButtons(this.NAME)) {
				pagetoolbar.addActionButtons(this.NAME, []);
			}
			pagetoolbar.setActiveAction(this.NAME);
			
			//Show toolbar
			var group_node = this.toolbar.get('contentBox').one('div.yui3-editor-toolbar-main-content');
			group_node.transition({
				'duration': 0.35,
				'easing': 'ease-out',
				'marginTop': '0px'
			});
			
			//Toggle classnames
			var nodes = this.toolbar.groupNodes;
			for(var id in nodes) {
				nodes[id].removeClass('yui3-editor-toolbar-' + id + '-hidden');
			}
			
			//Show "Done", "Close" buttons
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			this.show();
		}
	});
	
});