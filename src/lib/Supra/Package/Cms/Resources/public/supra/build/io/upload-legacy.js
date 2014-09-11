/**
 * Handles file upload process
 */
YUI.add('supra.io-upload-legacy', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * http://hacks.mozilla.org/category/fileapi/:
	 * 		http://hacks.mozilla.org/2011/01/how-to-develop-a-html5-image-uploader/
	 * 		http://hacks.mozilla.org/2011/03/the-shortest-image-uploader-ever/
	 */
	
	function UploaderIO (config) {
		UploaderIO.superclass.constructor.apply(this, arguments);
		this.on('destroy', this.onBeforeDestroy, this);
	}
	
	UploaderIO.NAME = 'uploader-io';
	
	UploaderIO.ATTRS = {
		/**
		 * URI where file should be uploaded
		 * @type {String}
		 */
		'requestUri': {
			value: null
		},
		
		/**
		 * Additional data which will be added to the POST body
		 * @type {Object}
		 */
		'data': {
			value: null
		},
		
		/**
		 * Additional data which will be added to event
		 * @type {Object}
		 */
		'eventData': {
			value: null
		},
		
		/**
		 * Form element
		 * @type {Object}
		 */
		'form': {
			value: null
		},
		
		/**
		 * Iframe element
		 * @type {Object}
		 */
		'iframe': {
			value: null
		}
		
	};
	
	Y.extend(UploaderIO, Y.Base, {
		
		/**
		 * XHR object
		 * @type {Object}
		 * @private
		 */
		xhr: null,
		
		/**
		 * Start file upload
		 */
		start: function () {
			var uri = this.get('requestUri'),
				
				form = this.get('form'),
				iframe = this.get('iframe'),
				input = null,
				
				limit = 500,					//file size limit
			  
				data = {
					"MAX_FILE_SIZE": limit * 1024 * 1024
				};
			
			//Add data to the form
			data = Supra.io.serialize(Supra.mix(data, this.get('data') || {}));
			
			for(var i in data) {
				input = Y.Node.create('<input type="hidden" />');
				input.setAttribute('name', i);
				input.setAttribute('value', decodeURIComponent(data[i]));
				form.append(input);
			}
			
			//Set action
			if (!uri.match(/:\/\//)) {
				uri = document.location.protocol + '//' + document.location.hostname + uri;
			}
			
			form.setAttribute('action', uri);
			
			//Send
			try {
				form.submit();
			} catch (e) {
				//Error occured
				this.fire('load', Supra.mix({'data': null}, this.get('eventData') || {}));
				return;
			}
			
			//Listeners
			iframe.once('load', this.onLoad, this);
		},
		
		/**
		 * Abort file upload
		 */
		abort: function () {
			//Not possible
		},
		
		/**
		 * On complete
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onLoad: function (evt) {
			var event_data = this.get('eventData') || {},
				response_text = this.get('iframe').getDOMNode().contentWindow.document.body.innerHTML,
				response = Supra.io.parseResponse(this.get('requestUri'), {'type': 'json'}, response_text);
			
			//Handle error message if there is one
			Supra.io.handleResponse({}, response);
			
			if (response.status && response.data) {
				this.fire('load', Supra.mix({'data': response.data}, event_data));
			} else {
				this.fire('load', Supra.mix({'data': null}, event_data));
			}
			
			//Once file is uploaded, this object becomes useless
			this.destroy();
		},
		
		/**
		 * Handle destroy event
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onBeforeDestroy: function (evt) {
			//Remove all hidden fields
			var form = this.get('form');
			form.all('input[type="hidden"]').remove();
			
			//Unset attributes
			this.set('form', null);
			this.set('iframe', null);
			this.set('data', null);
			this.set('eventData', null);
		}
		
	});
	
	Supra.IOUploadLegacy = UploaderIO;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['base', 'json']});