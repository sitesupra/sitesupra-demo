//Invoke strict mode
"use strict";


//Add module definitions
SU.addModule('website.input-dial', {
	path: 'modules/input-dial.js',
	requires: ['supra.input-proto']
});
SU.addModule('website.tree-node-permissions', {
	path: 'modules/tree-node.js',
	requires: ['supra.tree-node-dragable']
});
SU.addModule('website.permission-list', {
	path: 'modules/permission-list.js',
	requires: ['dd', 'supra.input']
});

/**
 * Main manager action, initiates all other actions
 */
Supra('supra.input', 'supra.languagebar', 'supra.tree-dragable', 'website.tree-node-permissions', 'website.permission-list', 'supra.slideshow-multiview', 'website.input-dial', function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PermissionProperties',
		
		/**
		 * Load action stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load action template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		
		/**
		 * Application data
		 * @type {Object}
		 * @private
		 */
		application: null,
		
		/**
		 * User data for selected application
		 * @type {Object}
		 * @private
		 */
		user: null,
		
		/**
		 * Form instance
		 * @see Supra.Form
		 * @type {Object}
		 * @private
		 */
		form: null,
		
		/**
		 * Slideshow instance
		 * @see Supra.SlideshowMultiView
		 * @type {Object}
		 * @private
		 */
		slideshow: null,
		
		/**
		 * Tree instance
		 * @see Supra.Tree
		 * @type {Object}
		 * @private
		 */
		tree: null,
		
		/**
		 * Tree is localized
		 * @type {Boolean}
		 * @private
		 */
		localized: false,
		
		/**
		 * LanguageBar instance
		 * @see Supra.LanguageBar
		 * @type {Object}
		 * @private
		 */
		languagebar: null,
		
		/**
		 * Application for which tree is rendered
		 * @type {String}
		 * @private
		 */
		tree_application_id: null,
		
		/**
		 * Permission list
		 * @type {Object}
		 * @see Supra.PermissionList
		 * @private
		 */
		list: null,
		
		
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			
			this.slideshow = new Supra.SlideshowMultiView({
				'srcNode': this.one(),
				'slide': 'propertiesSlide'
			});
			this.slideshow.render();
			
		},
		
		/**
		 * Render form
		 */
		renderForm: function (properties, values) {
			var container = this.one('.properties-inner'),
				i = 0,
				ii = properties.length,
				obj = {},
				val,
				sublabel = '',
				subproperty = null;
			
			this.localized = false;
			if (this.form) this.form.destroy();
			
			for(; i<ii; i++) {
				obj[properties[i].id] = properties[i];
				
				if (properties[i].sublabel) {
					sublabel = properties[i].sublabel;
				}
				if (properties[i].subproperty) {
					subproperty = properties[i].subproperty;
					this.localized = subproperty.localized;
				}
			}
			
			this.form = new Supra.Form({
				'inputs': obj,
				'autoDiscoverInputs': false
			});
			
			this.form.render(container);
			this.form.setValues(values, 'id');
			
			var inputs = this.form.getInputs(),
				fn;
			for(i in inputs) {
				fn = this.onInputChange;
				if (i == 'allow') fn = this.onAllowChange;
				
				inputs[i].on('change', fn, this);
			}
			
			
			//Create or update permission list
			if (!this.list) {
				this.list = new Supra.PermissionList({
					'sublabel': sublabel,
					'subproperty': subproperty,
					'localized': this.localized,
					'tree': null,			//Tree is not created yet
					'languagebar': null		//Neither is LanguageBar
				});
				this.list.render(this.one('.properties'));
				
				//On new item add save it
				this.list.on('change', function (evt) {
					this.sendValueChange('allow', this.form.getInput('allow').getValue(), evt.id, evt.locale);
				}, this);
			} else {
				this.list.set('sublabel', sublabel);
				this.list.set('subproperty', subproperty);
				this.list.set('localized', this.localized);
				this.list.resetValue();
			}
			
			if (this.languagebar) {
				if (this.localized) {
					this.languagebar.show();
				} else {
					this.languagebar.hide();
				}
			}
			
			this.onAllowChange({'value': values.allow, 'list': values}); 
		},
		
		/**
		 * Render tree
		 */
		renderTree: function (list) {
			//If application didn't changed then there is no need to reload
			if (this.tree_application_id == this.application.id) {
				//Fill permission list
				if (list && list.items) {
					this.list.setValue(list.items);
				}
				return;
			}
			
			//To request URI add application ID
			var locale = (this.languagebar ? this.languagebar.get('locale') : '');
			var uri = this.getDataPath('datalist', {'application_id': this.application.id, 'locale': locale});
			
			//Create or reload tree
			if (!this.tree) {
				//Create tree
				var container = this.slideshow.getSlide('treeSlide');
				var tree = new Supra.TreeDragable({
					'requestUri': uri,
					'defaultChildType': Supra.TreeNodePermissions,
					
					//Because of slideshow overflow need to place proxies outside slideshow
					'dragProxyParent': this.getPlaceHolder()
				});
				
				tree.render(container.one('div'));
				this.tree = tree;
				this.list.set('tree', tree);
				
				this.renderLanguageBar(container.one('div.languages'));
			} else {
				this.tree.set('requestUri', uri);
				this.tree.reload();
			}
			
			this.tree_application_id = this.application.id;
			
			//Fill permission list
			this.tree.once('render:complete', function () {
				if (list && list.items) {
					this.list.setValue(list.items);
				}
			}, this);
		},
		
		/**
		 * Reload tree data
		 */
		reloadTree: function () {
			//To request URI add application ID
			var uri = this.getDataPath('datalist', {'application_id': this.application.id, 'locale': this.languagebar.get('locale')});
			
			this.tree.set('requestUri', uri);
			this.tree.reload();
		},
		
		/**
		 * Create language bar
		 * 
		 * @private
		 */
		renderLanguageBar: function (container) {
			//All language content
			var all = [{
				'title': '',
				'languages': [{'id': '', 'flag': 'px', 'title': 'All languages'}]
			}];
			
			//Create language bar
			this.languagebar = new SU.LanguageBar({
				'locale': SU.data.get('locale'),
				'contexts': all.concat(SU.data.get('contexts')),
				'localeLabel': SU.Intl.get(['userpermissions', 'permissions_for'])
			});
			
			this.languagebar.after('localeChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					this.reloadTree();
				}
			}, this);
			
			this.languagebar.render(container);
			
			this.list.set('languagebar', this.languagebar);
			
			if (!this.localized) {
				this.languagebar.hide();
			}
		},
		
		/**
		 * On 'Permissions' change show/hide tree
		 */
		onAllowChange: function (event) {
			if (event.value == 1) {
				this.list.show();
				this.slideshow.set('slide', 'treeSlide');
				this.renderTree(event.list);
			} else {
				this.list.hide();
				this.slideshow.set('slide', 'propertiesSlide');
			}
			
			if (!event.list) {
				//Save properties only if not first change (when setting initial values)
				this.saveProperties();
				
				//Send property change
				this.sendValueChange('allow', event.value, null, event.locale);
			}
		},
		
		/**
		 * On 'Permissions' change show/hide tree
		 */
		onInputChange: function (event) {
			this.sendValueChange(event.target.get('id'), event.value);
		},
		
		sendValueChange: function (name, value, id, locale) {
			var user = Manager.User.getData();
			var post = {
				'user_id': user.user_id,
				'application_id': this.application.id,
				'property': name,
				'locale': locale,
				'value': value
			};
			
			//Send only changed item
			if (id) {
				var list = this.list.getValue();
				for(var i=0,ii=list.length; i<ii; i++) {
					if (list[i] == id || list[i].id == id) {
						post.list = list[i];
					}
				}
			}
			
			//Save value change
			Supra.io(this.getDataPath('save'), {
				'data': post,
				'method': 'post'
			});
		},
		
		saveProperties: function () {
			var user = Manager.getAction('User').getData(),
				values = this.form.getValues('id'),
				items = this.list.getValue();
			
			user.permissions[this.application.id] = Supra.mix(values, {
				'items': items
			});
		},
		
		hide: function () {
			if (this.get('visible')) {
				this.set('visible', false);
				this.slideshow.hide();
				
				this.saveProperties();
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function (application, properties, user) {
			
			if (this.get('visible') && this.form) {
				//Save properties before changing application
				this.saveProperties();
			}
			
			this.slideshow.show();
			this.application = application;
			this.user = user;
			
			this.renderForm(properties, user);
			this.show();
		}
	});
	
});