YUI.add('dashboard.visitors', function (Y) {
	//Invoke strict mode
	'use strict';
	
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
	
	var COLORS = {
			'pageviews': '#4ecb69',
			'pageviews_line': '#59ff7c',
			'visits': '#e4700d',
			'visits_line': '#fe7500',
			'visitors': '#73b6c5',
			'visitors_line': '#55c5ff'
		};
	
	var LABEL_FORMAT = '%b %e',
		TOOLTIP_FORMAT = '%b %e, %Y';
	
	/**
	 * Statistics module
	 */
	function Visitors (config) {
		Visitors.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Visitors.NAME = 'visitors';
	Visitors.CSS_PREFIX = 'su-' + Visitors.NAME;
	Visitors.CLASS_NAME = Y.ClassNameManager.getClassName(Visitors.NAME);
 
	Visitors.ATTRS = {
		//Title
		'title': {
			'value': '',
			'setter': '_setTitle'
		},
		//Heading link title
		'linkTitle': {
			'value': ''
		},
		//Heading link target
		'linkTarget': {
			'value': ''
		},
		//Data
		'data': {
			'value': null,
			'setter': 'renderData'
		}
	};
	
	Visitors.HTML_PARSER = {
		'title': function (srcNode) {
			var attr = srcNode.getAttribute('suTitle');
			if (attr) return attr;
		},
		'linkTitle': function (srcNode) {
			var attr = srcNode.getAttribute('suLinkTitle');
			if (attr) return attr;
		},
		'linkTarget': function (srcNode) {
			var attr = srcNode.getAttribute('suLinkTarget');
			if (attr) return attr;
		}
	};
 
	Y.extend(Visitors, Y.Widget, {
		
		//Templates
		TEMPLATE_HEADING: TEMPLATE_HEADING,
		TEMPLATE_BODY: TEMPLATE_BODY,
		
		//Nodes
		nodes: {
			'heading': null,
			'body': null
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
				'heading': null,
				'body': null
			};
			
			this.get('boundingBox').addClass('su-block');
			
			var template = null,
				heading = null,
				body = null;
			
			template = Supra.Template.compile(this.TEMPLATE_HEADING);
			heading = this.nodes.heading = Y.Node.create(template({
				'title': this.get('title'),
				'linkTitle': this.get('linkTitle'),
				'linkTarget': this.get('linkTarget')
			}));
			
			template = Supra.Template.compile(this.TEMPLATE_BODY);
			body = this.nodes.body = Y.Node.create(template({}));
			
			this.get('boundingBox').append(heading).append(body);
			
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
			
			var container = this.nodes.body.one('.chart'),
				formatted = [];
			
			if (data) {
				container.removeClass('loading');
				
				//Count data for general stats
				var i = 0,
					ii = data.visitors.length,
					date = null;
				
				for (; i<ii; i++) {
					/*
						'date': data.visitors[i].date,
						'pageviews': data.visitors[i].pageviews,
						'visitors': data.visitors[i].visitors,
						'visits': data.visitors[i].visits
					*/
				}
				
				//@TODO Replace image with real data
				container.set('innerHTML', '<img class="details" src="/cms/dashboard/images/apps/visitors-stats.png" alt="" /><div class="inner"></div>');
				
				this.renderChart(data.visitors);
			}
			
			return data;
		},
		
		/**
		 * Render chart
		 */
		renderChart: function (data) {
			var container = this.nodes.body.one('.chart .inner');
			
			var tooltip = {
				'styles': {
					'backgroundColor': '#202230',
					'color': '#ffffff',
					'borderColor': '#4e4a58',
					'textAlign': 'left',
					'fontSize': '11px',
					'fontWeight': 'bold',
					'padding': '5px',
					'borderRadius': '3px',
					'line-height': '15px'
				},
				'setTextFunction': function (textField, val) {
					textField.setContent(String(val));
				},
				'planarLabelFunction': function (categoryAxis, valueItems, index, seriesArray, seriesIndex) {
					// Format date
					var date  = categoryAxis.getKeyValueAt(this.get('categoryKey'), index),
						label = Y.DataType.Date.reformat(date, 'in_date', TOOLTIP_FORMAT) + '<br />',
						name  = '';
					
					// Format values
					for (var i=0, ii=valueItems.length; i<ii; i++) {
						name = valueItems[i].displayName;
						label += '<span style="color: ' + COLORS[name] + '">' + Supra.Intl.get(['dashboard', 'visitors', name]) + ':</span> ' + valueItems[i].value + '<br />';
					}
					return label;
				}
			};
			
			var chart = this.chart = new Y.Chart({
				'dataProvider': data,
				'type': 'combo',
				'horizontalGridlines': false,
				'verticalGridlines': false,
				
				'tooltip': tooltip,
				'interactionType': 'planar',
				
				'showLines': true,
				'showMarkers': false,
				'showAreaFill': true,
				
				'seriesCollection': [
                	{
						'type': 'combo',
						'xAxis': 'xaxis',
                		'yAxis': 'yaxis',
                		'xKey': 'date',
                		'yKey': 'pageviews'
                	},
                	{
						'type': 'combo',
						'xAxis': 'xaxis',
                		'yAxis': 'yaxis',
                		'xKey': 'date',
                		'yKey': 'visits'
                	},
                	{
						'type': 'combo',
						'xAxis': 'xaxis',
                		'yAxis': 'yaxis',
                		'xKey': 'date',
                		'yKey': 'visitors'
                	}
				],
				
				'axes': {
					'yaxis': {
						'keys': ['pageviews', 'visits', 'visitors'],
						'position': 'none',
						'type': 'numeric'
					},
					'xaxis': {
						'keys': ['date'],
						'position': 'bottom',
						'labelFunction': function (val) {
							return Y.DataType.Date.reformat(val, 'in_date', LABEL_FORMAT);
						},
						'type': 'category',
						'styles': {
							'majorTicks': {
								'display': 'none',
								'length': 0,
							},
							'minorTicks': {
								'length': 0,
							},
							'line': {
								'color': 'transparent'
							},
							'label': {
								'color': '#a9acc1',
								'fontSize': 100,
								'textDecoration': 'none'
							}
						}
					}
				},
				
				'styles': {
					'graph': {
						'background': {
							'fill': {
								'color': 'transparent'
							},
							'border': {
								'color': 'transparent'
							}
						}
					},
					'series': {
						'pageviews': {
							'area': {
								'color': COLORS.pageviews
							},
							'line': {
								'weight': 2,
								'color': COLORS.pageviews_line
							},
							'marker': {
								'width': 18,
								'height': 18
							}
						},
						'visits': {
							'area': {
								'color': COLORS.visits
							},
							'line': {
								'weight': 2,
								'color': COLORS.visits_line
							},
							'marker': {
								'width': 18,
								'height': 18
							}
						},
						'visitors': {
							'area': {
								'color': COLORS.visitors
							},
							'line': {
								'weight': 2,
								'color': COLORS.visitors_line
							},
							'axes': {
								'majorTicks': {
									'display': 'none'
								}
							},
							'marker': {
								'width': 18,
								'height': 18
							}
						}
					}
				}
			});
			
			chart._showTooltip = this._showChartTooltip;
			
			chart.render(container);
		},
		
		/**
		 * Position chart tooltip so that it doesn't move outside the window
		 * 
		 * @param {String} msg Tooltip message
		 * @param {Number} x Tooltip x coordinate
		 * @param {Number} y Tooltip y coordinate
		 * @private
		 */
		_showChartTooltip: function (msg, x, y) {
			var tt = this.get("tooltip"),
		        node = tt.node,
		        cb = this.get("boundingBox"),
		        cbW = parseFloat(cb.getComputedStyle("width")),
		        offsetX = x,
		        tooltipWidth = parseFloat(node.getComputedStyle('width'));
		    
		    if (x + tooltipWidth > cbW && (x - tooltipWidth) > 0) {     
		        x = x - 30 - tooltipWidth; 
		    }
		    
		    if (msg) 
		    {
		        tt.visible = true;
	            tt.setTextFunction(node, msg);
	            node.setStyle("top", y + "px");
	            node.setStyle("left", x + "px");
		        node.setStyle("visibility", "visible");
		    }
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
			if (this.nodes.heading) this.nodes.heading.one('span').set('text', title);
			return title;
		}
	});
 
	Supra.Visitors = Visitors;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:['widget', 'charts', 'dashboard.chart-hover-plugin']});