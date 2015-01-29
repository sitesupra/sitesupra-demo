/**
 * Gallery block
 * 
 * @version 1.0.0
 */
if (isCMSMode) {
    // There is no need for lightbox in CMS
    define(['jquery'], function () {
        $.fn.gallery = function () {};
    });
} else {
    define(['jquery', 'lib/photoswipe.min', 'lib/photoswipe-ui-default.min'], function ($, PhotoSwipe, PhotoSwipeUI_Default) {
        'use strict';
        
        // Property name under which instance of Gallery will be saved
        // inside element data
        var DATA_INSTANCE_PROPERTY = 'gallery';
        
        // PhotoSwipe HTML
        var photoSwipeElement = $('<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true"><div class="pswp__bg"></div><div class="pswp__scroll-wrap"><div class="pswp__container"><div class="pswp__item"></div><div class="pswp__item"></div><div class="pswp__item"></div></div><div class="pswp__ui pswp__ui--hidden"><div class="pswp__top-bar"><div class="pswp__counter"></div><button class="pswp__button pswp__button--close" title="Close (Esc)"></button><button class="pswp__button pswp__button--share" title="Share"></button><button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button><button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button><div class="pswp__preloader"><div class="pswp__preloader__icn"><div class="pswp__preloader__cut"><div class="pswp__preloader__donut"></div></div></div></div></div><div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap"><div class="pswp__share-tooltip"></div></div><button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)"></button><button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)"></button><div class="pswp__caption"><div class="pswp__caption__center"></div></div></div></div></div>').appendTo('body');
        
        
        /**
         * Gallery block, using PhotoSwipe library
         * 
         * @param {Object} el Container element
         */
        function Gallery (el) {
            this.el = $(el);
            this.el.on('click', 'figure a', $.proxy(this.handleItemClick, this));
        }
        Gallery.prototype = {
            
            /**
             * Open specific image by index
             *
             * @param {Number} index Image index
             */
            open: function (index) {
                var gallery = new PhotoSwipe(photoSwipeElement.get(0), PhotoSwipeUI_Default, this.findItems(), {
                    'index': index,
                    // looks nicer this way
                    'bgOpacity': 0.9,
                    // image w/h ratio most likely doesn't match full size ratio
                    // so we use opacity animation
                    'showHideOpacity': true,
                    // disable history module
                    'history': false, 
                    // disable sharing
                    'shareEl': false
                });
                
                gallery.init();
            },
            
            /**
             * Returns image data
             *
             * @returns {Array} List image of image data
             * @protected
             */
            findItems: function () {
                var elements = this.el.find('figure a');
                
                return $.map(elements, function (element, index) {
                    var link = $(element),
                        image = link.find('img'),
                        size = String(link.data('size') || '').split('x');
                    
                    return {
                        'src': link.data('index', index).attr('href'),
                        'msrc': image.attr('src'),
                        'w': parseInt(size[0], 10) || 0,
                        'h': parseInt(size[1], 10) || 0
                    };
                });
            },
            
            /**
             * On item click open lightbox
             *
             * @param {Object} e jQuery.Event
             * @protected
             */
            handleItemClick: function (e) {
                e.preventDefault();
                var index = $(e.target).data('index');
                this.open(index);
            },
            
            /**
             * Destroy gallery
             */
            destroy: function () {
                this.el = null;
            }
        };
        
        $.Gallery = Gallery;
    	
        
        /*
         * jQuery plugin
         */
        $.fn.gallery = function () {
            var options = typeof prop === 'object' ? prop : null,
    			fn = typeof prop === 'string' && typeof Gallery.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null,
    			args = fn ? Array.prototype.slice.call(arguments, 1) : null;
    		
    		return this.each(function () {
    			var element = $(this),
    				widget = element.data(DATA_INSTANCE_PROPERTY);
    			
    			if (!widget) {
    				widget = new Gallery (element, $.extend({}, element.data(), options || {}));
    				element.data(DATA_INSTANCE_PROPERTY, widget);
    			} else if (fn) {
    				widget[fn].apply(widget, args);
    			}
    		});
        };
    });
}
