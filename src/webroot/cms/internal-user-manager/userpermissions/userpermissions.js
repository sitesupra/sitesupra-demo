//Invoke strict mode
"use strict";


//Add module definitions
SU.addModule('website.input-dial', {
	path: 'modules/input-dial.js',
	requires: ['supra.input-proto']
});


/**
 * Main manager action, initiates all other actions
 */
Supra('website.input-dial', function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Action.PluginContainer, Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'UserPermissions',
		
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
			
			//@TODO
			Supra.io(this.getDataPath('applications'), {
				'context': this,
				'on': {
					'success': this.renderApplications
				}
			});
			
		},
		
		/**
		 * Render application list
		 * 
		 * @param {Array} data Application list
		 * @private
		 */
		renderApplications: function (data /* Application list */) {
			
			var container = this.one('ul');
			var html = Supra.Template('applicationsListItem', data);
			
			container.empty();
			container.append(html);
			
		},
		
		/**
		 * Update UI
		 * 
		 * @param {Object} data User data
		 * @private
		 */
		setUserData: function (data /* User data */) {
			
			this.one('div.info img').setAttribute('src', data.avatar);
			this.one('div.info a').set('text', data.name || Supra.Intl.get(['userdetails', 'default_name']));
			this.one('div.info b').set('text', Supra.Intl.get(['userdetails', 'group_' + data.group]));
			
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			
			this.setUserData(Manager.getAction('User').getData());
			this.show();
			
		}
	});
	
});