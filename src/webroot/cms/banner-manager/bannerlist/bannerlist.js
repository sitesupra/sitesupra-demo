//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.list-dd', {
	path: 'modules/list-dd.js',
	requires: ['dd-delegate']
});

/**
 * Main manager action, initiates all other actions
 */
Supra('website.list-dd', function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'BannerList',
		
		/**
		 * Action doesn't have stylesheet
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
		 * Banner group and banner data
		 * @type {Array}
		 * @private
		 */
		data: null,
		
		
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			//Set default buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Load banners
			this.load();
			
			//On banner click start editing
			this.one('div.list-groups').delegate('click', function (e) {
				var target = e.target.closest('li'),
					item_id = target.getAttribute('data-id');
				
				if (item_id) {
					Supra.Manager.executeAction('BannerEdit', item_id);
					this.hide();
				}
			}, 'li', this);
			
			//Bind drag and drop
			this.bindDragAndDrop();
		},
		
		/**
		 * Load banner list
		 */
		load: function () {
			//Load data
			Supra.io(this.getDataPath('load'), {
				'context': this,
				'on': {'complete': this.fillBannerList}
			});
		},
		
		/**
		 * Populate banner list when it completes loading
		 * 
		 * @param {Array} data Banner list
		 * @param {Number} status Request response status
		 * @private
		 */
		fillBannerList: function (data /* Banner list */, status /* Request response status */) {
			if (status && data) {
				//Save data, will be used if creating new banner
				this.data = data;
				
				//Fill template
				var template = Supra.Template('listGroups', {'data': data});
				this.one('div.list-groups').append(template);
				
				//Set width based on banner group count
				var groups = this.all('div.list-groups div.list-group');
				groups.setStyle('width', ~~(100 / data.length) + '%');
				
				//Remove old drop instances
				this.dd.removeDrops();
				
				//Add new drop instances
				groups.each(function (item) {
					this.dd.addDrop(item.one('ul'));
				}, this);
			}
			
			//Hide loading icon
			Y.one('body').removeClass('loading');
		},
		
		/**
		 * Bind drag and drop
		 * 
		 * @private
		 */
		bindDragAndDrop: function () {
			
			this.plug(Supra.ListDD, {
				'dragContainerSelector': 'div.list-groups',
				'proxyClass': 'list-proxy',
				'targetClass': 'list-group-target'
			});
			
			this.dd.addDrag(this.one('div.list-add'));
			
			this.dd.on('drop', this.onDrop, this);
		},
		
		/**
		 * Add new banner on drop
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		onDrop: function (e /* Event */) {
			var target = e.drop_node,
				drag_id = e.drag_id,
				drop_id = e.drop_id;
			
			if (!drag_id) {
				Supra.Manager.executeAction('BannerEdit', null, drop_id);
				this.hide();
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			//Change toolbar buttons
			var toolbar = Manager.getAction('PageToolbar'),
				buttons = Manager.getAction('PageButtons');
			
			if (toolbar.get('created')) {
				toolbar.setActiveAction(this.NAME);
			}
			if (buttons.get('created')) {
				buttons.setActiveAction(this.NAME);
			}
			
			this.show();
		}
	});
	
});