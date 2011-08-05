//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra('supra.form', function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, Action.PluginForm, Action.PluginFooter, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'UserDetails',
		
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
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			
			//On 'Reset password' click show confirmation
			this.footer.getButton('reset').on('click', function () {
				Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['userdetails', 'reset_message']),
					'useMask': true,
					'buttons': [
						{'id': 'yes', 'style': 'mid-blue', 'click': this.resetPassword, 'context': this},
						{'id': 'no'}
					]
				});
			}, Manager.getAction('User'));
			
			//On 'Delete user' click show confirmation
			this.footer.getButton('delete').on('click', function () {
				Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['userdetails', 'delete_message']),
					'useMask': true,
					'buttons': [
						{'id': 'yes', 'style': 'mid-red', 'click': this.deleteUser, 'context': this},
						{'id': 'no'}
					]
				});
			}, Manager.getAction('User'));
			
			//On form values change update data
			this.form.on('change', this.onDataChange, this);
			
		},
		
		/**
		 * Update UI
		 * 
		 * @param {Object} data User data
		 * @private
		 */
		updateUI: function (data) {
			
			if ('avatar' in data) {
				this.one('div.info img').setAttribute('src', data.avatar);
			}
			if ('name' in data) {
				this.one('div.info a').set('text', data.name || Supra.Intl.get(['userdetails', 'default_name']));
			}
			if ('group' in data) {
				this.one('div.info b').set('text', Supra.Intl.get(['userdetails', 'group_' + data.group]));
			}
			
		},
		
		/**
		 * On change update user data with form values
		 */
		onDataChange: function () {
			var values = this.form.getValues(data, 'name'),
				data = Manager.getAction('User').getData();
			
			//Mix form values into data
			Supra.mix(data, values);
			
			this.updateUI(data);
		},
		
		/**
		 * Set user data
		 * 
		 * @param {Object} data User data
		 * @private
		 */
		setData: function (data /* User data */) {
			
			this.updateUI(data);
			
			this.form.setValues(data, 'name');
			
			if (data.user_id) {
				//If there is a user, then show 'Reset password' and 'Delete' buttons
				this.footer.show();
			} else {
				//If there is no user, then hide buttons
				this.footer.hide();
			}
			
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			
			this.setData(Manager.getAction('User').getData());
			this.show();
			
		}
	});
	
});