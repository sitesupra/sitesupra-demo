//Invoke strict mode
"use strict";

SU('anim', 'transition', function (Y) {
	
	//Shortcuts
	var Manager = SU.Manager,
		Action = Manager.Action;
	
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
		 */
		'treenodes': [],
		
		
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		'render': function () {
			//Close button
			this.get('controlButton').on('click', this.hide, this);
			
			//On locale change reload data
			Manager.SiteMap.languageSelector.on('valueChange', function (evt) {
				if (this.get('visible') && evt.newVal != evt.prevVal) {
					this.load(null, evt.newVal);
				}
			}, this);
			
			//When page is restore, send request
			Manager.SiteMap.tree.on('page:restore', this.onPageRestore, this);
			
			//When page is restore, send request
			Manager.SiteMap.tree.on('page:delete', this.load, this);
		},
		
		/**
		 * Render list items
		 * 
		 * @param {Object} data List data
		 * @param {Boolean} status Response status
		 * @private
		 */
		'renderItems': function (data, status) {
			var container = this.one('.recycle-list'),
				treenodes = this.treenodes,
				html = null,
				node = null,
				last_title = '';
			
			//Set date titles
			for(var i=0,ii=data.length; i<ii; i++) {
				data[i].date_title = this.dateToTitle(data[i].date);
				data[i].date_diff = (last_title != data[i].date_title);
				last_title = data[i].date_title;
			}
			
			html = Supra.Template('recycleItemList', {'items': data});
			
			container.set('innerHTML', html);
			this.one().removeClass('loading');
			
			for(var i=0,ii=treenodes.length; i<ii; i++) {
				treenodes[i].destroy();
			}
			this.treenodes = treenodes = [];
			
			for(var i=0,ii=data.length; i<ii; i++) {
				node = container.one('li[data-id="' + data[i].id + '"] p.title');
				
				//Attributes
				data[i].state = data[i].state || (data[i].published ? 'published' : 'draft');
				
				//Data
				data[i].full_path = data[i].full_path || '';
				
				this.bindItem(node, data[i]);
			}
		},
		
		/**
		 * Returns title from date
		 * 
		 * @param {String} date Date string or date object
		 * @return Date title
		 * @type {String}
		 */
		'dateToTitle': function (date) {
			var date = Date.parse(date),
				diff = Math.ceil(((new Date()).getTime() - date) / 86400000),
				title = '';
			
			if (diff <= 1) {
				title = Supra.Intl.get(['sitemap', 'today']);
			} else if (diff == 2) {
				title = Supra.Intl.get(['sitemap', 'yesterday']);
			} else if (diff <= 7) {
				title = Supra.Intl.get(['sitemap', 'last_week']);
			} else {
				title = Supra.Intl.get(['sitemap', 'older']);
			}
			
			return title;
		},
		
		/**
		 * Bind drag & drop
		 */
		'bindItem': function (node, data) {
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
				'dragable': true,
				'groups': ['restore-' + type],
				'type': type
			});
			
			treeNode.render();
			
			this.treenodes.push(treeNode);
		},
		
		/**
		 * On page:restore collect data and send to server
		 */
		'onPageRestore': function (e) {
			var node   = e.node,
				data   = node.get('data'),
				
				out    = {
					'locale': this.getLocale(),
					'parent_id': 0,
					'reference_id': 0,
					'page_id': data.id
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
			this.one().addClass('loading');
			
			Supra.io(this.getDataPath('restore'), {
				'data': out,
				'method': 'post',
				'context': this,
				'on': {
					'complete': function (response, status) {
						if (status) {
							this.restoreSuccess(node, data);
						} else {
							this.restoreFailure(node, data);
						}
						
						this.one().removeClass('loading');
					}
				}
			});
		},
		
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
				element = this.one('.recycle-list li.item[data-id="' + data.id + '"]');
			
			//Remove element
			if (element) {
				element.remove();
			}
			
			//Load permissions
			var tree = node.get('tree');
			tree.loadPagePermissions(data);
			
			//If last item was removed then hide recycle bin
			if (!this.one('.recycle-list li.item')) {
				this.hide();
			}
		},
		
		/**
		 * On failure revert
		 * 
		 * @param {Object} node TreeNode instance
		 * @private
		 */
		'restoreFailure': function (node) {
			var tree = node.get('tree'),
				data = tree.get('data');
			
			data.remove(node);
			tree.remove(node);
			node.destroy();
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
			if (mode && typeof mode == 'string') {
				return mode;
			} else {
				return Manager.getAction('SiteMap').tree.get('mode');
			}
		},
		
		/**
		 * Returns locale
		 * 
		 * @param {String} locale Optional, suggested locale
		 * @return Locale
		 * @type {String}
		 * @private 
		 */
		'getLocale': function (locale) {
			return locale || Manager.getAction('SiteMap').languageSelector.get('value');
		},
		
		/**
		 * Load recycle bin data
		 */
		'load': function (mode, locale) {
			//Loading style
			this.one().addClass('loading');
			
			Supra.io(this.getLoadRequestURI(mode), {
				'data': {
					'locale': this.getLocale(locale)
				},
				'context': this,
				'on': {'complete': this.renderItems}
			});
		},
		
		/**
		 * Execute action
		 */
		'execute': function () {
			this.show();
			this.load();
		}
	});
	
});