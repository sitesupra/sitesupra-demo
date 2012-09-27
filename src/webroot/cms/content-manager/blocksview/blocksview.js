Supra(function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Create Action class
	new Action(Action.PluginLayoutSidebar, {
		
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
		 * @type {Object}
		 * @private
		 */
		blocks: null,
		
		/**
		 * Type of blocks which are shown
		 * either 'blocks' or 'palceholders'
		 * @type {String}
		 * @private
		 */
		type: '',
		
		/**
		 * Layout container action NAME
		 * @type {String}
		 * @private
		 */
		LAYOUT_CONTAINER: 'LayoutRightContainer',
		
		
		
		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			
			var container = this.one('ul.block-list');
			
			//Bind listeners
			container.delegate('mouseenter', function (evt) {
				var target = evt.target.closest('LI'),
					content_id = target.getAttribute('data-id');
				
				if (this.type == 'blocks') {
					//Blocks
					this.blocks[content_id].set('highlightOverlay', true);
				} else {
					//Place holders
					Manager.PageContent.getContent().set('highlight', true);
					this.blocks[content_id].set('highlight', true);
				}
				
			}, 'li', this);
			
			container.delegate('mouseleave', function (evt) {
				var target = evt.target.closest('LI'),
					content_id = target.getAttribute('data-id');
				
				if (this.type == 'blocks') {
					//Blocks
					this.blocks[content_id].set('highlightOverlay', false);
				} else {
					//Place holders
					Manager.PageContent.getContent().set('highlight', false);
					this.blocks[content_id].set('highlight', false);
				}
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
			
			//Control button
			this.get('controlButton').on('click', this.hide, this);
		},
		
		/**
		 * Render block list
		 */
		renderData: function () {
			if (!this.type) return;
			
			var blocks = this.blocks = Manager.PageContent.getContent().getAllChildren(),
				block = null,
				block_type = null,
				block_definition = null,
				container = this.one('ul.block-list'),
				template_data = [],
				item = null,
				is_placeholder = false,
				title = '',
				icon = '';
			
			//Update heaidng
			var heading = this.one('h2');
			if (this.type == 'blocks') {
				heading.set('text', Supra.Intl.get(['blocks', 'title']));
			} else {
				heading.set('text', Supra.Intl.get(['placeholders', 'title']));
			}
			
			//Update block list
			for(var id in blocks) {
				//If not closed and is not list
				if (!blocks[id].isClosed()) {
					is_placeholder = blocks[id].isInstanceOf('page-content-list');
					
					if ((this.type == 'blocks' && !is_placeholder) || (this.type != 'blocks' && is_placeholder)) {
						
						block = blocks[id];
						block_definition = block.getBlockInfo();
						
						if (block_definition.hidden) {
							//Don't show hidden blocks (eq. broken block)
							continue;
						}
						
						//Change block title into more readable form
						title = block_definition ? block_definition.title : '';
						if (!title) {
							title = id;
							title = title.replace(/[\-\_\.]/g, ' ');
							title = title.substr(0,1).toUpperCase() + title.substr(1);
						}
						
						//Icon
						icon = (block_definition ? block_definition.icon : '');
						if (!icon) {
							if (is_placeholder) {
								//Default placeholder icon
								icon = '/cms/lib/supra/img/blocks/icons-items/list_32x32.png';
							} else {
								//Default block icon
								icon = '/cms/lib/supra/img/blocks/icons-items/default_32x32.png';
							}
						}
						
						template_data.push({
							'id': id,
							'title': title,
							'icon': icon 
						});
						
					}
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
		},
		
		/**
		 * Show blocks
		 */
		setType: function (type) {
			var prev_type = this.type;
			
			if (type == 'blocksview') {
				this.type = 'blocks';
			} else {
				this.type = 'palceholders';
			}
			
			//On first run execute doesn't need to be called
			if (prev_type) {
				this.execute();
			} else {
				this.renderData();
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			//Get all blocks for selected type and display icons
			this.renderData();
		}
	});
	
});