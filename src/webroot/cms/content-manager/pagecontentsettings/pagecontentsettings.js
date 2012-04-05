//Invoke strict mode
"use strict";

Supra(function (Y) {
	
	//Shortcuts
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	var ACTION_TEMPLATE = '\
			<div class="sidebar block-settings">\
				<div class="sidebar-header">\
					<button class="button-back hidden"><p></p></button>\
					<img src="/cms/lib/supra/img/sidebar/icons/settings.png" alt="" />\
					<button type="button" class="button-control"><p>{#buttons.done#}</p></button>\
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
		
		
		
		
		//Template
		template: ACTION_TEMPLATE,
		
		//Options
		options: null,
		
		// Form instance
		form: null,
		
		// Done button callback
		callback: null,
		
		// Editor toolbar was visible
		open_toolbar_on_hide: false,
		
		// Set page button visibility
		tooglePageButtons: function (visible) {
			var buttons = SU.Manager.PageButtons.buttons[this.NAME];
			for(var i=0,ii=buttons.length; i<ii; i++) buttons[i].set('visible', visible);
			
			this.get('controlButton').get('visible', !!visible);
		},
		
		// Render action container
		render: function () {
			//Create toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//"Done" button
			this.get('controlButton').on('click', function () {
				if (Y.Lang.isFunction(this.callback)) {
					this.callback();
				}
			}, this);
		},
		
		// Hide
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Hide buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Hide form
			if (this.form) {
				if (this.open_toolbar_on_hide) {
					Manager.EditorToolbar.execute();
				}
				
				this.form.hide();
				this.form = null;
				this.callback = null;
				this.open_toolbar_on_hide = false;
			}
			
		},
		
		// Execute action
		execute: function (form, options) {
			var options = this.options = Supra.mix({
				'doneCallback': null,
				'hideEditorToolbar': false,
				
				'properties': null,		//Properties class instance
				'scrollable': false,
				'title': null,
				'icon': '/cms/lib/supra/img/sidebar/icons/settings.png'
			}, options || {});
			
			//Show buttons
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			//Set form
			if (form) {
				if (this.form) this.form.hide();
				this.form = form;
				this.callback = options.doneCallback;
				this.show();
				form.show();
				
				this.tooglePageButtons(!!options.doneCallback);
				
				if (options.hideEditorToolbar) {
					var has_html_inputs          = options.properties.get('host').html_inputs_count,
						toolbar_currenly_visible = Manager.EditorToolbar.get('visible');
					
					//Store if editor toolbar should be shown when properties form is closed
					this.open_toolbar_on_hide = has_html_inputs && toolbar_currenly_visible;
					
					if (toolbar_currenly_visible) {
						Manager.EditorToolbar.hide();
					}
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
					slideshow.syncUI(); 
				}
			}
		}
	});
	

});
