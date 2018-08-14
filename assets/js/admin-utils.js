;
var wpf = {

	// This file contains a collection of utility functions.

	/**
	 * Start the engine.
	 *
	 * @since 1.0.1
	 */
	init: function() {

		wpf.bindUIActions();

		jQuery(document).ready(wpf.ready);
	},

	/**
	 * Document ready.
	 *
	 * @since 1.0.1
	 */
	ready: function() {
	},

	/**
	 * Element bindings.
	 *
	 * @since 1.0.1
	 */
	bindUIActions: function() {

		// The following items should all trigger the fieldUpdate trigger
		jQuery(document).on('wpformsFieldAdd', wpf.fieldUpdate);
		jQuery(document).on('wpformsFieldDelete', wpf.fieldUpdate);
		jQuery(document).on('wpformsFieldMove', wpf.fieldUpdate);
		jQuery(document).on('focusout', '.wpforms-field-option-row-label input', wpf.fieldUpdate);
		jQuery(document).on('wpformsFieldChoiceAdd', wpf.fieldUpdate);
		jQuery(document).on('wpformsFieldChoiceDelete', wpf.fieldUpdate);
		jQuery(document).on('wpformsFieldChoiceMove', wpf.fieldUpdate);
		jQuery(document).on('focusout', '.wpforms-field-option-row-choices input.label', wpf.fieldUpdate);
	},

	/**
	 * Trigger fired for all field update related actions.
	 *
	 * @since 1.0.1
	 */
	fieldUpdate: function() {

		var fields = wpf.getFields();
		jQuery(document).trigger('wpformsFieldUpdate', [fields] );
		// console.log('Field update detected');
	},

	/**
	 * Dynamically get the fields from the current form state.
	 *
	 * @since 1.0.1
	 * @param array allowedFields
	 * @return object
	 */
	getFields: function(allowedFields) {

		var formData       = jQuery('#wpforms-builder-form').serializeObject(),
			fields         = formData.fields,
			fieldOrder     = [],
			fieldsOrdered  = new Array(),
			fieldBlacklist = ['html','divider','pagebreak'];

		if (!fields) {
			return false;
		}
				
		// Find and store the order of forms. The order is lost when javascript
		// serilizes the form.
		jQuery('.wpforms-field-option').each(function(index, ele) {
			fieldOrder.push(jQuery(ele).data('field-id'));
		});

		// Remove fields that are not supported and check for white list
		jQuery.each(fields, function(index, ele) {
			if (ele) {
				if (jQuery.inArray(fields[index].type, fieldBlacklist) == '1' ){
					delete fields[index];
					wpf.removeArrayItem(fieldOrder, index);
				} else if (typeof allowedFields !== 'undefined' && allowedFields && allowedFields.constructor === Array) {
					if (jQuery.inArray(fields[index].type, allowedFields) == '-1' ){
						delete fields[index];
						wpf.removeArrayItem(fieldOrder, index);
					}
				}
			}
		});

		// Preserve the order of field choices 
		for(var key in fields) {
			if (fields[key].choices) {
				jQuery('#wpforms-field-option-row-'+fields[key].id+'-choices li').each(function(index, ele) {
					var choiceKey = jQuery(ele).data('key');
					fields[key].choices['choice_'+choiceKey] = fields[key].choices[choiceKey];
					fields[key].choices['choice_'+choiceKey].key = choiceKey;
					delete fields[key].choices[choiceKey];
				});
			}
		}

		// Preserve the order of fields 
		for(var key in fieldOrder) {
			fieldsOrdered['field_'+fieldOrder[key]] = fields[fieldOrder[key]];
		}

		return fieldsOrdered;
	},

	/**
	 * Toggle the loading state/indicator of a field option.
	 *
	 * @since 1.2.8
	 */
	fieldOptionLoading: function(option, unload) {

		var $option = jQuery(option),
			$label  = $option.find('label'),
			unload  = (typeof unload === 'undefined') ? false : true,
			spinner = '<i class="fa fa-spinner fa-spin wpforms-loading-inline"></i>';

		if (unload) {
			$label.find('.wpforms-loading-inline').remove();
			$label.find('.wpforms-help-tooltip').show();
			$option.find('input,select,textarea').prop('disabled', false);			
		} else {
			$label.append(spinner);
			$label.find('.wpforms-help-tooltip').hide();
			$option.find('input,select,textarea').prop('disabled', true);
		}
	},

	/**
	 * todo: get a single field
	 *
	 * @since 1.1.10
	 * @param {[type]} id
	 * @param {[type]} key
	 * @return {[type]}
	 */
	getField: function(id,key) {
		// @todo
	},

	// hasField @todo

	/**
	 * Remove items from an array.
	 *
	 * @since 1.0.1
	 * @param array array
	 * @param mixed item index/key
	 * @return array
	 */
	removeArrayItem: function(array, item) {
		var removeCounter = 0;
		for (var index = 0; index < array.length; index++) {
			if (array[index] === item) {
				array.splice(index, 1);
				removeCounter++;
			index--;
			}
		}
		return removeCounter;
	},

	/**
	 * Sanitize string.
	 *
	 * @since 1.0.1
	 * @deprecated 1.2.8
	 */
	sanitizeString: function(str) {
		
		return str.trim();
	},

	/**
	 * Update query string in URL.
	 *
	 * @since 1.0.0
	 */
	updateQueryString: function(key, value, url) {

		if (!url) url = window.location.href;
		var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
			hash;

		if (re.test(url)) {
			if (typeof value !== 'undefined' && value !== null)
				return url.replace(re, '$1' + key + "=" + value + '$2$3');
			else {
				hash = url.split('#');
				url = hash[0].replace(re, '$1$3').replace(/(&|\?)$/, '');
				if (typeof hash[1] !== 'undefined' && hash[1] !== null) 
					url += '#' + hash[1];
				return url;
			}
		} else {
			if (typeof value !== 'undefined' && value !== null) {
				var separator = url.indexOf('?') !== -1 ? '&' : '?';
				hash = url.split('#');
				url = hash[0] + separator + key + '=' + value;
				if (typeof hash[1] !== 'undefined' && hash[1] !== null) 
					url += '#' + hash[1];
				return url;
			}
			else
				return url;
		}
	},

	/**
	 * Get query string in a URL.
	 *
	 * @since 1.0.0
	 */
	getQueryString: function(name) {
	
		var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
		return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
	},

	/**
	 * Is number?
	 *
	 * @since 1.2.3
	 */
	isNumber: function(n) {
		return !isNaN(parseFloat(n)) && isFinite(n);
	},

	/**
	 * Sanitize amount and convert to standard format for calculations.
	 *
	 * @since 1.2.6
	 */
	amountSanitize: function(amount) {

		amount = amount.replace(/[^0-9.,]/g,'');

		if ( wpforms_builder.currency_decimal == ',' && ( amount.indexOf(wpforms_builder.currency_decimal) !== -1 ) ) {
			if ( wpforms_builder.currency_thousands == '.' && amount.indexOf(wpforms_builder.currency_thousands) !== -1 ) {;
				amount = amount.replace(wpforms_builder.currency_thousands,'');
			} else if( wpforms_builder.currency_thousands == '' && amount.indexOf('.') !== -1 ) {
				amount = amount.replace('.','');
			}
			amount = amount.replace(wpforms_builder.currency_decimal,'.');
		} else if ( wpforms_builder.currency_thousands == ',' && ( amount.indexOf(wpforms_builder.currency_thousands) !== -1 ) ) {
			amount = amount.replace(wpforms_builder.currency_thousands,'');
		}

		return wpf.numberFormat( amount, 2, '.', '' );	
	},

	/**
	 * Format amount.
	 *
	 * @since 1.2.6
	 */
	amountFormat: function(amount) {

		amount = String(amount);

		// Format the amount
		if ( wpforms_builder.currency_decimal == ',' && ( amount.indexOf(wpforms_builder.currency_decimal) !== -1 ) ) {
			var sepFound = amount.indexOf(wpforms_builder.currency_decimal);
				whole    = amount.substr(0, sepFound);
				part     = amount.substr(sepFound+1, amount.strlen-1);
				amount   = whole + '.' + part;
		}

		// Strip , from the amount (if set as the thousands separator)
		if ( wpforms_builder.currency_thousands == ',' && ( amount.indexOf(wpforms_builder.currency_thousands) !== -1 ) ) {
			amount = amount.replace(',','');
		}

		if ( wpf.empty( amount ) ) {
			amount = 0;
		}

		return wpf.numberFormat( amount, 2, wpforms_builder.currency_decimal, wpforms_builder.currency_thousands );
	},

	/**
	 * Format number.
	 *
	 * @link http://locutus.io/php/number_format/
	 * @since 1.2.6
	 */
	numberFormat: function (number, decimals, decimalSep, thousandsSep) { 

		number = (number + '').replace(/[^0-9+\-Ee.]/g, '')
		var n = !isFinite(+number) ? 0 : +number
		var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals)
		var sep = (typeof thousandsSep === 'undefined') ? ',' : thousandsSep
		var dec = (typeof decimalSep === 'undefined') ? '.' : decimalSep
		var s = ''

		var toFixedFix = function (n, prec) {
			var k = Math.pow(10, prec)
			return '' + (Math.round(n * k) / k).toFixed(prec)
		}

		// @todo: for IE parseFloat(0.55).toFixed(0) = 0;
		s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.')
		if (s[0].length > 3) {
			s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep)
		}
		if ((s[1] || '').length < prec) {
			s[1] = s[1] || ''
			s[1] += new Array(prec - s[1].length + 1).join('0')
		}

		return s.join(dec)
	},

	/**
	 * Empty check similar to PHP.
	 *
	 * @link http://locutus.io/php/empty/
	 * @since 1.2.6
	 */
	empty: function(mixedVar) {
	
		var undef
		var key
		var i
		var len
		var emptyValues = [undef, null, false, 0, '', '0']

		for (i = 0, len = emptyValues.length; i < len; i++) {
			if (mixedVar === emptyValues[i]) {
				return true
			}
		}

		if (typeof mixedVar === 'object') {
			for (key in mixedVar) {
				if (mixedVar.hasOwnProperty(key)) {
					return false
				}
			}
			return true
		}

		return false
	}
}
wpf.init();