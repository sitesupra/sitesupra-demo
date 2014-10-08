Supra("supra.input", "cookie", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var COOKIE_CHANGE_CHECK_INTERVAL = 3000;

	//Shortcut
	var Manager = Supra.Manager,
		Loader = Manager.Loader,
		Action = Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginForm, Action.PluginFooter, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: "Login",
		
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
		 * Stores cookie string before popping up login form to compare afterwards
		 * @type {String}
		 * @private
		 */
		originalCookie: null,
		
		/**
		 * Resize event listener
		 * @type {Object}
		 * @private
		 */
		resizeListener: null,
		
		/**
		 * Error states
		 * @type {Object}
		 * @private
		 */
		errors: {
			"email": false,
			"password": false,
			"both": false // when server returns that login failed
		},
		
		

		/**
		 * Set configuration/properties
		 * 
		 * @private
		 */
		initialize: function () {
			//Use mask and update zIndex only if login form is shown inline
			if (!this.isLoginManager()) {
				this.panel.set("zIndex", 1000);
				this.panel.set("useMask", true);
			}
			
			Y.one("body").removeClass("loading");
		},
		
		/**
		 * Bind listeners, etc.
		 * 
		 * @private
		 */
		render: function () {
			//Panel
			this.panel.get("boundingBox").addClass("login-form-panel");
			this.centerPanel = Supra.throttle(this.centerPanel, 60, this);
			
			this.panel.on("visibleChange", function (event) {
				if (event.newVal) {
					this.onPanelShow();
				} else {
					this.onPanelHide();
				}
			}, this);
			
			//Login form
			this.loginform.on("submit", this.submit, this);
			this.decorateInput(this.loginform.getInput("email"), "email");
			this.decorateInput(this.loginform.getInput("password"), "password");
			
			//Footer
			this.footer.getButton("ok").set("style", "mid-blue");
			
			//Password reset link
			if (Supra.data.get(["site", "portal"])) {
				var uri = Supra.data.get(["site", "password_reset_uri"]);
				if (uri) {
					this.one("#reset").removeClass("hidden").setAttribute("href", uri);
				}
			}
		},
		
		/**
		 * When panel is shown bind to window resize
		 * 
		 * @private
		 */
		onPanelShow: function () {
			if (!this.resizeListener) {
				this.resizeListener = Y.on('resize', this.centerPanel);
			}
		},
		
		onPanelHide: function () {
			if (this.resizeListener) {
				this.resizeListener.detach();
				this.resizeListener = null;
			}
		},
		
		centerPanel: function () {
			this.panel.centered();
		},
		
		/**
		 * Decorate input
		 * Add error and valid markers, add/remove "empty" classname
		 * immediately on user input
		 * 
		 * @private
		 */
		decorateInput: function (input, name) {
			var node = input.get("boundingBox");
			node.append(Y.Node.create("<em class=\"error\"></em>"));
			node.append(Y.Node.create("<em class=\"valid\"></em>"));
			
			input.on("input", function (event) {
				if (!event.value) {
					input.addClass(input.getClassName("empty"));
					this.errors[name] = true;
					this.errors.both = false;
				} else {
					input.removeClass(input.getClassName("empty"));
					this.errors[name] = false;
					this.errors.both = false;
				}
				
				this.setErrorStyles();
			}, this);
		},
		
		/**
		 * Returns true if current manager is "login"
		 *
		 * @return True if manager is "login"
		 * @type {Boolean}
		 * @private
		 */
		isLoginManager: function () {
			return (Supra.data.get(["application", "id"]) == "cms_authentication");
		},
		
		/**
		 * Submit form
		 * 
		 * @private
		 */
		submit: function (e) {
			e.halt();
			
			var uri = Supra.Url.generate('cms_authentication_login_check');
			var data = this.loginform.getValues("name", true);
			
			if (!this.validate(data)) return;
			
			//Disable button and form
			this.loginform.set("disabled", true);
			this.footer.getButton("ok").set("loading", true);
			
			//Send request manually, because of unusual response types
			//On failure 403 error will be caught by Supra.io and
			//this action will be executed again
			Supra.io(uri, {
				"data": data,
				"method": "post",
				"type": "html",	//On success will respond with empty page
				"context": this,
				"on": { "success": this.onLoginSuccess, "failure": this.onLoginFailure }
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
			data.supra_password = data.supra_password;
			
			if (!data.supra_login || !data.supra_password) {
				//Show error message
				this.errors.email = !data.supra_login;
				this.errors.password = !data.supra_password;
				this.errors.both = false;
				
				this.setErrorMessage(Supra.Intl.get(["login", "error"]));
				
				//Focus input
				if (!data.supra_login) {
					this.loginform.getInput("supra_login").focus();
				} else if (!data.supra_password) {
					this.loginform.getInput("supra_password").focus();
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
		onLoginSuccess: function (data) {

			if (data != "1") {
				return this.onLoginFailure(data);
			}

			if (this.isLoginManager()) {
				//Reload page, server will take care of the rest
				document.location.search += ((document.location.search == "") ? "?" : "&") + "success=" + (new Date()).valueOf();
			} else {

				this.cancelCookieChangeWatch();

				var key = Supra.data.get("sessionName"),	//Cookie key
					value = key ? Y.Cookie.get(key) : "";	//Session ID
				
				//Update session ID
				Supra.data.set("sessionId", value);
				
				//Enable button and form
				this.footer.getButton("ok").set("loading", false);
				this.loginform.set("disabled", false);
				
				//Hide form
				this.hide();
				
				//Restore pinging
				Supra.session.ping();
				
				//Execute requests which were queued
				Supra.io.loginRequestQueue.run();
			}
		},

		/**
		 * Handle server failures
		 *
		 * @private
		 */
		onLoginFailure: function (data) {
			data = data || "Internal Server Error";
			
			this.errors.both = true;
			this.errors.email = this.errors.password = false;
			this.setErrorMessage(data);
			
			//Enable button and form
			this.footer.getButton("ok").set("loading", false);
			this.loginform.set("disabled", false);
		},
		
		/**
		 * Show or hide error message
		 *
		 * @param {String} mesasge
		 * @private
		 */
		setErrorMessage: function (message) {
			if (message) {
				this.one("div.error-message").set("text", message).removeClass("hidden");
			} else {
				this.one("div.error-message").addClass("hidden");
			}
			
			this.setErrorStyles();
		},
		
		/**
		 * Update input styles
		 */
		setErrorStyles: function () {
			var errors = this.errors;
			this.loginform.getInput("email").set("error", errors.email || errors.both);
			this.loginform.getInput("password").set("error", errors.password || errors.both);
		},
		
		/**
		 * Execute action
		 *
		 * @param {Object} response Request response object
		 */
		execute: function (response) {
			//Show form
			this.show();
			
			//Show or hide error message
			this.errors.email = this.errors.password = false;
			if (response && response.error_message) {
				this.errors.both = true;
			}
			
			this.setErrorMessage(response ? response.error_message : null);
			
			//Show in the middle of the screen
			this.centerPanel();
			
			//Enable button and form
			this.footer.getButton("ok").set("loading", false);
			this.loginform.set("disabled", false);
			
			//Disable login field if needed
			if (Supra.data.get(["application", "id"]) != "cms_authentication") {
				//If opened from another manager (session expired) the disable login input
				var login = Supra.data.get(["user", "login"]),
					input = this.loginform.getInput("supra_login");
				
				input.set("value", login)
					 .set("disabled", true);
			}
			
			//Reset password field value and focus input
			this.loginform.getInput("supra_password").resetValue();
			if (!this.loginform.getInput("supra_login").getValue()) {
				this.loginform.getInput("supra_login").focus();
			} else {
				this.loginform.getInput("supra_password").focus();
			}
			
			//Disable ping
			Supra.session.cancelPing();
			this.watchCookieChanges();
		},

		/**
		 * Cookie changes timeout handler
		 * @type {Object}
		 * @private
		 */
		timeout_handler: null,

		/**
		 * Start to watch cookie changes
		 */
		watchCookieChanges: function () {
			if (this.timeout_handler) return;
			if (this.isLoginManager()) return;

			// Remember the original cookie string
			this.originalCookie = document.cookie;
			this.timeout_handler = Y.later(COOKIE_CHANGE_CHECK_INTERVAL, this, this._checkCookieChanges, null, true);
		},

		/**
		 * Cancel cookie change watch
		 */
		cancelCookieChangeWatch: function () {
			if (this.timeout_handler) {
				this.timeout_handler.cancel();
				this.timeout_handler = null;
			}
		},

		/**
		 * Check cookie for changes, assume login success
		 *
		 * @private
		 */
		_checkCookieChanges: function () {
			if (document.cookie != this.originalCookie) {
				this.onLoginSuccess("1");
			}
		}
		
	});
	
});