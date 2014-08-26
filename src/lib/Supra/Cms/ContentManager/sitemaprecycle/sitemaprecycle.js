Supra('anim', 'transition', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.Action,
		YDate = Y.DataType.Date;
	
	//Create Action class
	new Action(Action.PluginLayoutSidebar, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		'NAME': 'SiteMapRecycle',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		'HAS_STYLESHEET': true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		'HAS_TEMPLATE': true,
		
		/**
		 * Layout container action NAME
		 * @type {String}
		 * @private
		 */
		'LAYOUT_CONTAINER': 'LayoutLeftContainer',
		
		
		
		
		/**
		 * Tree node list
		 * @type {Array}
		 * @private
		 */
		treenodes: [],
		
		/**
		 * Event listeners
		 * @type {Array}
		 * @private
		 */
		listeners: [],
		
		/**
		 * Timeline node
		 * @type {Object}
		 * @private
		 */
		timeline: null,
		
		/**
		 * Execution options
		 * @type {Object}
		 * @private
		 */
		options: null,
		
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		'render': function () {
			//Close button
			this.get('controlButton').on('click', this.hide, this);
			
			//Timeline list
			this.timeline = this.one('div.timeline');
			this.timeline.delegate('click', this.toggleSection, 'p.title', this);
			
			this.on('visibleChange', function (e) {
				if (e.newVal != e.prevVal && !e.newVal) {
					this.onHide();
				}
			}, this);
		},
		
		/**
		 * Bind drag and drop
		 * 
		 * @param {Object} node List node which to bind
		 * @param {Object} data List item data
		 * @private
		 */
		'bindItem': function (node, data) {
			var fn = 'bind' + this.options.type + 'Item';
			if (this[fn]) {
				this[fn](node, data);
			}
		},
		
		/**
		 * Bind action
		 * 
		 * @private
		 */
		'bindAction': function () {
			var fn = 'bind' + this.options.type;
			if (this[fn]) {
				this[fn]();
			}
		},
		
		/**
		 * Unbind action
		 * 
		 * @private
		 */
		'unbindAction': function () {
			var listeners = this.listeners,
				i = 0,
				ii = listeners.length;
			
			for (; i<ii; i++) {
				listeners[i].detach();
			}
			
			this.listeners = [];
		},
		
		
		/* ------------------------------- SITEMAP ------------------------------- */
		
		
		/**
		 * Attach listener to SiteMap
		 * 
		 * @private
		 */
		'bindSiteMap': function () {
			var action = Manager.SiteMap,
				languages = action.languageSelector,
				tree = action.tree,
				listeners = this.listeners;
			
			//On locale change reload data
			listeners.push(
				languages.on('valueChange', function (evt) {
					if (this.get('visible') && evt.newVal != evt.prevVal) {
						this.load(null, evt.newVal);
					}
				}, this)
			);
			
			//On mode change reload data
			listeners.push(
				tree.on('modeChange', function (evt) {
					if (evt.newVal != evt.prevVal) {
						this.load(evt.newVal, null);
					}
				}, this)
			);
			
			//When page is restore, send request
			listeners.push(
				tree.on('page:restore', this.onSiteMapPageRestore, this)
			);
			
			listeners.push(
				tree.on('page:delete', this.load, this)
			);
		},
		
		/**
		 * Bind drag & drop for sitemap
		 * 
		 * @param {Object} node List node which to bind
		 * @param {Object} data List item data
		 * @private
		 */
		'bindSiteMapItem': function (node, data) {
			var action = Manager.SiteMap,
				tree   = action.tree,
				view   = tree.get('view'),
				mode   = this.getMode(),
				type   = data.type || (mode == 'pages' ? 'page' : 'template');
			
			var treeNode = new action.TreeNodeFake({
				'srcNode': node,
				'tree': tree,
				'view': view,
				'data': data,
				'draggable': true,
				'groups': ['restore-' + type],
				'type': type
			});
			
			treeNode.render();
			
			this.treenodes.push(treeNode);
		},
		
		/**
		 * Returns SiteMap locale
		 * 
		 * @param {String} locale Optional, suggested locale
		 * @return Locale
		 * @type {String}
		 * @private 
		 */
		'getSiteMapLocale': function (locale) {
			return locale || Manager.getAction('SiteMap').languageSelector.get('value');
		},
		
		/**
		 * Returns current SiteMap mode
		 * 
		 * @param {String} mode Optional, suggested mode
		 * @return Mode
		 * @type {String}
		 * @private
		 */
		'getSiteMapMode': function (mode) {
			if (mode && typeof mode == 'string') {
				return mode;
			} else {
				return Manager.getAction('SiteMap').tree.get('mode');
			}
		},
		
		/**
		 * On page:restore collect data and send to server
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		'onSiteMapPageRestore': function (e) {
			var node   = e.node,
				data   = node.get('data'),
				
				out    = {
					'locale': this.getLocale(),
					'parent_id': 0,
					'reference_id': 0,
					'page_id': data.id,
					'revision_id': data.revision
				},
				
				next =   null;
			
			//parent_id
			if (node.isInstanceOf('TreeNode')) {
				if (!node.get('root')) {
					out.parent_id = node.get('parent').get('data').id;
					
					//Set full path
					data.full_path = (node.get('parent').get('data').full_path || '') + data.path + '/';
				}
			} else if (node.isInstanceOf('DataGridRow')) {
				out.parent_id = node.get('parent').get('parent').get('data').id;
			}
			
			//reference_id
			next = node.next();
			if (next) {
				out.reference = next.get('data').id;
			}
			
			//Loading icon
			this.get('contentNode').addClass('loading');
			
			Supra.io(this.getDataPath('restore'), {
				'data': out,
				'method': 'post',
				'context': this,
				'on': {
					'complete': function (response, status) {
						if (status) {
							this.restoreSuccess(node, data);
						} else {
							this.restoreSiteMapFailure(node, data);
							this.get('contentNode').removeClass('loading');
						}
					}
				}
			});
		},
		
		/**
		 * On failure revert
		 * 
		 * @param {Object} node TreeNode instance
		 * @private
		 */
		'restoreSiteMapFailure': function (node) {
			var tree = node.get('tree'),
				data = tree.get('data');
			
			data.remove(node);
			tree.remove(node);
			node.destroy();
		},
		
		
		/* ------------------------------- BLOG ------------------------------- */
		
		
		/**
		 * Attach listener to Blog
		 * 
		 * @private
		 */
		'bindBlog': function () {
			var action = Manager.Blog,
				datagrid = action.widgets.datagrid,
				languages = action.widgets.languageSelector,
				listeners = this.listeners;
			
			//On locale change reload data
			listeners.push(
				languages.on('valueChange', function (evt) {
					if (this.get('visible') && evt.newVal != evt.prevVal) {
						this.load(null, evt.newVal);
					}
				}, this)
			);
			
			//When page is restore, send request
			listeners.push(
				datagrid.on('datagrid:restore', this.onBlogPageRestore, this)
			);
			
			listeners.push(
				datagrid.on('row:remove', this.load, this)
			);
		},
		
		/**
		 * Bind drag & drop for Blog
		 * 
		 * @param {Object} node List node which to bind
		 * @param {Object} data List item data
		 * @private
		 */
		'bindBlogItem': function (node, data) {
			var drag = new Y.DD.Drag({
				'node': node,
				'groups': ['restore-page']
			});
				
			drag.plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,
				cloneNode: true
			});
			
			drag.set('data', data);
			
			drag.on('drag:start', function (e) {
				var drag_node = e.target.get('dragNode');
				
				drag_node.addClass('su-blog-restore');
				drag_node.one('.title').set('innerHTML', data.title);
				
				Y.one('body').append(drag_node);
			}, this);
			
			drag.on('drag:end', function () {}, this);
		},
		
		/**
		 * Returns Blog locale
		 * 
		 * @param {String} locale Optional, suggested locale
		 * @return Locale
		 * @type {String}
		 * @private 
		 */
		'getBlogLocale': function (locale) {
			return locale || Manager.getAction('Blog').widgets.languageSelector.get('value');
		},
		
		/**
		 * Returns current Blog mode
		 * 
		 * @param {String} mode Optional, suggested mode
		 * @return Mode
		 * @type {String}
		 * @private
		 */
		'getBlogMode': function (mode) {
			return 'pages';
		},
		
		/**
		 * Returns request filter
		 * 
		 * @returns {String} Request filter
		 * @private
		 */
		'getBlogFilter': function () {
			return 'blog';
		},
		
		/**
		 * On page:restore collect data and send to server
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		'onBlogPageRestore': function (e) {
			var node   = e.node,
				data   = e.data,
				
				out    = {
					'locale': this.getLocale(),
					'parent_id': this.options.parent_id,
					'reference_id': 0,
					'page_id': data.id,
					'revision_id': data.revision
				},
				
				next =   null;
			
			//Add page to the blog list (begining)
			var datagrid = Manager.Blog.widgets.datagrid,
				reference = datagrid.getRowByIndex(0);
			
			datagrid.add(data, reference ? reference.get('id') : null);
			
			//Loading icon
			this.get('contentNode').addClass('loading');
			
			Supra.io(this.getDataPath('restore'), {
				'data': out,
				'method': 'post',
				'context': this,
				'on': {
					'complete': function (response, status) {
						if (status) {
							this.restoreSuccess(node, data);
						} else {
							this.restoreBlogFailure(node, data);
							this.get('contentNode').removeClass('loading');
						}
					}
				}
			});
		},
		
		/**
		 * On failure revert
		 * 
		 * @param {Object} node TreeNode instance
		 * @private
		 */
		'restoreBlogFailure': function (node) {
			
		},
		
		/* ------------------------------- DATA ------------------------------- */
		
		
		/**
		 * On restore success remove item from list and 
		 * hide list if there are no more items
		 * 
		 * @param {Object} node TreeNode instance
		 * @param {Object} data Page data
		 * @private
		 */
		'restoreSuccess': function (node, data) {
			var id = data.id,
				element = this.get('contentNode').one('.timeline p[data-id="' + data.id + '"]');
			
			//Remove element
			if (element) {
				element.remove();
			}
			
			//Load permissions
			if (node) {
				var tree = node.get('tree');
				if (tree) {
					tree.loadPagePermissions(data);
				}
			}
			
			//reload list
			this.load();
		},
		
		/**
		 * Returns data request URI
		 * 
		 * @param {String} mode Optional, suggested mode
		 * @return Request URI
		 * @type {String}
		 * @private
		 */
		'getLoadRequestURI': function (mode) {
			mode = this.getMode(mode);
			
			//Fix URI
			if (mode == 'pages') {
				mode = 'sitemap';
			}
			
			return this.getDataPath(mode);
		},
		
		/**
		 * Returns current mode
		 * 
		 * @param {String} mode Optional, suggested mode
		 * @return Mode
		 * @type {String}
		 * @private
		 */
		'getMode': function (mode) {
			var fn = 'get' + this.options.type + 'Mode';
			if (this[fn]) {
				return this[fn](mode);
			} else {
				return '';
			}
		},
		
		/**
		 * Returns locale
		 * 
		 * @param {String} locale Optional, suggested locale
		 * @returns {String} Locale
		 * @private 
		 */
		'getLocale': function (locale) {
			var fn = 'get' + this.options.type + 'Locale';
			if (this[fn]) {
				return this[fn](locale);
			} else {
				return '';
			}
		},
		
		/**
		 * Returns request filter
		 * 
		 * @returns {String} Request filter
		 * @private
		 */
		'getFilter': function () {
			var fn = 'get' + this.options.type + 'Filter';
			if (this[fn]) {
				return this[fn]();
			} else {
				return '';
			}
		},
		
		/**
		 * Load recycle bin data
		 */
		'load': function (mode, locale) {
			//Loading style
			this.get('contentNode').addClass('loading');
			
			Supra.io(this.getLoadRequestURI(mode), {
				'data': {
					'locale': this.getLocale(locale),
					'filter': this.getFilter()
				},
				'context': this,
				'on': {'success': this.renderData}
			});
		},
		
		'renderData': function (data) {
			var container = this.get('contentNode'),
				html = null;
			
			container.removeClass('loading');
			
			if (data.length) {
				var parsedData = this.parseData(data);
								
				this.timeline.set('innerHTML', Supra.Template('recycle-timeline', {'data': parsedData})).show();
				container.one('.empty').hide();
				
				// create tree nodes
				this.createNodes(data);
			} else {
				//No items
				html = Supra.Template('recycleItemEmpty', {});
				container.one('.empty').set('innerHTML', html).show();
				this.timeline.hide();
			}
				
			this.updateScrollbars();
		},
		
		'createNodes': function (data) {
			var node = null,
				container = this.get('contentNode'),
				treenodes = this.treenodes;
			
			for(var i=0,ii = treenodes.length; i<ii; i++) {
				treenodes[i].destroy();
			}
			this.treenodes = treenodes = [];
			
			for(i = 0, ii = data.length; i<ii; i++) {
				node = container.one('.timeline p[data-id="' + data[i].id + '"]');
				
				if (data[i].disabled) {
					node.addClass('disabled');
				} else {
					this.bindItem(node, data[i]);
				}
			}
		},
		
		/**
		 * Parse data and change format
		 * 
		 * @param {Object} data Data
		 * @returns {Object} Formatted data
		 * @private
		 */
		'parseData': function (data) {
			var i = 0,
				ii = data.length,
				out = {},
				groups = null,
				date = null;
		
			for(; i<ii; i++) {
				date = this.parseDate(data[i].date);
				
				if (!out[date.group]) {
					out[date.group] = {
						'sort': date.group_sort,
						'title': date.group_title,
						'latest': date.latest,
						'groups': {}
					}
				}
				if (!out[date.group].groups[date.group_datetime]) {
					out[date.group].groups[date.group_datetime] = {
						'sort': date.group_datetime,
						'datetime': date.group_datetime,
						'pages': []
					};
				}
				
				out[date.group].groups[date.group_datetime].pages.push({
					'id': data[i].id,
					'title': data[i].title,
					'action': data[i].action,
					'datetime': date.datetime,
					'author': data[i].author
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
		 * 
		 * @param {String} date Date to convert
		 * @returns {String} Formatted date string
		 * @private
		 */
		'parseDate': function (date) {
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
				out.group_title = Supra.Intl.get(['timeline', 'today']);
				out.group_datetime = YDate.reformat(raw, 'raw', '%H:00');
				out.latest = true;
				out.group_sort = [3, null];
			}
			else if (raw.getTime() >= y_day.getTime())
			{
				out.group = YDate.reformat(raw, 'raw', '%Y-%m-%d');
				out.group_title = Supra.Intl.get(['timeline', 'yesterday']);
				out.group_datetime = YDate.reformat(raw, 'raw', '%H:00');
				out.latest = true;
				out.group_sort = [2, null];
			}
			else if (raw.getTime() >= month.getTime())
			{
				out.group = YDate.reformat(raw, 'raw', '%Y-%m-00');
				out.group_title = Supra.Intl.get(['timeline', 'this_month']);
				out.group_datetime = raw.getDate();
				out.group_sort = [1, null];
			}
			else
			{
				out.group = YDate.reformat(raw, 'raw', '%Y-%m-00');
				out.group_title = month_names[raw.getMonth()];
				out.group_datetime = raw.getDate();
				out.group_sort = [0, YDate.reformat(raw, 'raw', '%Y-%m')];
			}
			
			return out;
		},
		
		
		/* ------------------------------- UI ------------------------------- */
		
		
		/**
		 * Update scrollbars
		 * 
		 * @private
		 */
		'updateScrollbars': function () {
			this.one('.su-scrollable').fire('contentResize');
		},
		
		/**
		 * Expand / collapse section
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		'toggleSection': function (e) {
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
		 * On hide clean up and call callback
		 * 
		 * @private
		 */
		'onHide': function () {
			this.unbindAction();
			
			if (Y.Lang.isFunction(this.options.onclose)) {
				this.options.onclose();
			}
			
			this.options = null;
		},
		
		/**
		 * Execute action
		 */
		'execute': function (options) {
			this.options = Supra.mix({
				'type': 'SiteMap',
				'parent_id': null,
				'onclose': null
			}, options || {});
			
			this.bindAction();
			this.show();
			this.load();
		}
	});
	
});