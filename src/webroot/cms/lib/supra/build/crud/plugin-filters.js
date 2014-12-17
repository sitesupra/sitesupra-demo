YUI.add('supra.crud-plugin-filters', function (Y) {
	//Invoke strict mode
	"use strict";
	
	
	var CRUD_FILTER_VIEW = '\
			<form class="quick-filters">\
				<div class="quick-filters-search">\
					{% if ui_quickfilters.search %}\
						<input type="text" name="q" />\
					{% endif %}\
					{% if ui_filters and ui_filters.fields|length %}\
						<a class="advanced-filters-toggle">{{ "crud.filters.advanced"|intl }}</a>\
					{% endif %}\
				</div>\
			</form>\
			\
			{% if ui_filters and ui_filters.fields|length %}\
				<form class="advanced-filters">\
					{{ ui_filters.html|default("") }}\
				</form>\
			{% endif %}';
	
	
	/**
	 * Crud filters is a widget, which handles filters and filtering
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = "filters";
	Plugin.NS = "filters";
	
	Plugin.ATTRS = {
		'srcNode': {
			value: null
		},
		'contentNode': {
			value: null
		},
		'configuration': {
			value: null
		}
	};
	
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * List of sub-widgets
		 * @type {Object}
		 * @private
		 */
		widgets: null,
		
		/**
		 * Advanced filter visibility state
		 * @type {Boolean}
		 * @private
		 */
		advancedFiltersVisible: false,
		
		/**
		 * Last known filter values
		 * @type {Object}
		 * @private
		 */
		lastFilterValues: null,
		
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			var host = this.get('host'),
				configuration = host.get('configuration');
			
			this.widgets = {};
			
			if (configuration) {
				this._ready({'newVal': configuration});
			} else {
				host.once('configurationChange', this._ready, this);
			}
		},
		
		/**
		 * Teardown plugin
		 */
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
			
			// Clean up data
			this.lastFilterValues = null;
		},
		
		
		/* ------------------------------ Render ------------------------------ */
		
		
		/**
		 * Configuration has been loaded and UI can be set up
		 *
		 * @private
		 */
		_ready: function (e) {
			// Render
			var container = this.get('srcNode'),
				config    = e.newVal,
				view;
			
			this.set('configuration', config);
			
			Supra.Template.compile(CRUD_FILTER_VIEW, 'crudFilterView'); // cached internally
			view = Y.Node.create(Supra.Template('crudFilterView', config.toObject()));
			container.append(view);
			
			this._renderListQuickFilters();
			this._renderListFilters();
			
			this.lastFilterValues = this.getValues();
		},
		
		_renderListQuickFilters: function () {
			// Quick filter form
			var container = this.get('srcNode'),
				form,
				input,
				config = this.get('configuration');
			
			form = new Supra.Form({
				'srcNode': container.one('form.quick-filters'),
				'style': 'vertical',
				'inputs': config.getConfigFields('ui_quickfilters.fields')
			});
			form.render();
			this.widgets.quickFilterForm = form;
			
			// Search input
			if (config.getConfigValue('ui_quickfilters.search')) {
				input = form.getInput('q');
				input.render();
				input.addClass('search');
				input.plug(Supra.Input.String.Clear);
				
				this.widgets.searchInput = input;
			}
			
			// When filter values changes update list
			var inputs = form.getInputs(),
				key,
				filter = Supra.throttle(this._checkFilterChange, 300, this, true),
				filter_quick = Supra.throttle(this._checkFilterChange, 1, this, true);
			
			for (key in inputs) {
				inputs[key].after('change', filter_quick);
				inputs[key].after('input', filter);
			}
		},
		
		_renderListFilters: function () {
			// Filter form
			var config = this.get('host').get('configuration');
			
			if (config.getConfigValue('ui_filters.fields', []).length) {
				var container = this.get('srcNode'),
					form,
					input;
				
				form = new Supra.Form({
					'srcNode': container.one('form.advanced-filters'),
					'style': 'vertical',
					'inputs': config.getConfigFields('ui_filters.fields')
				});
				form.render();
				this.widgets.filterForm = form;
				
				container.one('.advanced-filters-toggle').on('click', this.toggle, this);
				
				// When filter input size changes update content size
				container.on('contentresize', this._contentresize, this);
				
				// When filter values changes update list
				var inputs = form.getInputs(),
					key,
					filter = Supra.throttle(this._checkAdvancedFilterChange, 300, this, true),
					filter_quick = Supra.throttle(this._checkAdvancedFilterChange, 1, this, true);
				
				for (key in inputs) {
					inputs[key].after('change', filter_quick);
					inputs[key].after('input', filter);
				}
			}
		},
		
		
		/* -------------------------- Filtering -------------------------- */
		
		
		/**
		 * Update list with filters
		 */
		_checkFilterChange: function (e) {
			var values = this.getValues(),
				prev_values = this.lastFilterValues;
			
			if (prev_values) {
				// Find if anything actually changed
				var keys = Y.Array.unique(Y.Object.keys(values).concat(Y.Object.keys(prev_values))),
					i = 0,
					ii = keys.length,
					changed = false;
				
				for (; i<ii; i++) {
					if (this._compareValues(values[keys[i]], prev_values[keys[i]])) {
						continue;
					}
					
					changed = true;
					break;
				}
				
				if (!changed) {
					this.lastFilterValues = values;
					return;
				}
			}
			
			this.lastFilterValues = values;
			this.fire('filter', {'filters': values});
		},
		
		/**
		 * Compare two values if they are similar
		 *
		 * @param {Object} _a First value
		 * @param {Object} _b Second value
		 * @returns {Boolean} True if values are similar, otherwise false
		 */
		_compareValues: function (_a, _b) {
			var a = _a ? _a : null,
				b = _b ? _b : null;
			
			if (!a && !b) return true;
			
			if (Y.Lang.isArray(a) && !a.length) a = null;
			if (Y.Lang.isArray(b) && !b.length) b = null;
			
			if (Y.Lang.isArray(a) && Y.Lang.isArray(b)) {
				// Compare arrays
				if (a.length !== b.length) return false;
				
				var i=0, ii=_a.length;
				for (; i<ii; i++) {
					if (!this._compareValues(a[i], b[i])) {
						return false;
					}
				}
				
				return true;
			} else {
				return a === b;
			}
		},
		
		/**
		 * Update list if advanced filters are visible
		 * 
		 * @private
		 */
		_checkAdvancedFilterChange: function () {
			if (this.advancedFiltersVisible) {
				this._checkFilterChange();
			}
		},
		
		/**
		 * When filter size changes update content to take remaining space
		 *
		 * @private
		 */
		_contentresize: function () {
			var controls = this.get('srcNode'),
				content  = this.get('contentNode'),
				height   = 58;
				
			if (this.advancedFiltersVisible) {
				height += this.widgets.filterForm.get('boundingBox').get('offsetHeight');
			}
			
			content.setStyle('top', height);
			
			content.all('.su-scrollable').each(function (node) {
				node.fire('contentresize');
			});
		},
		
		
		/* ------------------------------ API ------------------------------ */
		
		
		/**
		 * Show advanced filters if there are any
		 */
		expand: function () {
			this.toggle(true);
		},
		
		/**
		 * Hide advanced filters
		 */
		collapse: function () {
			this.toggle(false);
		},
		
		/**
		 * Toggle advanced filters if there are any
		 */
		toggle: function (_visible) {
			var visible;
			
			if (_visible === true || _visible === false) {
				if (this.advancedFiltersVisible === _visible) return;
				visible = _visible;
			} else {
				visible = !this.advancedFiltersVisible;
			}
			
			this.advancedFiltersVisible = visible;
			
			var height  = 58,
				
				controls = this.get('srcNode'),
				content  = this.get('contentNode'),
				
				anim;
			
			if (visible) {
				height += this.widgets.filterForm.get('boundingBox').get('offsetHeight');
			}
		
			anim = new Y.Anim({
				'node': controls,
			    'duration': 0.3,
			    'easing': Y.Easing.easeOutStrong,
				'to': {'height': height}
			});
			anim.on('end', function () {
				if (visible) {
					// Allow overflow, needed for Select input, which otherwise
					// will be cut
					controls.setStyle('overflow', 'visible');
				}
			});
			anim.run();
			
			anim = new Y.Anim({
				'node': content,
			    'duration': 0.3,
			    'easing': Y.Easing.easeOutStrong,
				'to': {'top': height}
			});
			anim.on('end', function () {
				if (visible) {
					controls.setStyle('height', 'auto');
				}
				this._contentresize();
				
				// Update list
				this._checkFilterChange();
			}, this);
			anim.run();
			
			controls.setStyle('overflow', 'hidden');
		},
		
		/**
		 * Returns filter values, if advanced filters are visible, then those values
		 * are included too
		 * 
		 * @returns {Object} Filter values
		 */
		getValues: function () {
			var values = this.widgets.quickFilterForm.getSaveValues('id');
			
			if (this.advancedFiltersVisible) {
				Supra.mix(values, this.widgets.filterForm.getSaveValues('id'));
			}
			
			return values;
		}
		
	});
	
	(Supra.Crud || (Supra.Crud = {})).PluginFilters = Plugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.template']});
