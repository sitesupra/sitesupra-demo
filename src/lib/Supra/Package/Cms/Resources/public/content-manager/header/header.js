/**
 * Header action, app dock
 */
Supra('supra.header', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Action = Supra.Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'Header',
		
		/**
		 * No stylesheet for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * No template for this action
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Application list, last application is currently active
		 * @type {Array}
		 * @private
		 */
		stack: [],
		
		/**
		 * All application data
		 */
		applications: null,
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			//Change srcNode
			this.set('srcNode', Y.all('#cmsHeader'));
			
			//Create application dock bar
			this.app = new Supra.AppDock();
			this.setActiveApplication(Supra.data.get('application'));
			
			Supra.Manager.executeAction('LayoutContainers');
			Supra.Manager.executeAction('PageToolbar');
			Supra.Manager.executeAction('PageButtons');
		},
		
		/**
		 * Set active application
		 * 
		 * @param {Object} data Application data
		 */
		setActiveApplication: function (data) {
			if (data === null || data === undefined) {
				return;
			}
			if (typeof data === "string") {
				if (this.stack.length && this.stack[this.stack.length - 1].id.indexOf(data) != -1) {
					//If already opened, then skip
					return;
				}
				return this.getApplicationData(data, this.setActiveApplication, this);
			}
			if (data.id == 'Supra\\Cms\\ContentManager') {
				data = Supra.mix({}, data, {'title': ''});
				
				// Show page header, only visible for page manager
				if (Supra.Manager.PageHeader) {
					Supra.Manager.PageHeader.show();
				}
			} else {
				// Hide page header, not visible for any oher manager
				if (Supra.Manager.PageHeader) {
					Supra.Manager.PageHeader.hide();
				}
			}
			
			var apps = this.stack,
				index = this.indexOfApplication(data);
			
			if (index != -1) {
				apps.splice(index + 1);
			} else {
				apps.push(data);
			}
			
			this.app.set("data", data);
		},
		
		/**
		 * Unset active application
		 * Previous item from application list will be set as active
		 * 
		 * @param {Object} data Application data
		 */
		unsetActiveApplication: function (data) {
			var apps = this.stack,
				index = this.indexOfApplication(data);
			
			//If there is only one application, then we can't remove it
			if (apps.length <= 1 || index <= 0) return;
			
			this.setActiveApplication(apps[index - 1]);
		},
		
		/**
		 * Searches for application by partial ID and calls callback function setting first argument as application data
		 * 
		 * @param {String} id ID or partial ID
		 * @param {Function} callback Callback function to which application data will be returned to
		 * @param {Object} context Callback function context, optional
		 */
		getApplicationData: function (id, callback, context) {
			if (this.applications) {
				if (this.applications.then) {
					//Still loading, add as callback
					this.applications.then(Y.bind(function (data) {
						this.getApplicationData(id, callback, context);
					}, this));
				} else {
					var applications = this.applications,
						i = 0,
						ii = applications.length;
					
					for (; i<ii; i++) {
						if (applications[i].id.indexOf(id) !== -1) {
							callback.call(context || this, applications[i]);
							return;
						}
					}
					
					callback.call(context || this, null);
				}
			} else {
				//Load all application data from server
				this.applications = Supra.io(Supra.Manager.getAction("Applications").getDataPath("applications"), function (data) {
					//Remove size and extension from icon path
					Y.Array.forEach(data.applications, function (app) {
						app.icon = (app.icon || '').replace(/_\d+x\d+\.png/, '');
					});
					
					this.applications = data.applications;
					this.getApplicationData(id, callback, context);
				}, this);
			}
		},
		
		/**
		 * Returns application index in application list
		 * 
		 * @param {Object} data Application to search for
		 * @private
		 */
		indexOfApplication: function (data) {
			var apps = this.stack,
				i = 0, ii = apps.length,
				str = (typeof data === "string");
			
			for (; i<ii; i++) {
				if (str) {
					if (apps[i].id.indexOf(data) !== -1) return i;
				} else {
					if (apps[i].id == data.id) return i;
				}
			}
			
			return -1;
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.app.render(this.one());
		}
	});
	
});