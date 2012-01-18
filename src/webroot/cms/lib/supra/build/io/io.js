//Invoke strict mode
"use strict";

YUI().add("supra.io", function (Y) {
	
	var ERROR_INVALID_RESPONSE = 'Error occured, please try again later';
	
	Supra.io = function (url, cfg, context) {
		var cfg = cfg || {},
			fn = null,
			io = null,
			fn_success = function () {};
		
		//Clone args object to make sure it's unchanged
		var args = [url, cfg, context];
		if (args[1] && !SU.Y.Lang.isFunction(args[1])) {
			//Only if cfg is not callback function
			args[1] = Supra.mix({}, args[1]);
			if (args[1].on) {
				args[1].on = {
					'success': args[1].on.success,
					'failure': args[1].on.failure,
					'complete': args[1].on.complete
				}
			}
		}
		
		//Second parameter is allowed to be callback function
		if (Y.Lang.isFunction(cfg)) {
			fn_success = cfg;
			cfg = {};
		}
		
		var cfg_default = {
			'type': 'json',
			'data': null,
			'sync': false,
			'context': null,
			'suppress_errors': false,
			'on': {
				'success': fn_success,
				'failure': null,
				'complete': null
			}
		};
		
		if (context) {
			cfg_default.context = context;
		}
		
		//Save context and remove from config to avoid traversing them
		context = cfg.context || cfg_default.context;
		cfg.context = cfg_default.context = null;
		
		//Use cfg_default and Y.mix to make sure properties for cfg exist
		cfg = Y.mix(cfg, cfg_default, false, null, 0, true);
		
		//Restore context
		cfg.context = cfg_default.context = context;
		
		//Success and failure methods are overwritten, save references to originals
		cfg.on._success = cfg.on.success;
		cfg.on._failure = cfg.on.failure;
		cfg.on._complete = cfg.on.complete;
		cfg.on.complete = null;
		
		//Add session id to data
		if (!('data' in cfg) || !Y.Lang.isObject(cfg.data)) {
			cfg.data = {};
		}
		
		var sid_name = SU.data.get('sessionName', null),
			sid_id = SU.data.get('sessionId', null);
			
		if (sid_name && sid_id) {
			cfg.data[sid_name] = sid_id;
		}
		
		//Convert object into string compatible with PHP
		if ('data' in cfg && Y.Lang.isObject(cfg.data)) {
			cfg.data = Supra.io.serializeIntoString(cfg.data);
		}
		
		//Set callbacks
		cfg.on.success = function (transaction, response) {

			var response = Supra.io.parseResponse(url, cfg, response.responseText);
			return Supra.io.handleResponse(cfg, response);

		};
		
		cfg.on.failure = function (transaction, response) {

			if (response.status == 401) {
				//Authentication error, session expired
				Y.log('Session expired', 'info');
				
				var pre_filter_message = response.getResponseHeader('X-Authentication-Pre-Filter-Message');
				
				//If there is authentication message then this was login request
				//which shouldn't be queued
				if (!pre_filter_message) {
					Supra.io.loginRequestQueue.add(args);
				}
				
				return Supra.io.handleResponse(cfg, {
					'status': response.status,
					'success': false,
					'data': null,
					'error_message': pre_filter_message
				});
				
			} else {
				//Invalid response
				Y.log('Request to "' + url + '" failed', 'error');
				
				return Supra.io.handleResponse(cfg, {
					'status': 0,
					'success': false,
					'data': null,
					'error_message': ERROR_INVALID_RESPONSE
				});
				
			}
		};
		
		io = Y.io(url, cfg);
		return io;
	};
	
	/**
	 * Parse response and check for correct format
	 * 
	 * @param {Object} cfg Request configuration
	 * @param {String} responseText Response text
	 * @return Parsed response
	 * @type {Object}
	 * @private
	 */
	Supra.io.parseResponse = function (url, cfg, responseText) {
		var data = null,
			response = {'status': false, 'data': null};
		
		//Localization, unless in configuration skipIntl is set
		if (responseText.indexOf('{#') !== -1 && (!cfg || !cfg.skipIntl)) {
			responseText = Supra.Intl.replace(responseText, 'json');
		}
		
		try {
			switch((cfg.type || '').toLowerCase()) {
				case 'json':
					data = Y.JSON.parse(responseText);
					Supra.mix(response, data);
					break;
				case 'jsonplain':
					data = Y.JSON.parse(responseText);
					Supra.mix(response, {'status': true, 'data': data});
					break;
				default:
					response = {'status': true, 'data': responseText};
					break;
			}
			
			if (!response.status && !response.error_message) {
				//Request didn't completed successfully and there is no message,
				//show default error message
				response.error_message = ERROR_INVALID_RESPONSE;
			}
			
		} catch (e) {
			Y.log('Unable to parse "' + url + '" request response: invalid JSON', 'error');
			response.error_message = ERROR_INVALID_RESPONSE;
		}
		
		return response;
	};
	
	/**
	 * Handle response.
	 * Show error message, confirmation window and call success or failure callbacks
	 * 
	 * @param {Object} cfg Request configuration
	 * @param {Object} response Response object
	 * @private
	 */
	Supra.io.handleResponse = function (cfg, response) {
		//Show login form
		if (response.status == 401) {
			
			if (Supra.Manager) {
				Supra.Manager.executeAction('Login', response);
			}
			
			return;
		}
		
		//Show error message
		if (response.error_message && !cfg.suppress_errors) {
			SU.Manager.executeAction('Confirmation', {
			    'message': response.error_message,
			    'useMask': true,
			    'buttons': [
			        {'id': 'delete', 'label': 'Ok'}
			    ]
			});
		
		//Show warning messages
		} else if (response.warning_message && !cfg.suppress_errors) {
			var message = response.warning_message,
				single = true;
			
			if (Y.Lang.isArray(message)) {
				if (message.length > 1) {
					single = false;
					message = '{#error.warnings#}<ul><li>' + message.join('</li><li>') + '</li></ul>';
				} else {
					message = message.shift();
				}
			}
			SU.Manager.executeAction('Confirmation', {
			    'message': message,
			    'align': single ? 'center' : 'left',
			    'useMask': true,
			    'buttons': [
			        {'id': 'delete', 'label': 'Ok'}
			    ]
			});
		}
		
		//Show confirmation message
		if (response.confirmation_message) {
			SU.Manager.executeAction('Confirmation', {
			    'message': response.confirmation_message,
			    'useMask': true,
			    'buttons': [{'id': 'yes'}, {'id': 'no'}]
			});
			//@TODO
		}
		
		//Missing callbacks, ignore
		if (!cfg || !cfg.on) return null;
		
		//Call callbacks
		var fn = response.status ? cfg.on._success : cfg.on._failure;
		
		delete(cfg.on._success);
		delete(cfg.on._failure);
		delete(cfg.on.success);
		delete(cfg.on.failure);
		
		if (Y.Lang.isFunction(cfg.on._complete)) {
			cfg.on._complete.apply(cfg.context, [response.data, response.status]);
		}
		
		delete(cfg.on._complete);
		delete(cfg.on.complete);
		
		if (Y.Lang.isFunction(fn)) {
			return fn.apply(cfg.context, [response.data, response.status]);
		} else {
			return null;
		}
	};
	
	
	/**
	 * 
	 * @param {Object} obj
	 * @param {Object} prefix
	 */
	Supra.io.serialize = function (obj, prefix) {
		if (!Y.Lang.isObject(obj) && !Y.Lang.isArray(obj)) return obj;
		var o = {}, name = null;
		
		for(var i in obj) {
			if (obj.hasOwnProperty(i)) {
				name = (prefix ? prefix + '[' + encodeURIComponent(i) + ']' : encodeURIComponent(i));
				
				if (Y.Lang.isDate(obj[i])) {
					//Automatically format date to Y-m-d
					o[name] = encodeURIComponent(Y.DataType.Date.reformat(obj[i], 'raw', 'in_datetime'));
				} else if (Y.Lang.isObject(obj[i]) || Y.Lang.isArray(obj[i])) {
					Supra.mix(o, this.serialize(obj[i], name));
				} else {
					//Null or undefined shouldn't be sent to server-side, because they are received as strings
					o[name] = encodeURIComponent(obj[i] === null || obj[i] === undefined ? '' : obj[i]);
				}
			}
		}
		
		return o;
	};
	
	/**
	 * Serialize data into string
	 * 
	 * @param {Object} obj
	 * @return Serialized data
	 * @type {String}
	 */
	Supra.io.serializeIntoString = function (obj) {
		if (!Y.Lang.isObject(obj) && !Y.Lang.isArray(obj)) return obj;
		var obj = Supra.io.serialize(obj), o = [];
		
		for(var i in obj) {
			o[o.length] = i + '=' + obj[i];
		}
		
		return o.join('&');
	};
	
	/**
	 * Queue of requests which resulted in 401 responses
	 */
	Supra.io.loginRequestQueue = {
		'queue': [],
		
		/**
		 * Add request to the queue
		 * 
		 * @param {Array} args Request arguments
		 */
		'add': function (args) {
			this.queue.push(args);
		},
		
		/**
		 * Execute all requests from queue
		 */
		'run': function () {
			var queue = this.queue;
			this.queue = [];
			
			for(var i=0,ii=queue.length; i<ii; i++) {
				Supra.io.apply(Supra.io, queue[i]);
			}
		}
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["io", "json"]});