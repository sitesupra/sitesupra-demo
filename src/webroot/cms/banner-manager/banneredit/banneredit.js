//Invoke strict mode
"use strict";


/**
 * Main manager action, initiates all other actions
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'BannerEdit',
		
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
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [
				{
					'id': 'change',
					'title': SU.Intl.get(['edit', 'change']),
					'icon': this.getPath() + 'images/icon-details.png',
					'action': 'MediaSidebar',
					'type': 'toggle'
				}
			]);
			
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [
				{
					'id': 'done',
					'callback': this.hide,
					'context': this
				}
			]);
			
			this.addChildAction('MediaSidebar');
		},
		
		/**
		 * Save banner data
		 */
		save: function () {
			var data = this.data;
			
			var uri = data.banner_id ? this.getDataPath('save') : this.getDataPath('insert');
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'on': {
					'success': function () {
						Manager.getAction('UserList').load();
					}
				}
			});
		},
		
		/**
		 * On hide save all data
		 */
		hide: function () {
			if (this.get('visible')) {
				this.set('visible', false);
				this.save();
			}
			
			return this;
		},
		
		/**
		 * Execute action
		 */
		execute: function (user_id, group_id) {
			//Change toolbar buttons
			var toolbar = Manager.getAction('PageToolbar'),
				buttons = Manager.getAction('PageButtons');
			
			if (toolbar.get('created')) {
				toolbar.setActiveAction(this.NAME);
			}
			if (buttons.get('created')) {
				buttons.setActiveAction(this.NAME);
			}
			
			this.setUser(user_id, group_id);
			
			this.show();
		}
	});
	
});