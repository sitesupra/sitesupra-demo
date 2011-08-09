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
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			
			var user = Manager.getAction('User');
			var details = Manager.getAction('UserDetails'),
				target = details.one('div.info em');
			
			this.panel.set('autoClose', true);
			this.panel.set('arrowVisible', false);
			this.panel.set('alignTarget', target);
			this.panel.set('alignPosition', 'T');
			this.panel.get('boundingBox').addClass('useravatar-container');
			this.panel.get('boundingBox').addClass('yui3-panel-true-hidden');
			
			
			//On window resize update panel position
			Y.one(window).on('resize', this.panel.syncUI, this.panel);
			
			this.after('visibleChange', this.handleVisibilityAnimation, this);
			
		},
		
		handleVisibilityAnimation: function (evt) {
			if (evt.newVal != evt.prevVal ) {
				var box = this.panel.get('boundingBox');
				
				if (evt.newVal) {
					box.removeClass('yui3-panel-true-hidden');
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
						box.addClass('yui3-panel-true-hidden');
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
		
		loadData: function () {
			Supra.io(this.getDataPath(), function (data) {
				
				//Render template inside node
				var html = Supra.Template('avatarTemplate', data);
				
				this.data = data;
				this.one('ul').empty();
				this.one('ul').append(html);
				
				//On item click change avatar
				this.one('ul').delegate('click', this.onAvatarClick, 'li', this);
				
				//Create uploader
				var ml = Manager.getAction('MediaLibrary'),
					target = this.one('li:last-child');
				
				this.uploader = new Supra.Uploader({
					'clickTarget': target,
					'dropTarget': target,
					
					'multiple': false,
					'accept': 'image/*',
					
					'requestUri': ml.getDataPath('upload') + '.php',
					'data': {
						'folder': Supra.data.get(['mediaLibrary', 'avatarFolder'], 0)
					}
				});
				
				this.uploader.on('file:upload', this.onFileUploadStart, this);
				this.uploader.on('file:complete', this.onFileUploadEnd, this);
				this.uploader.on('file:validationerror', this.validationError, this);
				
				//Show panel
				this.show();
				
			}, this);
		},
		
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
				var userdata = Manager.getAction('User').getData();
				userdata.avatar = data.sizes['48x48'].external_path;
				userdata.avatar_id = data.id;
				
				Manager.getAction('UserDetails').updateUI(userdata);
			}
			
			this.hide();
			
			//Remove loading icons
			this.one('li:last-child').removeClass('loading');
			
		},
		
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
					var userdata = Manager.getAction('User').getData();
					userdata.avatar = data.sizes['48x48'].external_path;
					userdata.avatar_id = id;
					
					Manager.getAction('UserDetails').updateUI(userdata);
				}
				
				this.hide();
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			
			if (this.data) {
				this.show();
			} else {
				this.loadData();
			}
			
		}
	});
	
});