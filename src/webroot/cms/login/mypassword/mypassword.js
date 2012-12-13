Supra("supra.input", function (Y) {
	//Invoke strict mode
	"use strict";

	//Shortcut
	var Manager = Supra.Manager,
		Loader = Manager.Loader,
		Action = Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, Action.PluginForm, Action.PluginFooter, {
		
		REQUIREMENTS_TEMPLATE: "<h3>{{ title }}</h3>\
				<ul>\
					{% for requirement in requirements %}\
					<li>{{ requirement }}</li>\
					{% endfor %}\
				</ul>",
		
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
			"password_new": false,
			"password_current": false,
			"password_confirm": false,
			"all": false
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
			
			
			var requirements = Supra.data.get("password_requirements");

			if (requirements) {
				var templateData = {"title": Supra.Intl.get(["password", "requirements_title"]), "requirements": requirements},
					html = (Supra.Template.compile(this.REQUIREMENTS_TEMPLATE))(templateData);

				this.one("div.requirements").set("innerHTML", html).removeClass("hidden");
			}
			
			//Password form
			this.passwordform.on("submit", this.submit, this);
			
			this.decorateInput(this.passwordform.getInput("passwordCurrent"), "password_current", true);
			this.decorateInput(this.passwordform.getInput("passwordNew"), "password_new");
			this.decorateInput(this.passwordform.getInput("passwordConfirm"), "password_confirm");
			
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
		decorateInput: function (input, name, use_valid) {
			var node = input.get("boundingBox");
			node.append(Y.Node.create("<em class=\"error\"></em>"));
			
			//if (use_valid) {
			//	node.append(Y.Node.create("<em class=\"valid\"></em>"));
			//}
			
			input.on("input", function (event) {
				if (!event.value) {
					input.addClass(input.getClassName("empty"));
					this.errors[name] = true;
					this.errors.all = false;
				} else {
					input.removeClass(input.getClassName("empty"));
					this.errors[name] = false;
					this.errors.all = false;
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
			
			this.setErrorMessage(null, 'new');
			this.setErrorMessage(null, 'current');
			
			if ( ! data.supra_password_current 
				|| ! data.supra_password
				|| ! data.supra_password_confirm) {
				
				this.errors.password_new = !data.supra_password;
				this.errors.password_confirm = !data.supra_password_confirm;
				this.errors.password_current = !data.supra_password_current;

				this.errors.all = false;
								
				if ( ! data.supra_password_current) {
					this.passwordform.getInput("supra_password_current").focus();
				} else if ( ! data.supra_password) {
					this.passwordform.getInput("supra_password").focus();
				} else if ( ! data.supra_password_confirm) {
					this.passwordform.getInput("supra_password_confirm").focus();
				}
				
				if (this.errors.password_new){ 
					this.setErrorMessage(Supra.Intl.get(["password", "error_fields_required"]), 'new');
				}
				
				if (this.errors.password_current){ 
					this.setErrorMessage(Supra.Intl.get(["password", "error_field_required"]), 'current');
				}
				
				
				return false;
			}
			
			if (data.supra_password_current == data.supra_password) {
			
				this.errors.password_current = 
					this.errors.all = false;

				this.errors.password_new = 
					this.errors.password_confirm = true;
				
				this.passwordform.getInput("passwordNew").focus();
				
				this.setErrorMessage(null, 'current');
				this.setErrorMessage(Supra.Intl.get(["password", "error_same_passwords"]), 'new');
				
				return false;
			}
			
			if (data.supra_password != data.supra_password_confirm) {
				
				this.errors.password_new = 
					this.errors.password_confirm = true;
				
				this.passwordform.getInput("passwordNew").focus();
				
				this.setErrorMessage(null, 'current');
				this.setErrorMessage(Supra.Intl.get(["password", "error_passwords_missmatch"]), 'new');
				
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

			this.errors.password_new = this.errors.password_current
					= this.errors.all = false;
				
			this.setErrorMessage(null, 'new');
			this.setErrorMessage(null, 'current');

			if (data && data.errors) {
				
				if (data.errors.password_new && data.errors_password_current) {
					this.errors.all = true;
					
					this.errors.password_new = false;
					this.errors.password_current = false;
				}
				else {
					this.errors.password_new = this.errors.password_confirm = data.errors.password_new;
					this.errors.password_current = data.errors.password_current;
				}
	
			}
			
			if (data.errors.password_current || data.errors.password_new) {
				
				if (data.errors.password_current) {
					this.setErrorMessage(Supra.Intl.get(["password", "error_current"]), 'current');
				} 
				
				if (data.errors.password_new) {
					this.setErrorMessage(Supra.Intl.get(["password", "error_requirements"]), 'new');
				}
			} else {
				this.setErrorMessage('Internal server error', 'current');
				this.setErrorMessage(null, 'new');
			}

			//Enable button and form
			this.footer.getButton("ok").set("loading", false);
			this.passwordform.set("disabled", false);
			
			if (this.errors.password_current || this.errors.all) {
				this.passwordform.getInput("passwordCurrent").focus();
			} 
			else if (this.errors.password_new) {
				this.passwordform.getInput("passwordNew").focus();
			}	
			
		},
		
		/**
		 * Show or hide error message
		 *
		 * @param {String} mesasge
		 * @private
		 */
		setErrorMessage: function (message, selector) {
			var node = null;
			
			if (selector) {
				node = this.one("div.error-message." + selector);
			} else {
				node = this.one("div.error-message");
			}
			
			if (message) {
				node.set("text", message).removeClass("hidden");
			} else {
				node.addClass("hidden");
			}
			
			this.setErrorStyles();
		},
		
		/**
		 * Update input styles
		 */
		setErrorStyles: function () {
			var errors = this.errors;
			
			this.passwordform.getInput("passwordCurrent")
						.set("error", errors.password_current || errors.all);
						
			this.passwordform.getInput("passwordNew")
						.set("error", errors.password_new || errors.all);
						
			this.passwordform.getInput("passwordConfirm")
						.set("error", errors.password_confirm || errors.all);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			//Show form
			this.show();
			
			//Show in the middle of the screen
			this.centerPanel();
			
			Supra.session.cancelPing();
			
			//Show or hide error message
			this.errors.password_current = this.errors.password_new
				this.errors.all = false;
				
			this.setErrorMessage(null);
			
			//Enable button and form
			this.footer.getButton("ok").set("loading", false);
			this.passwordform.set("disabled", false);
			
			//Reset password field value and focus input
			this.passwordform.getInput("passwordNew").resetValue();
			this.passwordform.getInput("passwordConfirm").resetValue();
			this.passwordform.getInput("passwordCurrent").resetValue().focus();
		}
		
	});
	
});