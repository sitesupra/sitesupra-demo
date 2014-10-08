YUI.add("dashboard.stats-list", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var TEMPLATE_HEADING = '\
			<div class="su-block-heading">\
				<h2>\
					<span>{{ title|escape }}</span>\
				</h2>\
			</div>';
	
	var TEMPLATE_BODY = '\
			<div class="su-block-content">\
				<ul class="data-list loading">\
					<li class="loading-icon"></li>\
				</ul>\
			</div>';
	
	var TEMPLATE_ITEM = '\
			<li class="item">\
				<p>{{ title|escape }}</p>\
				<p class="right">\
					<b>{{ amount }}</b>\
					{% if change > 0 %}<span class="up">+{{ change }}</span>{% else %}<span class="down">{{ change }}</span>{% endif %}\
				</p>\
			</li>';
	
	/**
	 * Statistics module
	 */
	function Stats (config) {
		Stats.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Stats.NAME = "stats";
	Stats.CSS_PREFIX = 'su-' + Stats.NAME;
	Stats.CLASS_NAME = Y.ClassNameManager.getClassName(Stats.NAME);
 
	Stats.ATTRS = {
		//Title
		"title": {
			"value": "",
			"setter": "_setTitle"
		},
		//Data
		"data": {
			"value": null,
			"setter": "renderData"
		},
		//Empty text
		"labelEmpty": {
			"value": null
		}
	};
	
	Stats.HTML_PARSER = {
		"title": function (srcNode) {
			var attr = srcNode.getAttribute("data-title");
			if (attr) return attr;
		}
	};
 
	Y.extend(Stats, Y.Widget, {
		
		//Templates
		TEMPLATE_HEADING: TEMPLATE_HEADING,
		TEMPLATE_ITEM: TEMPLATE_ITEM,
		TEMPLATE_BODY: TEMPLATE_BODY,
		
		//Nodes
		nodes: {
			"heading": null,
			"body": null
		},
		
		//Data
		data: null,
		
 
		/**
		 * Create/add nodes, render widgets
		 *
		 * @private
		 */
		renderUI: function () {
			Stats.superclass.renderUI.apply(this, arguments);
			
			var template = null,
				heading = null,
				body = null,
				box = this.get('boundingBox');
				
			this.nodes = {
				"heading": null,
				"body": null
			};
			
			box.addClass("su-block");
			
			if (!this.get('visible')) {
				box.addClass("hidden");
			}
			
			template = Supra.Template.compile(this.TEMPLATE_HEADING);
			heading = this.nodes.heading = Y.Node.create(template({
				"title": this.get("title")
			}));
			
			template = Supra.Template.compile(this.TEMPLATE_BODY);
			body = this.nodes.body = Y.Node.create(template({}));
			
			this.get("boundingBox").append(heading).append(body);
			
			if (this.get('data')) {
				this.renderData(this.get('data'));
			}
		},
		
		
		/**
		 * ---------------------------- LIST -------------------------
		 */
		
		
		/**
		 * Render data
		 */
		renderData: function (data) {
			if (!this.nodes.body) return data;
			
			var container = this.nodes.body.one("ul");
			
			if (data && data.length) {
				var i = 0,
					ii = data.length,
					template = Supra.Template.compile(this.TEMPLATE_ITEM),
					html = '';
				
				for(; i<ii; i++) {
					html += template(data[i]);
				}
				
				container.set("innerHTML", html);
				container.removeClass("loading");
			} else {
				var label = this.get("labelEmpty") || Supra.Intl.get(["dashboard", "no_data"]);
				
				container.set("innerHTML", '<li class="empty">' + label + '</li>');
				container.removeClass("loading");
			}
			
			return data;
		},
 
 
		/**
		 * ---------------------------- ATTRIBUTES -------------------------
		 */
 
 
		/**
		 * Title attribute setter
		 * 
		 * @param {String} value New title
		 * @return New title
		 * @type {String}
		 * @private
		 */
		_setTitle: function (title) {
			if (this.nodes.heading) this.nodes.heading.one("span").set("text", title);
			return title;
		},
		
		/**
		 * Visibility attribute setter
		 * 
		 * @param {Boolean} visible
		 * @private
		 */
		_uiSetVisible: function (visible) {
			if (!this.get('rendered')) return !!visible;
			var node = this.get('boundingBox'),
				is_hidden = node.hasClass('hidden');
			
			if (visible && is_hidden) {
				node.setStyles({'opacity': 0})
					.removeClass('hidden')
					.transition({'opacity': 1, 'duration': 0.35});
			} else if (!visible && !is_hidden) {
				node.transition({'opacity': 0, 'duration': 0.35}, function () {
						node.addClass('hidden');
					});
			}
			
			return !!visible;
		}
	});
 
	Supra.DashboardStatsList = Stats;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:["widget"]});