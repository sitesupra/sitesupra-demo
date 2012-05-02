//Invoke strict mode
"use strict";

SU('supra.tabs', 'supra.template', 'dd-drag', function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.Action,
		Loader = Manager.Loader;
	
	var SLIDE_ROOT = 'slideMain',
		ICON_GROUP_PATH = '/cms/lib/supra/img/blocks/icons-groups/';
	
	/**
	 * Sidebar panel action to Insert new block 
	 * Actual block information is taken from Blocks action
	 */
	new Action(Action.PluginLayoutSidebar, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageInsertBlock',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Layout container action NAME
		 * @type {String}
		 * @private
		 */
		LAYOUT_CONTAINER: 'LayoutLeftContainer',
		
		
		
		/**
		 * Block data
		 * @type {Object}
		 */
		data: null,
		
		/**
		 * Slideshow instance
		 * @type {Object}
		 */
		slideshow: null,
		
		/**
		 * Elements which are draggable
		 * @type {Array}
		 */
		drags: [],
		
		/**
		 * Drag and drop elements
		 * @type {Array}
		 */
		dnd: [],
		
		/**
		 * Temporary drag and drop instance for item
		 * description slide
		 * 
		 * @type {Object}
		 */
		dnd_tmp: null,
		
		/**
		 * Load blocks data
		 * 
		 * @private
		 */
		renderData: function () {
			if (this.data) return;
			this.data = {};
			
			var Blocks = Manager.getAction('Blocks'),
				data_groups = Blocks.getAllGroups(),
				data_all = Blocks.getAllBlocksArray();
			
			//Create groups
			var i = 0,
				ii = data_groups.length,
				group = null,
				content = null,
				main_content = this.slideshow.getSlide(SLIDE_ROOT),
				group_html = '',
				contents = {};
			
			for(; i<ii; i++) {
				group = data_groups[i];
				
				//Create slide
				content = this.slideshow.addSlide({'id': group.id});
				content.setAttribute('data-title', group.title);
				content.setAttribute('data-icon', ICON_GROUP_PATH + group.id + '.png');
				content.addClass('button-item-list');
				
				contents[group.id] = content.one('.su-slide-content');
				contents[group.id].append(Supra.Template('blockDragTemplate', {}));
				
				//
				group_html += Supra.Template('blockGroupTemplate', group);
			}
			
			main_content.one('.su-slide-content').append(group_html);
			
			//Create block items
			i = 0;
			ii = data_all.length;
			
			for(; i<ii; i++) {
				var block = data_all[i];
				
				if (block.hidden) {
					continue;
				}
				
				var node = Y.Node.create(Supra.Template('blockItemTemplate', block));
				
				contents[block.group].append(node);
				
				this.data[block.id] = block;
				this.data[block.id].node = node;
			}
			
			//Drag&drop
			this.setupDD();
			
			//Fire resize event
			this.slideshow.syncUI();
		},
		
		/**
		 * On slide change show/hide buttons and call callback function
		 * 
		 * @param {Object} evt
		 */
		onSlideChange: function (evt) {
			var slide_id = evt.newVal,
				new_item = (slide_id ? Y.one('#' + slide_id) : null);
			
			if (evt.newVal == SLIDE_ROOT) {
				this.get('backButton').hide();
			} else {
				this.get('backButton').show();
			}
			
			//Update header title and icon
			if (new_item) {
				var node = new_item.get('parentNode'),
					title = new_item.getAttribute('data-title') || node.getAttribute('data-title'),
					icon = new_item.getAttribute('data-icon') || node.getAttribute('data-icon');
				
				this.set('title', title);
				this.set('icon', icon);
			}
		},
		
		/**
		 * Returns block by Id
		 * 
		 * @param {String} id Block ID
		 * @return Block properties
		 * @type {Object}
		 */
		getBlock: function (id) {
			return (type in this.data ? this.data[type] : null);
		},
		
		/**
		 * Set up Drag & Drop
		 * 
		 * @private
		 */
		setupDD: function () {
			this.dnd = [];
			this.drags = this.getPlaceHolder().all('div.button-item');
			this.drags.each(Y.bind(function (v, k, items) {
				var node = items.item(k),
					id = node.getAttribute('data'),
					data = this.data[id];
				
				//Add to DD list 
				this.dnd.push(
					Supra.Manager.PageContent.registerDD({
						'type': 'block',
						'data': data,
						'id': id,
						'node': node,
						'useProxy': true
					})
				);
				
			}, this));
		},
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			//Create slideshow
			this.slideshow = new Supra.Slideshow({
				'srcNode': this.one('div.slideshow')
			});
			this.slideshow.on('slideChange', this.onSlideChange, this);
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			this.slideshow.render();
			
			this.get('controlButton').on('click', this.close, this);
			this.get('backButton').on('click', this.slideshow.scrollBack, this.slideshow);
			
			//Attach event listeners
			this.one().delegate(['click', 'keyup'], this.openSlide, 'a[data-target],div[data-target]', this);
		},
		
		/**
		 * Open slide
		 */
		openSlide: function (e) {
			if (e.type == 'keyup' && e.keyCode != 13 && e.keyCode != 39) return; //Return key or arrow right
			
			var node = e.target.closest('a,div'),
				id = node.getAttribute('data-target');
			
			//Check if opening block description and create slide
			if (this.data[id] && this.slideshow.get('slide') != id) {
				var node = this.slideshow.addSlide({'id': id, 'removeOnHide': true}),
					content = node.one('div.su-slide-content');
				
				content.append(Supra.Template('blockPreviewTemplate', this.data[id]));
				
				//Drag and drop
				if (this.dnd_tmp) this.dnd_tmp.destroy();
				this.dnd_tmp = Supra.Manager.PageContent.registerDD({
					'type': 'block',
					'data': this.data[id],
					'id': id,
					'node': content.one('.button-item'),
					'useProxy': true
				});
			}
			
			this.slideshow.set('slide', id);
		},
		
		/**
		 * Handle close button click
		 * 
		 * @private 
		 */
		close: function () {
			//Enable block editing
			Supra.Manager.PageContent.getContent().set('highlight', false);
			this.hide();
		},
		
		/**
		 * On hide scroll back to first slide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			this.slideshow
					.set('noAnimation', true)
					.set('slide', SLIDE_ROOT)
					.set('noAnimation', false);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			this.renderData();
			this.slideshow.syncUI();
			
			//Blocks not editable while this action is visible
			Supra.Manager.PageContent.getContent().set('highlight', true);
		}
	});
	
});