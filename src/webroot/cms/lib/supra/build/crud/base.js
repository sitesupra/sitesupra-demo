YUI.add('supra.crud-base', function (Y) {
	//Invoke strict mode
	"use strict";
	
	
	var CRUD_LIST_VIEW = '\
			<div class="view-controls">\
				{% if attributes.locale %}\
					<div class="locale-selector"></div>\
				{% endif %}\
			</div>\
			<div class="view-content"></div>';
	
	var CRUD_FORM_VIEW = '\
			<div class="view-controls">\
				<button class="button-back" data-style="small-gray" data-action="back">{{ "buttons.back"|intl }}</button>\
				<div class="align-right">\
					<button data-style="small-blue" data-action="save">{{ "buttons.save"|intl }}</button>\
				</div>\
			</div>\
			<div class="view-content view-form">\
				<form>\
					{% if ui_edit.html %}\
						{{ ui_edit.html }}\
					{% endif %}\
				</form>\
			</div>';
	
	
	/**
	 * Crud base is a widget, which handles lists, toolbar, form, etc.
	 */
	function Base (config) {
		this.view = 'list';
		this.widgets = {
			'slideshow': null
		};
		
		Base.superclass.constructor.apply(this, arguments);
	}
	
	Base.NAME = 'crud';
	Base.CSS_PREFIX = 'su-' + Base.NAME;
	Base.CLASS_NAME = 'su-' + Base.NAME;
	
	Base.ATTRS = {
		// Crud manager id
		'providerId': {
			value: null
		},
		
		// Loading state
		'loading': {
			value: false
		},
		
		// Standalone, don't show "Close" button
		'standalone': {
			value: true
		},
		
		/*
		 * Configuration for Crud manager
		 * If not specified then will be loaded
		 */
		'configuration': {
			value: null
		}
	};
	
	
	Y.extend(Base, Y.Widget, {
		
		// We don't need content node
		CONTENT_TEMPLATE: null,
		
		
		/**
		 * Current view, either list or form
		 * @type {String}
		 * @private
		 */
		view: 'list',
		
		/**
		 * List of sub-widgets
		 * @type {Object}
		 * @private
		 */
		widgets: null,
		
		
		renderUI: function () {
			var slideshow;
			
			// Creat slideshow
			this.widgets.slideshow = slideshow = new Supra.Slideshow({
				'animationUnitType': '%'
			});
			slideshow.render(this.get('contentBox'));
			// List scrolling handled by 
			slideshow.addSlide({'id': 'list', 'scrollable': false});
			slideshow.addSlide({'id': 'edit', 'scrollable': false});
			
			this.widgets.slideshow = slideshow;
			
			this.loadConfiguration();
		},
		
		bindUI: function () {
			this.after('loadingChange', this._uiSetLoading, this);
		},
		
		/**
		 * Create toolbar buttons
		 */
		renderToolbarUI: function () {
			var id = this.get('providerId'),
				buttons = [];
			
			if (!this.get('standalone')) {
				//Add "Close" button
				buttons.push({
					'id': 'done',
					'context': this,
					'callback': this.close
				});
			}
			
			// Each instance must have its own buttons, otherwise one instance
			// can't open another instance without breaking buttons
			Supra.Manager.getAction('PageToolbar').addActionButtons('CrudList' + id, []);
			Supra.Manager.getAction('PageButtons').addActionButtons('CrudList' + id, buttons);
			Supra.Manager.getAction('PageToolbar').addActionButtons('CrudEdit' + id, []);
			Supra.Manager.getAction('PageButtons').addActionButtons('CrudEdit' + id, []);
		},
		
		renderListUI: function () {
			Supra.Template.compile(CRUD_LIST_VIEW, 'crudListView'); // cached internally
			
			var id = this.get('providerId'),
				slide = this.widgets.slideshow.getSlide('list'),
				view = Y.Node.create(Supra.Template('crudListView', this.get('configuration').toObject())),
				controls,
				list;
			
			// Split view into controls and content
			slide.one('.su-slide-content').append(view);
			controls = slide.one('.view-controls');
			list = slide.one('.view-content');
			
			this.plug(Supra.Crud.PluginFilters, {'srcNode': controls, 'contentNode': list});
			this.plug(Supra.Crud.PluginList, {'srcNode': list});
			
			this.filters.on('filter', this.reloadList, this);
			this.list.on('edit', this.openRecord, this);
			
			Supra.Manager.getAction('PageToolbar').setActiveAction('CrudList' + id);
			Supra.Manager.getAction('PageButtons').setActiveAction('CrudList' + id);
		},
		
		renderFormUI: function () {
			Supra.Template.compile(CRUD_FORM_VIEW, 'crudFormView'); // cached internally
			
			var slide = this.widgets.slideshow.getSlide('edit'),
				view = Y.Node.create(Supra.Template('crudFormView', this.get('configuration').toObject())),
				controls,
				form;
			
			// Split view into controls and content
			slide.one('.su-slide-content').append(view);
			controls = slide.one('.view-controls');
			form = slide.one('.view-content form');
			
			this.plug(Supra.Crud.PluginEdit, {'toolbarNode': controls, 'formNode': form});
			this.edit.on('save', this.reloadList, this);
			this.edit.on('close', this.openList, this);
		},
		
		destructor: function () {
			var widgets = this.widgets,
				key;
			
			// Destroy all widgets
			for (key in widgets) {
				if (widgets[key].destroy) {
					widgets[key].destroy();
				}
			}
			
			this.widgets = null;
			
			// Remove loading icon
			if (this._loadingIcon) {
				this._loadingIcon.remove(true);
				this._loadingIcon = null;
			}
		},
		
		close: function () {
			var id = this.get('providerId');
			
			Supra.Manager.getAction('PageToolbar').unsetActiveAction('CrudList' + id);
			Supra.Manager.getAction('PageButtons').unsetActiveAction('CrudList' + id);
			
			this.hide();
			this.fire('close');
		},
		
		
		/* -------------------------- Filtering -------------------------- */
		
		
		reloadList: function (e) {
			var filters = e.filters,
				list    = this.list;
			
			list.setFilters(filters);
		},
		
		
		/* -------------------------- Editing -------------------------- */
		
		
		openRecord: function (e) {
			var id = this.get('providerId'),
				record = e.row;
			
			if (!this.edit) {
				this.renderFormUI();
			}
			
			this.edit.open(e.recordId, Supra.mix({}, e.data, e.values));
			this.widgets.slideshow.scrollTo('edit');
			
			Supra.Manager.getAction('PageToolbar').setActiveAction('CrudEdit' + id);
			Supra.Manager.getAction('PageButtons').setActiveAction('CrudEdit' + id);
		},
		
		/**
		 * Close editing and open list
		 */
		openList: function (e) {
			var id = this.get('providerId');
			Supra.Manager.getAction('PageToolbar').setActiveAction('CrudList' + id);
			Supra.Manager.getAction('PageButtons').setActiveAction('CrudList' + id);
			
			this.widgets.slideshow.scrollTo('list');
		},
		
		
		/* -------------------------- Configuration loader -------------------------- */
		
		
		/**
		 * Load configuration
		 */
		loadConfiguration: function () {
			this.set('loading', true);
			Supra.Crud.getConfiguration(this.get('providerId')).always(this._handleLoadAlways, this).done(this._handleLoadComplete, this);
		},
		
		_handleLoadAlways: function () {
			this.set('loading', false);
		},
		
		_handleLoadComplete: function (configuration) {
			this.set('configuration', configuration);
			this.renderToolbarUI();
			this.renderListUI();
		},
		
		
		/* -------------------------- UI state setters -------------------------- */
		
		
		/**
		 * Handle loading attribute 
		 */
		_uiSetLoading: function (e) {
			// If value changed or icon hasn't been rendered yet
			if (e.newVal != e.prevVal || (e.newVal && !this._loadingIcon)) {
				this.widgets.slideshow.getSlide(this.view);
				
				var icon = this._loadingIcon,
					content = this.get('boundingBox');
				
				if (!icon && e.newVal) {
					icon = this._loadingIcon = Y.Node.create('<div class="loading-icon"></div>');
					content.append(icon);
				}
				
				content.toggleClass('loading', e.newVal);
			}
		}
		
	});
	
	(Supra.Crud || (Supra.Crud = {})).Base = Base;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['widget', 'supra.crud-configuration', 'supra.crud-plugin-filters', 'supra.crud-plugin-list', 'supra.slideshow', 'supra.template']});
