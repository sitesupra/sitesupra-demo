//Invoke strict mode
"use strict";

YUI.add("website.stats", function (Y) {
 
	var TEMPLATE_HEADING = '\
			<div class="su-block-heading ui-center-darker-background">\
				<h2>\
					<span>{{ title|escape }}</span>\
					{% if linkTitle and linkTarget %}<a target="_blank" href="{{ linkTarget|escape }}">{{ linkTitle|escape }}</a>{% endif %}\
				</h2>\
			</div>';
	
	var TEMPLATE_BODY = '\
			<div class="su-block-content ui-center-dark-background">\
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
		//Heading link title
		"linkTitle": {
			"value": ""
		},
		//Heading link target
		"linkTarget": {
			"value": ""
		},
		//Data
		"data": {
			"value": null,
			"setter": "renderData"
		}
	};
	
	Stats.HTML_PARSER = {
		"title": function (srcNode) {
			var attr = srcNode.getAttribute("suTitle");
			if (attr) return attr;
		},
		"linkTitle": function (srcNode) {
			var attr = srcNode.getAttribute("suLinkTitle");
			if (attr) return attr;
		},
		"linkTarget": function (srcNode) {
			var attr = srcNode.getAttribute("suLinkTarget");
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
			
			this.nodes = {
				"heading": null,
				"body": null
			};
			
			this.get("boundingBox").addClass("su-block");
			
			var template = null,
				heading = null,
				body = null;
			
			template = Supra.Template.compile(this.TEMPLATE_HEADING);
			heading = this.nodes.heading = Y.Node.create(template({
				"title": this.get("title"),
				"linkTitle": this.get("linkTitle"),
				"linkTarget": this.get("linkTarget")
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
			
			if (data) {
				var i = 0,
					ii = data.length,
					template = Supra.Template.compile(this.TEMPLATE_ITEM),
					html = '';
				
				for(; i<ii; i++) {
					html += template(data[i]);
				}
				
				container.set("innerHTML", html);
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
		}
	});
 
	Supra.Stats = Stats;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:["widget"]});