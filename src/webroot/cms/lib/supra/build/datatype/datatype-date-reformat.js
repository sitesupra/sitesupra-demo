//Invoke strict mode
"use strict";

/*
 * Add custom date format support to Y.DataType.Date.parse
 * Example:
 * 		Y.DateType.Date.parse('2001-05-22', {format: '%Y-%d-%m'})
 * 		Y.Parsers.date('2001/05/22 11:22 PM', {format: '%Y/%d/%m %H:%M %p'})
 */
YUI.add('supra.datatype-date-reformat', function(Y) {

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
