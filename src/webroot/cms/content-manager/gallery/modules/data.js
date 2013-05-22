YUI.add('gallery.data', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	/**
	 * Slide data
	 */
	function GalleryData (config) {
		GalleryData.superclass.constructor.apply(this, arguments);
	}
	
	GalleryData.NAME = 'gallery-data';
	GalleryData.NS = 'data';
	
	GalleryData.ATTRS = {
		'data': {
			value: null,
			setter: '_setData',
			getter: '_getData'
		}
	};
	
	Y.extend(GalleryData, Y.Plugin.Base, {
		
		/**
		 * Data
		 * @type {Array}
		 * @private
		 */
		_data: null,
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			this._data = this._data || [];
		},
		
		/**
		 * Automatically called by Base, during destruction
		 */
		destructor: function () {
			this.resetAll();
		},
		
		/**
		 * Reset cache, clean up
		 */
		resetAll: function () {
			this.set('data', []);
		},
		
		
		/* ---------------------------- SETTERS --------------------------- */
		
		
		/**
		 * Add slide
		 * 
		 * @param {Object} data Slide data
		 * @returns {String} Slide id
		 */
		addSlide: function (slide) {
			var data = this._data,
				length = data.length,
				id = slide.id;
			
			if (!id) {
				id = slide.id = Y.guid();
			}
			
			data.push(slide);
			this.fire('add', {'data': slide});
			
			return id;
		},
		
		/**
		 * Remove slide by id
		 * 
		 * @param {String} id Slide id
		 * @returns {Object} Removed slide data
		 */
		removeSlideById: function (id) {
			var index = this.getIndexById(id);
			return index != -1 ? this.removeSlideByIndex(index) : null;
		},
		
		/**
		 * Remove slide by index
		 * 
		 * @param {Number} index Slide index
		 * @returns {Object} Removes slide data
		 */
		removeSlideByIndex: function (index) {
			var data = this._data,
				slide = null;
			
			if (index >=0 && index < data.length) {
				slide = data[index];
				data.splice(index, 1);
				this.fire('remove', {'data': slide});
			}
			
			return slide;
		},
		
		/**
		 * Change slide index
		 * 
		 * @param {Number} from Item index which to move
		 * @param {Number} to Index where to move item
		 */
		swapSlideIndex: function (from, to) {
			var data = this._data,
				item = null;
			
			if (from >= 0 && from < data.length) {
				item = data[from];
				data.splice(from, 1);
				data.splice(to, 0, item);
			}
		},
		
		/**
		 * Change/update slide data
		 * 
		 * @param {String} id Slide id
		 * @param {Object} data Slide data
		 */
		changeSlide: function (id, data) {
			if (id && data) {
				var slide = this.getSlideById(id),
					prevData = null;
				
				if (slide) {
					// Get all values which we are about to overwrite (only those values!)
					prevData = Y.mix({}, slide, true, Y.Object.keys(data));
					
					Supra.mix(slide, data);
					this.fire('update', {'id': id, 'newData': data, 'prevData': prevData});
					return true;
				}
			}
			return false;
		},
		
		
		/* ---------------------------- GETTERS --------------------------- */
		
		
		/**
		 * Returns slide data by id
		 * 
		 * @param {String} id Slide id
		 * @returns {Object} Slide data
		 */
		getSlideById: function (id) {
			var index = this.getIndexById(id);
			return index != -1 ? this.getSlideByIndex(index) : null;
		},
		
		/**
		 * Returns slide data by index
		 * 
		 * @param {Number} index Slide index
		 * @returns {Object} Slide data
		 */
		getSlideByIndex: function (index) {
			var data = this._data;
			if (data && index >=0 && index < data.length) {
				return data[index];
			} else {
				return null;
			}
		},
		
		/**
		 * Returns slide index by id
		 * 
		 * @param {String} id Slide id
		 * @returns {Number} Slide index or -1 if slide is not found
		 */
		getIndexById: function (id) {
			var data = this._data,
				i = 0,
				ii = data ? data.length : 0;
			
			for (; i<ii; i++) {
				if (data[i].id == id) return i;
			}
			
			return -1;
		},
		
		/**
		 * Returns slide count
		 * 
		 * @returns {Number} Slide count
		 */
		getSize: function () {
			var data = this._data;
			return data ? data.length : 0;
		},
		
		/**
		 * Returns new slide data
		 * 
		 * @returns {Object} New/empty slide data
		 */
		getNewSlideData: function () {
			var properties = this.get('host').options.properties,
				i = 0,
				ii = properties.length,
				data = {},
				value = null;
			
			for (; i<ii; i++) {
				value = properties[i]['default'] || properties[i].value;
				if (value) {
					data[properties[i].id] = value;
				} else {
					data[properties[i].id] = '';
				}
			}
			
			data.id = '';
			return data;
		},
		
		
		/* ---------------------------- ATTRIBUTES --------------------------- */
		
		/**
		 * Data attribute setter
		 * 
		 * @param {Object} data Data
		 * @returns {Object} New data
		 * @private
		 */
		_setData: function (data) {
			this._data = data = data || [];
			
			for (var i=0, ii=data.length; i<ii; i++) {
				// If there is no 'id' property, then add it
				if (!data[i].id) {
					data[i].id = Y.guid();
				}
			}
			
			return data;
		},
		
		/**
		 * Data attribute getter
		 * 
		 * @returns {Object} Data
		 * @private
		 */
		_getData: function () {
			return this._data;
		}
		
	});
	
	Supra.GalleryData = GalleryData;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin']});