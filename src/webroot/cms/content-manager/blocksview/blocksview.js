//Invoke strict mode
"use strict";

SU(function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Add as right bar child
	Manager.getAction('LayoutRightContainer').addChildAction('BlocksView');
	
	//Create Action class
	new Action(Supra.Manager.Action.PluginContainer, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'BlocksView',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		/**
		 * Block list
		 */
		blocks: null,
		
		
		
		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': this.hide
			}]);
			
			
			var container = this.one('ul.block-list');
			
			//Bind listeners
			container.delegate('mouseenter', function (evt) {
				var target = evt.target.closest('LI'),
					content_id = target.getAttribute('data-id');
				
				this.blocks[content_id].set('highlightOverlay', true);
			}, 'li', this);
			
			container.delegate('mouseleave', function (evt) {
				var target = evt.target.closest('LI'),
					content_id = target.getAttribute('data-id');
				
				this.blocks[content_id].set('highlightOverlay', false);
			}, 'li', this);
			
			container.delegate('click', function (evt) {
				this.hide();
				
				var target = evt.target.closest('LI'),
					content_id = target.getAttribute('data-id'),
					contents = null;
				
				//Start editing content
				contents = Manager.PageContent.getContent();
				contents.set('activeChild', this.blocks[content_id]);
				
				//Show properties form
				/*
				if (this.blocks[content_id].properties) {
					this.blocks[content_id].properties.showPropertiesForm();
				}
				*/
			}, 'li', this);
		},
		
		/**
		 * Render block list
		 */
		renderData: function () {
			var blocks = this.blocks = Manager.PageContent.getContent().getAllChildren(),
				block = null,
				block_type = null,
				block_definition = null,
				container = this.one('ul.block-list'),
				template_data = [],
				item = null;
			
			for(var id in blocks) {
				//If not locked and is not list
				if (!blocks[id].isLocked() && !blocks[id].isInstanceOf('page-content-list')) {
					block = blocks[id];
					block_definition = block.getBlockInfo();
					
					template_data.push({
						'id': id,
						'title': block_definition.title,
						'icon': block_definition.icon
					});
				}
			}
			
			//Render items
			container.set('innerHTML', Supra.Template('pageBlockListItems', {'blocks': template_data}));
		},
		
		
		/**
		 * Hide action
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Hide buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Hide action
			Manager.getAction('LayoutRightContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			//Show buttons
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			//Show content
			Manager.getAction('LayoutRightContainer').setActiveAction(this.NAME);
			
			//
			this.renderData();
		}
	});
	
});