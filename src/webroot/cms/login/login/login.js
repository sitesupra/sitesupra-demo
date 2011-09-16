SU('supra.form', 'cookie', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Loader = Manager.Loader,
		Action = Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginForm, Action.PluginFooter, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Login',
		
		/**
		 * Action doesn't have a template
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		/**
		 * Set configuration/properties
		 * 
		 * @private
		 */
		initialize: function () {
			//Use mask and update zIndex only if login form is shown inline
			if (!this.isLoginManager()) {
				this.panel.set('zIndex', 1000);
				this.panel.set('useMask', true);
			}
		},
		
		/**
		 * Bind listeners, etc.
		 * 
		 * @private
		 */
		render: function () {
			this.loginform.on('submit', this.submit, this);
			
			Y.one('body').removeClass('loading');
		},
		
		/**
		 * Returns true if current manager is 'login'
		 *
		 * @return True if manager is 'login'
		 * @type {Boolean}
		 * @private
		 */
		isLoginManager: function () {
			return (Supra.data.get(['application', 'id']) == 'login');
		},
		
		/**
		 * Submit form
		 * 
		 * @private
		 */
		submit: function () {
			var uri = Loader.getDynamicPath() + Loader.getActionBasePath('Login');
			var data = this.loginform.getValues('name', true);
			
			//@TODO Replace with actual form validation
			if (!this.validate(data)) return;
			
			//Disable button and form
			this.loginform.set('disabled', true);
			this.footer.getButton('done').set('loading', true);
			
			//Send request manually, because of unusual response types
			Supra.io(uri, {
				'data': data,
				'method': 'post',
				'type': 'html',	//On success will respond with empty page
				'context': this,
				'on': { 'success': this.onLoginSuccess }
			});
		},
		
		/**
		 * Validate form
		 *
		 * @param {Object} data Form data
		 * @return True on success, false if data didn't passed validation
		 * @type {Boolean}
		 * @private
		 */
		validate: function (data) {
			data.supra_login = Y.Lang.trim(data.supra_login);
			data.supra_password = Y.Lang.trim(data.supra_password);
			
			if (!data.supra_login || !data.supra_password) {
				//Show error message
				this.setErrorMessage(Supra.Intl.get(['login', 'error']));
				
				//Focus input
				if (!data.supra_login) {
					this.loginform.getInput('supra_login').focus();
				} else if (!data.supra_password) {
					this.loginform.getInput('supra_password').focus();
				}
				
				return false;
			}
			
			return true;
		},
		
		/**
		 * Handle successful login
		 * In login manager reload page, server-side will redirect to correct page
		 * In inline mode update session ID and re-run all requests which failed
		 * 
		 * @private
		 */
		onLoginSuccess: function () {
			if (this.isLoginManager()) {
				//Reload page, server will take care of the rest
				document.location.reload();
			} else {
				var key = Supra.data.get('sessionName'),	//Cookie key
					value = Y.Cookie.get(key);				//Session ID
				
				//Update session ID
				Supra.data.set('sessionId', value);
				
				//Enable button and form
				this.footer.getButton('done').set('loading', false);
				this.loginform.set('disabled', false);
				
				//Hide form
				this.hide();
				
				//Execute requests which were queued
				Supra.io.loginRequestQueue.run();
			}
		},
		
		/**
		 * Show or hide error message
		 *
		 * @param {String} mesasge
		 * @private
		 */
		setErrorMessage: function (message) {
			if (message) {
				this.one('div.error-message').set('text', message).removeClass('hidden');
			} else {
				this.one('div.error-message').addClass('hidden');
			}
			
			this.loginform.getInput('supra_password').set('error', !!message);
			this.loginform.getInput('supra_login').set('error', !!message);
		},
		
		/**
		 * Execute action
		 *
		 * @param {Object} response Request response object
		 */
		execute: function (response) {
			//Show or hide error message
			this.setErrorMessage(response ? response.error_message : null);
			
			//Show in the middle of the screen
			this.panel.centered();
			
			//Enable button and form
			this.footer.getButton('done').set('loading', false);
			this.loginform.set('disabled', false);
			
			//Disable login field if needed
			if (Supra.data.get(['application', 'id']) != 'login') {
				//If opened from another manager (session expired) the disable login input
				var login = Supra.data.get(['user', 'login']),
					input = this.loginform.getInput('supra_login');
				
				input.set('value', login)
					 .set('disabled', true);
			}
			
			//Reset password field value and focus input
			this.loginform.getInput('supra_password').resetValue();
			if (!this.loginform.getInput('supra_login').getValue()) {
				this.loginform.getInput('supra_login').focus();
			} else {
				this.loginform.getInput('supra_password').focus();
			}
		}
		
	});
	
});