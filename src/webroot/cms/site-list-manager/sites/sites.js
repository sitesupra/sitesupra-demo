//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(
	
	"transition",
	"supra.datagrid",
	"supra.datagrid-new-item",
	
function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: "Sites",
		
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
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ["LayoutContainers"],
		
		
		
		/**
		 * All widgets
		 * @type {Object}
		 * @private
		 */
		widgets: {
			"datagrid": null,
			"new_item": null
		},
		
		/**
		 * Application data has been loaded
		 * @type {Boolean}
		 * @private
		 */
		loaded: false,
		
		
		
		/**
		 * @constructor
		 */
		initialize: function () {
			
		},
		
		/**
		 * Render widgets
		 */
		render: function () {
			
			//Hide loading icon
			Y.one("body").removeClass("loading");
			
			this.renderHeader();
			
			//On dashboard execute hide this action
			var dashboard = Manager.getAction("Applications");
			dashboard.on("execute", this.hide, this);
		},
		
		/**
		 * Render header
		 */
		renderHeader: function () {
			var node = this.one("div.sites-manager-header");
			
			node.one("div.user span").set("text", Supra.data.get(["user", "name"]));
			
			var avatar = Supra.data.get(["user", "avatar"]);
			if (avatar) {
				node.one("div.user img").setAttribute("src", Supra.data.get(["user", "avatar"]));
			} else {
				node.one("div.user img").addClass("hidden");
			}
			
			if (Supra.data.get(['application', 'id']) === 'Supra\\Cms\\SiteListManager') {
				//???
				Supra.Y.one('div.yui3-app-content').addClass('hidden');
			}
			
			//Logout
			node.one('a.close').on("click", function() {
				document.location = Supra.Manager.Loader.getDynamicPath() + '/logout/'
			});
		},
		
		
		/* ------------------------------------- Data grid ------------------------------------- */
		
		
		/**
		 * Show new domain form
		 * 
		 * @private
		 */
		showForm: function () {
			if (!this.widgets.form) this.createForm();
			
			var node = this.one("div.add-site");
			if (!node.hasClass("hidden")) return; // already visible
			
			node.setStyles({
				"height": "0px",
				"opacity": 0
			});
			node.removeClass("hidden");
			node.transition({
				"opacity": 1,
				"height": "116px", // 96px height + 20px margin
				"duration": 0.35
			});
		},
		
		/**
		 * Hide new domain form
		 * 
		 * @private
		 */
		hideForm: function () {
			if (this.widgets.form.get('disabled')) return;
			var node = this.one("div.add-site");
			
			node.transition({
				"height": "0px",
				"opacity": 0,
				"duration": 0.35
			}, Y.bind(function () {
				node.addClass("hidden");
				this.widgets.form.getInput("name").set("value", "");
				this.widgets.form.getInput("domain").set("value", "");
			}, this));
		},
		
		/**
		 * Create form
		 */
		createForm: function () {
			if (this.widgets.form) return;
			
			var form = this.widgets.form = new Supra.Form({
				"srcNode": this.one("form")
			});
			
			form.render();
			form.on("submit", this.saveDomain, this);
			
			//Submit button
			var button = this.widgets.submit = new Supra.Button({
				"srcNode": form.get("boundingBox").one("button")
			});
			
			button.render();
			
			//Cancel
			form.get("boundingBox").one("a.cancel").on("click", this.hideForm, this);
		},
		
		/**
		 * Save new domain
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		saveDomain: function (e) {
			var form = this.widgets.form,
				values = form.getSaveValues(),
				uri = this.getDataPath('dev/create'),
				error = false,
				valid = false;
			
			valid = Y.Lang.trim(values.name);
			form.getInput('name').set('error', !valid);
			if (!valid) error = true;
			
			valid = Y.Lang.trim(values.domain);
			form.getInput('domain').set('error', !valid);
			if (!valid) error = true;
			
			if (!error) {
				this.widgets.form.set('disabled', true);
				this.widgets.submit.set('loading', true);
				
				//Submit
				Supra.io(uri, {
					'data': values,
					'method': 'post',
					'context': this,
					'on': {
						'complete': function (data, status) {
							this.widgets.form.set('disabled', false);
							this.widgets.submit.set('loading', false);
							this.hideForm();
							
							if (status && data.redirect) {
								//Redirect
								document.location = data.redirect;
							}
						}
					}
				});
			}
			
			e.halt();
		},
		
		
		/* ------------------------------------- Data grid ------------------------------------- */
		
		
		/**
		 * Create DataGrid and load site list
		 * 
		 * @private
		 */
		createDataGrid: function () {
			if (this.widgets.datagrid) return;
			
			var container = this.one("div.sites-list div.su-block-content");
			
			var datagrid = this.widgets.datagrid = new Supra.DataGrid({
				"requestURI": this.getDataPath("dev/sites"),
				"columns": [
					{"id": "title", "title": "Title"},
					{"id": "id", "title": "Domain"},
					{"id": "status", "title": "Status", "formatter": this.formatStatusColumn}
				]
			});
			datagrid.render(container);
			datagrid.on("row:click", this.handleRowClick, this);
			datagrid.on('drag:insert', this._handleRowInsert, this);
			
			//New item
			var new_item = this.widgets.new_item = new Supra.DataGridNewItem({
				'newItemLabel': Supra.Intl.get(['site-list-manager', 'new_site']),
				'draggable': false
			});
			
			new_item.render(container);
			new_item.on('insert:click', this._handleRowInsert, this);
		},
		
		/**
		 * Format data grid status column text
		 * 
		 * @private
		 */
		formatStatusColumn: function (col_id, value, data) {
			return "<div class=\"status-" + value + "\">" + Y.Escape.html(Supra.Intl.get(["site-list-manager", "status", String(value)])) + "</div>";
		},
		
		/**
		 * Handle row click
		 */
		handleRowClick: function (e) {
			var record_id = e.row.getID();
			if (record_id) {
				//@TODO Clicked on record, redirect where?
				alert("@TODO: Clicked on " + record_id + ", now redirect where?");
			}
		},
		
		/**
		 * Handle new item insert
		 */
		_handleRowInsert: function (e) {
			this.showForm();
		},
		
		
		/* ------------------------------------- Action ------------------------------------- */
		
		
		/**
		 * Animate dashboard out of view
		 */
		hide: function () {
			//Site list application is opened, can't close it
			if (Supra.data.get(["application", "id"]) === "Supra\\Cms\\SiteListManager") return;
			
			this.set("visible", false);
			
			var transition = {
				"transform": "scale(2)",
				"opacity": 0,
				"duration": 0.5
			};
			
			if (Y.UA.ie && Y.UA.ie < 10) {
				transition.msTransform = transition.transform;
			}
			
			this.one().transition(transition, Y.bind(function () {
				this.one().addClass("hidden");
			}, this));
			
			//Remove from header
			Manager.getAction('Header').unsetActiveApplication(this.NAME);
		},
		
		/**
		 * Animate dashboard into view
		 */
		show: function () {
			this.one().removeClass("hidden");
			
			this.set("visible", true);
			
			var styles = {"opacity": 1},
				transition = null;
			
			//Animation turned off ?
			if (this.get('animation') !== false) {
				
				if (Y.UA.opera || (Y.UA.ie && Y.UA.ie < 10)) {
					//Fallback for non-supporting browsers
					styles = { "opacity": 0 };
					transition = { "opacity": 1 };
				} else {
					//Transformation transitions supported
					styles = {
						"opacity": 0,
						"transform": "scale(2)"
					};
					transition = {
						"opacity": 1,
						"transform": "scale(1)"
					};
				}
				
			}
			
			Y.later(150, this, function () {
				var node = this.one();
				node.setStyles(styles);
				
				if (transition) {
					node.transition(transition, Y.bind(function () {
						this.createDataGrid();
					}, this));
				} else {
					this.createDataGrid();
				}
			});
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			//Add to header, because this is full screen application
			Manager.getAction('Header').setActiveApplication({
				"id": this.NAME,
				"path": "/cms/site-list-manager",
				"title": Supra.Intl.get(["site-list-manager", "title"]),
				"icon": "/cms/lib/supra/img/apps/site-list-manager"
			});
			this.NAME
		}
	});
	
});