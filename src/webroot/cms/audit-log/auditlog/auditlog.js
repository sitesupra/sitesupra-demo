//Invoke strict mode
"use strict";

Supra(
	
	'supra.slideshow',
	'supra.datagrid',
	'supra.datagrid-loader',
	
function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Create Action class
	new Action(Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'AuditLog',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['PageToolbar', 'PageButtons'],
		
		
		
		widgets: {
			'datagrid': null
		},
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			this.widgets.datagrid = new Supra.DataGrid({
				//Url
				'requestURI': this.getDataPath('load'),
				
				'idColumn': ['id'],
				
				'dataColumns': [
					{'id': 'id'},
					{'id': 'icon'}
				],
				
				'columns': [{
					'id': 'component',
					'title': Supra.Intl.get(['audit', 'columns', 'component']),
					'formatter': this.formatColumnComponent
				}, {
					'id': 'level',
					'title': Supra.Intl.get(['audit', 'columns', 'level']),
					'formatter': this.formatColumnLevel
				}, {
					'id': 'user',
					'title': Supra.Intl.get(['audit', 'columns', 'user'])
				}, {
					'id': 'time',
					'title': Supra.Intl.get(['audit', 'columns', 'time']),
					'formatter': this.formatColumnTime
				}, {
					'id': 'subject',
					'title': Supra.Intl.get(['audit', 'columns', 'subject']),
					'formatter': this.formatColumnSubject
				}],
				
				'srcNode': this.one('div.datagrid'),
				
				//User can't click on rows
				'clickable': false
			});
			
			this.widgets.datagrid.plug(Supra.DataGrid.LoaderPlugin, {
				'recordHeight': 40
			});
		},
		
		/**
		 * Format title column
		 * 
		 * @param {String} id Column ID
		 * @param {String} value Column content
		 * @param {Object} data Row data
		 * @private
		 */
		formatColumnComponent: function (id, value, data) {
			if (data.icon) {
				return '<span class="icon"><img src="' + Y.Escape.html(data.icon) + '" alt="" /></span><span class="text">' + Y.Escape.html(value) + '</span>'; 
			} else {
				return '<span class="icon"></span><span class="text">' + Y.Escape.html(value) + '</span>';
			}
		},
		
		/**
		 * Format level column
		 * 
		 * @param {String} id Column ID
		 * @param {String} value Column content
		 * @param {Object} data Row data
		 * @private
		 */
		formatColumnLevel: function (id, value, data) {
			return Y.Escape.html(Supra.Intl.get(['audit', 'level', value], ''));
		},
		
		/**
		 * Format subject column
		 * 
		 * @param {String} id Column ID
		 * @param {String} value Column content
		 * @param {Object} data Row data
		 * @private
		 */
		formatColumnSubject: function (id, value, data) {
			value = Y.Escape.html(value);
			
			if (data.level == 50) {
				return '<p class="fatal">' + value + '<span>' + Supra.Intl.get(['audit', 'fatal_error']) + '</span></p>';
			} else {
				return '<p>' + value + '</p>';
			}
		},
		
		/**
		 * Format time column
		 * 
		 * @param {String} id Column ID
		 * @param {String} value Column content
		 * @param {Object} data Row data
		 * @private
		 */
		formatColumnTime: function (id, value, data) {
			return Y.DataType.Date.since(value, 'in_datetime') + '<small>' + Y.DataType.Date.reformat(value, 'in_datetime', 'out_datetime_short') + '</small>';
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Add buttons to toolbar
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, [{
				'id': 'auditlog_filters',
				'type': 'toggle',
				'title': Supra.Intl.get(['audit', 'filter', 'title']),
				'icon': '/cms/lib/supra/img/toolbar/icon-filters.png',
				'action': 'AuditLogFilters'
			}]);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, []);
			
			this.widgets.datagrid.render();
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
		}
	});
	
});