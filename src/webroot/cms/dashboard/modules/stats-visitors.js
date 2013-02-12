//Invoke strict mode
YUI.add('dashboard.stats-visitors', function (Y) {
	'use strict';
	
	var TEMPLATE_HEADING = '\
			<div class="su-block-heading ui-center-darker-background">\
				<h2>\
					<span>{{ title|escape }}</span>\
					<small>{{ website|escape }}</small>\
					<button suStyle="small" class="button-settings"></button>\
					<button suStyle="small" class="button-done"></button>\
				</h2>\
			</div>';
	
	var TEMPLATE_BODY = '\
			<div class="su-block-content ui-center-dark-background">\
				<div class="chart loading">\
					<div class="monthly clearfix"></div>\
					<div class="daily"></div>\
				</div>\
			</div>';
	
	var TEMPLATE_ITEM = '\
			<div class="item">\
				<p class="count">{{ amount }}</p>\
				<p>{{ title|escape }}</p>\
			</div>';
	
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
		//Website title
		'website': {
			'value': '',
			'setter': '_setWebsiteTitle'
		},
		//Website title
		'account_name': {
			'value': '',
			'setter': '_setAccountName'
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
		}
	};
 
	Y.extend(Visitors, Y.Widget, {
		
		// Templates
		TEMPLATE_HEADING: TEMPLATE_HEADING,
		TEMPLATE_BODY: TEMPLATE_BODY,
		TEMPLATE_ITEM: TEMPLATE_ITEM,
		
		// Nodes
		_nodes: {
			'heading': null,
			'body': null
		},
		
		// Widgets
		_widgets: {
			'slideshow': null,
			'settingsButton': null,
			'doneButton': null,
			'profilesButton': null,
			'unauthorizeButton': null
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
			
			var template = null,
				heading = null,
				body = null,
				node = null,
				slideshow = null,
				settings_button = null,
				done_button,
				box = this.get('boundingBox');
			
			this._nodes = {
				'heading': null,
				'body': null
			};
			this._widgets = {
				'slideshow': null,
				'settingsButton': null,
				'doneButton': null,
				'profilesButton': null,
				'unauthorizeButton': null
			};
			
			box.addClass("su-block");
			
			if (!this.get('visible')) {
				box.addClass("hidden");
			}
			
			
			template = Supra.Template.compile(this.TEMPLATE_HEADING);
			heading = this._nodes.heading = Y.Node.create(template({
				'title': this.get('title'),
				'website': this.get('website')
			}));
			
			template = Supra.Template.compile(this.TEMPLATE_BODY);
			body = this._nodes.body = Y.Node.create(template({}));
			
			this.get('contentBox').append(heading).append(body);
			
			// Settings button
			settings_button = this._widgets.settingsButton = new Supra.Button({
				'srcNode': heading.one('button.button-settings')
			});
			settings_button.addClass(settings_button.getClassName('stats-settings'));
			settings_button.render();
			settings_button.on('click', this.showSettings, this);
			
			// Done button
			done_button = this._widgets.doneButton = new Supra.Button({
				'srcNode': heading.one('button.button-done'),
				'label': Supra.Intl.get(['buttons', 'done'])
			});
			done_button.addClass(done_button.getClassName('stats-settings'));
			done_button.render();
			done_button.hide();
			done_button.on('click', this.closeSettings, this);
			
			// Slideshow
			slideshow = this._widgets.slideshow = new Supra.Slideshow();
			slideshow.render(body);
			slideshow.addSlide({'id': 'analytics_main', 'scrollable': false});
			
			slideshow.on('slideChange', function () {
				// Disable button loading state
				this._widgets.profilesButton.set('loading', false);
				this._widgets.unauthorizeButton.set('loading', false);
				this._widgets.profilesButton.set('disabled', false);
				this._widgets.unauthorizeButton.set('disabled', false);
			}, this);
			
			node = slideshow.getSlide('analytics_main').one('.su-slide-content');
			node.append(body.one('.chart'));
			
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
			if (!this._nodes.body) return data;
			
			if (data) {
				this._nodes.body.one('.chart').removeClass('loading');
				
				this.renderStats(data.monthly);
				this.renderChart(data.daily);
			}
			
			return data;
		},
		
		/**
		 * Render monthly statistics
		 */
		renderStats: function (data) {
			var container = this._nodes.body.one('.chart .monthly'),
				template  = Supra.Template.compile(this.TEMPLATE_ITEM),
				html      = '';
			
			html += template({
				'title': Supra.Intl.get(['dashboard', 'visitors', 'visitors']),
				'amount': Y.DataType.Number.format(data.visitors, {'thousandsSeparator': ' '})
			});
			
			html += template({
				'title': Supra.Intl.get(['dashboard', 'visitors', 'visits']),
				'amount': Y.DataType.Number.format(data.visits, {'thousandsSeparator': ' '})
			});
			
			html += template({
				'title': Supra.Intl.get(['dashboard', 'visitors', 'pageviews']),
				'amount': Y.DataType.Number.format(data.pageviews, {'thousandsSeparator': ' '})
			});
			
			container.empty().append(html);
		},
		
		/**
		 * Render chart with daily data
		 */
		renderChart: function (data) {
			var container = this._nodes.body.one('.chart .daily');
			
			if (this.chart) {
				this.chart.set('dataProvider', data);
				return;
			}
			
			// Render styled chart
			var tooltip = {
				'styles': {
					'backgroundColor': '#202230',
					'color': '#ffffff',
					'borderColor': '#4e4a58',
					'textAlign': 'left',
					'fontSize': '12px',
					'fontWeight': 'bold',
					'padding': '7px 10px',
					'borderRadius': '3px',
					'line-height': '18px'
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
						label += '<span class="tooltip-label" style="color: ' + COLORS[name] + '">' + Supra.Intl.get(['dashboard', 'visitors', name]) + ':</span> <span class="tooltip-value">' + valueItems[i].value + '</span><br />';
					}
					return label;
				}
			};
			
			var chart = this.chart = new Y.Chart({
				'dataProvider': data,
				'type': 'combo',
				'horizontalGridlines': false, //{ 'styles': { 'line': { 'weight': 1, 'color': '#4e4a58' } } },
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
                		'yKey': 'pageviews',
                		'styles': {
                			'line-weight': 1,
                			'color': '#4e4a58'
                		}
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
							'line': {
								'color': 'transparent'
							},
							'label': {
								'color': '#a9acc1',
								'fontSize': 100,
								'textDecoration': 'none',
								'textShadow': '0 1px 0 rgba(0, 0, 0, 0.75)'
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
		 * ---------------------------- SETTINGS -------------------------
		 */
		
		
		/**
		 * Show Google Analytics settings
		 */
		showSettings: function () {
			var slideshow = this._widgets.slideshow,
				slide = null,
				button = null,
				node = null;
			
			if (!slideshow.getSlide('analytics_settings')) {
				slide = slideshow.addSlide({'id': 'analytics_settings', 'scrollable': false});
				slide = slide.one('.su-slide-content');
				
				node = Y.Node.create('<div class="stats-settings"></div>');
				slide.append(node);
				
				this._widgets.profilesButton = button = new Supra.Button({
					'label': Supra.Intl.get(['dashboard', 'settings', 'change_website']),
					'style': 'small'
				});
				button.render(node);
				button.addClass(button.getClassName('fill'));
				button.on('click', this._fireProfilesEvent, this);
				
				this._widgets.unauthorizeButton = button = new Supra.Button({
					'label': this._getUnauthorizeButtonLabel(),
					'style': 'small-red'
				});
				button.render(node);
				button.addClass(button.getClassName('fill'));
				button.on('click', this._fireUnauthorizeEvent, this);
			}
			
			slideshow.set('slide', 'analytics_settings');
			
			this._widgets.settingsButton.hide();
			this._widgets.doneButton.show();
		},
		
		/**
		 * Hide Google Analytics settings
		 */
		closeSettings: function () {
			var slideshow = this._widgets.slideshow;
			slideshow.set('slide', 'analytics_main');
			
			this._widgets.settingsButton.show();
			this._widgets.doneButton.hide();
		},
		
		/**
		 * Fire profile button click event
		 * 
		 * @private
		 */
		_fireProfilesEvent: function () {
			this._widgets.profilesButton.set('loading', true);
			this._widgets.unauthorizeButton.set('disabled', true);
			this.fire('profilesListClick');
		},
		
		/**
		 * Fire unauthorize button click event
		 * 
		 * @private
		 */
		_fireUnauthorizeEvent: function () {
			this._widgets.unauthorizeButton.set('loading', true);
			this._widgets.profilesButton.set('disabled', true);
			this.fire('unauthorizeClick');
		},
		
		/**
		 * Returns button label
		 * 
		 * @param {String} account_name Optional account name, if not set then taken from attribute
		 * @returns {String} Button label
		 * @private
		 */
		_getUnauthorizeButtonLabel: function (account_name) {
			var account_name = account_name || this.get('account_name') || '';
			return Supra.Intl.get(['dashboard', 'settings', 'remove_analytics']).replace('%s', account_name);
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
			if (this._nodes.heading) this._nodes.heading.one('span').set('text', title);
			return title;
		},
		
		/**
		 * Website title attribute setter
		 * 
		 * @param {String} title New title
		 * @return New title
		 * @type {String}
		 * @private
		 */
		_setWebsiteTitle: function (title) {
			if (this._nodes.heading) this._nodes.heading.one('small').set('text', title);
			return title;
		},
		
		/**
		 * Account nameattribute setter
		 * 
		 * @param {String} account_name Account name
		 * @return Account name
		 * @type {String}
		 * @private
		 */
		_setAccountName: function (account_name) {
			var button = this._widgets.unauthorizeButton;
			if (button) {
				button.set('label', this._getUnauthorizeButtonLabel(account_name));
			}
			
			return account_name;
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
				hidden = node.hasClass('hidden');
			
			if (visible && hidden) {
				node.setStyles({'opacity': 0})
					.removeClass('hidden')
					.transition({'opacity': 1, 'duration': 0.35});
			} else if (!visible && !hidden) {
				node.transition({'opacity': 0, 'duration': 0.35}, Y.bind(function () {
					node.addClass('hidden');
					
					this._widgets.slideshow.set('noAnimations', true);
					this._widgets.slideshow.set('slide', 'analytics_main');
					this._widgets.slideshow.set('noAnimations', false);
					
					this._widgets.settingsButton.show();
					this._widgets.doneButton.hide();
				}, this));
			}
			
			return !!visible;
		}
	});
 
	Supra.DashboardStatsVisitors = Visitors;
 
	//Since this widget has Supra namespace, it doesn"t need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
 
}, YUI.version, {requires:['widget', 'charts', 'dashboard.chart-hover-plugin']});