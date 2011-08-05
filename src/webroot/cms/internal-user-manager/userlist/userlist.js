//Invoke strict mode
"use strict";

//Add module definitions
SU.addModule('website.userlist-dd', {
	path: 'modules/userlist-dd.js',
	requires: ['dd-delegate']
});

/**
 * Main manager action, initiates all other actions
 */
Supra('website.userlist-dd', function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Avatar preview size
	var PREVIEW_SIZE = '32x32';
	
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'UserList',
		
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
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			//Set default buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Load users
			this.load();
			
			//On user click start editing
			this.one('div.userlist-groups').delegate('click', function (e) {
				var target = e.target.closest('li'),
					user_id = target.getAttribute('data-id');
				
				if (user_id) {
					this.editUser(user_id);
				}
			}, 'li', this);
			
			//Bind drag and drop
			this.bindDragAndDrop();
		},
		
		/**
		 * Load user list
		 */
		load: function () {
			//Load data
			Supra.io(this.getDataPath(), {
				'context': this,
				'on': {'complete': this.fillUserList}
			});
		},
		
		/**
		 * Populate user list when it completes loading
		 * 
		 * @param {Array} data User list
		 * @param {Number} status Request response status
		 * @private
		 */
		fillUserList: function (data /* User list */, status /* Request response status */) {
			var template = this.one('#userListItem').get('innerHTML'),
				groups = {},
				item = null;
			
			if (data && (status === undefined || status)) {
				//Find all group nodes
				this.all('.userlist-group ul').each(function () {
					var group_id = this.ancestor().getAttribute('data-group');
					
					this.empty();
					groups[group_id] = this;
				});
				
				//Populate
				for(var i=0,ii=data.length; i<ii; i++) {
					data[i].avatar = data[i].avatar || '/cms/lib/supra/img/avatar-default-' + PREVIEW_SIZE + '.png';
					item = Y.Node.create(Y.substitute(template, data[i]));
					groups[data[i].group].append(item);
				}
			}
			
			//Hide loading icon
			Y.one('body').removeClass('loading');
		},
		
		/**
		 * Render user
		 */
		renderUser: function (data /* User data */, target /* Target container */) {
			var template = this.one('#userListItem').get('innerHTML'),
				item = null;
			
			//Populate
			data.avatar = data.avatar || '/cms/lib/supra/img/avatar-default-' + PREVIEW_SIZE + '.png';
			item = Y.Node.create(Y.substitute(template, data));
			
			target.append(item);
		},
		
		/**
		 * Bind drag and drop
		 * 
		 * @private
		 */
		bindDragAndDrop: function () {
			
			this.plug(Supra.UserListDD, {
				'dropSelector': 'div.userlist-groups ul',
				'dragContainerSelector': 'div.userlist-groups',
				'dragSelector': 'li',
				'proxyClass': 'userlist-proxy',
				'targetClass': 'userlist-group-target'
			});
			
			this.dd.addDrag(this.one('div.user-add'));
			
			this.dd.on('drop', this.onDrop, this);
		},
		
		/**
		 * Add new user or change user group
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		onDrop: function (e /* Event */) {
			var target = e.drop_node,
				drag_id = e.drag_id,
				drop_id = e.drop_id;
			
			if (drag_id) {
				//Moving
				Supra.io(this.getDataPath('update'), {
					'data': {
						'user_id': drag_id,
						'group': drop_id
					},
					'method': 'post',
					'context': this
				});
			} else {
				//Adding
				this.addUser(drop_id);
			}
		},
		
		/**
		 * Start editing user
		 * 
		 * @param {String} user_id User ID
		 * @private
		 */
		editUser: function (user_id) {
			Supra.Manager.executeAction('User', user_id);
			this.hide();
		},
		
		/**
		 * Add user to the group
		 * 
		 * @param {String} group_id Group ID
		 */
		addUser: function (group_id /* Group ID */) {
			Supra.Manager.executeAction('User', null, group_id);
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