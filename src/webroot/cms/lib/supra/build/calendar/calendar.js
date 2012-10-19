YUI.add("supra.calendar", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var YDate = Y.DataType.Date;
	
	/**
	 * Calendar class 
	 * 
	 * @alias Supra.Calendar
	 * @param {Object} config Configuration
	 */
	function Calendar (config) {
		Calendar.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Calendar.NAME = "calendar";
	Calendar.CSS_PREFIX = 'su-' + Calendar.NAME;
	
	Calendar.ATTRS = {
		'firstWeekDay': Supra.data.get('dateFirstWeekDay'),
		
		'navigationNode': null,
		'bodyNode': null,
		'datesNode': null,
		
		/**
		 * Date
		 * Setter accepts raw and formatted date
		 * Getter returns formatted date
		 */
		'date': {
			value: new Date(),
			setter: '_setDate',
			getter: '_getDate'
		},
		
		'rawDate': {
			value: new Date()
		},
		
		/**
		 * Predefined date list
		 */
		'dates': {
			value: [],
			setter: '_setDates'
		},
		
		/**
		 * Currently visible month
		 */
		'displayDate': {
			value: null,
			setter: '_setDisplayDate'
		},
		
		/**
		 * Min date
		 */
		'minDate': {
			value: null,
			setter: '_setMinDate'
		},
		
		/**
		 * Max date
		 */
		'maxDate': {
			value: null,
			setter: '_setMaxDate'
		},
		
		/**
		 * Don't use animations
		 */
		'noAnimations': {
			value: false
		}
	};
	
	Calendar.HTML_PARSER = {
		'navigationNode': function (srcNode) {
			return srcNode.one('.' + this.getClassName('nav'));
		},
		'bodyNode': function (srcNode) {
			return srcNode.one('.' + this.getClassName('body'));
		},
		'datesNode': function (srcNode) {
			return srcNode.one('.' + this.getClassName('dates'));
		}
	};
	
	Y.extend(Calendar, Y.Widget, {
		/**
		 * Calendar animation object
		 * @type {Object}
		 */
		anim: null,
		animReverse: null,
		animDir: -1,
		
		renderUI: function () {
			var contentNode = this.get('contentBox'),
				navNode = this.get('navigationNode'),
				bodyNode = this.get('bodyNode'),
				suggestionsNode = this.get('suggestionsNode');
			
			if (!navNode) {
				navNode = Y.Node.create(
					'<div class="' + this.getClassName('nav') + '">\
						<a class="' + this.getClassName('prev') + '"></a>\
						<a class="' + this.getClassName('next') + '"></a>\
						<p></p>\
					</div>');
				
				contentNode.prepend(navNode);
				this.set('navigationNode', navNode);
				
				navNode.one('.su-calendar-prev').on('mousedown', this.goPrevMonth, this);
				navNode.one('.su-calendar-next').on('mousedown', this.goNextMonth, this);
			}
			
			if (!bodyNode) {
				bodyNode = Y.Node.create(
					'<div class="' + this.getClassName('body') + '"></div>');
				
				navNode.insert(bodyNode, 'after');
				this.set('bodyNode', bodyNode);
				
				bodyNode.delegate('click', Y.bind(this._selectDate, this), 'td');
			}
			
			if (!suggestionsNode) {
				suggestionsNode = Y.Node.create(
					'<div class="' + this.getClassName('suggestions') + '"></div>');
				
				contentNode.append(suggestionsNode);
				this.set('suggestionsNode', suggestionsNode);
			}
			
			this.set('displayDate', this.get('rawDate'));
			this.set('date', this.get('rawDate'));
			this.set('dates', this.get('dates'));
			
			//Redraw when date chagnes
			this.after('dateChange', this.syncUISelected, this);
			
			//Redraw when display date changes
			this.after('displayDateChange', this.onDisplayDateChange, this);
			
			//Create animation 
			this.anim = new Y.Anim({
				node: bodyNode,
			    duration: 0.1,
			    easing: Y.Easing.easeOutStrong,
				from: {opacity: 1, left: 0},
				to: {opacity: 0, left: -16}
			});
			this.anim.on('end', function () {
				this.syncUI();
				this.animReverse.set('from', {opacity: 0, left: this.animDir * -16});
				this.animReverse.run();
			}, this);
			
			this.animReverse = new Y.Anim({
				node: bodyNode,
			    duration: 0.1,
			    easing: Y.Easing.easeOut,
				from: {opacity: 0, left: 16},
				to: {opacity: 1, left: 0}
			});
		},
		
		onDisplayDateChange: function (e) {
			if (e.prevVal.getFullYear() != e.newVal.getFullYear() || e.prevVal.getMonth() != e.newVal.getMonth()) {
				
				if (!this.get('noAnimations')) {
					this.animDir = e.newVal.getTime() > e.prevVal.getTime() ? -1 : 1;
					this.anim.set('to', {opacity: 0, left: this.animDir * 16});
					this.anim.run();
				} else {
					this.syncUI();
					this.anim.get('node').setStyles({
						'left': 0,
						'opacity': 1
					});
				}
			}
		},
		
		renderCalendarBody: function () {
			var date = this._dateGetDateOnly(this.get('rawDate')),
				dateTime = date.getTime(),
				minDate = this.get('minDate'),
				maxDate = this.get('maxDate'),
				minDateTime = 0,
				maxDateTime = 0,
				curDate = this._dateGetDateOnly(this.get('displayDate')),
				curDateTime = 0,
				curMonth = curDate.getMonth(),
				lastDate = new Date(curDate),
				lastDateTime = null,
				firstWeekDay = parseInt(this.get('firstWeekDay'), 10) || 1,
				weekDayNames = Y.Intl.get('datatype-date-format').a,
				bodyNode = this.get('bodyNode'),
				headHTML = [],
				bodyHTML = [],
				rowHTML = [],
				k,
				classname = '';
			
			if (minDate) minDateTime = this._dateGetDateOnly(minDate).getTime();
			if (maxDate) maxDateTime = this._dateGetDateOnly(maxDate).getTime();
			
			//Set date to first which is visible in calendar (possibly last month)
			curDate.setDate(1);
			var day = firstWeekDay - curDate.getDay() + 1;
			curDate.setDate(day > 1 ? day - 7 : day);
			curDateTime = curDate.getTime();
			
			//Set date to last which is visible in calendar (possible next month)
			lastDate = new Date(curDate);
			lastDate.setDate(curDate.getDate() + 41);
			/*
			lastDate.setDate(1);
			lastDate.setMonth(lastDate.getMonth() + 1);
			
			if (lastDate.getDay() != (firstWeekDay + 6) % 7) {
				lastDate.setDate(lastDate.getDate() + (7 - lastDate.getDay() + firstWeekDay));
			}
			*/
			lastDateTime = lastDate.getTime();
			
			//Render header
			for(var i=firstWeekDay,ii=7+firstWeekDay; i<ii; i++) {
				k = i % 7;
				headHTML.push('<th>' + weekDayNames[k].toLowerCase() + '</th>');
			}
			
			//Render body
			k = 0;
			
			while(curDateTime <= lastDateTime) {
				classname = '';
				if (curDateTime == dateTime) {
					classname += ' selected';
				}
				
				if ((minDateTime && minDateTime > curDateTime) || (maxDateTime && maxDateTime < curDateTime)) {
					classname += ' disabled';
				}
				if (curMonth != curDate.getMonth()) {
					classname += ' out';
				}
				
				rowHTML.push('<td data-date="' + YDate.reformat(curDate, 'raw', 'in_date') + '"' + (classname ? ' class="' + classname + '"' : '') + '><span>' + curDate.getDate() + '</span></td>');
				
				k++;
				if (k == 7) {
					k = 0;
					bodyHTML.push('<tr>' + rowHTML.join('') + '</tr>');
					rowHTML = [];
				}
				
				curDate.setDate(curDate.getDate()+1);
				curDateTime = curDate.getTime();
			}
			
			if (rowHTML.length) bodyHTML.push('<tr>' + rowHTML.join('') + '</tr>');
			
			bodyNode.set('innerHTML', '<table><thead><tr>' + headHTML.join('') + '</tr></thead><tbody>' + bodyHTML.join('') + '</tbody></table>');
		},
		
		syncUI: function () {
			var date = this.get('displayDate');
			
			//Set navigation month
			var monthName = YDate.format(date, {format: '%B %Y'});
			this.get('navigationNode').one('p').set('innerHTML', monthName);
			
			this.renderCalendarBody();
		},
		
		syncUISelected: function () {
			var bodyNode = this.get('bodyNode'),
				nodeSelected = bodyNode.one('.selected'),
				date = YDate.reformat(this.get('rawDate'), 'raw', 'in_date');
			
			//Unmark old element
			if (nodeSelected) nodeSelected.removeClass('selected')
			
			//Mark new element
			nodeSelected = bodyNode.one('td[data-date="' + date + '"]');
			if (nodeSelected) nodeSelected.addClass('selected');
		},
		
		/**
		 * Show previous month
		 */
		goPrevMonth: function (e) {
			var date = new Date(this.get('displayDate'));
			date.setMonth(date.getMonth() - 1);
			this.set('displayDate', date);
			
			if (e) e.halt();
		},
		
		/**
		 * Show next month
		 */
		goNextMonth: function (e) {
			var date = new Date(this.get('displayDate'));
			date.setMonth(date.getMonth() + 1);
			this.set('displayDate', date);
			
			if (e) e.halt();
		},
		
		/**
		 * Set selected date
		 * 
		 * @param {Event} e
		 */
		_selectDate: function (e) {
			var target = e.target;
				target = target.test('TD') ? target : target.ancestor('TD');
			
			if (target.hasClass('disabled')) return;
			
			var attr = target.getAttribute('data-date'),
				date = YDate.reformat(attr, 'in_date', 'raw');
			
			this.set('date', date);
		},
		
		/**
		 * Validate and set date
		 * 
		 * @param {Date} date
		 * @return Date
		 * @type {Date}
		 */
		_setDate: function (date) {
			var minDate = this.get('minDate'),
				maxDate = this.get('maxDate');
			
			date = date ? YDate.reformat(date, 'out_date', 'raw') || YDate.reformat(date, 'in_date', 'raw') : null;
			date = date || this.get('rawDate') || new Date();
			
			if (minDate && date.getTime() < minDate.getTime()) {
				date = new Date(minDate);
			} else if (maxDate && date.getTime() > maxDate.getTime()) {
				date = new Date(maxDate);
			}
			
			this.set('rawDate', date);
			
			return date;
		},
		
		/**
		 * Returns formatted date
		 * 
		 * @param {Date} date
		 * @return Date string
		 * @type {String}
		 */
		_getDate: function (date) {
			return YDate.reformat(date, 'raw', 'out_date');
		},
		
		/**
		 * Validate and set date
		 * 
		 * @param {Date} date
		 * @return Date
		 * @type {Date}
		 * @private
		 */
		_setDisplayDate: function (date) {
			date = date ? YDate.reformat(date, 'out_date', 'raw') || YDate.reformat(date, 'in_date', 'raw') : null;
			date = date || this.get('displayDate') || new Date();
			
			return date;
		},
		
		/**
		 * Validate and set min-date
		 * 
		 * @param {Date} date
		 * @return Date
		 * @type {Date}
		 * @private
		 */
		_setMinDate: function (minDate) {
			var date = this.get('rawDate') || new Date();
			
			minDate = minDate ? YDate.reformat(minDate, 'out_date', 'raw') || YDate.reformat(minDate, 'in_date', 'raw') : null;
			if (minDate && date.getTime() < minDate.getTime()) {
				this.set('date', new Date(minDate));
			}
			return minDate;
		},
		
		/**
		 * Validate and set max-date
		 * 
		 * @param {Date} date
		 * @return Date
		 * @type {Date}
		 * @private
		 */
		_setMaxDate: function (maxDate) {
			var date = this.get('rawDate') || new Date();
			
			maxDate = maxDate ? YDate.reformat(maxDate, 'out_date', 'raw') || YDate.reformat(maxDate, 'in_date', 'raw') : null;
			if (maxDate && date.getTime() < maxDate.getTime()) {
				this.set('date', new Date(maxDate));
			}
			return maxDate;
		},
		
		/**
		 * Removes time from date (sets to 00:00:00)
		 * 
		 * @param {Date} date
		 * @return Date object with time 00:00:00
		 * @type {Date}
		 */
		_dateGetDateOnly: function (date) {
			var d = new Date(date);
			d.setHours(0);
			d.setMinutes(0);
			d.setSeconds(0);
			d.setMilliseconds(0);
			return d;
		},
		
		/**
		 * Draw predefined date list
		 * 
		 * @param {Array} dates Date list
		 */
		_setDates: function (dates) {
			var datesNode = this.get('datesNode');
			
			if (Y.Lang.isArray(dates) && dates.length) {
				if (!datesNode) {
					datesNode = Y.Node.create('<div class="' + this.getClassName('dates') + '"></div>');
					datesNode.delegate('click', Y.bind(this._onDatesItemClick, this), 'a');
					this.set('datesNode', datesNode);
				} else {
					datesNode.all('a').remove();
				}
				
				var date = '',
					title = '';
				
				for(var i=0,ii=dates.length; i<ii; i++) {
					date = YDate.reformat(dates[i].date, 'in_date', 'in_date');
					title = Supra.Intl.replace(dates[i].title);
					
					datesNode.append(Y.Node.create('<a data-date="' + date + '">' + title + '</a>'));
				}
				
				this.get('contentBox').append(datesNode);
			} else if (datesNode) {
				datesNode.remove();
				this.set('datesNode', null);
			}
			
			return dates;
		},
		
		/**
		 * Handle click on dates item
		 */
		_onDatesItemClick: function (e) {
			var target = e.target;
			var date = target.getAttribute('data-date');
			
			date = YDate.reformat(date, 'in_date', 'raw');
			
			this.set('date', date);
			this.set('displayDate', date);
		}
		
	});
	
	
	Supra.Calendar = Calendar;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["widget", "anim", "datatype-date"]});