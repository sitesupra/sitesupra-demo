//Invoke strict mode
"use strict";

YUI.add("dashboard.inbox", function (Y) {
 
	var TEMPLATE_HEADING = '\
			<div class="su-block-heading ui-center-darker-background">\
				<h2>\
					<span>{{ title|escape }}</span>\
				</h2>\
			</div>';
	
	var TEMPLATE_BODY = '\
			<div class="su-block-content ui-center-dark-background">\
				<ul class="data-list loading">\
					<li class="loading-icon"></li>\
				</ul>\
			</div>';
	
	var TEMPLATE_ITEM = '\
			<li class="item {% if new %}new{% endif %}">\
				<!-- {% if new and buy %}<button>{{ "dashboard.inbox.buy_now"|intl }}</button>{% endif %} -->\
				<p>{{ title|escape }}</p>\
			</li>';
	
	/**
	 * Statistics module
	 */
	function Inbox (config) {
		Inbox.superclass.constructor.apply(this, arguments);
		
		this.init.apply(this, arguments);
	}
	
	Inbox.NAME = "inbox";
	Inbox.CSS_PREFIX = 'su-' + Inbox.NAME;
	Inbox.CLASS_NAME = Y.ClassNameManager.getClassName(Inbox.NAME);
 
	Inbox.ATTRS = {};
	
	Inbox.HTML_PARSER = {};
 
	Y.extend(Inbox, Supra.Stats, {
		
		//Templates
		TEMPLATE_HEADING: TEMPLATE_HEADING,
		TEMPLATE_ITEM: TEMPLATE_ITEM,
		TEMPLATE_BODY: TEMPLATE_BODY,
		
		
		/**
		 * Button list 
		 * @type {Array}
		 * @private
		 */
		buttons: null,
		
		
		destructor: function () {
			var buttons = this.buttons;
			
			if (buttons) {
				for(var i=0,ii=buttons.length; i<ii; i++) {
					buttons[i].destroy();
				}
			}
			
			delete(this.buttons);
		},
		
		/**
		 * ---------------------------- LIST -------------------------
		 */
		
		
		/**
		 * Render data
		 */
		renderData: function (data) {
			data = Inbox.superclass.renderData.apply(this, arguments);
			
			if (!this.nodes.body) return data;
			
			//Title
			var has_new_messages = false;
			if (data) {
				for (var i=0, ii=data.length; i<ii; i++) {
					if (data[i]["new"]) {
						has_new_messages = true;
						break;
					}
				}
			}
			
			this.set("title", has_new_messages ? Supra.Intl.get(["dashboard", "inbox", "new_mail"]) : Supra.Intl.get(["dashboard", "inbox", "title"]));
			
			//Render buttons
			var container = this.nodes.body.one("ul"),
				buttons = container.all("button"),
				button = null,
				list = [];
			
			for (var i=0, ii=buttons.size(); i<ii; i++) {
				button = new Supra.Button({
					"srcNode": buttons.item(i),
					"style": "small-blue"
				});
				button.render();
				list.push(button);
			}
			
			this.buttons = list;
			
			return data;
		}
	});
 
	Supra.Inbox = Inbox;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:["dashboard.stats"]});