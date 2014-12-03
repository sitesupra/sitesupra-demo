/*
 * @version 1.0.3
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'app/ajaxform'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {

	var _super = $.app.AjaxForm.prototype;
	
	$.extend(_super, {
		
		/**
		 * Reload HTML content from the server
		 * 
		 * @param {Object} params Additional request parameters which will be sent to server. Optional
		 */
		'reload': function (params) {
			//Reload content
			this.beforeReload();
			
			var is_form = false,
				form_data = null;
			
			if (params instanceof jQuery && params.is('form')) {
				is_form = true;
				
				if (typeof FormData !== 'undefined') {
					// Modern browsers can send data easily
					var form_data = this.getFormData(params);
					
					$.ajax(this.url, {
						'cache': false,
						'type': 'POST',
						'contentType': false, // prevent jquery from adding content-type header
						'processData': false, // prevent jquery from converting data into string
						'data': form_data,
						'dataType': 'html'
					})
						.done(this.proxy(this.onReload))
						.fail(this.proxy(function () { this.disable(false); }));
					
				} else {
					// Fallback
					this.fallbackSubmit({
						'type': 'POST',
						'dataType': 'html'
					})
						.done(this.proxy(this.onReload))
					 	.fail(this.proxy(function () { this.disable(false); }));
				}
			} else {
				$.ajax(this.url, {
					'cache': false,
					'type': this.method,
					'data': params,
					'dataType': 'html'
				}).done(this.proxy(this.onReload));
			}
			
			//Prevent default behaviour
			return false;
		},
	
		/**
		 * Submit form
		 */
		'submit': function () {
			if (this.disabled) {
				return false;
			}
	
			var multipart = this.isMultiPart(),
				values = this.serialize();
			
			if (this.validate(values)) {
				if (multipart) {
					// Form has file inputs
					this.reload(this.getForm());
				} else {
					// There are no file inputs, simply submit values
					this.reload(values);
				}
				
				this.disable(true);
			}
			
			return false;
		},
		
		
		/* ------------------------ HTML5 ---------------------- */
		
		/**
		 * Returns FormData instance for form
		 * 
		 * @param {Object} form Form element
		 * @returns {Object} FormData instance
		 * @private
		 */
		'getFormData': function (form) {
			var node = form.get(0),
				form_data = null;
			
			if (typeof node.getFormData === 'function') {
				// Firefox has getFormData()
				form_data = node.getFormData();
			} else {
				form_data = new FormData();
				
				// Collect all data
				form.find('input[name], select[name], textarea[name]').each(function (i, element) {
					var input = $(element),
						name  = input.attr('name');
					
					if (input.is('input:file')) {
						var files = element.files;
						
						if (input.attr('multiple')) {
							// Multiple files per input
							var i = 0,
								ii = files.length;
							
							for (; i<ii; i++) {
								form_data.append(name, files[i]);
							}
						} else if (files.length) {
							// Single file per input
							form_data.append(name, files[0]);
						}
					} else if (input.is(':checkbox,:radio')) {
						if (input.is(':checked')) {
							form_data.append(name, input.val() || '1');
						}
					} else {
						form_data.append(name, input.val());
					}
				});
			}
			
			return form_data;
		},
		
		
		/* ------------------------ IFRAME fallback ---------------------- */
		
		
		/**
		 * Create iframe where upload form will submit to
		 * 
		 * @param {String} id Uploader ID
		 * @param {String} uri Upload uri
		 */
		'fallbackCreateIframe': function(id, uri) {
			//create frame
			var html = '<iframe id="' + id + '" name="' + id + '" style="position:absolute; top:-9999px; left:-9999px"';
			
			if(window.ActiveXObject) {
				if (typeof uri== 'boolean') {
					html += ' src="' + 'javascript:false' + '"';
				} else if (typeof uri== 'string') {
					html += ' src="' + uri + '"';
				}
			}
			html += ' />';
	
			$(html).appendTo(document.body);
			return $('#' + id).get(0);			
		},
		
		'fallbackSubmit': function(s) {		
			s = $.extend({'secureuri': false}, $.ajaxSettings, s);
			
			var id = new Date().getTime(),
				requestDone = false,
				
				form = this.getForm(),
				frameId = 'ajaxFormUploadFrame' + id,
				io = this.fallbackCreateIframe(frameId, s.secureuri),
			
				deferred = $.Deferred(),
				fake_xhr = {'abort': function () {}};
			
			// Watch for a new set of requests
			if (s.global && !$.active++) {
				$.event.trigger( "ajaxStart" );
			}
			
			// Create the request object
			var xml = {}   
			if (s.global) {
				$.event.trigger("ajaxSend", [xml, s]);
			}
			
			// Timeout checker
			if (s.timeout > 0) {
				setTimeout($.proxy(function() {
					// Check to see if the request is still happening
					if(!requestDone) {
						requestDone = this.fallbackUploadCallback(s, "timeout", xml, frameId, deferred);
					}
				}, this), s.timeout);
			}
			
			try {
				form.attr('method', s.type);
				form.attr('target', frameId);
				form.attr('encoding', 'multipart/form-data');	  			
				form.attr('enctype', 'multipart/form-data');
						
				form.unbind('submit', this.submitEvent)
					.submit()
					.on('submit', this.submitEvent);
				
			} catch(e) {
				$.event.trigger("ajaxError", [s, xml, null, e]);
				deferred.reject([s, xml, null, e]);
			}
			
			$('#' + frameId).load($.proxy(function () {
				requestDone = this.fallbackUploadCallback(s, null, xml, frameId, deferred);
			}, this));
			
			return deferred.promise(fake_xhr);
	
		},
		
		/**
		 * Handle uplaod request
		 * 
		 * @private
		 */
		'fallbackUploadCallback': function(s, isTimeout, xml, frameId, deferred) {
			var io = document.getElementById(frameId);
			try {
				if(io.contentWindow) {
					xml.responseText = io.contentWindow.document.body?io.contentWindow.document.body.innerHTML:null;
					xml.responseXML = io.contentWindow.document.XMLDocument?io.contentWindow.document.XMLDocument:io.contentWindow.document; 
				} else if(io.contentDocument) {
					xml.responseText = io.contentDocument.document.body?io.contentDocument.document.body.innerHTML:null;
					xml.responseXML = io.contentDocument.document.XMLDocument?io.contentDocument.document.XMLDocument:io.contentDocument.document;
				}
			} catch(e) {
				$.event.trigger("ajaxError", [s, xml, null, e]);
				deferred.reject([s, xml, null, e]);
			}
			
			if (xml || isTimeout == "timeout") {
				var status;
				try {
					status = isTimeout != "timeout" ? "success" : "error";
					// Make sure that the request was successful or notmodified
					if (status != "error") {
						// process the data (runs the xml through httpData regardless of callback)
						var data = this.fallbackProcessResponseData( xml, s.dataType );	
						// If a local callback was specified, fire it and pass it the data
						if (s.success) {
							s.success( data, status );
						}
						
						// Deferred
						deferred.resolve(data);
	
						// Fire the global callback
						if(s.global) {
							$.event.trigger("ajaxSuccess", [xml, s, data]);
							deferred.resolve([s, xml, status]);
						}
					} else {
						$.event.trigger("ajaxError", [s, xml, status]);
						deferred.reject([s, xml, status]);
					}
				} catch(e) {
					status = "error";
					$.event.trigger("ajaxError", [s, xml, status, e]);
					deferred.reject([s, xml, status, e]);
				}
	
				// The request was completed
				if (s.global) {
					$.event.trigger("ajaxComplete", [xml, s]);
				}
				
				// Handle the global AJAX counter
				if (s.global && ! --$.active) {
					$.event.trigger("ajaxStop");
				}
	
				// Process result
				if (s.complete) {
					s.complete(xml, status);
				}
				
				$(io).unbind();
	
				setTimeout(function() {
					try {
						$(io).remove();
					} catch(e) {
						$.event.trigger("ajaxError", [s, xml, null, e]);
						deferred.reject([s, xml, null, e]);
					}
				}, 100);
	
				xml = null;
				
				// Request is done, so we return true
				return true;
			} else {
				return false;
			}
		},
		
		/**
		 * Process request response
		 * 
		 * @param {Object} r Request response
		 * @param {String} type Response type
		 * @returns {Object} Request response data
		 * @private
		 */
		'fallbackProcessResponseData': function( r, type ) {
			var data = !type;
			data = type == "xml" || data ? r.responseXML : r.responseText;
			// If the type is "script", eval it in global context
			if ( type == "script" )
				$.globalEval( data );
			// Get the JavaScript object, if JSON is used.
			if ( type == "json" )
				eval( "data = " + data );
			// evaluate scripts within html
			/*
			if ( type == "html" )
				$("<div>").html(data).evalScripts();
			*/
			
			return data;
		},
		
		
		/* ------------------------ Helpers ---------------------- */
		
		
		/**
		 * Returns true if form has files
		 * 
		 * @returns {Boolean} True if form has file inputs, otherwise false
		 * @private
		 */
		'isMultiPart': function () {
			var form = this.getForm();
			return !!form.find('input[type="file"]').size();
		}
		
	});
	
	return $.app.AjaxForm;

}));