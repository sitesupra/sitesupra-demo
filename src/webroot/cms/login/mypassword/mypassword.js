Supra("supra.input", function (Y) {
	//Invoke strict mode
	"use strict";

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
		NAME: "MyPassword",
		
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
			"passwordNew": false,
			"passwordCurrent": false,
			"passwordConfirm": false,
			"both": false
		},
		
		

		/**
		 * Set configuration/properties
		 * 
		 * @private
		 */
		initialize: function () {
			Y.one("body").removeClass("loading");
		},
		
		/**
		 * Bind listeners, etc.
		 * 
		 * @private
		 */
		render: function () {
			//Panel
			this.panel.get("boundingBox").addClass("password-form-panel");
			this.centerPanel = Supra.throttle(this.centerPanel, 60, this);
			
			this.panel.on("visibleChange", function (event) {
				if (event.newVal) {
					this.onPanelShow();
				} else {
					this.onPanelHide();
				}
			}, this);
			
			//Password form
			this.passwordform.on("submit", this.submit, this);
			
			this.decorateInput(this.passwordform.getInput("passwordNew"), "passwordNew");
			this.decorateInput(this.passwordform.getInput("passwordCurrent"), "passwordCurrent");
			this.decorateInput(this.passwordform.getInput("passwordConfirm"), "passwordConfirm");
			
			//Footer
			this.footer.getButton("ok").set("style", "mid-blue");
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
		 * Submit form
		 * 
		 * @private
		 */
		submit: function (e) {
			e.halt();
			
			var uri = this.getDataPath('change');
			var data = this.passwordform.getValues("name", true);
			
			if (!this.validate(data)) return;
			
			//Disable button and form
			this.passwordform.set("disabled", true);
			this.footer.getButton("ok").set("loading", true);
			
			//Send request and capture response
			Supra.io(uri, {
				"data": data,
				"method": "post",
				"type": "json",
				"context": this,
				"on": { "success": this.onChangeSuccess, "failure": this.onChangeFailure }
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

			if ( ! data.supra_password_current 
				|| ! data.supra_password
				|| ! data.supra_password_confirm) {
				
				this.errors.passwordNew = !data.supra_password;
				this.errors.passwordConfirm = !data.supra_password_confirm;
				this.errors.passwordCurrent = !data.supra_password_confirm;

				this.errors.both = false;
				
				if ( ! data.supra_password_current) {
					this.passwordform.getInput("supra_password_current").focus();
				} else if ( ! data.supra_password) {
					this.passwordform.getInput("supra_password").focus();
				} else if ( ! data.supra_password_confirm) {
					this.passwordform.getInput("supra_password_confirm").focus();
				}			
				
				return false;
			}
			
			if (data.supra_password_current == data.supra_password) {
				
				this.errors.passwordConfirm = 
					this.errors.both = false;
				
				this.errors.passwordNew = 
					this.errors.passwordConfirm = true;
				
				this.passwordform.getInput("passwordNew").focus();
				
				this.setErrorMessage(Supra.Intl.get(["password", "error_same_passwords"]));
				
				return false;
			}
			
			if (data.supra_password != data.supra_password_confirm) {
				
				this.errors.passwordNew = 
					this.errors.passwordConfirm = true;
				
				this.passwordform.getInput("passwordNew").focus();
				
				this.setErrorMessage(Supra.Intl.get(["password", "error_passwords_missmatch"]));
				
				return false;
			}
			
			return true;
		},
		
		/**
		 * @private
		 */
		onChangeSuccess: function (data) {

			if (data && ! data.success) {
				return this.onChangeFailure(data);
			}

			document.location.search += ((document.location.search == "") ? "?" : "&") + "success=" + (new Date()).valueOf();
		},

		/**
		 * Handle server failures
		 *
		 * @private
		 */
		onChangeFailure: function (data) {
									
			data.errorMessage = data.errorMessage || "Internal server error";
			
			if (data || data.errorFields) {
				this.errors.both = false;
				
				this.errors.passwordCurrent = data.errorFields.passwordCurrent || false;
				this.errors.passwordNew = data.errorFields.passwordNew || false;
				this.errors.passwordConfirm = data.errorFields.passwordConfirm || false;
			
			} else {

				this.errors.passwordCurrent = this.errors.passwordNew;
				this.errors.passwordConfirm = false;
				this.errors.both = true;
			}
			
			this.setErrorMessage(data.errorMessage);

			if (this.errors.passwordCurrent) {
				this.passwordform.getInput("supra_password_current").focus();
			} else if (this.errors.passwordNew) {
				this.passwordform.getInput("supra_password").focus();
			} else if (this.errors.passwordConfirm) {
				this.passwordform.getInput("supra_password_confirm").focus();
			}		

			//Enable button and form
			this.footer.getButton("ok").set("loading", false);
			this.passwordform.set("disabled", false);
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
			
			this.passwordform.getInput("passwordCurrent")
						.set("error", errors.passwordCurrent || errors.both);
						
			this.passwordform.getInput("passwordNew")
						.set("error", errors.passwordNew || errors.both);
						
			this.passwordform.getInput("passwordConfirm")
						.set("error", errors.passwordConfirm);
		},
		
		/**
		 * Execute action
		 *
		 * @param {Object} response Request response object
		 */
		execute: function (response) {
			//Show form
			this.show();
			
			//Show in the middle of the screen
			this.centerPanel();
			
			Supra.session.cancelPing();
			
			//Show or hide error message
			this.errors.current = this.errors.password 
				this.errors.confirm = false;
				
			if (response && response.error_message) {
				this.errors.both = true;
			}
			
			this.setErrorMessage(response ? response.error_message : Supra.Intl.get(["password", "expired"]));
			
			//Enable button and form
			this.footer.getButton("ok").set("loading", false);
			this.passwordform.set("disabled", false);
			
			//Reset password field value and focus input
			this.passwordform.getInput("passwordNew").resetValue();
			this.passwordform.getInput("passwordConfirm").resetValue();
			this.passwordform.getInput("passwordCurrent").resetValue();

			this.passwordform.getInput("passwordCurrent").focus();
		}
		
	});
	
});