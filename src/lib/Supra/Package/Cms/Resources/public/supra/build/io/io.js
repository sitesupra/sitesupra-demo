YUI().add("supra.io", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var ERROR_INVALID_RESPONSE = 'Error occured, please try again later';
	
	Supra.io = function (url, cfg, permissions, callback, context) {
		var io = null;
		
		//Clone args object to make sure it's unchanged
		var args = [url, cfg, permissions, callback, context];
		
		//Check optional arguments
		var normal = Supra.io.normalizeArguments(url, cfg, permissions, callback, context);
		url         = normal[0];
		cfg         = normal[1];
		permissions = normal[2];
		callback    = normal[3];
		context     = normal[4];
		
		//Success and failure methods are overwritten, save references to originals
		cfg.on._success = cfg.on.success;
		cfg.on._failure = cfg.on.failure;
		cfg.on._complete = cfg.on.complete;
		cfg.on.complete = null;
		cfg._data = cfg.data;
		cfg._url = url;
		cfg.deferred = cfg.deferred || new Supra.Deferred();
		
		//Add session id to data
		if (!('data' in cfg) || !Y.Lang.isObject(cfg.data)) {
			cfg._data = cfg.data = {};
		}
		
		//Convert Supra.Form into data
		if (cfg.data.isInstanceOf && cfg.data.isInstanceOf('form')) {
			cfg.data = cfg.data.getSaveValues();
		}
		
		var sid_name = Supra.data.get('sessionName', null),
			sid_id = Supra.data.get('sessionId', null);
			
		if (sid_name && sid_id) {
			cfg.data[sid_name] = sid_id;
		}
		
		//Add permissions to the request
		if (cfg.permissions) {
			cfg.data = Supra.mix({
				'_check-permissions': cfg.permissions
			}, cfg.data);
			
			//Make sure Supra.Permission.request doesn't do another request
			Supra.Permission.setIsLoading(cfg.permissions)
		}
		
		//Convert object into string compatible with PHP
		cfg.data = Supra.io.serializeIntoString(cfg.data);
		
		//Set callbacks
		cfg.on.success = function (transaction, response) {
			
			if (cfg.transportMethod != 'jsonp') {
				// Second argument is data as string
				var response = Supra.io.parseResponse(url, cfg, response.responseText);
			} else {
				// First argument is data as object/array/etc
				var response = Supra.io.parseResponse(url, cfg, transaction);
			}
			return Supra.io.handleResponse(cfg, response);

		};
		cfg.on.failure = function (transaction, response) {

			if (cfg.transportMethod != 'jsonp') {
				// XHR request
				if (response.status == 401) {
					//Authentication error, session expired
					Y.log('Session expired', 'info');
					
					var pre_filter_message = response.getResponseHeader('X-Authentication-Pre-Filter-Message');
					var pre_filter_redirect_url = response.getResponseHeader('X-Authentication-Pre-Filter-Redirect');
					
					//If there is authentication message then this was login request
					//which shouldn't be queued
					if (!pre_filter_message) {
						Supra.io.loginRequestQueue.add(args);
					}
					
					return Supra.io.handleResponse(cfg, {
						'status': response.status,
						'success': false,
						'data': null,
						'error_message': pre_filter_message,
						'redirect_url': pre_filter_redirect_url
					});
					
				} else {
					//Invalid response
					Y.log('Request to "' + url + '" failed', 'debug');

					if (response.responseText) {
						
						var response = Supra.io.parseResponse(url, cfg, response.responseText);
						response.status = false;
						
						return Supra.io.handleResponse(cfg, response);
						
					} else {
						
						return Supra.io.handleResponse(cfg, {
							'status': 0,
							'success': false,
							'data': null,
							'error_message': ERROR_INVALID_RESPONSE
						});
					}
					
				}
			} else {
				// JSONP request
				Y.log('Request to "' + url + '" failed', 'debug');

				return Supra.io.handleResponse(cfg, {
					'status': 0,
					'success': false,
					'data': null,
					'error_message': ERROR_INVALID_RESPONSE
				});
			}
		};
		
		if (cfg.transportMethod === 'jsonp' && cfg.method === 'post') {
			// Trying to send jsonp POST request, currently not supported!
			// Invalid request
			Y.log('Request to "' + url + '" failed, because POST request can not be sent through JSONP', 'debug');
			
			io.supra_cfg = cfg;
			io._abort = io.abort;
			io.abort = Supra.io.abort;
			
			// Apply promise functionality to io object
			cfg.deferred.promise(io);
			
			io.rejectWith(cfg.context, [null, false]);
		} else {
			io = Y[cfg.transportMethod](url, cfg);
			
			io.supra_cfg = cfg;
			io._abort = io.abort;
			io.abort = Supra.io.abort;
			
			// Apply promise functionality to io object
			cfg.deferred.promise(io);
		}
		
		return io;
	};
	
	/**
	 * Abort request without showing an error message
	 */
	Supra.io.abort = function () {
		if (this.supra_cfg) {
			this.supra_cfg.suppress_errors = true;
		}
		this._abort();
	};
	
	/**
	 * Normalize Supra.io arguments
	 * 
	 * @return Array with normalized arguments
	 * @type {Array}
	 * @private
	 */
	Supra.io.normalizeArguments = function (url, cfg, permissions, callback, context) {
		//Check optional arguments
		if (Y.Lang.isArray(cfg)) {
			//cfg argument missing
			context = callback;
			callback = permissions;
			permissions = cfg;
			cfg = {};
		} else if (Y.Lang.isFunction(cfg)) {
			//cfg and permissions arguments missing
			callback = cfg;
			context = permissions;
			cfg = {};
			permissions = null;
		} else if (Y.Lang.isFunction(permissions)) {
			//permissions argument missing
			context = callback;
			callback = permissions;
			permissions = null;
		} else if (Y.Lang.isObject(permissions) && !Y.Lang.isArray(permissions)) {
			//permissions and callback arguments missing
			context = permissions;
			callback = null;
			permissions = null;
		} else if (Y.Lang.isObject(callback)) {
			context = callback;
			callback = null;
		}
		
		//Normalize permissions
		if (!Y.Lang.isArray(permissions)) {
			permissions = null;
		}
		
		//Configuration
		if (!Y.Lang.isObject(cfg)) {
			cfg = {};
		}
		
		var cfg_new = {
			'type': 'json',
			'data': null,
			'permissions': permissions,
			'sync': false,
			'context': context,
			'suppress_errors': false,
			'transportMethod': 'io',
			'on': {
				'success': callback,
				'failure': null,
				'complete': null
			}
		};
		
		//External request ?
		if (url.indexOf('http') == 0 && url.indexOf(document.location.protocol + '//' + document.location.hostname) == -1) {
			cfg_new.type = 'jsonplain';
			cfg_new.transportMethod = 'jsonp';
			cfg_new.method = 'get';
		}
		
		//Save context and remove from config to avoid traversing them on Supra.mix
		context = cfg.context || cfg_new.context;
		cfg.context = cfg_new.context = null;
		
		Supra.mix(cfg_new, cfg, true);
		
		//Restore context
		cfg.context = cfg_new.context = context;
		
		//Remove traversing in url, eq  /folder/folder/../something
		url = url.replace(/\/[^\/\?]+\/\.\.\//i, '/');
		
		return [url, cfg_new, permissions, callback, context];
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
		if (responseText.indexOf && responseText.indexOf('{#') !== -1 && (!cfg || !cfg.skipIntl)) {
			responseText = Supra.Intl.replace(responseText, 'json');
		}
		
		if (responseText.indexOf && responseText.indexOf('{%') !== -1) {
			responseText = Supra.Template.extractTemplates(responseText);
		}
		
		try {
			switch((cfg.type || '').toLowerCase()) {
				case 'json':
					data = typeof responseText === 'object' ? responseText : Y.JSON.parse(responseText);
					Supra.mix(response, data);
					break;
				case 'jsonplain':
					data = typeof responseText === 'object' ? responseText : Y.JSON.parse(responseText);
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
			Y.log('Unable to parse "' + url + '" request response: invalid JSON', 'debug');
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
		//Show login/password form
		if (response.status == 401) {
			
			if (response.redirect_url) {
				return this.handleRedirect(cfg, response);
			}
			
			if (Supra.Manager) {
				Supra.Manager.executeAction('Login', response);
			}
			
			return;
		}
		
		//Show error message
		if (response.error_message) {
			this.handleErrorMessage(cfg, response);
		
		//Show warning messages
		} else if (response.warning_message) {
			this.handleWarningMessage(cfg, response);
		}
		
		//Show confirmation message
		if (response.confirmation) {
			return this.handleConfirmationMessage(cfg, response);
		}
		
		//Handle permissions
		if (response.permissions) {
			Supra.Permission.add(response.permissions, cfg.permissions);
		}
		
		//Missing callbacks, ignore
		if (!cfg || !cfg.on) return null;
		
		//Call callbacks
		var fn  = response.status ? cfg.on._success : cfg.on._failure,
			ret = null,
			deferred = cfg.deferred;
		
		if (Y.Lang.isFunction(cfg.on._complete)) {
			cfg.on._complete.apply(cfg.context, [response.data, response.status]);
		}
		
		if (Y.Lang.isFunction(fn)) {
			ret = fn.apply(cfg.context, [response.data, response.status]);
		}
		
		//Deferred
		if (response.status) {
			deferred.resolveWith(cfg.context, [response.data, response.status]);
		} else {
			deferred.rejectWith(cfg.context, [response.data, response.status]);
		}
		
		delete(cfg.permissions);
		delete(cfg._data);
		delete(cfg.data);
		delete(cfg.on._success);
		delete(cfg.on._failure);
		delete(cfg.on.success);
		delete(cfg.on.failure);
		delete(cfg.on._complete);
		delete(cfg.on.complete);
		delete(cfg.deferred);
		
		return ret;
	};
	
	/**
	 * Handle error message parameter
	 * Show error message
	 * 
	 * @param {Object} request Request configuration
	 * @param {Object} response Request response
	 * @private
	 */
	Supra.io.handleErrorMessage = function (cfg, response) {
		//No error or warning messages when "suppress_errors" parameter is set
		if (cfg.suppress_errors) return;

		Supra.Manager.executeAction('Confirmation', {
		    'message': response.error_message,
		    'useMask': true,
		    'buttons': [
		        {'id': 'delete', 'label': 'OK'}
		    ]
		});
	};
	
	/**
	 * Handle warning message parameter
	 * Show warning message
	 * 
	 * @param {Object} request Request configuration
	 * @param {Object} response Request response
	 * @private
	 */
	Supra.io.handleWarningMessage = function (cfg, response) {
		//No error or warning messages when "suppress_errors" parameter is set
		if (cfg.suppress_errors) return;
		
		var message,
			single;
		
		if (Y.Lang.isArray(response.warning_message)) {
			
			message = response.warning_message;

			if(message.length == 0) {
				return;
			}
			else if(message.lenght == 1) {
				
				single = true;
				message = message.shift();
			}
			else if (message.length > 1) {
				
				single = false;
				message = '{#error.warnings#}<ul><li>' + message.join('</li><li>') + '</li></ul>';
			}
		} 
		
		Supra.Manager.executeAction('Confirmation', {
			'message': message,
			'align': single ? 'center' : 'left',
			'useMask': true,
			'buttons': [
				{'id': 'delete', 'label': 'OK'}
			]
		});
	};
	
	/**
	 * Handle confirmation message parameter
	 * Show confirmation message
	 * 
	 * @param {Object} request Request configuration
	 * @param {Object} response Request response
	 * @private
	 */
	Supra.io.handleConfirmationMessage = function (cfg, response) {
		Supra.Manager.executeAction('Confirmation', {
		    'message': response.confirmation.question,
		    'useMask': true,
		    'buttons': [
		    	{'id': 'yes', 'context': this, 'click': function () { this.handleConfirmationResult(1, cfg, response); }},
		    	{'id': 'no',  'context': this, 'click': function () { this.handleConfirmationResult(0, cfg, response); }}
		    ]
		});
	};
	
	/**
	 * Handle redirect response
	 * 
	 * @param {Object} request Request configuration
	 * @param {Object} response Request response
	 * @private
	 */
	Supra.io.handleRedirect = function (cfg, response) {
		// Note: this may not go through if user is editing page
		document.location = response.redirect_url;
	};
	
	/**
	 * On message confirm or deny send same request again and add answer to
	 * the data
	 * 
	 * @param {Number} answer Confirmation message answer
	 * @param {Object} request Request configuration
	 * @param {Object} response Request response
	 * @private
	 */
	Supra.io.handleConfirmationResult = function (answer, cfg, response) {
		
		// Wrong answer, do nothing
		if (response.confirmation.answer != null && response.confirmation.answer != answer) {
			
			//Call callbacks
			var fn  = response.status ? cfg.on._success : cfg.on._failure,
				ret = null,
				deferred = cfg.deferred;
			
			if (Y.Lang.isFunction(cfg.on._complete)) {
				cfg.on._complete.apply(cfg.context, [null, false]);
			}
			
			if (Y.Lang.isFunction(fn)) {
				ret = fn.apply(cfg.context, [null, false]);
			}
			
			//Deferred
			deferred.rejectWith(cfg.context, [null, false]);
			
			//Clean up
			delete(cfg.permissions);
			delete(cfg._data);
			delete(cfg.data);
			delete(cfg.on._success);
			delete(cfg.on._failure);
			delete(cfg.on.success);
			delete(cfg.on.failure);
			delete(cfg.on._complete);
			delete(cfg.on.complete);
			delete(cfg.deferred);
			
			return;
		}
		
		var url = cfg._url;
		
		//Restore original values
		cfg.on.success  = cfg.on._success;
		cfg.on.failure  = cfg.on._failure;
		cfg.on.complete = cfg.on._complete;
		cfg.data        = cfg._data;
		
		delete(cfg.on._success);
		delete(cfg.on._failure);
		delete(cfg.on._complete);
		delete(cfg._data);
		delete(cfg._url);
		
		//Add answer to the request
		if (!('data' in cfg) || !Y.Lang.isObject(cfg.data)) {
			cfg.data = {};
		}
		if (!('_confirmation' in cfg.data)) {
			cfg.data['_confirmation'] = {};
		}
		
		cfg.data['_confirmation'][response.confirmation.id] = answer;
		
		//Run request again
		Supra.io(url, cfg);
	};
	
	
	/**
	 * 
	 * @param {Object} obj
	 * @param {Object} prefix
	 */
	Supra.io.serialize = function (obj, prefix) {
		if (!Y.Lang.isObject(obj) && !Y.Lang.isArray(obj)) return obj;
		var o = {}, name = null;
		
		// Advanced encoding
		if (obj && typeof obj.toURIComponent === 'function') {
			obj = obj.toURIComponent();
			
			if (obj === undefined) {
				return {}; // do not convert
			}
		}
		
		for(var i in obj) {
			if (obj.hasOwnProperty(i)) {
				// Advanced encoding
				if (obj[i] && typeof obj[i].toURIComponent === 'function') {
					obj[i] = obj[i].toURIComponent();
					
					if (obj[i] === undefined) {
						continue; // do not convert
					}
				}
				
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
	
}, YUI.version, {requires: ["io", "json", "jsonp", "jsonp-url"]});