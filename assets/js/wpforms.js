;(function($) {

	var WPForms = {

		/**
		 * Start the engine.
		 *
		 * @since 1.2.3
		 */
		init: function() {

			// Document ready
			$(document).ready(WPForms.ready);

			// Page load
			$(window).on('load', WPForms.load);

			WPForms.bindUIActions();
		},

		/**
		 * Document ready.
		 *
		 * @since 1.2.3
		 */
		ready: function() {

			WPForms.loadValidation();
			WPForms.loadDatePicker();
			WPForms.loadTimePicker();
			WPForms.loadInputMask();
			WPForms.loadPayments();

			$(document).trigger('wpformsReady');
		},

		/**
		 * Page load.
		 *
		 * @since 1.2.3
		 */
		load: function() {

		},

		//--------------------------------------------------------------------//
		// Initializing
		//--------------------------------------------------------------------//

		/**
		 * Load jQuery Validation.
		 *
		 * @since 1.2.3
		 */
		loadValidation: function() {

			// Only load if jQuery validation library exists
			if (typeof $.fn.validate !== 'undefined') { 

				// Payments: Validate method for Credit Card Number
				if(typeof $.fn.payment !== 'undefined') { 
					$.validator.addMethod( "creditcard", function(value, element) {
						//var type  = $.payment.cardType(value);
						var valid = $.payment.validateCardNumber(value);
						return this.optional(element) || valid;
					}, "Please enter a valid credit card number.");
					// @todo validate CVC and expiration
				}

				// Validate method for file extensions
				$.validator.addMethod( "extension", function(value, element, param) {
					param = typeof param === "string" ? param.replace( /,/g, "|" ) : "png|jpe?g|gif";
					return this.optional(element) || value.match( new RegExp( "\\.(" + param + ")$", "i" ) );
				}, $.validator.format("File type is not allowed") );

				// Validate method for file size
				$.validator.addMethod("maxsize", function(value, element, param) {
					var maxSize = param,
						optionalValue = this.optional(element),
						i, len, file;
					if (optionalValue) {
						return optionalValue;
					}
					if (element.files && element.files.length) {
						i = 0;
						len = element.files.length;
						for (; i < len; i++) {
							file = element.files[i];
							if (file.size > maxSize) {
								return false;
							}
						}
					}
					return true;
				}, $.validator.format("File exceeds max size allowed"));

				// Validate email addresses
				$.validator.methods.email = function( value, element ) {
					return this.optional( element ) || /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@((?=[a-z0-9-]{1,63}\.)(xn--)?[a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,63}$/.test( value );
				}

				// Validate confirmations
				$.validator.addMethod("confirm", function(value, element, param) {
					return $.validator.methods.equalTo.call(this, value, element, param);
				}, function(params, element) {
					return $(element).data('rule-confirm-msg');
				});

				// Validate 12-hour time
				$.validator.addMethod( "time12h", function( value, element ) {
					return this.optional( element ) || /^((0?[1-9]|1[012])(:[0-5]\d){1,2}(\ ?[AP]M))$/i.test( value );
				}, "Please enter time in 12-hour AM/PM format (eg 8:45 AM)" );

				// Validate 24-hour time
				$.validator.addMethod( "time24h", function( value, element ) {
					return this.optional(element) || /^(([0-1]?[0-9])|([2][0-3])):([0-5]?[0-9])(\ ?[AP]M)?$/i.test(value);  
				}, "Please enter time in 24-hour format (eg 22:45)" );

				// Finally load jQuery Validation library for our forms
				$('.wpforms-validate').each(function() {
					var form   = $(this),
						formID = form.data('formid');

					if (typeof window['wpforms_'+formID] != "undefined" && window['wpforms_'+formID].hasOwnProperty('validate')) {	
						properties = window['wpforms_'+formID].validate;
					} else if ( typeof wpforms_validate != "undefined") {
						properties = wpforms_validate;
					} else {
						properties = {
							errorClass: 'wpforms-error',
							validClass: 'wpforms-valid',
							errorPlacement: function(error, element) {
								if (element.attr('type') == 'radio' || element.attr('type') == 'checkbox' ) {
									element.parent().parent().parent().append(error);
								} else if (element.is('select') && element.attr('class').match(/date-month|date-day|date-year/)) {
									if (element.parent().find('label.wpforms-error:visible').length === 0) {
										element.parent().find('select:last').after(error);
									}
								} else {
									error.insertAfter(element);
								}
							},
							submitHandler: function(form) {

								var $form   = $(form),
									$submit = $form.find('.wpforms-submit'),
									altText = $submit.data('alt-text');

								if (altText) {
									$submit.text(altText).prop('disabled', true);
								}

								form.submit();
							}
						}
					}
					form.validate( properties );
				});
			}
		},

		/**
		 * Load jQuery Date Picker.
		 *
		 * @since 1.2.3
		 */
		loadDatePicker: function() {

			// Only load if jQuery datepicker library exists
			if (typeof $.fn.flatpickr !== 'undefined') { 
				$('.wpforms-datepicker').each(function() {
					var element = $(this),
						form    = element.closest('.wpforms-form'),
						formID  = form.data('formid');

					if (typeof window['wpforms_'+formID] != "undefined" && window['wpforms_'+formID].hasOwnProperty('datepicker') ) {	
						properties = window['wpforms_'+formID].datepicker;
					} else if ( typeof wpforms_datepicker != "undefined") {
						properties = wpforms_datepicker;
					} else {
						properties = {}
					}
					element.flatpickr(properties)
				});
			};
		},

		/**
		 * Load jQuery Time Picker.
		 *
		 * @since 1.2.3
		 */
		loadTimePicker: function() {

			// Only load if jQuery timepicker library exists
			if (typeof $.fn.timepicker !== 'undefined') { 
				$('.wpforms-timepicker').each(function() {
					var element = $(this),
						form    = element.closest('.wpforms-form'),
						formID  = form.data('formid');

					if (typeof window['wpforms_'+formID] != "undefined" && window['wpforms_'+formID].hasOwnProperty('timepicker') ) {	
						properties = window['wpforms_'+formID].timepicker;
					} else if ( typeof wpforms_timepicker != "undefined") {
						properties = wpforms_timepicker;
					} else {
						properties = {
							scrollDefault: 'now',
							forceRoundTime: true
						}
					}
					element.timepicker(properties);
				});
			}
		},

		/**
		 * Load jQuery input masks.
		 *
		 * @since 1.2.3
		 */
		loadInputMask: function() {

			// Only load if jQuery input mask library exists
			if (typeof $.fn.inputmask !== 'undefined') { 
				$('.wpforms-masked-input').inputmask();
			};
		},

		/**
		 * Payments: Do various payment-related tasks on load.
		 *
		 * @since 1.2.6
		 */
		loadPayments: function() {

			// Update Total field(s) with latest calculation
			$('.wpforms-payment-total').each(function(index, el) {
				WPForms.amountTotal(this);
			})

			// Credit card valdation
			if(typeof $.fn.payment !== 'undefined') { 
				$('.wpforms-field-credit-card-cardnumber').payment('formatCardNumber');
				$('.wpforms-field-credit-card-cardcvc').payment('formatCardCVC');
			};
		},

		//--------------------------------------------------------------------//
		// Binds
		//--------------------------------------------------------------------//

		/**
		 * Element bindings.
		 *
		 * @since 1.2.3
		 */
		bindUIActions: function() {

			// Pagebreak navigation
			$(document).on('click', '.wpforms-page-button', function(event) {
				event.preventDefault();
				WPForms.pagebreakNav($(this));
			});

			// Payments: Update Total field(s) when latest calculation.
			$(document).on('change input', '.wpforms-payment-price', function(event) {
				WPForms.amountTotal(this);
			});

			// Payments: Restrict user input payment fields
			$(document).on('input', '.wpforms-payment-user-input', function(event) {
				var $this = $(this),
					amount = $this.val();
				$this.val(amount.replace(/[^0-9.,]/g, ''));
			});	

			// Payments: Sanitize/format user input amounts
			$(document).on('focusout', '.wpforms-payment-user-input', function(event) {
				var $this     = $(this),
					amount    = $this.val(),
					sanitized = WPForms.amountSanitize(amount),
					formatted = WPForms.amountFormat(sanitized);
				$this.val(formatted);
			});	

			// OptinMonster: initialize again after OM is finished.
			// This is to accomodate moving the form in the DOM.
			$(document).on('OptinMonsterAfterInject', function(event) {
				WPForms.ready();
			});
		},

		/**
		 * Update Pagebreak navigation.
		 *
		 * @since 1.2.2
		 */
		pagebreakNav: function(el) {

			var $this      = $(el),
				valid      = true,
				action     = $this.data('action'),
				page       = $this.data('page'),
				page2      = page;
				next       = page+1,
				prev       = page-1,
				formID     = $this.data('formid'),
				$form      = $this.closest('.wpforms-form'),
				$page      = $form.find('.wpforms-page-'+page),
				$submit    = $form.find('.wpforms-submit-container');
				$indicator = $form.find('.wpforms-page-indicator');

			// Toggling between pages
			if ( action == 'next' ){
				// Validate
				if (typeof $.fn.validate !== 'undefined') { 
					$page.find('input.wpforms-field-required, select.wpforms-field-required, textarea.wpforms-field-required, .wpforms-field-required input').each(function(index, el) {
						var field = $(el);
						if ( field.valid() ) {
						} else {
							valid = false;
						}
					});
					// Scroll to first/top error on page
					var $topError = $page.find('.wpforms-error').first();
					if ($topError.length) {
						$('html, body').animate({
							scrollTop: $topError.offset().top-75
						}, 750, function() {
							$topError.focus();
						});
					}
				}
				// Move to next page
				if (valid) {
					page2 = next;
					$page.hide();
					var $nextPage = $form.find('.wpforms-page-'+next);
					$nextPage.show();
					if ( $nextPage.hasClass('last') ) {
						$submit.show();
					}
					// Scroll to top of the form
					$('html, body').animate({
						scrollTop: $form.offset().top-75
					}, 1000);
				}
			} else if ( action == 'prev' ) {
				// Move to prev page
				page2 = prev;
				$page.hide();
				$form.find('.wpforms-page-'+prev).show();
				$submit.hide();
				// Scroll to top of the form
				$('html, body').animate({
					scrollTop: $form.offset().top-75
				}, 1000);
			}

			if ( $indicator ) {
				var theme = $indicator.data('indicator'),
					color = $indicator.data('indicator-color');
				if ('connector' === theme || 'circles' === theme) {
					$indicator.find('.wpforms-page-indicator-page').removeClass('active');
					$indicator.find('.wpforms-page-indicator-page-'+page2).addClass('active');
					$indicator.find('.wpforms-page-indicator-page-number').removeAttr('style');
					$indicator.find('.active .wpforms-page-indicator-page-number').css('background-color', color);
					if ( 'connector' == theme) {
						$indicator.find('.wpforms-page-indicator-page-triangle').removeAttr('style');
						$indicator.find('.active .wpforms-page-indicator-page-triangle').css('border-top-color', color);
					}
				} else if ('progress' === theme) {
					var $pageTitle = $indicator.find('.wpforms-page-indicator-page-title'),
						$pageSep   = $indicator.find('.wpforms-page-indicator-page-title-sep'),
						totalPages = ($('.wpforms-page').length),
						width = (page2/totalPages)*100;
					$indicator.find('.wpforms-page-indicator-page-progress').css('width', width+'%');
					$indicator.find('.wpforms-page-indicator-steps-current').text(page2);
					if ($pageTitle.data('page-'+page2+'-title')) {
						$pageTitle.css('display','inline').text($pageTitle.data('page-'+page2+'-title'));
						$pageSep.css('display','inline');
					} else {
						$pageTitle.css('display','none');
						$pageSep.css('display','none');
					}
				}
			}
		},

		//--------------------------------------------------------------------//
		// Other functions
		//--------------------------------------------------------------------//

		/**
		 * Payments: Calculate total.
		 *
		 * @since 1.2.3
		 */
		amountTotal: function(el) {

			var $form                = $(el).closest('.wpforms-form'),
				total                = 0,
				totalFormatted       = 0,
				totalFormattedSymbol = 0,
				currency             = WPForms.getCurrency();

			$('.wpforms-payment-price').each(function(index, el) {
				var amount = 0,
					$this  = $(this);

				if ($this.attr('type') === 'text' || $this.attr('type') === 'hidden' ) {
					amount = $this.val();
				} else if ($this.attr('type') === 'radio' && $this.is(':checked')) {
					amount = $this.data('amount');
				}
				if (!WPForms.empty(amount)) {
					amount = WPForms.amountSanitize(amount);
					total  = Number(total)+Number(amount);
				}
			});

			totalFormatted = WPForms.amountFormat(total);

			if ( 'left' == currency.symbol_pos) {
				totalFormattedSymbol = currency.symbol+' '+totalFormatted;
			} else {
				totalFormattedSymbol = totalFormatted+' '+currency.symbol;
			}

			$form.find('.wpforms-payment-total').each(function(index, el) {
				if ($(this).attr('type') == 'hidden') {
					$(this).val(totalFormattedSymbol);
				} else {
					$(this).text(totalFormattedSymbol);
				}
			});
		},

		/**
		 * Sanitize amount and convert to standard format for calculations.
		 *
		 * @since 1.2.6
		 */
		amountSanitize: function(amount) {

			var currency = WPForms.getCurrency();

			amount = amount.replace(/[^0-9.,]/g,'');

			if ( currency.decimal_sep == ',' && ( amount.indexOf(currency.decimal_sep) !== -1 ) ) {
				if ( currency.thousands_sep == '.' && amount.indexOf(currency.thousands_sep) !== -1 ) {;
					amount = amount.replace(currency.thousands_sep,'');
				} else if( currency.thousands_sep == '' && amount.indexOf('.') !== -1 ) {
					amount = amount.replace('.','');
				}
				amount = amount.replace(currency.decimal_sep,'.');
			} else if ( currency.thousands_sep == ',' && ( amount.indexOf(currency.thousands_sep) !== -1 ) ) {
				amount = amount.replace(currency.thousands_sep,'');
			}

			return WPForms.numberFormat( amount, 2, '.', '' );	
		},

		/**
		 * Format amount.
		 *
		 * @since 1.2.6
		 */
		amountFormat: function(amount) {

			var currency = WPForms.getCurrency();

			amount = String(amount);

			// Format the amount
			if ( currency.decimal_sep == ',' && ( amount.indexOf(currency.decimal_sep) !== -1 ) ) {
				var sepFound = amount.indexOf(currency.decimal_sep);
					whole    = amount.substr(0, sepFound);
					part     = amount.substr(sepFound+1, amount.strlen-1);
				amount = whole + '.' + part;
			}

			// Strip , from the amount (if set as the thousands separator)
			if ( currency.thousands_sep == ',' && ( amount.indexOf(currency.thousands_sep) !== -1 ) ) {
				amount = amount.replace(',','');
			}

			if ( WPForms.empty( amount ) ) {
				amount = 0;
			}

			return WPForms.numberFormat( amount, 2, currency.decimal_sep, currency.thousands_sep );
		},

		/**
		 * Get site currency settings.
		 *
		 * @since 1.2.6
		 */
		getCurrency: function() {

			var currency = {
				thousands_sep: ',',
				decimal_sep: '.',
				symbol: '$',
				symbol_pos: 'left' 
			}

			if ( 'undefined' !== wpforms_currency) {
				currency.thousands_sep = wpforms_currency.thousands;
				currency.decimal_sep   = wpforms_currency.decimal;
				currency.symbol        = wpforms_currency.symbol;
				currency.symbol_pos    = wpforms_currency.symbol_pos;
			}

			return currency;
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

	WPForms.init();

	window.wpforms = WPForms;

})(jQuery);