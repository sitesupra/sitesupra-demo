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
	
	//Avatar preview size
	var PREVIEW_SIZE = '48x48';
	
	
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
		 * Group mode state
		 * @type {Boolean}
		 * @private
		 */
		group_mode: false,
		
		
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			//Set default buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Group mode functionality
			Manager.getAction('PageButtons').addActionButtons(this.NAME + '-groups', [
				{
					'id': 'done',
					'callback': this.toggleGroupMode,
					'context': this
				}
			]);
			Manager.getAction('PageToolbar').addActionButtons(this.NAME + '-groups', []);
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [
				{
					'id': 'details',
					'title': SU.Intl.get(['userlist', 'manage_groups']),
					'icon': this.getActionPath() + 'images/icon-groups.png',
					'action': 'UserList',
					'actionFunction': 'toggleGroupMode',
					'type': 'button'
				}
			]);
			
			//Load users
			this.load();
			
			//On user/group click start editing
			this.one('div.userlist-groups').delegate('click', function (e) {
				var target = e.target.closest('li'),
					user_id = target.getAttribute('data-id'),
					group = target.closest('div.userlist-group'),
					group_id = group.getAttribute('data-group');
				
				if (user_id) {
					this.editUser(user_id, group_id);
				}
			}, 'li', this);
			
			this.one('div.userlist-groups').delegate('click', function (e) {
				var target = e.target.closest('div'),
					group_id = target.getAttribute('data-group');
				
				if (group_id) {
					this.editGroup(group_id);
				}
			}, 'p.edit-label', this);
			
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
		 * Group editing mode
		 * 
		 * @private
		 */
		toggleGroupMode: function () {
			var node = this.one('div.userlist-groups');
			var new_user = this.one('div.user-add');
			
			this.group_mode = !this.group_mode;
			
			if (!this.group_mode) {
				node.removeClass('group-mode');
				new_user.removeClass('hidden');
				
				Manager.getAction('PageButtons').setActiveAction(this.NAME);
				Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			} else {
				node.addClass('group-mode');
				new_user.addClass('hidden');
				
				Manager.getAction('PageButtons').setActiveAction(this.NAME + '-groups');
				Manager.getAction('PageToolbar').setActiveAction(this.NAME + '-groups');
			}
		},
		
		/**
		 * Populate user list when it completes loading
		 * 
		 * @param {Array} data User list
		 * @param {Number} status Request response status
		 * @private
		 */
		fillUserList: function (data /* User list */, status /* Request response status */) {
			
			var template = Supra.Template('userListItem'),
				groups = {},
				item = null;
			
			if (data && (status === undefined || status)) {
				//Find all group nodes
				this.all('.userlist-group ul').each(function () {
					var group_id = this.ancestor().getAttribute('data-group');
					groups[group_id] = this.empty();
				});
				
				//Populate
				Y.Array.each(data, function (data) {
					data.avatar = data.avatar || '/cms/lib/supra/img/avatar-default-' + PREVIEW_SIZE + '.png';
					data.user_id = Supra.data.get(['user', 'id']);
					groups[data.group].append(template(data));
				})
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
				'dropSelector': 'div.userlist-groups ul',
				'dragContainerSelector': 'div.userlist-groups',
				'dragSelector': 'li.dragable',
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
				drop_id = e.drop_id,
				drag_node = e.drag_node,
				drop_node = e.drop_node;
			
			if (drag_id) {
				//Moving
				Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['userlist', 'user_move_confirm']),
					'useMask': true,
					'buttons': [
						{'id': 'yes', 'style': 'small-blue', 'click': function () { this.onUserMoveConfirm(drag_id, drop_id, drag_node, drop_node); }, 'context': this},
						{'id': 'no'}
					]
				});
			} else {
				//Adding
				this.addUser(drop_id);
			}
		},
		
		/**
		 * If user confirms user group change send request to server
		 */
		onUserMoveConfirm: function (drag_id, drop_id, drag_node, drop_node) {
			Supra.io(this.getDataPath('update'), {
				'data': {
					'user_id': drag_id,
					'group': drop_id
				},
				'method': 'post',
				'context': this
			});
			
			//Move node
			drop_node.append(drag_node);
		},
		
		/**
		 * Start editing group
		 * 
		 * @param {String} group_id Group ID
		 * @private
		 */
		editGroup: function (group_id /* Group ID */) {
			Supra.Manager.executeAction('User', null, group_id, true);
			this.hide();
		},
		
		/**
		 * Start editing user
		 * 
		 * @param {String} user_id User ID
		 * @private
		 */
		editUser: function (user_id /* User ID */, group_id /* Group ID */) {
			Supra.Manager.executeAction('User', user_id, group_id);
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
				buttons = Manager.getAction('PageButtons'),
				name = this.group_mode ? this.NAME + '-groups' : this.NAME;
			
			if (toolbar.get('created')) {
				toolbar.setActiveAction(name);
			}
			if (buttons.get('created')) {
				buttons.setActiveAction(name);
			}
			
			this.show();
		}
	});
	
});