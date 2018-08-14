;(function($) {

	var s;

	var WPFormsSettings = {

		settings: {
			tabs:           '',
			tabs_nav:       '',
			tabs_hash:      window.location.hash,
			tabs_hash_sani: window.location.hash.replace('!', ''),
			media_frame:    false
		},

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			s = this.settings;

			WPFormsSettings.bindUIActions();

			$(document).ready(function() {
				WPFormsSettings.ready();
			});
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		ready: function() {

			s.tabs     = $('#wpforms-tabs');
			s.tabs_nav = $('#wpforms-tabs-nav');

			// If we have a hash and it begins with "wpforms-tab", set the proper tab to be opened.
			if ( s.tabs_hash && s.tabs_hash.indexOf('wpforms-tab-') >= 0 ) {
				$('.wpforms-active').removeClass('wpforms-active nav-tab-active');
				s.tabs_nav.find('a[href="' + s.tabs_hash_sani + '"]').addClass('wpforms-active nav-tab-active');
				s.tabs.find(s.tabs_hash_sani).addClass('wpforms-active').show();
			}
			$('.wpforms-circle-loader').fadeOut('fast', function() {
				$('#wpforms-tabs').fadeIn('fast');
				$('.wpforms-circle-loader').remove();
			});

			// Load color pickers
			$('.wpforms-color-picker').minicolors();
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		bindUIActions: function() {

			// Change tabs on click.
			$(document).on('click', '#wpforms-tabs-nav a', function(e){
				e.preventDefault();
	
				var $this = $(this);
				if ( $this.hasClass('wpforms-active') ) {
					return;
				} else {
					window.location.hash = s.tabs_hash = this.hash.split('#').join('#!');
					var current = s.tabs_nav.find('.wpforms-active').removeClass('wpforms-active nav-tab-active').attr('href');
					$this.addClass('wpforms-active nav-tab-active');
					s.tabs.find(current).removeClass('wpforms-active').hide();
					s.tabs.find($this.attr('href')).addClass('wpforms-active').show();
				}
			});		

			// Integrations tab accounts toggle
			$(document).on('click', '.wpforms-settings-provider-header', function(e) {
				e.preventDefault();
				$(this).parent().find('.wpforms-settings-provider-accounts').slideToggle();
			});	

			$(document).on('click', '.wpforms-settings-provider-accounts-toggle a', function(e) {
				e.preventDefault();
				var $connectFields = $(this).parent().next('.wpforms-settings-provider-accounts-connect');
				$connectFields.find('input[type=text], input[type=password]').val('');
				$connectFields.slideToggle();
			});

			$(document).on('click', '.wpforms-settings-provider-accounts-list a', function(e) {
				e.preventDefault();

				var $this = $(this),
					r     = confirm(wpforms_settings.provider_disconnect);

				if ( r != true ) {
					return false;
				}
				
				var data = {
					action  : 'wpforms_settings_provider_disconnect',
					provider: $this.data('provider'),
					key     : $this.data('key'),
					nonce   : wpforms_settings.nonce
				}
				$.post(wpforms_settings.ajax_url, data, function(res) {
					if (res.success){
						$this.parent().remove();
					} else {
						console.log(res);
					}
				}).fail(function(xhr, textStatus, e) {
					console.log(xhr.responseText);
				});
			});

			$(document).on('click', '.wpforms-settings-provider-connect', function(e) {
				e.preventDefault();
				var $this     = $(this),
					$icon     = $this.parent().find('i'),
					text      = $this.text(),
					$provider = $this.closest('.wpforms-settings-provider');

				$this.text(wpforms_settings.saving);
				$icon.show();

				var data = {
					action  : 'wpforms_settings_provider_add',
					data    : $(this).closest('form').serialize(),
					provider: $this.data('provider'),
					nonce   : wpforms_settings.nonce
				}
				$.post(wpforms_settings.ajax_url, data, function(res) {
					if (res.success) {
						$this.text(text);
						$provider.find('.wpforms-settings-provider-accounts-list ul').append(res.data.html);
						$provider.addClass('connected');
						$this.closest('.wpforms-settings-provider-accounts-connect').slideToggle();
					} else {
						console.log(res);
						alert( 'Could not authenticate with the provider' );
					}
					$this.text(text);
					$icon.hide();
				}).fail(function(xhr, textStatus, e) {
					console.log(xhr.responseText);
				});
			});

			// Image uploader
			$(document).on('click', '.wpforms-settings-upload-image', function(e){

				e.preventDefault();

				var $setting = $(this).closest('td');

				if ( s.media_frame ) {
					s.media_frame.open();
					return;
				}

				s.media_frame = wp.media.frames.wpforms_media_frame = wp.media({
					className: 'media-frame wpforms-media-frame',
					frame: 'select',
					multiple: false,
					title: wpforms_settings.upload_title,
					library: {
						type: 'image'
					},
					button: {
						text: wpforms_settings.upload_button
					}
				});

				s.media_frame.on('select', function(){
					// Grab our attachment selection and construct a JSON representation of the model.
					var media_attachment = s.media_frame.state().get('selection').first().toJSON();

					// Send the attachment URL to our custom input field via jQuery.
					$setting.find('.wpforms-settings-upload-image-value').val(media_attachment.url);
					$setting.find('.wpforms-settings-upload-image-display').empty().append('<img src="'+media_attachment.url+'">');
				});

				// Now that everything has been set, let's open up the frame.
				s.media_frame.open();
			})
		}
	}

	WPFormsSettings.init();
})(jQuery);