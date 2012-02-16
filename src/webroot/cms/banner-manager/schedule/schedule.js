//Invoke strict mode
"use strict";

SU('supra.calendar', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	
	//Calendar dates
	var DEFAULT_DATES = [
		{
			'date': Y.DataType.Date.format(new Date(), Supra.Calendar.INTERNAL_DATE_FORMAT),
			'title': Supra.Intl.get(['schedule_sidebar', 'select_today'])
		},
		{
			'date': Y.DataType.Date.format(new Date(+new Date() + 86400000), Supra.Calendar.INTERNAL_DATE_FORMAT),
			'title': Supra.Intl.get(['schedule_sidebar', 'select_tomorrow'])
		}
	];
	
	//Add as right bar child
	Manager.getAction('LayoutRightContainer').addChildAction('Schedule');
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Schedule',
		
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
		 * Calendar from date
		 * @type {Object}
		 * @private
		 */
		calendarFrom: null,
		
		/**
		 * Calendar to date
		 * @type {Object}
		 * @private
		 */
		calendarTo: null,
		
		/**
		 * Callback function
		 * @type {Function}
		 * @private
		 */
		callback: null,
		
		/**
		 * Data
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		
		/**
		 * Render widgets and add event listeners
		 */
		render: function () {
			this.on('visibleChange', function (evt) {
				if (evt.newVal != evt.prevVal) {
					if (evt.newVal) {
						this.one().removeClass('hidden');
					} else {
						this.one().addClass('hidden');
					}
				}
			}, this);
			
			//Buttons
			var buttons = this.all('button');
			
			this.button_close = new Supra.Button({'srcNode': buttons.filter('.button-save').item(0), 'style': 'small-blue'});
			this.button_close.render();
			this.button_close.on('click', this.close, this);
			
			this.button_remove = new Supra.Button({'srcNode': buttons.filter('.button-remove-schedule').item(0), 'style': 'small'});
			this.button_remove.render();
			this.button_remove.on('click', this.cancel, this);
			
			//Create calendars
			this.calendarFrom = new Supra.Calendar({
				'srcNode': this.one('.calendar-from'),
				'date': new Date(),
				'dates': DEFAULT_DATES
			});
			this.calendarFrom.render();
			
			this.calendarFrom.on('dateChange', function (e) {
				this.calendarTo.set('minDate', e.newVal);
				this.calendarTo.syncUI();
			}, this);
			
			this.calendarTo = new Supra.Calendar({
				'srcNode': this.one('.calendar-to'),
				'date': new Date(),
				'minDate': new Date(),
				'dates': DEFAULT_DATES
			});
			this.calendarTo.render();
			
		},
		
		/**
		 * Close sidebar and save values
		 */
		close: function () {
			//Callback
			if (Y.Lang.isFunction(this.callback)) {
				var data = this.getData();
				this.callback(data);
			}
			
			//Clean up
			this.callback = null;
			this.data = null;
			this.hide();
		},
		
		/**
		 * Close sidebar and reset values
		 */
		cancel: function () {
			//Callback
			if (Y.Lang.isFunction(this.callback)) {
				var data = this.getData();
				this.callback({
					'from': '',
					'to': ''
				});
			}
			
			//Clean up
			this.callback = null;
			this.data = null;
			this.hide();
		},
		
		/**
		 * Set calendar data and update UI
		 * 
		 * @param {Object} data
		 * @private
		 */
		setData: function (data) {
			if (!data || !data.from) {
				data = {
					'from': new Date(),
					'to': new Date()
				};
			}
			
			this.data = data;
			
			this.calendarFrom.set('noAnimations', true);
			this.calendarTo.set('noAnimations', true);
			
			this.calendarFrom.set('date', data.from);
			this.calendarFrom.set('displayDate', data.from);
			
			this.calendarTo.set('minDate', data.from);
			this.calendarTo.set('date', data.to);
			this.calendarTo.set('displayDate', data.to);
			
			this.calendarFrom.set('noAnimations', false);
			this.calendarTo.set('noAnimations', false);
		},
		
		getData: function () {
			return {
				'from': this.calendarFrom.get('date'),
				'to': this.calendarTo.get('date')
			};
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			//Hide action
			Manager.getAction('LayoutRightContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function (data, callback) {
			Manager.getAction('LayoutRightContainer').setActiveAction(this.NAME);
			
			this.setData(data || {});
			this.callback = callback;
		}
	});
	
});