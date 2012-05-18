//Invoke strict mode
"use strict";

/**
 * Custom modules
 */
Supra.addModule("website.stats", {
	path: "stats.js",
	requires: [
		"widget"
	]
});
Supra.addModule("website.inbox", {
	path: "inbox.js",
	requires: [
		"website.stats"
	]
});
Supra.addModule("website.pagination", {
	path: "pagination.js",
	requires: [
		"widget"
	]
});
Supra.addModule("website.app-list", {
	path: "app-list.js",
	requires: [
		"widget",
		"website.pagination",
		"transition",
		"dd"
	]
});
Supra.addModule("website.app-favourites", {
	path: "app-favourites.js",
	requires: [
		"widget",
		"website.pagination",
		"transition",
		"dd"
	]
});


/**
 * Main manager action, initiates all other actions
 */
Supra(
	
	"website.app-list",
	"website.app-favourites",
	"website.stats",
	"website.inbox",
	
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
		NAME: "Root",
		
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
		
		
		
		widgets: {
			"inbox": null,
			"keywords": null,
			"referring": null,
			
			"apps": null,
			"favourites": null
		},
		
		
		
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
			
			this.widgets.apps = new Supra.AppList({
				"srcNode": this.one("div.dashboard-apps")
			});
			
			this.widgets.favourites = new Supra.AppFavourites({
				"srcNode": this.one("div.dashboard-favourites")
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
			
			this.widgets.apps.render();
			this.widgets.favourites.render();
			
			this.loadInboxData();
			this.loadStatisticsData();
			this.loadApplicationData();
		},
		
		/**
		 * Load and set statistics data
		 * 
		 * @private
		 */
		loadStatisticsData: function () {
			Supra.io(this.getDataPath("dev/stats"), function (data, status) {
				if (status && data) {
					this.widgets.keywords.set("data", data.keywords);
					this.widgets.referring.set("data", data.sources);
				}
			}, this);
		},
		
		/**
		 * Load and set inbox data
		 * 
		 * @private
		 */
		loadInboxData: function () {
			Supra.io(this.getDataPath("dev/inbox"), function (data, status) {
				if (status && data) {
					this.widgets.inbox.set("data", data);
				}
			}, this);
		},
		
		/**
		 * Load and set application list and favourites data 
		 */
		loadApplicationData: function () {
			Supra.io(this.getDataPath("dev/applications"), function (data, status) {
				if (status && data) {
					var applications = [],
						favourites = [];
					
					Y.Array.each(data.applications, function (app) {
						//Only if not in favourites
						if (Y.Array.indexOf(data.favourites, app.id) === -1) {
							applications.push(app);
						} else {
							favourites.push(app);
						}
					});
					
					this.widgets.apps.set("data", applications);
					this.widgets.favourites.set("data", favourites);
				}
			}, this);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});