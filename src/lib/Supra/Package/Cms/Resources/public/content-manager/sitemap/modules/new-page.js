//Invoke strict mode
"use strict";

YUI().add('website.sitemap-new-page', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	function NewPage(config) {
		NewPage.superclass.constructor.apply(this, arguments);
	}
	
	NewPage.NAME = 'NewPage';
	NewPage.CSS_PREFIX = 'su-new-page';
	
	
	NewPage.TEMPLATE_ITEM = Supra.Template.compile('\
			<div data-type="{{ type }}" class="item {% if type == "page" %}item-page{% endif %}">\
				{% if icon %}<img src="{{ icon }}" alt="" />{% else %}<div class="img"></div><div class="drag"></div>{% endif %}\
				<label>{{ title }}</label>\
			</div>\
		');
	
	NewPage.TEMPLATE = '\
			<div class="deco"><img src="/public/cms/content-manager/sitemap/images/new-page-icon.png" alt="" /></div>\
			' + NewPage.TEMPLATE_ITEM({"type": "page", "title": Supra.Intl.get(["sitemap", "new_page_title"])}) + '\
			<div class="children-inner">\
				' + NewPage.TEMPLATE_ITEM({"type": "group", "icon": "/public/cms/content-manager/sitemap/images/apps/group.png", "title": Supra.Intl.get(["sitemap", "app_group"])}) + '\
			</div>\
		';
	
	NewPage.ATTRS = {
		/**
		 * Expanded state
		 */
		'expanded': {
			'value': false,
			'setter': '_setExpanded'
		},
		/**
		 * Mode
		 */
		'mode': {
			'value': 'pages',
			'setter': '_setMode'
		}
	};
	
	Y.extend(NewPage, Y.Widget, {
		
		/**
		 * Scrollable instance
		 * @type {Object}
		 * @private
		 */
		'_scrollable': null,
		
		/**
		 * Animation object
		 * @type {Object}
		 * @private
		 */
		'_anim': null,
		
		/**
		 * Application count
		 * @type {Number}
		 * @private
		 */
		'_application_count': 1,
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		'renderUI': function () {
			var contentBox = this.get('contentBox');
			
			//Render template
				contentBox.set('innerHTML', NewPage.TEMPLATE);
			
			//Create scrollable content
				this._scrollable = new Supra.Scrollable({
					'srcNode': contentBox.one('.children-inner')
				});
				
				this._scrollable.render();
				this._scrollable.get('boundingBox').addClass('block-inset').addClass('children');
			
			//Create animation
				this._anim = new Y.Anim({
					'node': this.get('contentBox').one('.children'),
					'from': {'height': 0},
					'to': {'height': 0},
					'duration': 0.25
				});
				
				this._anim.on('end', this._setExpandedAfter, this);
		},
		
		/**
		 * Bind UI events
		 * 
		 * @private
		 */
		'bindUI': function () {
			//Collapse / expand
			var contentBox = this.get('contentBox');
			contentBox.one('div.item-page label').on('click', this.toggle, this);
		},
		
		/**
		 * Sync UI state with widget attribute states
		 * 
		 * @private
		 */
		'syncUI': function () {
			this._loadApplications();
		},
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		/**
		 * Expanded attribute setter
		 * 
		 * @param {Boolean} value New attribute value
		 * @return New attribute value
		 * @type {Boolean}
		 * @private
		 */
		'_setExpanded': function (value) {
			if (this.get('expanded') == value) return !!value;
			
			var boundingBox = this.get('boundingBox'),
				anim = this._anim,
				count = this._application_count,
				height = Math.min(80 + 80 * count, 470);
			
			if (value) {
				//Slide down
				anim.stop();
				anim.set('from', {'height': '0px'});
				anim.set('to', {'height': height + 'px'});
				anim.run();
				
				boundingBox.addClass('expanded');
			} else {
				//Slide up
				anim.stop();
				anim.set('from', {'height': height + 'px'});
				anim.set('to', {'height': '0px'});
				anim.run();
				
				this._scrollable.set('disabled', true);
			}
			
			return !!value;
		},
		
		'_setExpandedAfter': function () {
			if (this.get('expanded')) {
				this._scrollable.set('disabled', false);
			} else {
				this.get('boundingBox').removeClass('expanded');
			}
		},
		
		/**
		 * Load application list
		 * 
		 * @private
		 */
		'_loadApplications': function () {
			var uri = Supra.Url.generate('cms_pages_sitemap_applications_list');
			
			Supra.io(uri, {
				'context': this,
				'on': {
					'success': this._renderApplications
				}
			});
		},
		
		/**
		 * Render application list
		 * 
		 * @param {Array} data Application data
		 * @param {Boolean} status Request status
		 * @private
		 */
		'_renderApplications': function (data, status) {
			var target = this.get('contentBox').one('div.children-inner'),
				tpl = NewPage.TEMPLATE_ITEM,
				i = 0,
				ii = data.length,
				node = null,
				treeNode = null;
			
			for(; i<ii; i++) {
				data[i].type = 'application';
				
				node = Y.Node.create(tpl(data[i]));
				target.append(node);
			}
			
			this.data = data;
			this._application_count = data.length + 1; //1 because we have also folder
			this._bindDnD(data);
		},
		
		/**
		 * Add drag and drop support
		 * 
		 * @private
		 */
		'_bindDnD': function (apps) {
			var data = [{
				'type': 'page',
				'draggable': true,
				'droppable': true,
				'new_children_first': false,
				'state': 'temporary',
				'children': [],
				'children_count': 0
			}, {
				'type': 'group',
				'draggable': true,
				'droppable': true,
				'new_children_first': false,
				'state': 'temporary',
				'children': [],
				'children_count': 0
			}];
			
			var i = 0,
				ii = apps.length;
			
			for(; i<ii; i++) {
				data.push({
					'type': apps[i].type,
					'application_id': (apps[i].type == 'application' ? apps[i].id : null),
					'draggable': apps[i].isDraggable ? true : false,
					'droppable': apps[i].isDropTarget,
					'droppablePlaces': apps[i].droppablePlaces,
					'new_children_first': apps[i].childInsertPolicy === 'prepend' ? true : false,
					'state': 'temporary',
					'children': [],
					'children_count': 0
				});
			}
			
			//Add drag and drop
			var treeNode = null,
				items = this.get('contentBox').all('div.item'),
				i = 0,
				ii = items.size(),
				
				tree = Action.tree,
				view = tree.get('view'),
				
				type = null;
			
			for(; i<ii; i++) {
				type = items.item(i).getAttribute('data-type');
				
				treeNode = new Action.TreeNodeFake({
					'srcNode': items.item(i),
					'tree': tree,
					'view': view,
					'data': data[i],
					'draggable': true,
					'groups': ['new-' + type],
					'type': type
				});
				
				treeNode.render();
			}
		},
		
		/**
		 * Update UI
		 */
		'_setMode': function (mode) {
			if (mode == 'pages') {
				this.get('boundingBox').removeClass('mode-templates');
			} else {
				this.get('boundingBox').addClass('mode-templates');
				this.set('expanded', false);
			}
		},
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
		/**
		 * Returns application data by ID
		 */
		'getApplicationData': function (id) {
			var data = this.data,
				i    = 0,
				ii   = data ? data.length : 0;
			
			for (; i<ii; i++) {
				if (data[i].id == id) return data[i];
			}
			
			return null;
		},
		
		/**
		 * Toggle list collapsed/expanded state
		 */
		'toggle': function () {
			//Templates doesn't have "applications"
			if (this.get('mode') == 'pages') {
				this.set('expanded', !this.get('expanded'));
			}
		}
	});
	
	
	Action.NewPage = NewPage;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.scrollable']});
