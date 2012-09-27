//Invoke strict mode
"use strict";

/**
 * Custom modules
 */
(function () {
	var STATIC_PATH = Supra.Manager.Loader.getStaticPath(),
		APP_PATH = Supra.Manager.Loader.getActionBasePath("Applications");
	
	Supra.setModuleGroupPath("dashboard", STATIC_PATH + APP_PATH + "/modules");
	
	Supra.addModule("dashboard.stats", {
		path: "stats.js",
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
	Supra.addModule("dashboard.visitors", {
		path: "visitors.js",
		requires: [
			"widget",
			"charts",
			"dashboard.chart-hover-plugin"
		]
	});
	Supra.addModule("dashboard.inbox", {
		path: "inbox.js",
		requires: [
			"dashboard.stats"
		]
	});
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
	Supra.addModule("dashboard.app-favourites", {
		path: "app-favourites.js",
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
Supra(
	
	"dashboard.app-list",
	"dashboard.app-favourites",
	"dashboard.stats",
	"dashboard.visitors",
	"dashboard.chart-hover-plugin",
	"dashboard.inbox",
	"transition",
	
function (Y) {
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
			"keywords": null,
			"referring": null,
			
			"apps": null,
			"favourites": null,
			
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
			this.widgets.inbox = new Supra.Inbox({
				"srcNode": this.one("div.dashboard-inbox")
			});
			this.widgets.keywords = new Supra.Stats({
				"srcNode": this.one("div.dashboard-keywords")
			});
			this.widgets.referring = new Supra.Stats({
				"srcNode": this.one("div.dashboard-referrers")
			});
			
			if (Supra.data.get(["site", "portal"])) {
				this.widgets.visitors = new Supra.Visitors({
					"srcNode": this.one("div.dashboard-visitors")
				});
			}
			
			this.widgets.apps = new Supra.AppList({
				"srcNode": this.one("div.dashboard-apps")
			});
			
			this.widgets.favourites = new Supra.AppFavourites({
				"srcNode": this.one("div.dashboard-favourites")
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
			
			//Stats widgets
			this.widgets.inbox.render();
			this.widgets.keywords.render();
			this.widgets.referring.render();
			
			if (this.widgets.visitors) {
				this.widgets.visitors.render();
			}
			
			this.widgets.apps.render();
			this.widgets.favourites.render();
			
			this.widgets.favourites.on("appadd", this.onFavourite, this);
			this.widgets.favourites.on("appremove", this.onFavouriteRemove, this);
			this.widgets.favourites.on("appmove", this.onFavouriteSort, this);
			
			this.widgets.favourites.on("appadd", this.removeAppFromApps, this);
			this.widgets.apps.on("appadd", this.removeAppFromFavourites, this);
			
			this.renderHeader();
			
			//Scrollable
			this.widgets.scrollable.render();
			this.widgets.favourites.on("resize", this.widgets.scrollable.syncUI, this.widgets.scrollable);
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
			
			this.loadInboxData();
			this.loadApplicationData();
			this.loadVisitorsData();
			this.loadStatisticsData();
			
			this.renderSiteInfo();
		},
		
		
		/* ------------------------------------ Data ------------------------------------ */
		
		
		/**
		 * Load and set statistics data
		 * 
		 * @private
		 */
		loadStatisticsData: function () {
			if (Supra.data.get(["site", "portal"])) {
				//@TODO Replace with real data, dummy data per #5323 request
				var uri = "dev/stats";
			} else {
				var uri = "../stats/stats";
			}
			
			Supra.io(this.getDataPath(uri), function (data, status) {
				if (status && data) {
					this.widgets.keywords.set("data", data.keywords);
					this.widgets.referring.set("data", data.sources);
				}
			}, this);
		},
		
		/**
		 * Load and set visitors data
		 * 
		 * @private
		 */
		loadVisitorsData: function () {
			if (Supra.data.get(["site", "portal"])) {
				//@TODO Replace with real data, dummy data per #5323 request
				var uri = "dev/visitors";
				
				Supra.io(this.getDataPath(uri), function (data, status) {
					if (status && data) {
						this.widgets.visitors.set("data", data);
					}
				}, this);
			}
		},
		
		/**
		 * Load and set inbox data
		 * 
		 * @private
		 */
		loadInboxData: function () {
			if (Supra.data.get(["site", "portal"])) {
				//@TODO Replace with real data, dummy data per #5323 request
				var uri = "dev/inbox";
			} else {
				var uri = "../inbox/inbox";
			}
			
			Supra.io(this.getDataPath(uri), function (data, status) {
				if (status && data) {
					this.widgets.inbox.set("data", data);
				}
			}, this);
		},
		
		/**
		 * Load and set application list and favourites data 
		 */
		loadApplicationData: function () {
			Supra.io(this.getDataPath("applications"), function (data, status) {
				if (status && data) {
					var applications = [],
						favourites = [],
						profile = null; // Profile application info
					
					Y.Array.each(data.applications, function (app) {
						//Only if not in favourites
						if (app.id.indexOf("\\Profile") !== -1 || app.id.indexOf("/Profile") !== -1) {
							this.updateProfileLink(app);
						} else {
							var index = Y.Array.indexOf(data.favourites, app.id);
							if (index === -1) {
								applications.push(app);
							} else {
								favourites[index] = app;
							}
						}
					}, this);
					
					this.widgets.apps.set("data", applications);
					this.widgets.favourites.set("data", favourites);
					
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
		
		
		/* ------------------------------------ Favourites ------------------------------------ */
		
		
		/**
		 * When application is added to favourites inform server
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onFavourite: function (e) {
			var app = e.application,
				ref = e.reference;
			
			Supra.io(this.getDataPath("favourite"), {
				"data": {
					"id": app.id,
					"before": ref ? ref.id : "",
					"favourite": 1
				},
				"method": "post",
				"context": this,
				"on": {
					"failure": function () {
						//Revert changes
						this.widgets.favourites.removeApplication(app.id, true);
						this.widgets.apps.addApplication(app, true);
					}
				}
			});
		},
		
		/**
		 * When application is removed from favourites inform server
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onFavouriteRemove: function (e) {
			var app = e.application;
			
			Supra.io(this.getDataPath("favourite"), {
				"data": {
					"id": app.id,
					"favourite": 0
				},
				"method": "post",
				"context": this,
				"on": {
					"failure": function () {
						//Revert changes
						this.widgets.apps.removeApplication(app.id, true);
						this.widgets.favourites.addApplication(app, true);
					}
				}
			});
		},
		
		/**
		 * When favourite application list is sorted inform server
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onFavouriteSort: function (e) {
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
		
		/**
		 * When application is addded to the app list remove it from favourites
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		removeAppFromFavourites: function (e) {
			this.widgets.favourites.removeApplication(e.application.id);
		},
		
		/**
		 * When application is addded to the favourites remove it from app list
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		removeAppFromApps: function (e) {
			this.widgets.apps.removeApplication(e.application.id);
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
			
			if (Y.UA.ie && Y.UA.ie < 10) {
				transition.msTransform = transition.transform;
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
						this.load();
						this.widgets.scrollable.syncUI();
					}, this));
				} else {
					this.load();
					this.widgets.scrollable.syncUI();
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