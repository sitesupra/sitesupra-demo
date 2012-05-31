//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra('supra.panel', 'transition', function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Action.PluginPanel, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'UserAvatar',
		
		/**
		 * Load action stylesheet
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
		 * Avatar data
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		/**
		 * Uploader object
		 * @see Supra.Uploader
		 * @type {Object}
		 * @private
		 */
		uploader: null,
		
		/**
		 * Resize event
		 * @type {Object}
		 * @private
		 */
		resize_event: null,
		
		/**
		 * Last known user ID
		 * @type {String}
		 * @private
		 */
		user_id: null,
		
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			
			var details = this.get('controller'),
				target = details.one('div.info em');
			
			this.panel.set('autoClose', true);
			this.panel.set('arrowVisible', true);
			this.panel.set('alignTarget', target);
			this.panel.set('alignPosition', 'T');
			this.panel.get('boundingBox').addClass('useravatar-container');
			this.panel.get('boundingBox').addClass('su-panel-true-hidden');
			
			
			//On window resize update panel position
			Y.one(window).on('resize', this.panel.syncUI, this.panel);
			
			this.after('visibleChange', this.handleVisibilityAnimation, this);
			
			//On item click change avatar
			this.one('ul').delegate('click', this.onAvatarClick, 'li', this);
			
			//Create uploader
			this.createUploader();
		},
		
		handleVisibilityAnimation: function (evt) {
			if (evt.newVal != evt.prevVal ) {
				var box = this.panel.get('boundingBox');
				
				if (evt.newVal) {
					box.removeClass('su-panel-true-hidden');
					this.panel.syncUI();
					
					Y.later(32, this, function () {
						box.addClass('yui3-animate');
						Y.later(32, this, function () {
							box.addClass('yui3-animate-in');
						});
					});
					
					//On window resize update panel position
					this.resize_event = Y.one(window).on('resize', this.panel.syncUI, this.panel);
				} else {
					box.addClass('yui3-animate-out');
					
					Y.later(350, this, function () {
						box.addClass('su-panel-true-hidden');
						box.removeClass('yui3-animate-in');
						box.removeClass('yui3-animate-out');
						box.removeClass('yui3-animate');
					});
					
					//Remove resize event
					if (this.resize_event) {
						this.resize_event.detach();
						this.resize_event = null;
					}
				}
			}
		},
		
		/**
		 * Load user avatar list
		 * 
		 * @private
		 */
		loadData: function () {
			var user_id = this.getUserId();
			
			//Update uploader parameter
			this.uploader.get('data').user_id = user_id;
			
			//Load data for this user
			Supra.io(this.getDataPath(), {
				'data': {
					'user_id': user_id
				},
				'context': this,
				'on': {
					'success': this.fillData
				}
			});
		},
		
		/**
		 * Render list
		 * 
		 * @param {Object} data Data
		 * @private
		 */
		fillData: function (data) {
			this.data = data;
			
			//Render template inside node
			var html = Supra.Template('avatarTemplate', {'avatars': data}),
				ul   = this.one('ul'),
				li   = ul.all('li');
			
			li.splice(0, li.size() - 1).remove();
			ul.prepend(html);
			
			//Show panel
			this.show();
		},
		
		/**
		 * Create uploader widget instance
		 * 
		 * @private
		 */
		createUploader: function () {
			if (this.uploader) return;
			
			//Create uploader
			var ml = Manager.getAction('MediaLibrary'),
				target = this.one('li:last-child');
			
			this.user_id = this.getUserId();
			
			this.uploader = new Supra.Uploader({
				'clickTarget': target,
				'dropTarget': target,
				
				'multiple': false,
				'accept': 'image/*',
				
				'requestUri': Manager.getAction('UserAvatar').getDataPath('upload'),
				'data': {
					'folder': Supra.data.get(['mediaLibrary', 'avatarFolder'], 0),
					'user_id': this.user_id
				}
			});
			
			this.uploader.on('file:upload', this.onFileUploadStart, this);
			this.uploader.on('file:complete', this.onFileUploadEnd, this);
			this.uploader.on('file:validationerror', this.validationError, this);
		},
		
		/**
		 * Show file type error message
		 * 
		 * @private
		 */
		validationError: function () {
			Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['useravatar', 'file_type_error']),
				'useMask': true,
				'buttons': [
					{'id': 'error'}
				]
			});
			
			this.one('li:last-child').removeClass('loading');
		},
		
		onFileUploadStart: function (event) {
			//Add loading icon
			this.one('li:last-child').addClass('loading');
		},
		
		onFileUploadEnd: function (event) {
			var data = event.data;
			if (data) {
				var userdata = this.getData();
				userdata.avatar = data.sizes['48x48'].external_path;
				userdata.avatar_id = data.id;
				
				var controller = this.get('controller');
				if (controller.updateUI) {
					controller.updateUI(userdata);
				}
			}
			
			this.hide();
			
			//Remove loading icons
			this.one('li:last-child').removeClass('loading');
			
		},
		
		/**
		 * On avatar click update user data
		 * 
		 * @private
		 */
		onAvatarClick: function (event) {
			var target = event.target.closest('li'),
				id = target.getAttribute('data-id'),
				data = null;
			
			if (id) {
				//Find avatar
				for(var i=0,ii=this.data.length; i<ii; i++) {
					if (this.data[i].id == id) {
						data = this.data[i];
						break;
					}
				}
				
				if (data) {
					var userdata = this.getData(),
						controller = this.get('controller');
					
					if(controller.isAllowedToUpdate && !controller.isAllowedToUpdate(userdata)) {
						this.hide();
						return;
					}
					
					userdata.avatar = data.sizes['48x48'].external_path;
					userdata.avatar_id = id;
					
					if (controller.updateUI) {
						controller.updateUI(userdata);
					}
				}
				
				this.hide();
			}
		},
		
		/**
		 * Returns controller data
		 * 
		 * @return User data
		 * @type {Object}
		 * @private
		 */
		getData: function () {
			return this.get('controller').getData();
		},
		
		/**
		 * Returns user id
		 * 
		 * @return User ID
		 * @type {String}
		 * @private
		 */
		getUserId: function () {
			return this.getData().user_id || Supra.data.get(["user", "id"]);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			if (!this.get('controller')) {
				this.set('controller', Manager.getAction('UserDetails'));
			}
			
			var user_id = this.getUserId();
			
			if (this.data && this.user_id == user_id) {
				this.show();
			} else {
				this.loadData();
			}
			
		}
	});
	
});