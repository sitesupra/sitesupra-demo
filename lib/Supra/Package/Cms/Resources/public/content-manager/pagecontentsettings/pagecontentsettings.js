Supra(function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.Action;
	
	var ACTION_TEMPLATE = '\
			<div class="sidebar block-settings">\
				<div class="sidebar-header">\
					<button class="button-back hidden"><p>{# buttons.back #}</p></button>\
					<img src="" class="hidden" alt="" />\
					<button type="button" class="button-control"><p>{# buttons.done #}</p></button>\
					<h2></h2>\
				</div>\
				<div class="sidebar-content has-header"></div>\
			</div>';
	
	/*
	 * Container action
	 * Used to insert form into LayoutRightContainer, automatically adjusts layout and
	 * shows / hides other LayoutRightContainer child actions when action is shown / hidden
	 */
	
	new Action(Action.PluginLayoutSidebar, {
		// Unique action name
		NAME: 'PageContentSettings',
		
		// No need for template
		HAS_TEMPLATE: false,
		
		// Load stylesheet
		HAS_STYLESHEET: false,
		
		// Layout container action NAME
		LAYOUT_CONTAINER: 'LayoutRightContainer',
		
		// Prevent PluginLayoutSidebar from managing toolbar buttons, we will do it manually
		PLUGIN_LAYOUT_SIDEBAR_MANAGE_BUTTONS: false,
		
		
		
		//Template
		template: ACTION_TEMPLATE,
		
		//Options
		options: null,
		
		// Form instance
		form: null,
		
		// Editor toolbar was visible
		open_toolbar_on_hide: [],
		
		// Options cache for forms
		optionsCache: {},
		
		// Slideshow slide change event attachment
		evt_slide_change: null,
		
		
		// Set page button visibility
		tooglePageButtons: function (toolbar_buttons, control_button) {
			var buttons = Supra.Manager.PageButtons.buttons[this.NAME];
			for(var i=0,ii=buttons.length; i<ii; i++) buttons[i].set('visible', toolbar_buttons);
			
			this.get('controlButton').set('visible', control_button);
		},
		
		// Render action container
		render: function () {
			//Create toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//"Done" button
			this.get('controlButton').on('click', function () {
				this.callback(true);
			}, this);
			
			//Handle slideshow change
			this.on('slideshowChange', this.onSlideshowChange, this);
		},
		
		/**
		 * On slideshow change bind to slide change to update title and icon
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		onSlideshowChange: function (e) {
			if (this.evt_slide_change) {
				this.evt_slide_change.detach();
				this.evt_slide_change = null;
			}
			
			if (e.newVal) {
				this.evt_slide_change = e.newVal.on('slideChange', this.onSlideChange, this);
			}
		},
		
		/**
		 * On slide change update sidebar title and icon
		 * 
		 * @param {Object} e Event facade object
		 * @private
		 */
		onSlideChange: function (e) {
			var slideshow = this.get('slideshow'),
				slide = e.newVal,
				title = '',
				icon  = '';
			
			if (slideshow && slide) {
				slide = slideshow.getSlide(slide);
				if (slide) {
					title = slide.getAttribute('data-title') || this.options.title;
					icon = slide.getAttribute('data-icon') || this.options.icon;
					
					if (title) this.set('title', title);
					if (icon) this.set('icon', icon);
				}
			}
		},
		
		/**
		 * Hide sidebar
		 * 
		 * @param {Boolean} keep_toolbar_buttons Don't hide toolbar buttons
		 */
		hide: function (options) {
			if (!this.get("visible")) return;
			Action.Base.prototype.hide.apply(this, arguments);
			
			var keepToolbarButtons = (options && options.keepToolbarButtons === true);
			
			//Hide buttons
			//Sometimes we don't want to hide buttons if sidebar is hidden only temporary
			if (!keepToolbarButtons) {
				Manager.getAction('PageToolbar').unsetActiveAction(this.options.toolbarActionName);
				Manager.getAction('PageButtons').unsetActiveAction(this.options.toolbarActionName);
			}
			
			//Hide form
			if (this.form) {
				var open_toolbar_on_hide = this.open_toolbar_on_hide.pop();
				if (!keepToolbarButtons && open_toolbar_on_hide) {
					Manager.EditorToolbar.execute();
				}
				
				this.callback();
				this.form.hide();
				this.form = null;
				this.options = null;
				this.set('slideshow', null);
			}
			
		},
		
		/**
		 * Trigger callbacks
		 * 
		 * @param {Boolean} done Trigger also done callback
		 */
		callback: function (done) {
			if (this.options) {
				var doneCallback = this.options.doneCallback,
					hideCallback = this.options.hideCallback;
				
				if (done && Y.Lang.isFunction(doneCallback)) {
					doneCallback();
				}
				if (Y.Lang.isFunction(hideCallback)) {
					hideCallback();
				}
			}
		},
		
		// Execute action
		execute: function (form, options) {
			if (this.form && this.form !== form) {
				this.callback();
				this.form.hide();
			}
			
			var cache = (form ? this.optionsCache[form.get('id')] : null) || {},
				options = this.options = Supra.mix({
					'doneCallback': null,
					'hideCallback': null,
					'hideEditorToolbar': false,
					'hideDoneButton': false,
					'toolbarActionName': this.NAME,
					
					'properties': null,		//Properties class instance
					'scrollable': false,
					'title': null,
					'icon': null, //'/cms/lib/supra/img/sidebar/icons/settings.png',
					
					'first_init': false
				}, cache, options || {});
			
			if (!options.first_init) {
				//Show buttons
				Manager.getAction('PageToolbar').setActiveAction(options.toolbarActionName);
				Manager.getAction('PageButtons').setActiveAction(options.toolbarActionName);
			}
			
			//Set form
			if (form) {
				this.form = form;
				
				// Cache options
				options.first_init = false;
				this.optionsCache[form.get('id')] = options;
				
				// Set settings sidebar as parent
				if (!form.get('parent')) {
					form.set('parent', this);
				}
				
				// Render
				if (!form.get('rendered')) {
					form.render(this.get('contentInnerNode'));
				}
				
				this.show();
				form.show();
				
				this.tooglePageButtons(!!options.doneCallback, !options.hideDoneButton);
				
				if (options.hideEditorToolbar) {
					var has_html_inputs          = options.properties.get('host').html_inputs_count,
						toolbar_currenly_visible = Manager.EditorToolbar.get('visible');
					
					//Store if editor toolbar should be shown when properties form is closed
					this.open_toolbar_on_hide.push(has_html_inputs);
					
					if (toolbar_currenly_visible) {
						Manager.EditorToolbar.hide();
					}
				} else {
					this.open_toolbar_on_hide.push(false);
				}
				
				//Scrollable
				this.set('scrollable', options.scrollable);
				
				//Title
				this.set('title', options.title || '');
				
				//Icon
				this.set('icon', options.icon);
				
				//Update slideshow position 
				var slideshow = form.get('slideshow'); 
				if (slideshow) {
					this.set('slideshow', slideshow);
					slideshow.syncUI(); 
				}
				
				// Layout
				Supra.Manager[this.LAYOUT_CONTAINER].syncLayout();
			}
		}
	});
	

});
