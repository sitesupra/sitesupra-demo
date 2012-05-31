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
			
			this.widgets.favourites.on("appadd", this.onFavourite, this);
			this.widgets.favourites.on("appremove", this.onFavouriteRemove, this);
			this.widgets.favourites.on("appmove", this.onFavouriteSort, this);
			
			this.widgets.favourites.on("appadd", this.removeAppFromApps, this);
			this.widgets.apps.on("appadd", this.removeAppFromFavourites, this);
		},
		
		/**
		 * Load and set statistics data
		 * 
		 * @private
		 */
		loadStatisticsData: function () {
			Supra.io(this.getDataPath("../stats/stats"), function (data, status) {
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
			Supra.io(this.getDataPath("../inbox/inbox"), function (data, status) {
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
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			this.loadInboxData();
			this.loadApplicationData();
			
			this.loadStatisticsData();
		}
	});
	
});