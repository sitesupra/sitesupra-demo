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
	
	//Create Action class
	new Action(Action.PluginLayoutSidebar, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'AuditLogFilters',
		
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
		
		
		
		widgets: {
			'form': null,
			'footer': null,
			'slideshow': null
		},
		
		
		
		/**
		 * @constructor
		 */
		initialize: function () {
			this.widgets.slideshow = new Supra.Slideshow({
				'srcNode': this.one('div.slideshow')
			});
			
			this.widgets.form = new Supra.Form({
				'srcNode': this.one('form')
			});
			
			this.widgets.footer = new Supra.Footer({
				'srcNode': this.one('div.footer')
			});
		},
		
		/**
		 * Render action widgets, attach event listeners
		 */
		render: function () {
			this.widgets.slideshow.render();
			this.widgets.slideshow.on('slideChange', this.onSlideChange, this);
			
			this.widgets.form.render();
			this.widgets.form.get('boundingBox').addClass('sidebar-fill');
			this.widgets.form.on('submit', this.filter, this);
			
			this.widgets.footer.render();
			this.widgets.footer.getButton('reset').on('click', this.resetFilters, this);
			
			//Control "Done" button
			this.get('controlButton').on('click', this.hide, this);
			
			//Back button
			this.get('backButton').on('click', this.widgets.slideshow.scrollBack, this.widgets.slideshow);
			
			//Load component list
			this.loadComponentInformation();
		},
		
		
		/*
		 * ------------------------------- COMPONENTS --------------------------------
		 */
		
		
		/**
		 * Load component information
		 * 
		 * @private
		 */
		loadComponentInformation: function () {
			Supra.io(this.getDataPath('components'), this.setComponentInformation, this);
		},
		
		/**
		 * Set component information
		 * 
		 * @param {Object} data Component data
		 * @private
		 */
		setComponentInformation: function (data) {
			var values = [{
				'id': '',
				'title': Supra.Intl.get(['audit', 'filter', 'all_components']) || ''
			}].concat(data);
			
			this.widgets.form.getInput('component').set('values', values);
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
				slideshow = this.widgets.slideshow;
			
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
			this.widgets.slideshow.scrollBack();
		},
		
		/*
		 * ------------------------------- API --------------------------------
		 */
		
		/**
		 * Filter
		 */
		filter: function () {
			var datagrid = Manager.getAction('AuditLog').widgets.datagrid,
				values = this.widgets.form.getValues('name');
			
			datagrid.requestParams.set(values);
			datagrid.reset();
		},
		
		/**
		 * Reset all filters
		 */
		resetFilters: function () {
			this.widgets.form.resetValues();
			this.filter();
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});