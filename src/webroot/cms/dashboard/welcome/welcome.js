/**
 * New site welcome message
 */
Supra("transition", function (Y) {
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
		NAME: "Welcome",
		
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
		 * Supporting widgets
		 * @type {Object}
		 * @private
		 */
		widgets: {},
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			//Set domain
			this.one("h2 .site-title").set("text", document.location.host);
			
			//Create button
			var button = this.widgets.button = new Supra.Button({
				"srcNode": this.one("button"),
				"style": "mid-blue"
			});
			
			button.render();
			button.on("click", this.hide, this);
		},
		
		hide: function () {
			//Disable button to prevent multiple hide calls
			this.widgets.button.set("disabled", true);
			
			//Fade out
			this.one().transition({
				"easing": "ease-out",
				"duration": 0.4,
				"opacity": 0
			}, Y.bind(function () {
				//Welcome screen will not be shown anymore, we can remove it
				this.destroy();
			}, this));
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			
		}
	});
	
});