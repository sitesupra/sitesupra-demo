//Invoke strict mode
"use strict";

/**
 * Custom modules
 */
(function () {
	var STATIC_PATH = Supra.Manager.Loader.getStaticPath(),
		APP_PATH = Supra.Manager.Loader.getActionBasePath("Applications");
	
	Supra.setModuleGroupPath("dashboard", STATIC_PATH + APP_PATH + "/modules");
	
	// Statistics
	Supra.addModule("dashboard.stats-list", {
		path: "stats-list.js",
		requires: [
			"widget"
		]
	});
	Supra.addModule("dashboard.chart-hover-plugin", {
		path: "chart-hover-plugin.js",
		requires: [
			"charts",
			"plugin"
		]
	});
	Supra.addModule("dashboard.stats-visitors", {
		path: "stats-visitors.js",
		requires: [
			"widget",
			"charts",
			"dashboard.chart-hover-plugin"
		]
	});
	Supra.addModule("dashboard.stats-summary", {
		path: "stats-summary.js",
		requires: [
			"widget"
		]
	});
	
	Supra.addModule("dashboard.stats", {
		path: "stats.js",
		requires: [
			"widget",
			"supra.io",
			"supra.deferred",
			"dashboard.stats-list",
			"dashboard.stats-visitors",
			"dashboard.stats-summary"
		]
	});
	
	// Inbox
	Supra.addModule("dashboard.inbox", {
		path: "inbox.js",
		requires: [
			"dashboard.stats-list"
		]
	});
	
	// Application list
	Supra.addModule("dashboard.pagination", {
		path: "pagination.js",
		requires: [
			"widget"
		]
	});
	Supra.addModule("dashboard.app-list", {
		path: "app-list.js",
		requires: [
			"widget",
			"dashboard.pagination",
			"transition",
			"dd"
		]
	});
})();

/**
 * Main manager action, initiates all other actions
 */
Supra([
	
	"dashboard.app-list",
	"transition",
	
	Supra.data.get(["site", "portal"]) ? "dashboard.inbox" : null,
	Supra.data.get(["site", "portal"]) ? "dashboard.stats" : null
	
], function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: "Applications",
		
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
			"inbox": null,
			"stats": null,
			
			"apps": null,
			
			"scrollable": null
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
			
			// If stats module is loaded
			if (Supra.DashboardStats) {
				this.widgets.inbox = new Supra.DashboardInbox({
					"requestUri": this.getDataPath("../inbox/inbox")
				});
				this.widgets.stats = new Supra.DashboardStats({
					"statsRequestUri": this.getDataPath("../stats/stats"),
					"profilesRequestUri": this.getDataPath("../stats/profiles"),
					"unauthorizeRequestUri": this.getDataPath("../stats/delete"),
					"saveRequestUri": this.getDataPath("../stats/save"),
					"srcNode": this.one("div.grid")
				});
			} else {
				this.one('.dashboard-analytics').addClass('hidden');
			}
			
			this.widgets.apps = new Supra.AppList({
				"srcNode": this.one("div.dashboard-apps"),
				"value": Supra.data.get(["application", "id"])
			});
			
			this.widgets.scrollable = new Supra.Scrollable({
				"srcNode": this.one("div.apps-scrollable")
			});
		},
		
		/**
		 * Render widgets
		 */
		render: function () {
			//Hide loading icon
			Y.one("body").removeClass("loading");
			
			this.widgets.apps.render();
			this.widgets.apps.on("appmove", this.onAppsSort, this);
			
			if (this.widgets.inbox) {
				this.widgets.inbox.render(this.one("div.inbox"));
			}
			if (this.widgets.stats) {
				this.widgets.stats.render();
			}
			
			this.renderHeader();
			
			//Scrollable
			this.widgets.scrollable.render();
		},
		
		/**
		 * Render header
		 */
		renderHeader: function () {
			var node = this.one("div.dashboard-header");
			
			node.one("a.user span").set("text", Supra.data.get(["user", "name"]));
			
			var avatar = Supra.data.get(["user", "avatar"]);
			if (avatar) {
				node.one("a.user img").setAttribute("src", Supra.data.get(["user", "avatar"]));
			} else {
				node.one("a.user img").addClass("hidden");
			}
			
			if (Supra.data.get(["application", "id"]) === "Supra\\Cms\\Dashboard") {
				Supra.Y.one("div.yui3-app-content").addClass("hidden");
			}
			
//			if (Supra.data.get(["application", "id"]) === "Supra\\Cms\\Dashboard") {
//				node.one("a.close").addClass("hidden");
//			} else {
				//node.one("a.close").on("click", this.hide, this);
				node.one("a.close").on("click", function() {
					document.location = Supra.Manager.Loader.getDynamicPath() + "/logout/"
				});
//			}
		},
		
		/**
		 * Load all data
		 */
		load: function () {
			if (this.loaded) return;
			this.loaded = true;
			this.loadApplicationData();
			this.renderSiteInfo();
		},
		
		
		/* ------------------------------------ Data ------------------------------------ */
		
		/**
		 * Load and set application list data 
		 */
		loadApplicationData: function () {
			Supra.io(this.getDataPath("applications"), function (data, status) {
				if (status && data) {
					var applications = [],
						profile = null; // Profile application info
					
					Y.Array.each(data.applications, function (app) {
						if (app.id.indexOf("\\Profile") !== -1 || app.id.indexOf("/Profile") !== -1) {
							this.updateProfileLink(app);
						} else {
							applications.push(app);
						}
					}, this);
					
					this.widgets.apps.set("data", applications);
					this.widgets.scrollable.syncUI();
					
					
				}
			}, this);
		},
		
		/**
		 * Update header profile link
		 */
		updateProfileLink: function (app) {
			var node = this.one("a.user");
			node.addClass('link');
			node.on("click", function () {
				//Open profile manager
				document.location = app.path;
			}, this);
		},
		
		
		/* ------------------------------------ Apps ------------------------------------ */
		
		
		/**
		 * When application list is sorted inform server
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onAppsSort: function (e) {
			var app = e.application,
				ref = e.reference;
			
			Supra.io(this.getDataPath("sort"), {
				"data": {
					"id": app.id,
					"before": ref ? ref.id : ""
				},
				"method": "post",
				"context": this
			});
		},
		
		
		/* ------------------------------------ Site info  ------------------------------------ */
		
		
		/**
		 * Render site info
		 * 
		 * @private
		 */
		renderSiteInfo: function () {
			var title = Supra.data.get(["site", "title"]),
				node = null;
			
			if (title) {
				node = this.one("div.site");
				node.removeClass("hidden");
				
				node.one("span").set("text", title);
				node.one("a").on("click", this.openSiteListManager, this);
			}
		},
		
		/**
		 * Open site list manager
		 * 
		 * @private
		 */
		openSiteListManager: function () {
			Supra.Manager.executeAction("Sites");
		},
		
		
		/* ------------------------------------ Action  ------------------------------------ */
		
		
		/**
		 * Animate dashboard out of view
		 */
		hide: function () {
			//Dashboard application is opened, can't close it
			if (Supra.data.get(["application", "id"]) === "Supra\\Cms\\Dashboard") return;
			
			this.set("visible", false);
			
			var transition = {
				"transform": "scale(2)",
				"opacity": 0,
				"duration": 0.35
			};
			
			if (Y.UA.gecko || Y.UA.opera || Y.UA.ie) {
				transition = {
					"opacity": 0,
					"duration": 0.35
				};
			}
			
			this.one().transition(transition, Y.bind(function () {
				this.one().addClass("hidden");
				
				// Enable page header
				var header = Supra.Manager.PageHeader;
				if (header && header.languagebar) {
					header.languagebar.set("disabled", false);
				}
			}, this));
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
			if (this.get("animation") !== false) {
				styles = {
					"opacity": 0,
					"transform": "scale(2)"
				};
				transition = {
					"opacity": 1,
					"transform": "scale(1)"
				};
				
				if (Y.UA.gecko || Y.UA.opera || Y.UA.ie) {
					//Fallback for non-supporting browsers
					styles = { "opacity": 0 };
					transition = { "opacity": 1 };
				}
				
			}
			
			Y.later(150, this, function () {
				var node = this.one(),
					after = Y.bind(function () {
						this.load();
						this.widgets.scrollable.syncUI();
					}, this);
				
				node.setStyles(styles);
				
				if (transition) {
					// Animate
					node.transition(transition, after);
				} else {
					after();
				}
			});
			
			// Disable page header
			if (Supra.Manager.PageHeader) {
				Supra.Manager.PageHeader.languagebar.set("disabled", true);
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