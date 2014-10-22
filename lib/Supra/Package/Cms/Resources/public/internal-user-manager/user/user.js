//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra('supra.slideshow', function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Avatar preview size
	var PREVIEW_SIZE = '48x48';
	
	//New user default data
	var NEW_USER_DATA = {
		'1': {
			'user_id': null,
			'name': '',
			'email': '',
			'avatar': null,
			'group': 1,
			'permissions': {},
			'canUpdate': true
		},
		'2': {
			'user_id': null,
			'name': '',
			'email': '',
			'avatar': null,
			'group': 2,
			'permissions': {},
			'canUpdate': true
		},
		'3': {
			'user_id': null,
			'name': '',
			'email': '',
			'avatar': null,
			'group': 3,
			'permissions': {},
			'canUpdate': true
		}
	};
	
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'User',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load action template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		
		/**
		 * User data
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		/**
		 * Slideshow object
		 * @type {Object}
		 * @private
		 */
		slideshow: null,
		
		/**
		 * Data type, 'user' or 'group'
		 * @type {String}
		 * @private
		 */
		type: null,
		
		
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			
			//Create slideshow
			this.slideshow = new Supra.Slideshow();
			this.slideshow.render(this.one());
			
			//On resize update slideshow slide position
			Y.on('resize', Y.bind(this.slideshow.syncUI, this.slideshow), window);
			
			//Create slides
			var slide = null, action = null;
			
			slide = this.slideshow.addSlide({'id': 'UserDetails'})
			action = Manager.getAction('UserDetails');
			action.setPlaceHolder(slide.one('.su-slide-content'));
			this.slideshow.set('slide', 'UserDetails');
			
			slide = this.slideshow.addSlide({'id': 'UserPermissions'})
			action = Manager.getAction('UserPermissions');
			action.setPlaceHolder(slide.one('.su-slide-content'));
			
			/*
			slide = this.slideshow.addSlide({'id': 'UserStats'})
			action = Manager.getAction('UserStats');
			action.setPlaceHolder(slide.one('.su-slide-content'));
			*/
			
			//Set default buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [
				{
					'id': 'details',
					'title': Supra.Intl.get(['userdetails', 'title']),
					'icon': 'internal-user-manager/user/images/icon-details.png',
					'action': 'UserDetails',
					'type': 'tab'	//like 'toggle', only it can't be unset by clicking again
				},
				{
					'id': 'permissions',
					'title': Supra.Intl.get(['userpermissions', 'title']),
					'icon': 'internal-user-manager/user/images/icon-permissions.png',
					'action': 'UserPermissions',
					'type': 'tab'
				}/*,
				{
					'id': 'stats',
					'title': Supra.Intl.get(['userstats', 'title']),
					'icon': this.getActionPath() + 'images/icon-stats.png',
					'action': 'UserStats',
					'type': 'tab'
				}*/
			]);
			
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [
				{
					'id': 'done',
					'callback': this.hide,
					'context': this
				}
			]);
			
			this.addChildAction('PermissionProperties');
			this.addChildAction('UserDetails');
			this.addChildAction('UserPermissions');
			/*this.addChildAction('UserStats');*/
			
			//Load media library Intl data
			//var app_path = Manager.Loader.getStaticPath() + Manager.Loader.getActionBasePath('MediaLibrary');
			//Supra.Intl.loadAppData(app_path);
		},
		
		/**
		 * Set user
		 * 
		 * @param {String} user_id User ID
		 * @param {String} group_id Group ID
		 */
		setUser: function (user_id /* User ID */, group_id /* Group ID */) {
			var buttons = Supra.Manager.PageToolbar.buttons;
			
			if (user_id) {
				//Enable stats button
				/*
				buttons.stats.set('disabled', false);
				*/
				
				//Loading icon
				this.one().addClass('loading');
				
				Supra.io(Supra.Url.generate('backoffice_user_load'), {
					'data': {
						'user_id': user_id
					},
					'context': this,
					'on': {'success': this.setUserData}
				});
			} else {
				//There are no stats for new user
				/*
				buttons.stats.set('disabled', true);
				*/
				
				var data = Supra.mix({}, NEW_USER_DATA[group_id], true);
				this.setUserData(data);
			}
		},
		
		/**
		 * Set group
		 * 
		 * @param {String} group_id Group ID
		 */
		setGroup: function (group_id /* Group ID */) {
			var uri = Manager.getAction('UserGroup').getDataPath('load');
			
			//Loading icon
			this.one().addClass('loading');
			
			Supra.io(uri, {
				'data': {
					'group_id': group_id
				},
				'context': this,
				'on': {'success': this.setGroupData}
			});
		},
		
		/**
		 * Update user data
		 * 
		 * @param {Object} data User data
		 */
		setUserData: function (data /* User data */) {
			this.one().removeClass('loading');
			
			data.avatar = data.avatar || '/public/cms/supra/img/avatar-default-' + PREVIEW_SIZE + '.png';
			this.data = data;
			this.fire('userChange', {'data': data});
			
			Manager.getAction('UserDetails').execute();
		},
		
		/**
		 * Update group data
		 * 
		 * @param {Object} data Group data
		 */
		setGroupData: function (data /* Group data*/) {
			this.one().removeClass('loading');
			
			data.avatar = data.avatar || '/cms/lib/supra/img/avatar-group-' + PREVIEW_SIZE + '.png';
			this.data = data;
			this.fire('userChange', {'data': data});
			
			Manager.getAction('UserPermissions').execute();
		},
		
		/**
		 * Returns user or group data
		 * 
		 * @return User data
		 * @type {Object}
		 */
		getData: function () {
			return this.data;
		},
		
		/**
		 * Returns true if currently editing user or false is editing group
		 * 
		 * @return True if editing user
		 * @type {Boolean}
		 */
		isUser: function () {
			return this.type == 'user';
		},
		
		
		/**
		 * Delete user
		 * 
		 * @private
		 */
		deleteUser: function () {
			
			if( ! Manager.getAction('UserDetails').isAllowedToDelete(this.data)) {
				return;
			}
			
			var uri = Supra.Url.generate('backoffice_user_delete');
			
			Supra.io(uri, {
				'method': 'post',
				'data': {
					'user_id': this.data.user_id
				},
				'context': this,
				'on': {
					'success': function () {
						this.data = {};
						this.hide();
						Manager.getAction('UserList').load();
					}
				}
			})
		},
		
		/**
		 * Reset password
		 * 
		 * @private
		 */
		resetPassword: function () {						
			if(!Manager.getAction('UserDetails').isAllowedToUpdate(this.data)) {
				return;
			}
			
			var uri = Supra.Url.generate('backoffice_user_reset');
			Supra.io(uri, {
				'method': 'post',
				'data': {
					'user_id': this.data.user_id
				},
				'context': this,
				'on': {
					'success': function () {
						var message = Supra.Intl.get(['userdetails', 'reset_success']);
						message = Y.substitute(message, this.data);
						
						Manager.executeAction('Confirmation', {
							'message': message,
							'buttons': [{
								'id': 'ok'
							}]
						});
					}
				}
			})
		},
		
		/**
		 * Save user data
		 * 
		 * @returns {Boolean} False if there was an error, otherwise true
		 */
		save: function (callback) {
			var data = Supra.mix({}, this.data),
				uri = null;
			
			if (this.isUser()) {
				if (!data.name && !data.email) {
					// Don't save anything, act as 'cancel'
					return true;
				}
				
				if (!data.name || !data.email || !Supra.Form.validate.email(data.email)) {
					// If 'name' or 'email' is missing show error message
					var message = Supra.Intl.get(['userdetails', 'required_message']);
						message = Y.substitute(message, this.data);
						
						Manager.executeAction('Confirmation', {
							'message': message,
							'buttons': [{
								'id': 'ok'
							}]
						});
					return false;
				}
				
				if (!data.canUpdate) {
					// Can't update, but that's not an error
					return true;
				}
				
				uri = data.user_id ? Supra.Url.generate('backoffice_user_save') : Supra.Url.generate('backoffice_user_insert');
				
				if (!data.avatar_id) {
					data.avatar = '';
					data.avatar_id = '';
				}
				
				//Disable permission button
				var toolbar = Manager.getAction('PageToolbar');
				toolbar.getActionButton('permissions').set('disabled', true);
				toolbar.getActionButton('details').set('loading', true);
			} else {
				uri = Manager.getAction('usergroup').getDataPath('save');
				
				//Groups don't have avatars
				delete(data.avatar);
			}
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'on': {
					'complete': function (data, status) {
						if (Y.Lang.isFunction(callback)) callback(data, status);
						
						//Enable permission button
						var toolbar = Manager.getAction('PageToolbar');
						toolbar.getActionButton('permissions').set('disabled', false);
						toolbar.getActionButton('details').set('loading', false);
					},
					'success': function () {
						Manager.getAction('UserList').load();
					}
				}
			});
			
			return true;
		},
		
		/**
		 * On hide set active action to UserList
		 */
		hide: function () {
			if (this.get('visible')) {
				if (this.save()) {
					this.set('visible', false);
					Manager.getAction('UserList').execute();	
				}
			}
			
			return this;
		},
		
		/**
		 * Execute action
		 */
		execute: function (user_id, group_id, group_editing) {
			//Change toolbar buttons
			var toolbar = Manager.getAction('PageToolbar'),
				buttons = Manager.getAction('PageButtons');
			
			if (toolbar.get('created')) {
				toolbar.setActiveAction(this.NAME);
				
				if (!group_editing) {
					//Admins has all rights, no need to manage them
					if (group_id == 1) {
						toolbar.buttons.permissions.hide();
					} else {
						toolbar.buttons.permissions.show();
					}
					
					toolbar.buttons.details.show();
				} else {
					toolbar.buttons.permissions.show();
					toolbar.buttons.details.hide();
				}
				
			}
			if (buttons.get('created')) {
				buttons.setActiveAction(this.NAME);
			}
			
			this.slideshow.set('noAnimations', true);
			
			if (!group_editing) {
				this.setUser(user_id, group_id);
				this.slideshow.set('slide', 'UserDetails');
				this.type = 'user';
			} else {
				this.setGroup(group_id);
				this.slideshow.set('slide', 'UserPermissions');
				this.type = 'usergroup';
			}
			
			this.slideshow.set('noAnimations', false);
			
			this.show();
			this.slideshow.syncUI();
		}
	});
	
});