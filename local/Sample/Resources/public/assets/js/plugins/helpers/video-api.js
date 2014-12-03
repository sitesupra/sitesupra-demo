/**
 * jQuery plugin to play, pause YouTube and Vimeo videos
 * 
 * @version 1.0.1
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	// Youtube
	var includeYouTube = function () {
		if (!window['YT']) {window.YT = {};}if (!YT.Player) {(function(){var a = document.createElement('script');a.src = 'http:' + '//s.ytimg.com/yts/jsbin/www-widgetapi-vfld_DuR5.js';a.async = true;var b = document.getElementsByTagName('script')[0];b.parentNode.insertBefore(a, b);})();}
		includeYouTube = function () {};
	};
	
	// Vimeo
	var includeVimeo = function () {
		var a = document.createElement('script');a.src = 'http:' + '//a.vimeocdn.com/js/froogaloop2.min.js?657b6-1368002347';var b = document.getElementsByTagName('script')[0];b.parentNode.insertBefore(a, b);
		includeVimeo = function () {};
	};
	
	
	
	window['onYouTubeIframeAPIReady'] = function () {
		var queue = Video.youtube_queue,
			i = 0,
			ii = queue.length;
		
		Video.youtube_queue = [];
		
		for (; i<ii; i++) {
			$.video[queue[i][0]].apply($.video, queue[i][1]);
		}
	};
	
	// API
	var Video = $.video = {
		'instances': {},
		'guid': 1,
		
		'youtube_ready': false,
		'youtube_queue': [],
		
		'INVALID': 0,
		'YOUTUBE': 1,
		'VIMEO': 2,
		
		'YOUTUBE_EVENTS': {
			0: 'finish',
			1: 'play',
			2: 'pause'
		},
		
		/**
		 * Returns video instance
		 */
		'getInstance': function (node) {
			var id = node.attr('id') || node.attr('id', 'player' + (++this.guid)).attr('id'),
				type = null,
				instance = this.instances[id];
			
			if (!instance) {
				type = this.getType(node);
				if (type == this.YOUTUBE && YT.Player) {
					this.instances[id] = instance = new YT.Player(id);
				} else if (type == this.VIMEO && typeof $f != 'undefined') {
					this.instances[id] = instance = $f(node.get(0));
				}
				
			}
			
			return instance;
		},
		
		/**
		 * List for state change
		 */
		'listen': function (node, func) {
			if (node.size() > 1) {
				// Separate call for each instance
				node.each(function () {
					$.video.listen($(this), func);
				});
				return;
			}
			
			var instance = this.getInstance(node),
				type = this.getType(node),
				self = this;
			
			if (instance) {
				
				if (type == this.YOUTUBE) {
					instance.addEventListener('onStateChange', function (event) { if (self.YOUTUBE_EVENTS[event.data]) func(self.YOUTUBE_EVENTS[event.data]); });
				} else { // Vimeo
					instance.addEvent('ready', function () {
						instance.addEvent('play', function () { func('play'); });
						instance.addEvent('pause', function () { func('pause'); });
						instance.addEvent('finish', function () { func('finish'); });
					});
				}
				
			} else if (typeof YT == 'undefined' || !YT.Player) {
				// Wait till YouTube is ready
				this.youtube_queue.push(['listen', [node, func]]);
			}
		},
		
		/**
		 * Call api method on element
		 */
		'api': function (node, api_method) {
			var instance = this.getInstance(node),
				type = this.getType(node);
			
			if (instance) {
				
				if (type == this.YOUTUBE) {
					switch (api_method) {
						case 'play':
							if (instance.playVideo) {
								instance.playVideo();
							} else {
								node.get(0).contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
							}
							break;
						case 'stop':
						case 'pause':
							if (instance.stopVideo) {
								instance.stopVideo();
							} else {
								node.get(0).contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
							}
							break;
					} 
				} else { // Vimeo
					if (instance.api) {
						instance.api(api_method);
					}
				}
				
			} else if (typeof YT == 'undefined' || !YT.Player) {
				// Wait till YouTube is ready
				this.youtube_queue.push(['api', [node, api_method]]);
			}
		},
		
		/**
		 * Returns video type
		 * 
		 * @param {Object} node Iframe node
		 * @returns {Number} Video type constant
		 */
		'getType': function (node) {
			var src = node.attr('src');
			if (src.indexOf('youtube') != -1) {
				includeYouTube();
				return Video.YOUTUBE;
			} else if (src.indexOf('vimeo') != -1) {
				includeVimeo();
				return Video.VIMEO;
			} else {
				return Video.INVALID;
			}
		}
	};
	
	
	/**
	 * jQuery plugin
	 * 
	 * @param {Function|String} func Function which will be called on state change or name of api call to make 
	 */
	$.fn.videoAPI = function (func) {
		if (typeof func == 'function') {
			// Add listener for state change
			$(this).each(function () {
				$.video.listen($(this), func);
			});
		} else if (typeof func == 'string') {
			// Execute API call
			$(this).each(function () {
				$.video.api($(this), func);
			});
		}
		return this;
	};
	
	
	// requirejs
	return Video;
	
}));