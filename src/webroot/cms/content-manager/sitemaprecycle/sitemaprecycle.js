//Invoke strict mode
"use strict";

SU('anim', 'transition', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	//Add as right bar child
	Manager.getAction('LayoutLeftContainer').addChildAction('SiteMapRecycle');
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'SiteMapRecycle',
		
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
		 * Close button, Supra.Button instance
		 * @type {Object}
		 */
		button_close: null,
		
		/**
		 * Tree node list
		 * @type {Array}
		 */
		treenodes: [],
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * 
		 * @private
		 */
		initialize: function () {
		},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		render: function () {
			//Close button
			this.renderHeader();
		},
		
		/**
		 * Create buttons
		 * 
		 * @private
		 */
		renderHeader: function () {
			var buttons = this.all('button');
			
			this.button_close = new Supra.Button({'srcNode': buttons.filter('.button-close').item(0), 'style': 'mid-blue'});
			this.button_close.render();
			this.button_close.on('click', this.hide, this);
		},
		
		/**
		 * Render list items
		 * 
		 * @param {Object} data List data
		 * @param {Boolean} status Response status
		 * @private
		 */
		renderItems: function (data, status) {
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
			container.removeClass('loading');
			
			for(var i=0,ii=treenodes.length; i<ii; i++) {
				treenodes[i].destroy();
			}
			this.treenodes = treenodes = [];
			
			for(var i=0,ii=data.length; i<ii; i++) {
				node = container.one('li[data-id="' + data[i].id + '"] label');
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
		dateToTitle: function (date) {
			var date = Y.DataType.Date.parse(date).getTime(),
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
		bindItem: function (node, data) {
			var container = this.one();
			var tree = Manager.getAction('SiteMap').getTree();
			var treenode = new Supra.FlowMapItemNormal({
				'data': data,
				'label': data.title,
				'icon': data.icon
			});
			
			treenode.render(document.body);
			treenode.get('boundingBox').remove();
			
			treenode._tree = tree;
			
			var dd = this.dd = new Y.DD.Drag({
				'node': node,
				'dragMode': 'point',
				'target': false
			}).plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,			// Don't move original node at the end of drag
				cloneNode: true
			});
			
			dd.set('treeNode', treenode);
			
			if (dd.target) {
				dd.target.set('treeNode', treenode);
			}
			
			//When starting drag all children must be locked to prevent
			//parent drop inside children
			dd.on('drag:afterMouseDown', treenode._afterMouseDown);
			
			//Set special style to proxy node
			dd.on('drag:start', treenode._dragStart);
			dd.on('drag:start', function () {
				container.append(this.get('dragNode'));
			});
			
			// When we leave drop target hide marker
			dd.on('drag:exit', treenode._dragExit);
			
			// When we move mouse over drop target update marker
			dd.on('drag:over', treenode._dragOver);
			
			dd.on('drag:end', function (e) { this._dragEnd(e, treenode, node) }, this);
			
			this.treenodes.push(treenode);
		},
		
		/**
		 * 
		 * @param {Object} e
		 */
		_dragEnd: function(e, treenode, node){
			var tree = Manager.getAction('SiteMap').getTree();
			
			if (treenode.drop_target) {
				var target = treenode.drop_target,
					drag_id = treenode.get('data').id,
					drop_id = target.get('data').id,
					position = treenode.marker_position,
					post_data = Manager.getAction('SiteMap').getDropPositionData(target, drop_id, drag_id, position);
				
				Supra.io(this.getDataPath('restore'), {
					'data': post_data,
					'method': 'post',
					'context': this,
					'on': {
						'complete': function (data, status) {
							if (status) this.restoreComplete(node);
						}
					}
				})
			}
			
			//Hide marker and cleanup data
			treenode.setMarker(null);
			
			//Unlock children to allow them being draged
			treenode.unlockChildren();
			
			//Make sure node is not actually moved
			e.preventDefault();
		},
		
		restoreComplete: function (node) {
			var sitemap = Manager.getAction('SiteMap');
			sitemap.flowmap.reload();
			sitemap.setLoading(true);
			
			node.remove();
			
			if (!this.one('.recycle-list li')) {
				this.hide();
			}
		},
		
		/**
		 * Load recycle bin data
		 */
		load: function (type) {
			var sitemap = Manager.getAction('SiteMap'),
				type = type || sitemap.input_type.getValue();
			
			this.one('.recycle-list').addClass('loading');
			Supra.io(this.getDataPath(type), this.renderItems, this);
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			Manager.getAction('LayoutLeftContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 *
		 * @param {Object} request_params Optional. Request parameters
		 */
		execute: function (request_params /* Request parameters */) {
			//Show MediaSidebar in left container
			Manager.getAction('LayoutLeftContainer').setActiveAction(this.NAME);
			
			this.load();
		}
	});
	
});