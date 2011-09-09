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
		 */
		initialize: function () {
			this.panel.set('zIndex', 1000);
			
			if (!this.isLoginManager()) {
				this.panel.set('useMask', true);
			}
		},
		
		isLoginManager: function () {
			return (Supra.data.get(['application', 'id']) == 'login');
		},
		
		/**
		 * Bind listeners, etc.
		 */
		render: function () {
			this.footer.getButton('done').on('click', this.form.submit, this.form);
			this.form.on('submit', this.submit, this);
			
			Y.one('body').removeClass('loading');
		},
		
		/**
		 * Submit form
		 */
		submit: function () {
			var uri = Loader.getDynamicPath() + Loader.getActionBasePath('Login');
			var data = this.form.getValues('name', true);
			
			if (!Y.Lang.trim(data.supra_login) || !Y.Lang.trim(data.supra_password)) {
				this.setErrorMessage(Supra.Intl.get(['login', 'error']));
				return;
			}
			
			//Disable button and form
			this.form.set('disabled', true);
			this.footer.getButton('done').set('loading', true);
			
			Supra.io(uri, {
				'data': data,
				'method': 'post',
				'type': 'html',	//On success will respond with empty page
				'context': this,
				'on': { 'success': this.onLoginSuccess }
			});
		},
		
		/**
		 * Handle successful login
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
				this.form.set('disabled', false);
				
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
		 */
		setErrorMessage: function (message) {
			if (message) {
				this.one('div.error-message').set('text', message).removeClass('hidden');
			} else {
				this.one('div.error-message').addClass('hidden');
			}
			
			this.form.getInput('supra_password').set('error', !!message);
			this.form.getInput('supra_login').set('error', !!message);
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
			this.form.set('disabled', false);
			
			//Empty form
			if (!response) {
				this.form.resetValues();
			}
		}
		
	});
	
});