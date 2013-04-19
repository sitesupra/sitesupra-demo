YUI().add('supra.htmleditor-plugin-table-mobile', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	//Regular expressions
	var REGEX_TABLE = /<table(.|\n|\r)*<\/table>/ig,
		REGEX_MOBILE_TABLE = /<table[^>]+class="?'?[^"'>]*mobile(.|\n|\r)*<\/table>/ig,
		REGEX_TABLE_START = /<table[^>]*>/i,
		REGEX_HEADINGS = /<th[^>]*>((.|\r|\n)*?)<\/th[^>]*>/ig,
		REGEX_ROWS = /<tr[^>]*>((.|\r|\n)*?)<\/tr[^>]*>/ig,
		REGEX_CELLS = /<td[^>]*>((.|\r|\n)*?)<\/td[^>]*>/ig,
		REGEX_COLSPAN = /\s+colspan="?'?(\d+)'?"?/i,
		
		CLASSNAME_EVEN = 'even',
		CLASSNAME_ODD = 'odd';
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	Supra.HTMLEditor.addPlugin('table-mobile', defaultConfiguration, {
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {},
		
		/**
		 * Process HTML and insert mobile friendly version of table
		 * Called before HTML is saved
		 * 
		 * @param {String} html
		 * @return Processed HTML
		 * @type {HTML}
		 */
		tagHTML: function (html) {
			var regex_table = REGEX_TABLE,
				regex_table_start = REGEX_TABLE_START,
				regex_rows = REGEX_ROWS,
				regex_cells = REGEX_CELLS,
				regex_colspan = REGEX_COLSPAN,
				
				classname_even = CLASSNAME_EVEN,
				classname_odd = CLASSNAME_ODD,
				
				extractHeadings = this.tagHTMLExtractHeadings;
			
			//Regex are dirty, but quick and does the job done
			html = html.replace(regex_table, function (match) {
				var html = match.match(regex_table_start)[0].replace(/<table/i, '\n<table class="mobile mobile-portrait"'),
					headings = extractHeadings(match),
					rows = match.match(regex_rows),
					cells = null,
					colspan = null,
					i = 0,
					ii = rows.length,
					k = 0,
					kk = 0,
					index = 0;
				
				for (; i<ii; i++) {
					cells = rows[i].match(regex_cells) || [];
					index = 0;
					
					for (k=0, kk=cells.length; k<kk; k++) {
						colspan = cells[k].match(regex_colspan);
						if (colspan) {
							cells[k] = cells[k].replace(colspan[0], '');
							colspan = parseInt(colspan[1], 10) || 1;
						} else {
							colspan = 1;
						}
						
						html += '<tr class="' + (i % 2 ? classname_even : classname_odd) + '">';
						html += headings[index] || '';
						html += cells[k] || '';
						html += '</tr>';
						
						index += colspan;
					}
				}
				
				return match.replace(/<table[^>]*(class="?'?[^"']*"?'?)?/i, '<table class="desktop tablet"') + html + '</table>';
			});
			
			return html;
		},
		
		/**
		 * Extract all headings from HTML
		 * 
		 * @param {String} html
		 * @return Array with all heading HTML
		 * @type {Array}
		 */
		tagHTMLExtractHeadings: function (html) {
			var regex_headings = REGEX_HEADINGS,
				regex_colspan = REGEX_COLSPAN,
				headings = [],
				heading = '',
				colspan = 1,
				matches = html.match(regex_headings),
				i = 0,
				ii = matches.length;
			
			for (; i<ii; i++) {
				heading = matches[i] || '';
				colspan = heading.match(regex_colspan);
				
				if (colspan) {
					heading = heading.replace(colspan[0], '');
					colspan = parseInt(colspan[1], 10) || 1;
				} else {
					colspan = 1;
				}
				
				headings.push(heading);
				
				if (colspan > 1) {
					for (var i=1; i<=colspan; i++) {
						headings.push('<th></th>');
					}
				}
			}
			
			return headings;
		},
		
		/**
		 * Process HTML and remove all mobile version tables
		 * Called before HTML is set
		 * 
		 * @param {String} html HTML
		 * @param {Object} data Data
		 * @return Processed HTML
		 * @type {String}
		 */
		untagHTML: function (html, data) {
			html = html.replace(REGEX_MOBILE_TABLE, '');
			return html;
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});