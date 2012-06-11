

//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(
	
	"supra.form",
	"transition",
	
function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: "Profile",
		
		/**
		 * Action has stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action has template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		/**
		 * Widgets
		 * @type {Object}
		 * @private
		 */
		widgets: {
			"formName": null,
			"saveName": null,
			
			"formPassword": null,
			"footerPassword": null,
			
			"formEmail": null,
			"saveEmail": null,
			
			"formSites": null,
			
			"buttonDeleteProfile": null
		},
		
		/**
		 * User data
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		
		
		/**
		 * 
		 * 
		 * @private
		 */
		initialize: function () {
			this.widgets.formName = new Supra.Form({
				"srcNode": this.one("div.name form")
			});
			this.widgets.saveName = new Supra.Button({
				"srcNode": this.one("div.name button")
			});
			
			this.widgets.formPassword = new Supra.Form({
				"srcNode": this.one("div.password form")
			});
			this.widgets.footerPassword = new Supra.Footer({
				"srcNode": this.one("div.password div.footer")
			});
			
			this.widgets.formEmail = new Supra.Form({
				"srcNode": this.one("div.email form")
			});
			this.widgets.saveEmail = new Supra.Button({
				"srcNode": this.one("div.email button")
			});
			
			this.widgets.formSites = new Supra.Form({
				"srcNode": this.one("div.sites form")
			});
			this.widgets.buttonDeleteProfile = new Supra.Button({
				"srcNode": this.one("div.delete button")
			});
		},
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			//Widgets
			this.widgets.formName.render();
			this.widgets.formName.on("submit", this.handleSubmit, this);
			this.widgets.saveName.render();
			
			this.widgets.formPassword.render();
			this.widgets.formPassword.on("submit", this.handleSubmit, this);
			this.widgets.footerPassword.render();
			this.widgets.formPassword.on("disabledChange", function (e) { this.set("disabled", e.newVal); }, this.widgets.footerPassword);
			
			this.widgets.formEmail.render();
			this.widgets.formEmail.on("submit", this.handleSubmit, this);
			this.widgets.saveEmail.render();
			
			this.widgets.formSites.render();
			
			this.widgets.buttonDeleteProfile.render();
			this.widgets.buttonDeleteProfile.on("click", this.deleteProfile, this);
			
			this.addInputBindings();
			
			//Handle edit and cancel clicks
			this.one().delegate("click", this.handleEdit, "a.edit", this);
			this.one().delegate("click", this.handleCancel, "a.cancel", this);
			
			//Handle avatar click
			this.one('div.info').on('click', this.handleAvatarClick, this);
			
			//Load profile data
			this.loadData();
		},
		
		/**
		 * Bind listeners to change label text on input value change
		 * 
		 * @private
		 */
		addInputBindings: function () {
			//Bindings, when input value changes update node content
			var forms = [this.widgets.formName, this.widgets.formPassword, this.widgets.formEmail],
				form = null,
				i = 0,
				ii = forms.length,
				nodes = null,
				n = 0,
				nn = 0,
				bind = null,
				input = null;
			
			for (; i<ii; i++) {
				form = forms[i];
				nodes = form.get("contentBox").all("*[suBind]");
				n = 0;
				nn = nodes.size();
				
				for (; n<nn; n++) {
					bind = nodes.item(n).getAttribute("suBind");
					input = form.getInput(bind);
					
					if (bind && input) {
						input.on("valueChange", function (e, node) {
							if (e.silent) return;
							this.setLabelText(node, e.newVal);
						}, this, nodes.item(n));
					}
				}
			}
		},
		
		/**
		 * Set node text
		 * 
		 * @param {String} label Node "suBind" attribute value
		 * @param {String} text Text value, optional
		 * @private
		 */
		setLabelText: function (label, text) {
			var node = Y.Lang.isWidget(label) ? label : this.all("[suBind='" + label + "']");
			
			if (typeof label === "string" && text === null || text === undefined) {
				var widgets = this.widgets,
					key = null,
					input = null;
				
				for (key in widgets) {
					if (widgets[key] && widgets[key].isInstanceOf("form")) {
						input = widgets[key].getInput(label);
						if (input) {
							text = input.get("value");
							if (input.getAttribute("type") == "password") text = text.replace(/./g, "•") || "******";
							break;
						}
					}
				}
			}
			
			if (node && text !== null && text !== undefined) {
				node.set("text", text);
			}
		},
		
		/**
		 * Load profile data
		 * 
		 * @private
		 */
		loadData: function () {
			Supra.io(this.getDataPath("dev/load"), this.setData, this);
		},
		
		/**
		 * Set profile data
		 * 
		 * @param {Object} data Profile data
		 * @private
		 */
		setData: function (data) {
			var widgets = this.widgets,
				widget = null,
				key = null,
				filtered = null;
			
			for(var name in widgets) {
				widget = widgets[name];
				filtered = {};
				
				if (widget && widget.isInstanceOf("form")) {
					//Only values for which there is an input
					for (key in data) {
						if (widget.getInput(key)) filtered[key] = data[key];
					}
					
					widget.setValues(filtered, "name");
				}
			}
			
			var sites = data.sites,
				i = 0,
				ii = sites.length,
				input = null,
				form = widgets.formSites.get("contentBox");
			
			for (; i<ii; i++) {
				input = new Supra.Input.Checkbox({
					"id": sites[i].id,
					"label": sites[i].title,
					"labels": ["{# profile.sites.on #}", "{# profile.sites.off #}"],
					"value": sites[i].value
				});
				
				input.render(form);
				input.on("valueChange", this.handleSiteChange, this);
				
				widgets.formSites.addInput(input);
			}
			
			this.data = data;
		},
		
		
		/**
		 * --------------------------- EDITING -----------------------------
		 */
		
		
		/**
		 * Show form
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		handleEdit: function (e) {
			var node = e.target.closest("form"),
				form = Y.Widget.getByNode(node),
				name = node.getAttribute("name");
			
			if (name == "Password") {
				form.getInput("new_password").set("value", "", {"silent": true});
				form.getInput("confirm_new_password").set("value", "", {"silent": true});
			}
			
			this.showForm(node);
		},
		
		/**
		 * Hide form, show labels
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		handleCancel: function (e) {
			var node = e.target.closest("form"),
				form = Y.Widget.getByNode(node);
			
			//Restore previous values
			form.setValues(this.data);
			
			//Remove error styles
			var inputs = form.getInputs(),
				key = null;
			
			for (key in inputs) inputs[key].set("error", false);
			
			this.hideForm(node);
		},
		
		/**
		 * Handle form submit
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		handleSubmit: function (e) {
			var form = e.target,
				node = form.get("contentBox"),
				button = Y.Widget.getByNode(node.one("button")),
				name = node.getAttribute("name"),
				values = form.getSaveValues("name");
			
			//Validate?
			if (!this.validate(name, values, form)) {
				return false;
			}
			
			//Disable form
			form.set("disabled", true);
			button.set("loading", true);
			
			//
			Supra.io(this.getDataPath("dev/save"), {
				"data": values,
				"method": "post",
				"context": this,
				"on": {
					"success": function () {
						this.handleSubmitSuccess(node, name, form, button, values);
					},
					"failure": function () {
						this.handleSubmitFailure(node, name, form, button, values);
					}
				}
			});
		},
		
		/**
		 * Handle request sucess
		 * 
		 * @private
		 */
		handleSubmitSuccess: function (node, name, form, button, values) {
			Supra.mix(this.data, form.getSaveValues("name"));
			
			this.hideForm(node);
			
			//Update UI
			if (name == "Password") {
				this.setLabelText("password", values.new_password.replace(/./g, "•"));
			}
			
			//Enable form
			form.set("disabled", false);
			button.set("loading", false);
		},
		
		/**
		 * Handle request failure
		 * 
		 * @private
		 */
		handleSubmitFailure: function (node, name, form, button, values) {
			this.hideForm(node);
			
			//Enable form
			form.set("disabled", false);
			button.set("loading", false);
		},
		
		/**
		 * Handle site on/off change
		 * 
		 * @private
		 */
		handleSiteChange: function (e) {
			if (!e.silent && e.newVal != e.prevVal) {
				var input = e.target,
					id = input.get("id");
				
				Supra.io(this.getDataPath("dev/save-site"), {
					"data": {
						"id": id,
						"value": e.newVal
					},
					"method": "post",
					"context": this,
					"on": {
						"failure": function () {
							//Revert value change
							input.set("value", e.prevVal, {"silent": true});
						}
					}
				});
			}
		},
		
		/**
		 * Validate values
		 */
		validate: function (name, values, form) {
			if (name == "Password") {
				var matches = (values.new_password && values.new_password == values.confirm_new_password);
				form.getInput("new_password").set("error", !matches);
				form.getInput("confirm_new_password").set("error", !matches);
				
				if (!matches) return false;
			} else if (name == "Name") {
				var valid = !!values.name;
				form.getInput("name").set("error", !valid);
				if (!valid) return false;
			} else if (name == "Email") {
				var valid = !!values.email;
				form.getInput("email").set("error", !valid);
				if (!valid) return false;
			}
			
			return true;
		},
		
		/**
		 * Show form
		 * 
		 * @param {Object} node Form node
		 * @private
		 */
		showForm: function (node) {
			var preview = node.one("div.preview"),
				inputs = node.one("div.inputs"),
				preview_height = preview.get("offsetHeight");
			
			inputs.removeClass("hidden");
			preview.setData("offsetHeight", preview_height);
			
			var inputs_height = inputs.get("offsetHeight"),
				diff_height = Math.abs(preview_height - inputs_height);
			
			if (diff_height >= 2) {
				preview.addClass("hidden");
				
				inputs.setStyle("height", preview_height + "px");
				inputs.transition({
					"height": inputs_height + "px",
					"duration": 0.35
				}, function () {
					this.setStyle("height", "auto");
				});
			} else {
				//Same height, use fadein / fadeout
				inputs.setStyles({
					"opacity": 0
				});
				inputs.transition({
					"opacity": 1,
					"duration": 0.25
				});
				preview.setStyles({
					"marginTop": - inputs_height + "px"
				});
				preview.transition({
					"opacity": 0,
					"duration": 0.35
				}, function () {
					preview.addClass("hidden");
				});
			}
		},
		
		/**
		 * Hide form
		 * 
		 * @param {Object} node Form node
		 * @private
		 */
		hideForm: function (node) {
			var preview = node.one("div.preview"),
				inputs = node.one("div.inputs"),
				preview_height = preview.getData("offsetHeight"),
				inputs_height = inputs.get("offsetHeight"),
				diff_height = Math.abs(preview_height - inputs_height);
			
			if (diff_height >= 2) {
				inputs.setStyle("height", inputs_height + "px");
				inputs.transition({
					"height": preview_height + "px",
					"duration": 0.35
				}, function () {
					preview.removeClass("hidden");
					inputs.addClass("hidden");
					inputs.setStyle("height", "auto");
				});
			} else {
				preview.removeClass("hidden");
				
				//Same height, use fadein / fadeout
				preview.transition({
					"opacity": 1,
					"duration": 0.25
				});
				inputs.transition({
					"opacity": 0,
					"duration": 0.35
				}, function () {
					inputs.addClass("hidden");
					preview.setStyles({"marginTop": "0"});
				});
			}
		},
		
		handleAvatarClick: function (event) {
			var UserAvatar = Manager.getAction('UserAvatar');
			UserAvatar.set('controller', this);
			UserAvatar.execute();
			event.halt();
		},
		
		
		/**
		 * --------------------------- DELETE PROFILE -----------------------------
		 */
		
		
		/**
		 * Delete profile
		 */
		deleteProfile: function () {
			Supra.Manager.executeAction("Confirmation", {
				"message": Supra.Intl.get(["profile", "remove", "confirmation"]),
				"buttons": [
					{"id": "yes", "style": "small-red", "click": this.deleteSiteConfirmed, "context": this},
					{"id": "no"}
				]
			});
		},
		
		/**
		 * Actually delete profile after confirmation
		 * 
		 * @private
		 */
		deleteProfileConfirmed: function () {
			//Disable all widgets
			this.setDisabled(true);
			this.widgets.buttonDeleteProfile.set("loading", true);
			
			//Send request
			Supra.io(this.getDataPath("dev/delete"), {
				"method": "post",
				"context": this,
				"on": {
					"complete": this.deleteProfileComplete
				}
			})
		},
		
		/**
		 * Handle delete profile request response
		 * 
		 * @param {Object} data Response data
		 * @param {Boolean} status Response status
		 * @private
		 */
		deleteProfileComplete: function (data, status) {
			if (status) {
				
				//@TODO
				
			} else {
				this.setDisabled(false);
				this.widgets.buttonDeleteSite.set("loading", false);
			}
		},
		
		
		/**
		 * --------------------------- API -----------------------------
		 */
		
		
		/**
		 * Disable or enable all widgets
		 * 
		 * @param {Boolean} disabled If true all widgets will be disabled, otherwise enabled
		 */
		setDisabled: function (disabled) {
			//Enable all widgets
			var widgets	= this.widgets,
				widget	= null,
				i		= null;
			
			for(var i in widgets) {
				widget = widgets[i];
				if ("disabled" in widget.getAttrs()) {
					widget.set("disabled", disabled);
				}
			}
		},
		
		/**
		 * Returns all profile data
		 * 
		 * @return Profile data
		 * @type {Object}
		 */
		getData: function () {
			return this.data;
		},
		
		/**
		 * Update UI after avatar change
		 * 
		 * @param {Object} data Data
		 */
		updateUI: function (data) {
			if ('avatar' in data) {
				this.one('div.info img').setAttribute('src', data.avatar + '?r=' + (+new Date()));
				
				//Send save request
				Supra.io(this.getDataPath("dev/save"), {
					"data": {
						"avatar": this.data.avatar,
						"avatar_id": this.data.avatar_id
					},
					"method": "post"
				});
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});