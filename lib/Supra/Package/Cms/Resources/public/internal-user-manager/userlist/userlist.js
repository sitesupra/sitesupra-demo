//Invoke strict mode
"use strict";

//Add module definitions
Supra.addModule('website.list-dd', {
	path: 'modules/list-dd.js',
	requires: ['dd', 'dd-delegate']
});

/**
 * Main manager action, initiates all other actions
 */
Supra('website.list-dd', 'supra.list', function (Y) {

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
		 * New user button, Supra.ListNewItem instance
		 * @type {Object}
		 * @private
		 */
		new_user: null,
		
		scrollables: [],
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {

			//Set default buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			
			//Scrollable contents
			var items = this.all('div.userlist-group-content'),
				i = 0,
				ii = items.size(),
				scrollable = null,
				scrollables = this.scrollables;
			
			for(; i<ii; i++) {
				scrollable = new Supra.Scrollable({'srcNode': items.item(i)});
				scrollables.push(scrollable.render());
			}
			
			//Load users
			this.load();
			
			//On user click start editing
			this.one('div.userlist-groups').delegate('click', function (e) {
				var target = e.target.closest('li'),
					user_id = target.getAttribute('data-id'),
					group = target.closest('div.userlist-group'),
					group_id = group.getAttribute('data-group');
				
				if (user_id) {
					this.editUser(user_id, group_id);
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
			Supra.io(Supra.Url.generate('backoffice_user_list'), {
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
			
			var template = Supra.Template('userListItem'),
				groups = {},
				item = null;
			
			if (data && (status === undefined || status)) {
				//Find all group nodes
				this.all('.userlist-group ul').each(function () {
					var group_id = this.closest('.userlist-group').getAttribute('data-group');
					groups[group_id] = this.empty();
				});
				
				//Populate
				Y.Array.each(data, function (data) {
					data.avatar = data.avatar || '/public/cms/supra/img/avatar-default-' + PREVIEW_SIZE + '.png';
					data.user_id = Supra.data.get(['user', 'id']);
					groups[data.group].append(template(data));
				})
			}
			
			//Hide loading icon
			Y.one('body').removeClass('loading');
			
			//Update scrollables
			Y.Array.each(this.scrollables, function (scrollable) {
				scrollable.syncUI();
			});
		},
		
		/**
		 * Bind drag and drop
		 * 
		 * @private
		 */
		bindDragAndDrop: function () {
			
			//New item draggable node
			this.new_user = new Supra.ListNewItem({
				'srcNode': this.one('.user-add'),
				'title': Supra.Intl.get(['userlist', 'new']),
				'dndGroups': ['default']
			});
			
			this.new_user.render();
			
			//List DnD
			this.plug(Supra.ListDD, {
				'dropSelector': 'div.userlist-groups ul',
				'dragContainerSelector': 'div.userlist-groups',
				'dragSelector': 'li.draggable',
				'proxyClass': 'userlist-proxy',
				'targetClass': 'userlist-group-target'
			});
			
			this.dd.addDrag(this.new_user.getDrag());
			
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
			Supra.io(Supra.Url.generate('backoffice_user_update'), {
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
				buttons = Manager.getAction('PageButtons');
			
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
