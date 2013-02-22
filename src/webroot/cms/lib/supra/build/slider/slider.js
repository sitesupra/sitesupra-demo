YUI.add('supra.slider', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Slider class 
	 * 
	 * @alias Supra.Slider
	 * @param {Object} config Configuration
	 */
	function Slider (config) {
		this.render_queue = [];
		this.history = [];
		this.slides = {};
		this.remove_on_hide = {};
		this.anim = null;
		
		Slider.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Slider.NAME = 'slider';
	Slider.CSS_PREFIX = 'su-' + Slider.NAME;
	
	Slider.ATTRS = {
		gutterSize: {
            value: 9,
            validator: Y.Lang.isNumber
        }
	};
	
	Y.extend(Slider, Y.Slider, {
		
		/**
	     * Rail template that will contain the end caps and the thumb.
	     * {placeholder}s are used for template substitution at render time.
	     *
	     * @property RAIL_TEMPLATE
	     * @type {String}
	     * @default &lt;span class="{railClass}">&lt;span class="{railMinCapClass}">&lt;/span>&lt;span class="{railMaxCapClass}">&lt;/span>&lt;/span>
	     */
	    RAIL_TEMPLATE     : '<span class="{railClass}">' +
	                            '<span class="{railLineMinClass}"></span>' +
	                            '<span class="{railLineMaxClass}"></span>' +
	                            '<span class="{railMinCapClass}"></span>' +
	                            '<span class="{railMaxCapClass}"></span>' +
	                        '</span>',
		
		/**
	     * Creates the Slider rail DOM subtree for insertion into the Slider's
	     * <code>contentBox</code>.  Override this method if you want to provide
	     * the rail element (presumably from existing markup).
	     *
	     * @method renderRail
	     * @return {Node} the rail node subtree
	     */
	    renderRail: function () {
	        var minCapClass = this.getClassName( 'rail', 'cap', this._key.minEdge ),
	            maxCapClass = this.getClassName( 'rail', 'cap', this._key.maxEdge ),
	            lineMinClass = this.getClassName( 'rail', 'line', this._key.minEdge ),
	            lineMaxClass = this.getClassName( 'rail', 'line', this._key.maxEdge );
	
	        return Y.Node.create(
	            Y.substitute( this.RAIL_TEMPLATE, {
	                railClass      : this.getClassName( 'rail' ),
	                railMinCapClass: minCapClass,
	                railMaxCapClass: maxCapClass,
	                railLineMinClass: lineMinClass,
	                railLineMaxClass: lineMaxClass
	            } ) );
	    },
	    
	    /**
	     * <p>Defaults the thumbURL attribute according to the current skin, or
	     * &quot;sam&quot; if none can be determined.  Horizontal Sliders will have
	     * their <code>thumbUrl</code> attribute set to</p>
	     * <p><code>&quot;/<em>configured</em>/<em>yu</em>i/<em>builddi</em>r/slider-base/assets/skins/sam/thumb-x.png&quot;</code></p>
	     * <p>And vertical thumbs will get</p>
	     * <p><code>&quot;/<em>configured</em>/<em>yui</em>/<em>builddir</em>/slider-base/assets/skins/sam/thumb-y.png&quot;</code></p>
	     *
	     * @method _initThumbUrl
	     * @protected
	     */
	    _initThumbUrl: function () {
	        if (!this.get('thumbUrl')) {
	            var skin = this.getSkinName() || 'supra',
	                base = Y.config.realBase || Y.config.base;
				
	            this.set('thumbUrl', '/cms/lib/supra/build/slider/assets/skins/' + skin + '/thumb-' + this.axis + '.png');
	        }
	    },
	    
	    /**
         * Positions the thumb in accordance with the translated value.
         *
         * @method _setPosition
         * @param value {Number} Value to translate to a pixel position
         * @param [options] {Object} Details object to pass to `_uiMoveThumb`
         * @protected
         */
        _setPosition: function ( value, options ) {
            var offset = this._valueToOffset( value ),
            	rail = this.rail,
            	node = null;
            
            this._uiMoveThumb( offset, options );
            
            if (rail) {
            	var gutter = this.get('gutterSize');
            	offset += 5;
            	
            	node = rail.one('.' + this.getClassName( 'rail', 'line', this._key.maxEdge ));
            	node.setStyle(this._key.minEdge, offset + 'px');
            	
            	node = rail.one('.' + this.getClassName( 'rail', 'line', this._key.minEdge ));
            	node.setStyle(this._key.dim, Math.max(0, offset - gutter) + 'px');
            }
        }
		
	});
	
	Supra.Slider = Slider;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};

}, YUI.version, {'requires': ['range-slider']});