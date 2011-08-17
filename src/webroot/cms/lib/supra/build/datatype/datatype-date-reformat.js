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
 *
 * @method parse
 * @param data {String | Number} Data to convert
 * @return {Date} A Date, or null.
 */
Dt.reformat = function(data, from, to) {
    var date = null;
    
    if (from == 'internal') {
    	date = Dt.parse(data, {'format': '%Y-%m-%d'});
    } else if (from == 'raw') {
    	date = Y.Lang.isDate(data) ? data : null;
    } else if (from) {
    	date = Dt.parse(data, {'format': from});
    } else {
    	date = Dt.parse(data, {'format': Y.config.dateFormat}); 
    }
    
    if (date) {
    	if (to == 'internal') {
    		return Dt.format(date, {'format': '%Y-%m-%d'});
    	} else if (to == 'raw') {
    		return date;
    	} else if (to) {
    		return Dt.format(date, {'format': to});
    	} else {
    		return Dt.format(date, {'format': Y.config.dateFormat});
    	}
    }
    
    return null;
};


}, YUI.version, {requires:['datatype-date']});
