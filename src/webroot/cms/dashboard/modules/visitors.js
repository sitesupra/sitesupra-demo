YUI.add("dashboard.visitors", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var TEMPLATE_HEADING = '\
			<div class="su-block-heading ui-center-darker-background">\
				<h2>\
					<span>{{ title|escape }}</span>\
					{% if linkTitle and linkTarget %}<a target="_blank" href="{{ linkTarget|escape }}">{{ linkTitle|escape }}</a>{% endif %}\
				</h2>\
			</div>';
	
	var TEMPLATE_BODY = '\
			<div class="su-block-content ui-center-dark-background">\
				<div class="chart loading"></div>\
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
	function Visitors (config) {
		Visitors.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Visitors.NAME = "visitors";
	Visitors.CSS_PREFIX = 'su-' + Visitors.NAME;
	Visitors.CLASS_NAME = Y.ClassNameManager.getClassName(Visitors.NAME);
 
	Visitors.ATTRS = {
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
	
	Visitors.HTML_PARSER = {
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
 
	Y.extend(Visitors, Y.Widget, {
		
		//Templates
		TEMPLATE_HEADING: TEMPLATE_HEADING,
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
			Visitors.superclass.renderUI.apply(this, arguments);
			
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
			
			var container = this.nodes.body.one(".chart");
			
			if (data) {
				container.removeClass("loading");
				
				//@TODO Replace image with real data
				container.set("innerHTML", '<img class="details" src="/cms/dashboard/images/apps/visitors-stats.png" alt="" /><div class="inner"></div>');
				container = container.one(".inner");
				
				var tooltip = {
					"styles": {
						"backgroundColor": "transparent",
						"color": "#ffffff",
						"borderColor": "transparent",
						"textAlign": "center",
						"fontSize": "11px",
						"fontWeight": "bold"
					},
					"markerLabelFunction": function(categoryItem, valueItem, itemIndex, series, seriesIndex) {
						return categoryItem.axis.get("labelFunction").apply(this, [valueItem.value, valueItem.axis.get("labelFormat")]);
					}
				};
				
				var chart = this.chart = new Y.Chart({
					"dataProvider": data.visitors,
					"type": "combo",
					"categoryKey": "date",
					"horizontalGridlines": false,
					"verticalGridlines": false,
					"tooltip": tooltip,
					"seriesCollection": [
						{
							"type": "column",
							"xAxis": "dateRange",
	                		"yAxis": "visitors",
	                		"xKey": "date",
	                		"yKey": "visitors",
	                		"styles": {
	                			"fill": {
									"color": "rgba(169, 172, 193, 0.25)"
								},
								"width": 49,
								"over": {
									"fill": {
										"color": "#00a0f1"
									}
								}
	                		}
	                	},
	                	{
							"type": "combo",
							"xAxis": "dateRange",
	                		"yAxis": "visitors",
	                		"xKey": "date",
	                		"yKey": "visitors"
	                	}
					],
					"axes": {
						"visitors": {
							"keys": ["visitors"],
							"position": "none",
							"type": "numeric"
						},
						"dateRange": {
							"keys": ["date"],
							"position": "bottom",
							"type": "category",
							"styles": {
								"majorTicks": {
									"display": "none"
								},
								"line": {
									"color": "#272933"
								},
								"label": {
									"color": "#a9acc1",
									"fontSize": 100,
									"over": {
										"color": "#ffffff"
									}
								}
							}
						}
					},
					"styles": {
						"graph": {
							"background": {
								"fill": {
									"color": "transparent"
								},
								"border": {
									"color": "transparent"
								}
							}
						},
						
						"series": {
							"visitors": {
								"line": {
									"weight": 4,
									"color": "#3c404b" //"rgba(255, 255, 255, 0.25)"
								},
								"marker": {
									"border": {
										"color": "#3c404b", //"rgba(255, 255, 255, 0.25)"
										"weight": 2
									},
									"fill": {
										"color": "#353844" //"rgba(255, 255, 255, 0.1)"
									},
									"width": 13,
									"height": 13,
									"over": {
										"border": {
											"color": "#ffffff"
										},
										"fill": {
											"color": "#00a0f1"
										}
									}
								},
								"axes": {
									"majorTicks": {
										"display": "none"
									}
								}
							}
						}
					}
				});
				
				chart.render(container);
				
				chart.plug(Supra.ChartHoverPlugin);
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
 
	Supra.Visitors = Visitors;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:["widget", "charts", "dashboard.chart-hover-plugin"]});