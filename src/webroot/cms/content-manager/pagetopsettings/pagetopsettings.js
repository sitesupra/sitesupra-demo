SU('supra.tabs', 'supra.form', function (Y) {
	
	/**
	 * Default tabs
	 * @type {Object}
	 */
	var TABS_DEFAULT = {
		'general': {'title': 'General'},
		'blocks': {'title': 'Blocks'},
		'service': {'title': 'Service'}
	};
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageTopSettings',
		
		/**
		 * No need for template
		 * @type {Boolean}
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			//Hide content until all widgets are rendered
			this.getPlaceHolder().addClass('hidden');
			
			//Check 'tabs' attribute for additional tab configuration
			var tab_config = Supra.mix({}, TABS_DEFAULT, this.get('tabs') || {});
			this.set('tabs', tab_config);
			
			//Create tabs
			var tabs = this.tabs = new Supra.Tabs({'style': 'vertical'});
			
			for(var id in tab_config) {
				if (Y.Lang.isObject(tab_config[id])) {
					tabs.addTab({"id": id, "title": tab_config[id].title, "icon": tab_config[id].icon});
				}
			}
			
			//Render "General" content
			this.initializeGeneralUI();
		},
		
		/**
		 * Create form
		 */
		initializeGeneralUI: function () {
			var form = this.form = new Supra.Form();
			
			form.addInput({
				"id": "title",
				"label": "Name:",
				"type": "String",
				"value": "Catalogue",
				"useReplacement": true
			});
			
			form.addInput({
				"id": "path",
				"label": "Address:",
				"type": "Path",
				"value": "catalogue",
				"path": "www.domain.com/sample/",
				"useReplacement": true
			});
			
			form.addInput({
				"id": "description",
				"type": "String",
				"value": "Description",
				"useReplacement": true
			});
			
			form.addInput({
				"id": "keywords",
				"type": "String",
				"value": "web development, web design, nearshore development, e-commerce, visualization, 3D, web 2.0, PHP, LAMP, SiteSupra Platform, CMS, content management, web application, Web systems, IT solutions, usability improvements, system design, FMS, SFS, design conception, design solutions, intranet systems development, extranet systems development, flash development, hitask",
				"useReplacement": true
			});
			
			/*
			form.addInput({
				"id": "template",
				"type": "template",
				"value": "template_3",
				"templateRequestUri": "/cms/templates.json"
			});
			*/
			
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			var placeholder = this.getPlaceHolder();
			
			//Add className to allow custom styles
			placeholder.addClass(Y.ClassNameManager.getClassName('tab', 'settings'));
			
			//When tab changes, fire 'resize' event 
			this.tabs.after('activeTabChange', function () {
				this.fire('resize');
			}, this);
			
			this.tabs.render(placeholder);
			
			//Render form inside "General" tab
			this.form.render(this.tabs.getTabContent('general'));
			this.form.get('boundingBox').addClass(Y.ClassNameManager.getClassName('form', 'settings'));
			
			//On title change update
			var input = this.form.getInput('title');
			function onTitleChange (event) {
				SU.Manager.Page.setPageTitle(this.getValue());
			}
			input.on('keyup', onTitleChange, input);
			input.on('reset', onTitleChange, input);
			
			//Show content
			setTimeout(this.bind(function () {
				this.getPlaceHolder().removeClass('hidden');
				this.fire('resize');
			}), 1);
		},
		
		/**
		 * Hide
		 */
		restore: function () {
			console.log('RESTORE');
			this.tabs.hide();
			this.fire('resize');
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.tabs.show();
			this.fire('resize');
		}
	});
	
});