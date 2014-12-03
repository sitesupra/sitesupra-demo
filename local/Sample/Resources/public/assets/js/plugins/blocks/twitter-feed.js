/**
 * Twitter feed
 * 
 * @version 1.0.1
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'refresh/refresh'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {

	/*
	 *  Project: tweetTimeline
	 *  Description: Display the recent tweets of a twitter user
	 *  Author: Robert Fleischmann (Dots United GmbH)
	 *  License: MIT (http://www.opensource.org/licenses/mit-license.php)
	 */
	
	var ICON = '\
		<svg id="su1" width="32px" height="32px" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve">\
		<g id="7935ec95c421cee6d86eb22ecd12e46d"><path style="display: inline;" d="M450.523,265.38C408.868,434.426,129.16,506.139,0.5,330.649\
		c49.285,47.04,135.112,51.213,189.533-5.076c-31.92,4.672-55.153-26.668-15.928-43.59c-35.242,3.854-54.874-14.9-62.904-30.85\
		c8.25-8.637,17.382-12.688,35.022-13.842c-38.604-9.127-52.855-28.011-57.213-50.954c10.698-2.547,24.088-4.758,31.409-3.769\
		c-33.805-17.679-45.54-44.305-43.685-64.306c60.413,22.454,98.911,40.476,131.099,57.743c11.458,6.143,24.281,17.171,38.708,31.186\
		c18.376-48.649,41.096-98.752,79.98-123.624c-0.645,5.644-3.664,10.883-7.665,15.176c11.056-10.031,25.361-16.931,39.927-18.926\
		c-1.67,10.951-17.404,17.111-26.928,20.673c7.209-2.246,45.492-19.322,49.673-9.576c4.921,11.046-26.419,16.157-31.728,18.066\
		c-4.009,1.359-7.975,2.822-11.881,4.413c48.478-4.843,94.795,35.211,108.302,84.893c0.972,3.588,1.927,7.562,2.813,11.734\
		c17.73,6.606,49.888-0.327,60.229-6.702c-7.484,17.722-26.928,30.772-55.652,33.156c13.816,5.746,39.9,8.938,57.889,5.866\
		C500.11,254.566,481.786,265.629,450.523,265.38z"></path></g></svg>';
	
	var MONTHS = [' Jan', ' Feb', ' Mar', ' Apr', ' May', ' Jun', ' Jul', ' Aug', ' Sep', ' Oct', ' Nov', ' Dec'];
	
	var prettyDate = function (tdate) {
		var system_date = new Date(Date.parse(tdate));
		var user_date = new Date();
		if ($.browser.msie) {
			system_date = new Date(Date.parse(tdate.replace(/( \+)/, ' UTC$1')));
		}
		var diff = Math.floor((user_date - system_date) / 1000);
		
		// Format as string
		if (diff <= 1) {      return "just now";}
		if (diff < 20) {      return diff + " seconds ago";}
		if (diff < 40) {      return "half a minute ago";}
		if (diff < 60) {      return "less than a minute ago";}
		if (diff <= 90) {     return "one minute ago";}
		if (diff <= 3540) {   return Math.round(diff / 60) + " minutes ago";}
		if (diff <= 5400) {   return "1 hour ago";}
		if (diff <= 86400) {  return Math.round(diff / 3600) + " hours ago";}
		if (diff <= 129600) { return "1 day ago";}
		if (diff < 604800) {  return Math.round(diff / 86400) + " days ago";}
		if (diff <= 777600) { return "1 week ago";}
		
		// Format %d %m %y
		var month = system_date.getMonth();
		var out = system_date.getDate() + MONTHS[month];
		
		if (system_date.getFullYear() != user_date.getFullYear()) {
			out += ' ' + system_date.getFullYear();
		}
		
		return out;
	};
	

    var TwitterTimeline = $.twitterTimeline = function(element, options) {
        this.element  = $(element);
        this.options  = $.extend(true, {}, this.options, options || {});

        this._init();
    };

    $.extend(TwitterTimeline.prototype, {
        options: {
            ajax: {
                url: 'http://api.twitter.com/1/statuses/user_timeline.json',
                data: {
                    screen_name     : 'twitter',
                    page            : 1,
                    trim_user       : true,
                    include_rts     : false,
                    exclude_replies : true
                },
                dataType: 'jsonp'
            },
            el: 'li',
            count: 5,
            refresh: false,
            tweetTemplate : function(item) {
                return  '<li>' +
                			'<div class="icon">' + ICON + '</div>' +
                			'<p>' + 
                				TwitterTimeline.parseTweet(item.text) + 
                				'<br >' + 
                				'<a class="time" href="https://twitter.com/' + item.user.id_str + '/status/' + item.id_str + '">' + prettyDate(item.created_at) + '</a>' +
                			'</p>' +
                		'</li>';
            },
            animateAdd: function(el) {
                return el;
            },
            animateRemove: function(el) {
                el.remove();
            }
        },
        _refreshTimeout: null,
        _init: function() {
            this.reset();
        },
        
        _getStorageData: function (limited) {
        	var data = [];
        	
        	if (this._useLocalStorage === true) {
                var cache = localStorage.getItem(this._localStorageKey);
                if (cache !== null) {
                    data = JSON.parse(cache) || [];
                }
            }
            
            if (limited) {
            	data = data.splice(0, this.options.count);
            }
            
            return data;
        },
        
        _updateStorageData: function (data) {
        	if (this._useLocalStorage === true && data.length > 0) {
	        	var cache = localStorage.getItem(this._localStorageKey);
	            cache = cache !== null ? JSON.parse(cache) : [];
	            cache = data.concat(cache).splice(0, 30);
	            cache = JSON.stringify(cache);
	            try {
	                localStorage.removeItem(this._localStorageKey); // http://stackoverflow.com/questions/2603682/is-anyone-else-receiving-a-quota-exceeded-err-on-their-ipad-when-accessing-local
	                localStorage.setItem(this._localStorageKey, cache);
	            } catch (e) {
	                // local storage should not be used because of security reasons like private browsing
	                // disable for further updates
	                this._useLocalStorage = false;
	            }
	        }
        },
        
        set: function (property, value) {
        	if (property === 'screen_name') {
        		this.options.ajax.data.screen_name = value;
        		this.reset();
        	} else if (property === 'count') {
        		this.options.count = value;
        		this.redraw();
        	}
        },
        
        clear: function () {
        	this._useLocalStorage = typeof localStorage !== 'undefined' && typeof JSON !== 'undefined';
            this._localStorageKey = 'twitterTimeline_' + this.options.ajax.data.screen_name;
            this.element.empty();
        },
        
        reset: function () {
        	this._useLocalStorage = typeof localStorage !== 'undefined' && typeof JSON !== 'undefined';
            this._localStorageKey = 'twitterTimeline_' + this.options.ajax.data.screen_name;
            
            //read localStorage and draw tweets if there are cached ones
            var data = this._getStorageData(true);
            if (data.length > 0) {
            	this.redraw(data, true);
            } else {
            	//get tweets
            	this.fetch();
            }            
        },
        
        redraw: function (data, force_fetch) {
        	var data = data || this._getStorageData(true);
        	this.element.empty();
        	this.draw(data);
        	
        	if (force_fetch || data.length < this.options.count) {
        		this.fetch();
        	}
        },
        
        draw: function (data) {
        	var self = this;
        	
        	if (this._refreshTimeout) {
                clearTimeout(this._refreshTimeout);
            }
        	
        	//add new tweets
            $.each(data.reverse(), function(idx, item) {
                //get tweet html from template and prepend to list
                var tweet = self.options.tweetTemplate.call(self, item);
                self.element.prepend(self.options.animateAdd($(tweet), idx));

                //remove last tweet if the number of elements is bigger than the defined count
                var tweets = self.element.children(self.options.el);
                if (tweets.length > self.options.count) {
                    self.options.animateRemove($(tweets[self.options.count]), idx);
                }
            });

            if (typeof this.options.refresh === 'number') {
                this._refreshTimeout = setTimeout($.proxy(this.fetch, this), this.options.refresh);
            }
        },
        
        update: function(data) {
            // update localStorage
            this._updateStorageData(data);
			
			// draw items
            this.draw(data);
        },
        fetch: function(options) {
        	if (!this.options.ajax.data.screen_name) return;
        	
        	var ajax = $.extend(true, {}, this.options.ajax, options || {}),
        		data = this._getStorageData();
        	
        	if (data.length) {
        		// Last item
        		ajax.data.since_id = data[0].id_str;
        	}
        	
            var self = this,
                success = ajax.success,
                xhr = $.ajax(ajax);

            xhr.done(function (data) {
            	self.update(data);
                if ($.isFunction(success)) {
                    success.apply(this, arguments);
                }
            });
        },
        destroy: function () {
        	this.element.empty();
        	this.element.data('twitterTimeline', null);
        	this.element = null;
        	this.options = null;
        	
        	if (this._refreshTimeout) {
        		clearTimeout(this._refreshTimeout);
        	}
        }
    });

    TwitterTimeline.parseTweet = function(text) {
        text = text.replace(/(\b(https?|ftp|file):\/\/[\-A-Z0-9+&@#\/%?=~_|!:,.;]*[\-A-Z0-9+&@#\/%=~_|])/ig, function(url) {
            return '<a href="' + url + '" target="_blank">' + url + '</a>';
        });

        text = text.replace(/(^|\s)@(\w+)/g, function(u) {
            return '<a href="http://twitter.com/' + $.trim(u.replace("@","")) + '" target="_blank">' + u + '</a>';
        });

        text = text.replace(/(^|\s)#(\w+)/g, function(t) {
            return '<a href="http://search.twitter.com/search/' + $.trim(t.replace("#","%23")) + '" target="_blank">' + t + '</a>';
        });

        return text;
    };

    $.fn.twitterTimeline = function(options) {
        if (typeof options === 'string') {
            var instance = $(this).data('twitterTimeline');
            return instance[options].apply(instance, Array.prototype.slice.call(arguments, 1));
        } else {
            return this.each(function() {
                var instance = $(this).data('twitterTimeline');

                if (instance) {
                    $.extend(true, instance.options, options || {});
                } else {
                    instance = new TwitterTimeline(this, options);
                    $(this).data('twitterTimeline', instance);
                }
            });
        }
    };
    
    
    /*
	 *  TwitterFeed block implementation
	 *  Author: Vide Infra Group
	 */
	if ($.refresh) {
		
		//$.refresh implementation
		$.refresh.on('refresh/twitterFeed', function (event, info) {
			// Initialize plugin
			var element = info.target.find('.twitter-feed-list');
			
			element.twitterTimeline({
				'ajax': {
					'data': {
						'screen_name': info.target.data('account')
					}
				},
				'count': info.target.data('limit')
			});
			
			element.data('twitterFeedFunction', $.wait(function () {
				var object  = element.data('twitterTimeline'),
					account = info.target.data('account');
				
				object.set('screen_name', account);
			}, 750));
		});
		
		$.refresh.on('cleanup/twitterFeed', function (event, info) {
			// Clean up plugin
			var element = info.target.find('.twitter-feed-list'),
				object = element.data('twitterTimeline');
			
			if (object) {
				object.destroy();
			}
		});
		
		$.refresh.on('update/twitterFeed', function (event, info) {
			var element = info.target.find('.twitter-feed-list'),
				object = element.data('twitterTimeline');
			
			if (!object) return;
			
			info.target.data(info.propertyName, info.propertyValue);
			
			switch (info.propertyName) {
				case "account":
					// Follow label
					element.find('a.follow-button .label b').text(info.propertyValue);
					
					// Tweet list
					object.clear();
					var fn = element.data('twitterFeedFunction');
					if (fn) fn();
					break;
				case "limit":
					object.set('count', info.propertyValue);
					break;
			}
		});
		
	}
	
	// requirejs
	return TwitterTimeline;

}));