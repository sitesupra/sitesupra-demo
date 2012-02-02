//Invoke strict mode
"use strict";

SU('anim', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	var YDate = Y.DataType.Date;
	
	//Add as right bar child
	Manager.getAction('LayoutLeftContainer').addChildAction('PageHistory');
	
	//Create Action class
	new Action(Action.PluginLayoutSidebar, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageHistory',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Layout container action NAME
		 * @type {String}
		 * @private
		 */
		LAYOUT_CONTAINER: 'LayoutLeftContainer',
		
		
		
		
		/**
		 * Page data
		 * @type {Object}
		 * @private
		 */
		history_data: {},
		
		/**
		 * List element, Y.Node instance
		 * @type {Object}
		 * @private
		 */
		element_list: null,
		
		/**
		 * Currently previewed version
		 * @type {String}
		 * @private
		 */
		current_version: null,
		
		/**
		 * "Restore" button
		 * @type {Array}
		 * @private
		 */
		button: null,
		
		/**
		 * History is loading
		 * @type {Boolean}
		 * @private
		 */
		loading: false,
		
		
		
		
		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Control button
			this.get('controlButton').on('click', this.hide, this);
			
			//List
			this.timeline = this.one('div.timeline');
			this.timeline.delegate('click', this.toggleSection, 'p.title', this);
			this.timeline.delegate('click', this.showVersionPreview, '.group p', this);
			
			//Loading state attribute
			this.addAttrs({
				'loading': {
					'value': false,
					'setter':
						function (value) {
							this.one('.timeline').setClass('disabled', value);
							return !!value;
						}
				}
			});
		},
		
		
		/* ------------------------------------ VERSION DATA ------------------------------------ */
		
		
		/**
		 * Show version preview
		 *
		 * @param {Event} e Event
		 * @private
		 */
		showVersionPreview: function (e) {
			if (this.get('loading')) return;
			
			var target = e.target.closest('p'),
				version_id = target.getAttribute('data-id'),
				prev_target = null;
			
			if (this.current_version != version_id) {
				prev_target = this.all('p.active');
				target.addClass('loading');
				target.addClass('active');
				
				this.set('loading', true);
				
				Manager.getAction('PageContent').getIframeHandler().showVersionPreview(version_id, function (data, status) {
					target.removeClass('loading');
					this.set('loading', false);
					
					if (!status) {
						//@TODO
						//target.removeClass('active');
					} else {
						prev_target.removeClass('active');
						this.current_version = version_id;
					}
				}, this);
				
				/*
				//Create button
				if (!this.button) {
					this.button = new Supra.Button({'label': Supra.Intl.get(['history', 'restore']), 'style': 'small'});
					this.button.render(target.one('span'));
					this.button.on('click', function () {
						this.restoreVersionConfirm(this.current_version);
					}, this);
				}
				
				//Move button to correct place
				target.one('span').append(this.button.get('boundingBox'));
				*/
			}
		},
		
		/**
		 * Restore specific version and hide PageHistory block
		 *
		 * @param {String} version_id
		 */
		restoreVersion: function (version_id) {
			//Disable elements
			this.set('loading', true);
			this.button.set('loading', true);
			Manager.PageButtons.buttons[this.NAME][0].set('disabled', true);
			
			Supra.io(this.getDataPath('restore'), {
				'method': 'post',
				'data': {
					'page_id': Manager.Page.getPageData().id,
					'version_id': version_id,
					'locale': Supra.data.get('locale')
				},
				'context': this,
				'on': {
					'success': function () {
						//Re-enable elements
						this.set('loading', false);
						this.button.set('loading', false);
						Manager.PageButtons.buttons[this.NAME][0].set('disabled', false);
						
						//Reload page
						this.reloadPage();
						this.hide();
					}
				}
			});
		},
		
		/**
		 * Restore specific version and confirm before doing so
		 *
		 * @param {String} version_id
		 */
		restoreVersionConfirm: function (version_id) {
			
			Supra.Manager.executeAction('Confirmation', {
				'message': SU.Intl.get(['history', 'restore_message']),
				'useMask': true,
				'buttons': [{
						'id': 'yes',
						'context': this,
						'click': function () {
							this.restoreVersion(version_id);
						}
					},
					{'id': 'no'}
				]
			});
			
		},
		
		/**
		 * Restore normal page view, reload page data 
		 */
		reloadPage: function () {
			var iframe = Manager.getAction('PageContent').iframe_handler;
			var data = Manager.Page.getPageData();
			
			//When iframe will be ready
			iframe.once('ready', function () {
				//Show editable areas
				iframe.contents.set('highlight', false);
			});
			
			//Reload page data
			Manager.Page.loadPage(data.id);
			
			this.current_version = null;
		},
		
		
		/* ------------------------------------ LIST ------------------------------------ */
		
		
		/**
		 * Reload data
		 */
		reloadList: function () {
			
			var data = [{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l11","date":"2012-02-02 13:30","action":"change","title":"Text block","author_fullname":"Admin"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l12","date":"2012-02-02 13:11","action":"change","title":"Text block","author_fullname":"Admin"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l13","date":"2012-02-02 10:55","action":"change","title":"Text block","author_fullname":"tim"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l14","date":"2012-02-02 06:43","action":"change","title":"Template","author_fullname":"Admin"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l15","date":"2012-02-02 06:22","action":"change","title":"Text block","author_fullname":"tim"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l16","date":"2012-02-01 11:26","action":"change","title":"Text block","author_fullname":"Admin"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l17","date":"2012-02-01 11:10","action":"publish","author_fullname":"tim"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l18","date":"2012-02-01 06:10","action":"publish","author_fullname":"Admin"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l18","date":"2012-02-01 06:01","action":"publish","author_fullname":"Admin"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l18","date":"2012-01-30 12:22","action":"publish","author_fullname":"Admin"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l18","date":"2012-01-28 18:22","action":"publish","author_fullname":"tim"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l18","date":"2012-01-28 14:55","action":"publish","author_fullname":"Admin"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l18","date":"2011-12-22 16:55","action":"publish","author_fullname":"Admin"},{"version_id":"0ab1c2d3e4f5g6h7i8j9k10l18","date":"2011-11-07 18:20","action":"publish","author_fullname":"tim"}];
			this.renderData(data, true);
			
			//@TODO
			/*
			Supra.io(this.getDataPath('load'), {
				'data': {
					'page_id': Manager.getAction('Page').getPageData().id,
					'locale': Supra.data.get('locale')
				},
				'context': this,
				'on': {
					'success': this.renderData
				}
			});
			*/
			
		},
		
		/**
		 * Draw data
		 */
		renderData: function (data, status) {
			data = this.parseData(data);
			
			this.get('contentNode').removeClass('loading');
			
			this.timeline.set('innerHTML', Supra.Template('timeline', {'data': data}));
			
			this.updateScrollbars();
		},
		
		
		/* ------------------------------------ Data parsing ------------------------------------ */
		
		
		/**
		 * Parse data and change format
		 */
		parseData: function (data) {
			var i = 0,
				ii = data.length,
				out = {},
				groups = null,
				date = null;
			
			for(; i<ii; i++) {
				date = this.parseDate(data[i].date);
				
				if (!out[date.group]) {
					out[date.group] = {
						'sort': date.group,
						'title': date.group_title,
						'latest': date.latest,
						'groups': {}
					}
				}
				if (!out[date.group].groups[date.group_datetime]) {
					out[date.group].groups[date.group_datetime] = {
						'sort': date.group_datetime,
						'datetime': date.group_datetime,
						'versions': []
					};
				}
				
				out[date.group].groups[date.group_datetime].versions.push({
					'version_id': data[i].version_id,
					'title': data[i].title,
					'action': data[i].action,
					'datetime': date.datetime,
					'author_fullname': data[i].author_fullname
				});
			}
			
			//Convert objects to arrays
			data = [];
			
			for(var i in out) {
				groups = [];
				for(var k in out[i].groups) {
					groups.push(out[i].groups[k]);
				}
				
				groups = groups.sort(function (a, b) {
					return a.sort < b.sort ? 1 : -1;
				});
				
				out[i].groups = groups;
				data.push(out[i]);
			}
			
			data.sort(function (a, b) {
				return a.sort < b.sort ? 1 : -1;
			});
			
			return data;
		},
		
		/**
		 * Parse date
		 */
		parseDate: function (date) {
			var today = new Date(),
				y_day = null,
				month = null,
				raw = YDate.reformat(date, 'in_datetime_short', 'raw'),
				month_names = Y.Intl.get('datatype-date-format').B,
				out = {
					'raw': raw,
					'latest': false,
					'group': '',
					'group_title': '',
					'group_datetime': '',
					'datetime': YDate.reformat(raw, 'raw', 'out_time_short')
				};
			
			today.setHours(0, 0, 0, 0);
			
			y_day = new Date(today.getTime() - 24*60*60*1000);
			
			month = new Date(today.getTime());
			month.setDate(1);
			
			if (raw.getTime() >= today.getTime())
			{
				out.group = YDate.reformat(raw, 'raw', '%Y-%m-%d');
				out.group_title = Supra.Intl.get(['history', 'today']);
				out.group_datetime = YDate.reformat(raw, 'raw', '%H:00');
				out.latest = true;
			}
			else if (raw.getTime() >= y_day.getTime())
			{
				out.group = YDate.reformat(raw, 'raw', '%Y-%m-%d');
				out.group_title = Supra.Intl.get(['history', 'yesterday']);
				out.group_datetime = YDate.reformat(raw, 'raw', '%H:00');
				out.latest = true;
			}
			else if (raw.getTime() >= month.getTime())
			{
				out.group = YDate.reformat(raw, 'raw', '%Y-%m-99');
				out.group_title = Supra.Intl.get(['history', 'this_month']);
				out.group_datetime = raw.getDate();
			}
			else
			{
				out.group = YDate.reformat(raw, 'raw', '%Y-%m-99');
				out.group_title = month_names[raw.getMonth()];
				out.group_datetime = raw.getDate();
			}
			
			return out;
		},
		
		
		/* ------------------------------------ UI ------------------------------------ */
		
		
		/**
		 * Show/hide section
		 * 
		 * @param {Object} e Event facade or Y.Node instance
		 */
		toggleSection: function (e) {
			var item = (e.target ? e.target.closest('.item') : e),
				section = item.one('.section'),
				height = 0,
				anim = null;
			
			if (item.hasClass('expanded')) {
				//Collapse
				anim = new Y.Anim({
					'node': section,
					'from': {'height': section.get('offsetHeight'), 'opacity': 1},
					'to':   {'height': 0, 'opacity': 0},
					'duration': 0.25,
					'easing': 'easeOut'
				});
				
				anim.on('end', Y.bind(function () {
					anim.destroy();
					item.removeClass('expanded');
					section.setStyles({'height': null});
					this.updateScrollbars();
				}, this));
				
				anim.run();
			} else {
				//Collapse expanded siblings
				/*
				var siblings = item.siblings('.expanded');
				if (siblings.size()) {
					this.toggleSection(siblings.item(0));
				}
				*/
				
				//Find content height
				section.setStyles({'display': 'block', 'position': 'absolute', 'left': '-9000px'});
				height = section.get('offsetHeight');
				
				//Animate
				section.setStyles({'display': null, 'position': null, 'left': null, 'height': '0px'});
				item.addClass('expanded');
				
				anim = new Y.Anim({
					'node': section,
					'from': {'height': 0, 'opacity': 0},
					'to':   {'height': height, 'opacity': 1},
					'duration': 0.25,
					'easing': 'easeOut'
				});
				
				anim.on('end', Y.bind(function () {
					anim.destroy();
					section.setStyles({'height': null});
					this.updateScrollbars();
				}, this));
				
				anim.run();
			}
		},
		
		/**
		 * Update scrollbars
		 */
		updateScrollbars: function () {
			this.one('.su-scrollable').fire('contentResize');
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			//Restore original content
			if (this.current_version) {
				this.reloadPage();
			}
			
			//Hide sidebar
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Unset active and loading, so that next time PageHistory is shown
			//there wouldn't be any items with selected or loading styles
			this.set('loading', false);
			this.timeline.all('.loading, .active').removeClass('loading active');
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			//Unset version
			this.current_version = null;
			
			//Load data
			this.updateScrollbars();
			this.reloadList();
		}
	});
	
});