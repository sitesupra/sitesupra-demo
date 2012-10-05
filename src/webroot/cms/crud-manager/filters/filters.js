//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(

	'supra.sildeshow',

function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	var Color = Y.DataType.Color;
	var CRUD = Supra.CRUD;
	
	//Create Action class
	new Action(Action.PluginLayoutSidebar, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Filters',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Layout container action NAME,
		 * This is PluginLayoutSidebar property
		 */
		LAYOUT_CONTAINER: 'LayoutRightContainer',
		
		
		/**
		 * List of widgets for each provider
		 * @type {Object}
		 * @private
		 */
		widgets: {},
		
		/**
		 * Current provider ID
		 * @type {String}
		 * @private
		 */
		providerId: null,
		
		
		
		/**
		 * @constructor
		 */
		initialize: function () {
			
		},
		
		/**
		 * Render action widgets, attach event listeners
		 */
		render: function () {
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			//Control "Done" button
			this.get('controlButton').on('click', this.onDone, this);
			
			//Back button
			this.get('backButton').on('click', this.onBackButton, this);
		},
		
		
		/*
		 * ------------------------------- FORMS --------------------------------
		 */
		
		/**
		 * Create provider form
		 * 
		 * @param {String} providerId Provider ID
		 * @private
		 */
		renderProviderForm: function (providerId) {
			var filters = CRUD.Providers.getProvider(providerId).filters,
				form = null,
				slideshow = null,
				footer = null;
			
			// Not all CRUD managers will have/need filters
			if (!filters) return;
			
			form = new Supra.Form({'style': 'vertical'});
			form.render(this.one('.sidebar-content'));
			form.addClass('sidebar-fill');
			
			slideshow = new Supra.Slideshow();
			slideshow.render(form.get('contentBox'));
			
			// Convert filter list into input list
			var input = null,
				key = null,
				slide = slideshow.addSlide('main').one('.su-slide-content');
			
			slide.ancestor().setAttribute('data-title', Supra.Intl.get(['crud', 'filter', 'title']));
			
			for (key in filters) {
				input = Supra.mix({
					'name': key,
					'containerNode': slide
				}, filters[key]);
				
				if (input.type === "Select") {
					// Add empty value to the list
					input.values.unshift({
						"id": "",
						"title": Supra.Intl.get(["crud", "filter", "all"])
					});
				}
				
				form.addInput(input);
			}
			
			// Footer
			footer = new Supra.Footer({
				'buttons': {
					'reset': {'id': 'reset', 'label': Supra.Intl.get(['crud', 'filter', 'reset'])},
					'save': {'id': 'save', 'label': Supra.Intl.get(['crud', 'filter', 'filter'])}
				}
			});
			footer.render(slide);
			
			this.widgets[providerId] = {
				'form': form,
				'slideshow': slideshow,
				'footer': footer
			};
		},
		
		bindProviderForm: function (providerId) {
			var widgets = this.widgets[providerId];
			
			widgets.slideshow.on('slideChange', this.onSlideChange, this);
			widgets.form.on('submit', this.filter, this);
			widgets.footer.getButton('reset').on('click', this.resetFilters, this);
			widgets.footer.getButton('save').on('click', widgets.form.submit, widgets.form);
		},
		
		/**
		 * Show provider form
		 * 
		 * @param {String} providerId Provider ID
		 * @private
		 */
		showProviderForm: function (providerId) {
			if (this.providerId == providerId) return;
			
			if (this.providerId) {
				this.widgets[this.providerId].form.hide();
			}
			
			if (!this.widgets[providerId]) {
				this.renderProviderForm(providerId);
				this.bindProviderForm(providerId);
			} else {
				this.widgets[providerId].form.show();
			}
			
			this.providerId = providerId;
		},
		
		
		/*
		 * ------------------------------- SLIDESHOW --------------------------------
		 */
		
		/**
		 * On slide change show/hide buttons and call callback function
		 * 
		 * @param {Object} evt
		 * @private
		 */
		onSlideChange: function (evt) {
			var slide_id = evt.newVal,
				slideshow = this.widgets[this.providerId].slideshow;
			
			if (slideshow.isRootSlide()) {
				this.get('backButton').hide();
			} else {
				this.get('backButton').show();
			}

			//Update header title and icon
			var node  = (slide_id ? this.one('#' + slide_id) : null);
			
			if (node) {
				var title = node.getAttribute('data-title'),
					icon  = node.getAttribute('data-icon');
				
				if (title) {
					this.set('title', title);
				}
				if (icon) {
					this.set('icon', icon);
				}
			}
		},
		
		/**
		 * On "Back" button click slide slideshow back
		 * 
		 * @private
		 */
		onBackButton: function () {
			this.widgets[this.providerId].slideshow.scrollBack();
		},
		
		/**
		 * On "Done" button click hide filters, or, if some sub-slide opened - slide back
		 *
		 * @private
		 */
		onDone: function () {
			var slideshow = this.widgets[this.providerId].slideshow;
			
			if (slideshow.isRootSlide()) {
				this.hide();
			} else {
				slideshow.scrollBack();
			}
		},
		
		
		/*
		 * ------------------------------- API --------------------------------
		 */
		
		
		/**
		 * Filter
		 */
		filter: function () {
			var datagrid = CRUD.Providers.getProvider('user').getDataGrid(),
				values = this.widgets[this.providerId].form.getValues('name');
			
			datagrid.requestParams.set(values);
			datagrid.reset();
		},
		
		/**
		 * Reset all filters
		 */
		resetFilters: function () {
			this.widgets[this.providerId].form.resetValues();
			this.filter();
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			this.show();
			this.showProviderForm(CRUD.Providers.getActiveProvider().get('id'));
		}
	});
	
});