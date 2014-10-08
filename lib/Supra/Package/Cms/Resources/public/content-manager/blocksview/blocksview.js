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
			container.delegate('mouseenter', this.itemOnMouseEnter, 'li', this);
			
			container.delegate('mouseleave', this.itemOnMouseLeave, 'li', this);
			
			container.delegate('click', this.itemOnClick, 'li', this);
			
			//When a block is selected hide blockview
			var content = Manager.PageContent.getContent();
			content.before('activeChildChange', function (event) {
				if (event.newVal && !event.prevVal && this.get('visible')) {
					this.hide();
				}
			}, this);
			
			//Control button
			this.get('controlButton').on('click', this.close, this);
		},
		
		/**
		 * Render block list
		 */
		renderData: function () {
			if (!this.type) return;
			
			var blocks = this.blocks = Manager.PageContent.getContent().getAllChildren(),
				filtered = [],
				block = null,
				block_type = null,
				block_definition = null,
				container = this.one('ul.block-list'),
				template_data = [],
				item = null,
				is_placeholder = false,
				title = '',
				icon = '',
				type = this.type,
				has_permissions = true;
			
			//Update heaidng
			var heading = this.one('h2');
			if (type == 'blocks') {
				heading.set('text', Supra.Intl.get(['blocks', 'title']));
			} else {
				heading.set('text', Supra.Intl.get(['placeholders', 'title']));
			}
			
			//Filter blocks
			for(var id in blocks) {
				block = blocks[id];
				has_permissions = true;
				
				if (block.isClosed() && !block.get('data').owner_id) {
					has_permissions = false;
				}
				
				//Show only blocks which are not closed and are editable
				if (has_permissions && block.get('editable')) {
					is_placeholder = block.isList();
					
					if ((type == 'blocks' && !is_placeholder) || (type != 'blocks' && is_placeholder)) {
						filtered.push(block);
					}
				}
			}
			
			blocks = this.sortBlocksByDomOrder(filtered);
			
			//Update block list
			var i = 0,
				ii = blocks.length,
				id = null;
			
			for(; i<ii; i++) {
				block = blocks[i];
				block_definition = block.getBlockInfo();
				id = block.getId();
				is_placeholder = block.isList();
				
				if (block_definition.hidden && !is_placeholder) {
					//Don't show hidden blocks (eq. broken block)
					//But show hidden placeholders (eq. Placeholder sets)
					continue;
				}
				
				//Icon
				icon = (block_definition ? block_definition.icon : '');
				if (!icon) {
					if (is_placeholder) {
						//Default placeholder icon
						icon = '/public/cms/supra/img/blocks/icons-items/list.png';
					} else {
						//Default block icon
						icon = '/public/cms/supra/img/blocks/icons-items/default.png';
					}
				}
				
				template_data.push({
					'id': id,
					'title': block.getBlockTitle(),
					'icon': icon 
				});
			}
			
			//Render items
			container.set('innerHTML', Supra.Template('pageBlockListItems', {'blocks': template_data}));
		},
		
		/**
		 * Sort block data by DOM order
		 * 
		 * @param {Array} blocks List of blocks
		 * @returns {Array} Block list ordered by their appearance in DOM
		 * @private
		 */
		sortBlocksByDomOrder: function (blocks) {
			var doc = Y.Node(Manager.PageContent.getContent().get('doc')),
				node,
				ancestor,
				index = 0,
				
				i = 0,
				ii = blocks.length,
				
				order = {},
				sorted = [],
				
				pad = function (n) { n = String(n); return '000'.substr(0, 3 - n.length) + n; };
			
			for (; i<ii; i++) {
				node = blocks[i].getNode();
				ancestor = node.ancestor();
				index = '';
				
				while(ancestor && !node.test('body')) {
					index = pad(ancestor.get('children').indexOf(node)) + index;
					
					node = node.ancestor();
					ancestor = node.ancestor();
				}
				
				order[blocks[i].getId()] = index;
			}
			
			sorted = [].concat(blocks);
			sorted.sort(function (a, b) {
				var a_index = order[a.getId()],
					b_index = order[b.getId()];
				
				return a_index == b_index ? 0 : (a_index < b_index ? -1 : 1);
			});
			
			return sorted;
		},
		
		
		/* --------------- UI EVENT HANDLERS ----------------- */
		
		
		/**
		 * On item mouse over highlight it
		 * 
		 * @param {Object} evt Event facade object
		 * @private
		 */
		itemOnMouseEnter: function (evt) {
			var target = evt.target.closest('LI'),
				content_id = target.getAttribute('data-id');
			
			if (this.type == 'blocks') {
				//Blocks
				this.blocks[content_id].set('highlightMode', 'blocks-hover');
			} else {
				//Place holders
				this.blocks[content_id].set('highlightMode', 'placeholders-hover');
			}
		},
		
		/**
		 * On item mouse leave remove highlight
		 * 
		 * @param {Object} evt Event facade object
		 * @private
		 */
		itemOnMouseLeave: function (evt) {
			var target = evt.target.closest('LI'),
				content_id = target.getAttribute('data-id');
			
			//Remove block specific highlighting
			this.blocks[content_id].set('highlightMode', null);
		},
		
		/**
		 * On item click open block for editing
		 * 
		 * @param {Object} evt Event facade object
		 * @private
		 */
		itemOnClick: function (evt) {
			var target = evt.target.closest('LI'),
				content_id = target.getAttribute('data-id'),
				contents = null;
			
			//Start editing content
			contents = Manager.PageContent.getContent();
			contents.set('activeChild', this.blocks[content_id]);
		},
		
		/**
		 * Close manager after control button click
		 */
		close: function () {
			Manager.PageContent.getContent().set('highlightMode', 'edit');
			this.hide();
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
			
			// Highlight overlays
			if (type == 'blocksview') {
				Manager.PageContent.getContent().set('highlightMode', 'blocks');
			} else {
				Manager.PageContent.getContent().set('highlightMode', 'placeholders');
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