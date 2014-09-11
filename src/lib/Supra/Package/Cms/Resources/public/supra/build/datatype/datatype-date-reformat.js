/*
 * Add custom date format support to Y.DataType.Date.parse
 * Example:
 * 		Y.DateType.Date.parse('2001-05-22', {format: '%Y-%d-%m'})
 * 		Y.Parsers.date('2001/05/22 11:22 PM', {format: '%Y/%d/%m %H:%M %p'})
 */
YUI.add('supra.datatype-date-reformat', function(Y) {
	//Invoke strict mode
	"use strict";
	
	var LANG = Y.Lang,
		Dt = Y.DataType.Date;
	
	/**
	 * Converts data to type Date.
	 * Supported formats: 'raw', 'FORMAT', 'in_date', 'in_time', 'in_time_short', 'in_datetime', 'out_date', 'out_time', 'out_time_short', 'out_datetime', 'out_datetime_short'
	 *
	 * @method parse
	 * @param data {String | Number} Data to convert
	 * @param from {String} Optional. From format, default is out_format
	 * @param to {String} Optional. To format, default is out_format
	 * @return {Date} A Date, or null.
	 */
	Dt.reformat = function(data, from, to) {
	    var date = null;
	    if (!data) return null;
	    
	    if (from == 'raw') {
	    	date = Y.Lang.isDate(data) ? data : null;
	    } else {
	    	date = Dt.parse(data, {'format': Dt.stringToFormat(from || 'out_date')});
	    }
	    
	    if (date) {
	    	if (to == 'raw') {
	    		return date;
	    	} else {
	    		return Dt.format(date, {'format': Dt.stringToFormat(to || 'out_date')});
	    	}
	    }
	    
	    return null;
	};
	
	/**
	 * Returns formatted string with time difference
	 * 
	 * @param {String} date Date
	 * @param format {String} Optional. Date format
	 * @return Date in pretty format
	 * @type {String}
	 */
	Dt.since = function (date, format, template) {
		var diff = 0;
		
		if (typeof date !== 'number') {
			date = Dt.reformat(date, format || 'in_datetime', 'raw');
			if (!date) return;
			
			diff = ~~(((+new Date()) - date.getTime()) / 1000);
		} else {
			diff = date;
		}
		 
		var day_diff = ~~(diff / 86400),
			cache = '',
			name = '',
			tpl = '',
			data = {'n': 1};
		
		if (day_diff == 0) {
			
			if (diff == 1) {
				data.n = 1;
				name = 'second';
			} else if (diff < 60) {
				data.n = ~~diff;
				name = 'seconds';
			} else if (diff < 120) {
				data.n = 1;
				name = 'minute';
			} else if (diff < 3600) {
				data.n = ~~(diff / 60);
				name = 'minutes';
			} else if (diff < 7200) {
				data.n = 1;
				name = 'hour';
			} else {
				data.n = ~~(diff / 3600);
				name = 'hours';
			}
			
		} else if (day_diff == 1) {
			data.n = 1;
			name = 'day';
		} else if (day_diff < 31) {
			data.n = day_diff;
			name = 'days';
		} else if (day_diff < 62) {
			data.n = 1;
			name = 'month';
		} else if (day_diff < 366) {
			data.n = ~~(day_diff / 31);
			name = 'months';
		} else if (day_diff < 732) {
			data.n = 1;
			name = 'year';
		} else {
			data.n = ~~(day_diff / 366);
			name = 'years';
		}
		
		tpl = Supra.Intl.get(['date', name]) || ('{{ n }} ' + name);
		
		if (template) {
			tpl = template.replace(/{{\s*n\s*}}/, tpl);
		} else {
			tpl = (Supra.Intl.get(['date', 'ago']) || '{{ n }} ago').replace(/{{\s*n\s*}}/, tpl);
		}
		
		tpl = Supra.Template.compile(tpl, 'datatype.date.since.' + name);
		
		return tpl(data);
	};
	
	Dt.stringToFormat = function (str) {
		switch(str) {
			case 'in_date':
				return '%Y-%m-%d';
			case 'in_time':
				return '%H:%M:%S';
			case 'in_time_short':
				return '%H:%M';
			case 'in_datetime':
				return '%Y-%m-%d %H:%M:%S';
			case 'in_datetime_short':
				return '%Y-%m-%d %H:%M';
			case 'out_date':
				return Supra.data.get('dateFormat');
			case 'out_time':
				return Supra.data.get('timeFormat');
			case 'out_time_short':
				return Supra.data.get('timeFormatShort');
			case 'out_datetime':
				return Supra.data.get('dateFormat') + ' ' + Supra.data.get('timeFormat');
			case 'out_datetime_short':
				return Supra.data.get('dateFormat') + ' ' + Supra.data.get('timeFormatShort');
			default:
				return str;
		}
	};

}, YUI.version, {requires:['datatype-date']});
