YUI.add('supra.lipsum', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Lorem ipsum text generator
	 */
	var Lipsum = {
		
		/**
		 * List of words
		 * @type {Array}
		 * @private
		 */
		LIPSUM: [
			"lorem", "ipsum", "dolor", "sit", "amet", "consectetur", "adipiscing", "elit", "ut", "aliquam", "purus", "luctus", "venenatis", "lectus", "magna", "fringilla",
			"urna", "porttitor", "rhoncus", "non", "enim", "praesent", "elementum", "facilisis", "leo", "vel", "est", "ullamcorper", "eget", "nulla", "facilisi", "etiam",
			"dignissim", "diam", "quis", "lobortis", "scelerisque", "fermentum", "dui", "faucibus", "in", "ornare", "quam", "viverra", "orci", "sagittis", "eu", "volutpat",
			"odio", "mauris", "massa", "vitae", "tortor", "condimentum", "lacinia", "eros", "donec", "ac", "tempor", "dapibus", "ultrices", "iaculis", "nunc", "sed", "augue",
			"lacus", "congue", "consequat", "felis", "et", "pellentesque", "commodo", "egestas", "phasellus", "eleifend", "pretium", "vulputate", "sapien", "nec", "malesuada",
			"bibendum", "arcu", "curabitur", "velit", "sodales", "sem", "integer", "justo", "vestibulum", "risus", "ultricies", "tristique", "aliquet", "at", "auctor", "id",
			"cursus", "metus", "mi", "posuere", "sollicitudin", "a", "semper", "duis", "tellus", "mattis", "nibh", "proin", "nisl", "habitant", "morbi", "senectus", "netus",
			"fames", "turpis", "tempus", "pharetra", "hendrerit", "gravida", "blandit", "hac", "habitasse", "platea", "dictumst", "quisque", "nisi", "suscipit", "maecenas",
			"cras", "aenean", "placerat", "tincidunt", "erat", "imperdiet", "euismod", "porta", "mollis", "nullam", "feugiat", "fusce", "suspendisse", "potenti", "vivamus",
			"dictum", "varius", "molestie", "accumsan", "neque", "convallis", "nam", "pulvinar", "laoreet", "interdum", "libero", "cum", "sociis", "natoque", "penatibus",
			"magnis", "dis", "parturient", "montes", "nascetur", "ridiculus", "mus", "ligula", "ante", "rutrum", "vehicula"
		],
	    
	    /**
	     * Generates random word
	     * 
	     * @returns {String} Random word
	     */
	    word: function () {
	    	return this.LIPSUM[this.rand(0, this.LIPSUM.length)];
	    },
		
		/**
		 * Generates number of words
		 * Options:
		 *   {Number} count Number of words to generate
		 *   {Number} variation Number of words by how many count will be increased/decreased (choosen by random)
		 *   {Boolean} capitalize If true then all words will be capitalized
		 *   {Boolean} uppercase If true then all words will be uppercase
		 * 
		 * @param {Object} options Number of words or options
		 * @returns {String} Generated string
		 */
		sentence: function (options) {
			if (typeof options === 'number') {
				options = {'count': options, 'variation': 0, 'capitalize': false, 'uppercase': false, 'punctuation': false};
			} else {
				options = Supra.mix({'count': 8, 'variation': 3, 'capitalize': false, 'uppercase': false, 'punctuation': false}, options);
			}
			
			var rand  = options.variation ? this.rand(-options.variation, options.variation) : 0, 
				count = Math.max(1, options.count + rand),
				i = 0,
				words_list = this.LIPSUM,
				words_count = words_list.length,
				word = null,
				output = [];
			
			for (; i<count; i++) {
				word = words_list[this.rand(0, words_count)];
				
				if (options.capitalize || i == 0) {
					word = word[0].toUpperCase() + word.substr(1);
				}
				if (options.uppercase) {
					word = word.toUpperCase();
				}
				
				output.push(word);
			}
			
			output = output.join(' ');
			
			if (!options.punctuation) {
				return output.replace(/\.|\,/g, '')
			} else {
				return output.replace(/\,$/, '') + '.';
			}
		},
		
		/**
		 * Generate number of sentences (default is 10 to 20) using 5 to 10 words per sentence
		 * Options:
		 *   {Number} count Number of sentences to generate
		 *   {Number} variation Number of sentences by how many count will be increased/decreased (choosen by random)
		 * 
		 * @param {Object} options Number of words or options
		 * @returns {String} Generated string
		 */
		paragraph: function (options) {
			if (typeof options === 'number') {
				options = {'count': options, 'variation': 0};
			} else {
				options = Supra.mix({'count': 15, 'variation': 5}, options);
			}
			
			var rand  = options.variation ? this.rand(-options.variation, options.variation) : 0, 
				count = Math.max(1, options.count + rand),
				i = 0,
				output = [];
			
			for (; i<count; i++) {
				output.push(this.sentence({'punctuation': true}));
			}
			
			return output.join(' ');
		},
		
		/**
		 * Generates HTML with lorem content
		 */
		html: function (options) {
			options = Supra.mix({
				'h1': false,
				'h2': true,
				'h3': true,
				'paragraph': true,
				'list': true
			}, options);
			
			var output = [];
			
			if (options.h1) {
				output.push('<h1>' + this.sentence({'count': 5, 'variation': 2}) + '</h1>');
				if (options.paragraph) output.push('<p>' + this.paragraph() + '</p>');
			}
			if (options.h2) {
				output.push('<h2>' + this.sentence({'count': 5, 'variation': 2}) + '</h2>');
				if (options.paragraph) output.push('<p>' + this.paragraph() + '</p>');
			}
			if (options.h3) {
				output.push('<h3>' + this.sentence({'count': 5, 'variation': 2}) + '</h3>');
				if (options.paragraph) output.push('<p>' + this.paragraph() + '</p>');
			}
			if (options.list) {
				output.push(
					'<ul>\n' +
					'	<li>' + this.sentence() + '</li>\n' +
					'	<li>' + this.sentence() + '</li>\n' +
					'	<li>' + this.sentence() + '</li>\n' +
					'	<li>' + this.sentence() + '</li>\n' +
					'	<li>' + this.sentence() + '</li>\n' +
					'</ul>'
				);
			}
			return output.join('\n');
		},
		
		/**
		 * Generates placeholder image
		 * 
		 * @param {Object} options Image options
		 * @returns {String} Base-64 encoded image or image url
		 */
		image: function (options) {
			// @TODO
		},
		
		/**
		 * Creates random number
		 * 
		 * @param {Number} min Minimal number
		 * @param {Number} max Maximal number
		 * @returns {Number} Random number between min and max
		 */
		rand: function (min, max) {
			min = min || 0;
			var r = Math.random();
			return Math.round(r * (max - min) + min);
		},
		
		/**
		 * Renerates random SKU number
		 * 
		 * @returns {String} SKU number
		 */
		sku: function (prefix) {
			var chr = '',
				num = '';
			
			// We are removing o, i and l, because they might be mistaken for 0 and 1
			prefix = prefix ? prefix.replace(/[^a-z0-9]/ig, '').replace(/[oil]/ig, '') : null;
			
			// 3 character string as prefix
			if (!prefix || typeof prefix !== 'string') {
				chr = 'abcdefghjkmnprstuvzqxyw';
				prefix = '';
				
				while (prefix.length < 3) {
					prefix += chr.charAt(Math.floor(Math.random() * chr.length));
				}
			}
			
			num = String(+new Date()).substr(3, 8);
			return prefix.toUpperCase() + num;
		}
		
	};
	
	Supra.Lipsum = Lipsum;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);
