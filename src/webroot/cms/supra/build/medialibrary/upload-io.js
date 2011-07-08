//Invoke strict mode
"use strict";

/**
 * Handles file upload process
 */
YUI.add('supra.medialibrary-upload-io', function (Y) {
	
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
				data = this.get('data') || {},
				uri = this.get('requestUri');
			
			fd.append("MAX_FILE_SIZE", "100000");
			fd.append("file", this.get('file'));
			for(var i in data) fd.append(i, data[i]);
			
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
		 * On complete
		 * 
		 * @param {Event} evt
		 * @private
		 */
		onLoad: function (evt) {
			var data = null,
				event_data = this.get('eventData') || {};
			
			try {
				data = Y.JSON.parse(this.xhr.responseText);
			} catch (err) {}
			
			this.fire('load', SU.mix({'data': data}, event_data));
			
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
				
				this.fire('progress', SU.mix({
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
	
	Supra.MediaLibraryList.UploadIO = UploaderIO;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['base', 'json']});