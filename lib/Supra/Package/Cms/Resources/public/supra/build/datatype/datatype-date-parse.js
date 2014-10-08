/*
 * Add custom date format support to Y.DataType.Date.parse
 * Example:
 * 		Y.DateType.Date.parse('2001-05-22', {format: '%Y-%d-%m'})
 * 		Y.Parsers.date('2001/05/22 11:22 PM', {format: '%Y/%d/%m %H:%M %p'})
 */
YUI.add('supra.datatype-date-parse', function(Y) {
	//Invoke strict mode
	"use strict";
	
	var LANG = Y.Lang,
		Dt = Y.DataType.Date;
	
	var regex_d = /^\s*(\d{2})/,
		regex_e = /^\s*(\d{1,2})/,
		regex_m = /^\s*(\d{2})/,
		regex_y = /^\s*(\d{2})/,
		regex_Y = /^\s*(\d{4})/,
		regex_C = /^\s*(\d{1,2})/,
		regex_H = /^\s*(\d{2})/,
		regex_I = /^\s*(\d{2})/,
		regex_l = /^\s*(\d{1,2})/,
		regex_p = /^\s*(PM|AM)/,
		regex_P = /^\s*(pm|am)/,
		regex_k = /^\s*(\d{1,2})/,
		regex_M = /^\s*(\d{2})/,
		regex_S = /^\s*(\d{2})/,
		regex_s = /^\s*(\d+)/;
	 
	/**
	 * Formats
	 */
	Dt.reverseformats = {
		
		//Date: 03
		d: function (str, out) {
			if (!regex_d.test(str)) return null;
			var m = str.match(regex_d);
			out.date = parseInt(m[1], 10);
			return str.replace(m[0], '');
		},
		//Date: 3
		e: function (str) {
			if (!regex_e.test(str)) return null;
			var m = str.match(regex_e)[1];
			out.date = parseInt(m[1], 10);
			return str.replace(m[0], '');
		},
		
		//Month: 06
		m: function (str, out) {
			if (!regex_m.test(str)) return null;
			var m = str.match(regex_m);
			out.month = parseInt(m[1], 10) - 1;
			return str.replace(m[0], '');
		},
		
		//Year: 11
		y: function (str, out) {
			if (!regex_y.test(str)) return null;
			var m = str.match(regex_y);
			out.year = parseInt(m[1], 10) + 2000;
			return str.replace(m[0], '');
		},
		//Full year: 2011
		Y: function (str, out) {
			if (!regex_Y.test(str)) return null;
			var m = str.match(regex_Y);
			out.year = parseInt(m[1], 10);
			return str.replace(m[0], '');
		},
		//Century: 20
		C: function (str, out) {
			if (!regex_C.test(str)) return null;
			var m = str.match(regex_C);
			out.year += parseInt(m[1], 10) * 100;
			return str.replace(m[0], '');
		},
		
		//Hours: 03
		H: function (str, out) {
			if (!regex_H.test(str)) return null;
			var m = str.match(regex_H);
			out.hours = parseInt(m[1], 10);
			return str.replace(m[0], '');
		},
		//Hours: 3
		k: function (str, out) {
			if (!regex_k.test(str)) return null;
			var m = str.match(regex_k);
			out.hours = parseInt(m[1], 10);
			return str.replace(m[0], '');
		},
		//Hours: 00-12
		I: function (str, out) {
			if (!regex_I.test(str)) return null;
			var m = str.match(regex_I);
			out.hours = parseInt(m[1], 10);
			return str.replace(m[0], '');
		},
		//Hours: 0-12
		l: function (str, out) {
			if (!regex_l.test(str)) return null;
			var m = str.match(regex_l);
			out.hours = parseInt(m[1], 10);
			return str.replace(m[0], '');
		},
		
		//Hours: PM
		p: function (str, out) {
			if (!regex_p.test(str)) return null;
			var m = str.match(regex_p);
			out.hours += (m[1] == 'PM' ? 12 : 0);
			return str.replace(m[0], '');
		},
		//Hours: pm
		P: function (str, out) {
			if (!regex_p.test(str)) return null;
			var m = str.match(regex_p);
			out.hours += (m[1] == 'pm' ? 12 : 0);
			return str.replace(m[0], '');
		},
		
		//Minutes: 03
		M: function (str, out) {
			if (!regex_M.test(str)) return null;
			var m = str.match(regex_M);
			out.minutes = parseInt(m[1], 10);
			return str.replace(m[0], '');
		},
		
		//Seconds: 03
		S: function (str, out) {
			if (!regex_S.test(str)) return null;
			var m = str.match(regex_S);
			out.seconds = parseInt(m[1], 10);
			return str.replace(m[0], '');
		},
		
		//Timestamp in seconds: 1308145753
		s: function (str, out) {
			if (!regex_s.test(str)) return null;
			var m = str.match(regex_s),
				d = new Date(parseInt(m[1], 10));
			out.year = d.getFullYear();
			out.month = d.getMonth();
			out.date = d.getDate();
			out.hours = d.getHours();
			out.minutes = d.getMinutes();
			out.seconds = d.getSeconds();
			return str.replace(m[0], '');
		}
		
	};
		
	/**
	 * Converts data to type Date.
	 *
	 * @method parse
	 * @param data {String | Number} Data to convert
	 * @return {Date} A Date, or null.
	 */
	Y.namespace("Parsers").date = Dt.parse = function(data, oConfig) {
		
	    if(LANG.isDate(data)) {
	        return data;
	    } else if (typeof data === 'string' && (data.indexOf('UTC') !== -1 || data.indexOf('GMT') !== -1)) {
			// Allow simple UTC or GMT values
			var raw = new Date(data);
			if(LANG.isDate(raw)) {
				return raw;
			}
		}
	
		oConfig = oConfig || {};
		var format = oConfig.format || Y.config.dateFormat  || "%Y-%m-%d",
			aggs = Dt.aggregates,
			formats = Dt.reverseformats,
			started = false;
	
		//Replace aggregates
		for(var i in aggs) format = format.replace('%' + i, aggs[i]);
	
		//Replace formats
		var str = data.replace(/^\s+|\s$/g, ''),
			empty = new Date(0,0,0,0,0,0),
			out = {
				'year': empty.getFullYear(),
				'month': empty.getMonth(),
				'date': empty.getDate(),
				'hours': empty.getHours(),
				'minutes': empty.getMinutes(),
				'seconds': empty.getSeconds()
			};
	
		for(var i=0,ii=format.length; i<ii; i++) {
			if (format[i] == '%') {
				if (i+1 < ii && format[i+1] in formats) {
					str = formats[format[i+1]](str, out);
					//If nothing was found then date is invalid
					if (str === null) return;
				}
				i++;
			} else if (format[i] == str[0]) {
				str = str.substr(1);
				started = true;
			} else if (started) {
				//If parsing has started and unexpected char found then date is invalid
				//Try using built in parser
				return new Date(data);
			} else {
				//If parsing hasn't started yet, then ignore
				str = str.substr(1);
			}
		}
	
		//Validate
		var d = new Date(out.year, out.month, out.date, out.hours, out.minutes, out.seconds);
		if (d.getFullYear() != out.year ||
			d.getMonth() != out.month ||
			d.getDate() != out.date ||
			d.getHours() != out.hours ||
			d.getMinutes() != out.minutes ||
			d.getSeconds() != out.seconds) return null;
	
		return d;
	};

}, YUI.version, {requires:['datatype-date']});
