//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.list-dd', {
	path: 'modules/list-dd.js',
	requires: ['dd', 'dd-delegate']
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
		 * Banner data
		 * @type {Array}
		 * @private
		 */
		data_banners: null,
		
		/**
		 * Group select widget
		 * @type {Object}
		 * @private
		 */
		select: null,
		
		/**
		 * Current banner index
		 * @type {Number}
		 * @private
		 */
		banner_index: 0,
		
		
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			//Set default buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Create list group select
			this.select = new Supra.Input.SelectList();
			this.select.render(this.one('div.list-group-select'));
			this.select.on('change', this.fillBannerList, this);
			
			//Load banners
			this.load();
			
			this.one('a.next').on('mousedown', function (e) {
				var index = Math.min(this.data_banners.length - 1, this.banner_index+1);
				if (index != this.banner_index) {
					this.positionBanners(index);
				}
				e.halt();
			}, this);
			this.one('a.prev').on('mousedown', function (e) {
				var index = Math.max(0, this.banner_index-1);
				if (index != this.banner_index) {
					this.positionBanners(index);
				}
				e.halt();
			}, this);
			
			//On banner click start editing
			this.one('div.list-banners').delegate('click', function () {
				var item_id = this.data_banners[this.banner_index].banner_id;
				Supra.Manager.executeAction('BannerEdit', item_id);
				this.hide();
			}, 'div.size0 > div, a', this);
			
			//Bind drag and drop
			this.bindDragAndDrop();
		},
		
		/**
		 * Load banner list
		 */
		load: function () {
			this.one('div.list-banners').set('innerHTML', '');
			
			//Load data
			Supra.io(this.getDataPath('load'), {
				'context': this,
				'on': {'complete': this.fillGroupList}
			});
		},
		
		/**
		 * Populate group list when loading completes
		 * 
		 * @param {Array} data Banner list
		 * @param {Number} status Request response status
		 * @private
		 */
		fillGroupList: function (data /* Banner list */, status /* Request response status */) {
			if (status && data) {
				//Save data, will be used if creating new banner
				this.data = data;
				
				//Set groups
				var values = [],
					i = 0,
					ii = data.length;
				
				for(; i<ii; i++) values.push({'id': data[i].group_id, 'title': data[i].title});
				this.select.set('values', values);
				
				//Banner hover
				var container = this.one('div.list-banners');
				
				container.delegate('mouseenter', function () {
					container.addClass('hover');
				}, 'div.size0 > div', this);
				container.delegate('mouseleave', function () {
					container.removeClass('hover');
				}, 'div.size0 > div', this);
				
				this.fillBannerList(values[0].id);
			}
			
			//Hide loading icon
			Y.one('body').removeClass('loading');
		},
		
		/**
		 * opulate banner list
		 */
		fillBannerList: function (event) {
			var value = typeof event == 'object' ? event.value : event;
			var group = null;
			var container = this.one('div.list-banners');
			var height = container.get('offsetHeight');
			
			for(var i=0,ii=this.data.length; i<ii; i++) {
				if (this.data[i].group_id == value) {
					group = this.data[i];
					this.data_banners = group.children;
					break;
				}
			}
			
			var template = Supra.Template('listBannersItem', group);
			container.addClass('hidden');
			
			Y.later(150, this, function () {
				container.set('innerHTML', template);
				this.positionBanners(0, height);
			});
			
		},
		
		positionBanners: function (index, height) {
			
			var container = this.one('div.list-banners'),
				container_height = container.get('offsetHeight') || height,
				banners = this.all('div.list-banners > div'),
				banner = null,
				i = 0,
				len = banners.size(),
				offset,
				size,
				classes = ['offset-3', 'offset-2', 'offset-1', 'offset0', 'offset1', 'offset2', 'offset3', 'size3', 'size2', 'size1', 'size0'],
				k = 0,
				top = 0,
				clen = classes.length;
			
			for(; i<len; i++) {
				banner = banners.item(i);
				for(k=0; k<clen; k++) banner.removeClass(classes[k]);
				
				top = ~~((container_height - this.data_banners[i].height) / 2 - 30);
				offset =  - (index - i);
				size = Math.min(Math.abs(offset), 3);
				banner.addClass('offset' + offset);
				banner.addClass('size' + size);
				banner.setStyle('top', top);
			}
			
			banners.removeClass('hidden');
			this.banner_index = index;
			
			Y.later(16, this, function () {
				container.removeClass('hidden');
			});
		},
		
		/**
		 * Bind drag and drop
		 * 
		 * @private
		 */
		bindDragAndDrop: function () {
			
			this.plug(Supra.ListDD, {
				'dragContainerSelector': 'div.list',
				'proxyClass': 'list-proxy',
				'targetClass': 'list-group-target'
			});
			
			this.dd.addDrop(this.one('div.list-banners'));
			
			this.dd.addDrag(this.one('div.list-add'));
			
			this.dd.on('drop', this.addNewBanner, this);
			
			this.one('div.list-add').on('click', this.addNewBanner, this);
		},
		
		/**
		 * Add new banner on drop
		 * 
		 * @private
		 */
		addNewBanner: function (e /* Event */) {
			var group_id = this.select.getValue();
			Supra.Manager.executeAction('BannerEdit', null, group_id);
			this.hide();
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