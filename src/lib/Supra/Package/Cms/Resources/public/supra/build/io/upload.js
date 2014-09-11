/**
 * Handles file upload process
 */
YUI.add('supra.io-upload', function (Y) {
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
		 * File which should be uploaded
		 * @type {Object}
		 */
		'file': {
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
			//Use FormData
			var fd = new FormData(),
				data = Supra.io.serialize(this.get('data') || {}),
				uri = this.get('requestUri'),
				limit = 500;	//500 MB
			
			fd.append("MAX_FILE_SIZE", limit * 1024 * 1024);
			fd.append("file", this.get('file'));
			for(var i in data) {
				fd.append(i, decodeURIComponent(data[i]));
			}
			
			var xhr = this.xhr = new XMLHttpRequest();
			
			//Progress
			if ('upload' in xhr) {
				xhr.upload.addEventListener("progress", Y.bind(this.onProgress, this), false);
			}

			//Send
			xhr.onload = Y.bind(this.onLoad, this);
			xhr.open("POST", uri);
			xhr.send(fd);
		},
		
		/**
		 * Abort file upload
		 */
		abort: function () {
			if (this.xhr) {
				this.fire('abort');
				this.fire('load', Supra.mix({'data': null}, this.get('eventData') || {}));
				this.xhr.abort();
				this.destroy();
			} else {
				this.destroy();
			}
		},
		
		/**
		 * On complete
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onLoad: function (evt) {
			var event_data = this.get('eventData') || {},
				response = Supra.io.parseResponse(this.get('requestUri'), {'type': 'json'}, this.xhr.responseText);
			
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
		 * On progress fire event
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onProgress: function (evt) {
			if (evt.lengthComputable) {
				var percentage = Math.round((evt.loaded * 100) / evt.total),
					event_data = this.get('eventData');
				
				this.fire('progress', Supra.mix({
					'total': evt.total,
					'loaded': evt.loaded,
					'percentage': percentage
				}, event_data));
			}
		},
		
		/**
		 * Handle destroy event
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onBeforeDestroy: function (evt) {
			delete(this.xhr);
		}
		
	});
	
	Supra.IOUpload = UploaderIO;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.io-upload-legacy']});