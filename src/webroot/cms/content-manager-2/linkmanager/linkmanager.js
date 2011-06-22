SU('supra.form', 'supra.slideshow', 'supra.tree', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Add as right bar child
	Manager.getAction('LayoutLeftContainer').addChildAction('LinkManager');
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'LinkManager',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Buttons
		 * @type {Object}
		 */
		button_cancel: null,
		button_back: null,
		
		/**
		 * Link to file / link to page slideshow, Supra.Slideshow instance
		 * @type {Object}
		 */
		slideshow: null,
		
		/**
		 * Link slideshow, Supra.Slideshow instance
		 * @type {Object}
		 */
		link_slideshow: null,
		
		/**
		 * Supra.Form instance
		 * @type {Object}
		 */
		form: null,
		
		/**
		 * Link data
		 * @type {Object}
		 */
		data: {},
		
		/**
		 * Callback function
		 * @type {Function}
		 */
		callback: null,
		
		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			//Slideshow widget
			this.slideshow = new Supra.Slideshow({
				'srcNode': this.getContainer('div.slideshow')
			});
			
			this.slideshow.render();
			this.slideshow.on('slideChange', function (evt) {
				var fn = 'on' + evt.newVal.substr(0,1).toUpperCase() + evt.newVal.substr(1);
				if (fn in this) {
					var node = this.slideshow.getSlide(evt.newVal);
					this[fn](node);
				}
			}, this);
			this.slideshow.after('slideChange', function (evt) {
				var heading = this.getContainer('h2.yui3-sidebar-header span');
				
				if (this.slideshow.history.length <= 1) {
					this.button_back.hide();
					heading.hide();
				} else {
					this.button_back.show();
					heading.set('text', evt.newVal == 'linkToPage' ? 'page' : 'file');
					heading.show();
				}
			}, this);
			
			//
			var links = this.getContainer().all('#linkToRoot a[data-slideshow]');
				links.on('click', this.onSlideshowLinkClick, this);
			
			
			//Back and Close buttons
			var buttons = this.getContainer().all('button');
			
			this.button_back = new Supra.Button({'srcNode': buttons.filter('.button-back').item(0)});
			this.button_back.render();
			this.button_back.hide();
			this.button_back.on('click', this.slideshow.scrollBack, this.slideshow);
			
			this.button_close = new Supra.Button({'srcNode': buttons.filter('.button-close').item(0), 'style': 'mid-blue'});
			this.button_close.render();
			this.button_close.on('click', this.close, this);
			
			
			//On visibility change show/hide container
			this.on('visibleChange', function (evt) {
				if (evt.newVal) {
					this.getContainer().removeClass('hidden');
				} else {
					this.getContainer().addClass('hidden');
				}
			}, this);
			
			//When layout position/size changes update slide position
			Manager.LayoutLeftContainer.layout.on('sync', this.slideshow.syncUI, this.slideshow);
			
			//Create form
			this.form = new Supra.Form({
				'srcNode': this.getContainer('form')
			});
			this.form.render();
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			//Hide action
			Manager.getAction('LayoutLeftContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * On slideshow link click navigate to slide
		 */
		onSlideshowLinkClick: function (e) {
			var target = (e.target.test('a') ? e.target : e.target.ancestor('a')),
				id = target.getAttribute('data-slideshow');
			
			if (id) {
				this.slideshow.set('slide', id);
			}
		},
		
		/**
		 * On link slide create widgets, etc.
		 * 
		 * @param {Object} node
		 */
		onLinkToPage: function (node) {
			if (!this.link_slideshow) {
				//Internal / External
					//Create slideshow
					var slideshow = this.link_slideshow = (new Supra.Slideshow({
						'srcNode': node.one('div.slideshow')
					})).render();
					
					//On Internal / External switch show slide
					this.form.getInput('linkManagerType').on('change', function (evt) {
						var slide = 'linkManager' + evt.value.substr(0,1).toUpperCase() + evt.value.substr(1);
						slideshow.set('slide', slide);
					}, this);
					
					//When layout position/size changes update slide position
					Manager.LayoutLeftContainer.layout.on('sync', this.link_slideshow.syncUI, this.link_slideshow);
				
				//Create tree
					//Use sitemap data
					var sitemap_data_path = SU.Manager.Loader.getActionInfo('Sitemap').path_data;
					
					//Create tree
					this.tree = new SU.Tree({
						srcNode: node.one('.tree'),
						requestUri: sitemap_data_path
					});
					this.tree.plug(SU.Tree.ExpandHistoryPlugin);
					this.tree.render();
			}
		},
		
		/**
		 * Restore state matching data
		 * 
		 * @param {Object} data
		 */
		setData: function (data) {
			data = SU.mix({
				'type': '',
				'target': '',
				'title': '',
				'href': '',
				'page_id': null,
				'page_path': '',
				'linktype': 'internal'
			}, data || {});
			
			if (this.link_slideshow) {
				this.link_slideshow.set('noAnimations', true);
			}
			
			//Set values by input name
			this.form.setValues(data, 'name');
			
			this.data = data;
			this.slideshow.set('noAnimations', true);
			
			switch (data.type) {
				case 'page':
					this.slideshow.set('slide', 'linkToPage');
					
					this.link_slideshow.set('noAnimations', true);
					this.form.getInput('linkManagerType').set('value', 'internal');
					this.link_slideshow.set('noAnimations', false);
					
					if (data.page_id) {
						var node = this.tree.getNodeById(data.page_id);
						if (!node) {
							this.tree.once('render:complete', function () {
								var node = this.tree.getNodeById(data.page_id);
								if (node) this.tree.set('selectedNode', node);
							}, this);
						} else {
							this.tree.set('selectedNode', node);
						}
					}
					
					break;
				case 'link':
					this.slideshow.set('slide', 'linkToPage');
					
					this.link_slideshow.set('noAnimations', true);
					this.form.getInput('linkManagerType').set('value', 'external');
					this.link_slideshow.set('noAnimations', false);
					
					break;
				case 'file':
					this.slideshow.set('slide', 'linkToFile');
					break;
				default:
					break;
			}
			
			if (this.link_slideshow) {
				this.link_slideshow.set('noAnimations', false);
			}
			this.slideshow.set('noAnimations', false);
		},
		
		/**
		 * Returns link data
		 * 
		 * @return Link data
		 * @type {Object}
		 */
		getData: function () {
			var data = SU.mix(this.data || {}, this.form.getValues('name'));
			
			if (this.slideshow.get('slide') == 'linkToPage') {
				if (data.linktype == 'internal') {
					//Link to page
					var tree_node = this.tree.get('selectedNode'),
						page_data = null,
						page_id = '',
						page_path = '';
					
					if (tree_node) {
						page_data = tree_node.get('data');
						if (page_data) {
							page_id = page_data.id;
							page_path = this.getTreePagePath(page_id);
						}
					}
					
					return {
						'type': 'page',
						'page_id': page_id,
						'page_path': page_path,
						'href': page_path,
						'target': data.target,
						'title': data.title
					};
				} else {
					//Link to external resource
					return {
						'type': 'link',
						'href': data.href,
						'target': data.target,
						'title': data.title
					};
				}
			} else {
				//Link to file
				
				//@TODO
			}
		},
		
		/**
		 * Returns tree page path
		 * 
		 * @param {Number} id
		 */
		getTreePagePath: function (id) {
			var data = this.tree.getIndexedData();
			return id && id in data ? data[id].fullpath : '';
		},
		
		/**
		 * Close and save data
		 */
		close: function () {
			if (this.callback) {
				var data = this.getData();
				this.callback(data);
				this.callback = null;
			}
			
			this.hide();
		},
		
		/**
		 * Execute action
		 */
		execute: function (data, callback) {
			Manager.getAction('LayoutLeftContainer').setActiveAction(this.NAME);
			
			this.callback = null;
			this.setData(data);
			
			if (SU.Y.Lang.isFunction(callback)) {
				this.callback = callback;
			}
		}
	});
	
});