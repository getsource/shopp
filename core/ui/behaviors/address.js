/*!
 * address.js - Description
 * Copyright © 2012 by Ingenesis Limited. All rights reserved.
 * Licensed under the GPLv3 {@see license.txt}
 */

(function($) {
	jQuery.fn.upstate = function () {

		$(this).change(function (e,init) {
			var $this = $(this),
				prefix = $this.attr('id').split('-')[0],
				country = $this.val(),
				state = $('#'+prefix+'-state'),
				menu = $('#'+prefix+'-state-menu'),
				options = '<option value=""></option>';

			if (menu.length == 0) return true;
			if (menu.hasClass('hidden')) menu.removeClass('hidden').hide();

			if (regions[country] || (init && menu.find('option').length > 1)) {
				state.setDisabled(true).addClass('_important').hide();
				if (regions[country]) {
					$.each(regions[country], function (value,label) {
						options += '<option value="'+value+'">'+label+'</option>';
					});
					if (!init) menu.empty().append(options).setDisabled(false).show().focus();
				}
				menu.setDisabled(false).show();
				$('label[for='+state.attr('id')+']').attr('for',menu.attr('id'));
			} else {
				menu.empty().setDisabled(true).hide();
				state.setDisabled(false).show().removeClass('_important');

				$('label[for='+menu.attr('id')+']').attr('for',state.attr('id'));
				if (!init) state.val('').focus();
			}
		}).trigger('change',[true]);

		return $(this);

	};

})(jQuery);


jQuery(document).ready(function($) {
	var sameaddr = $('.sameaddress');
	var shipFields = $('#shipping-address-fields');
	var billFields = $('#billing-address-fields');

	// Update name fields
	$('#firstname,#lastname').change(function () {
		$('#billing-name,#shipping-name').val(new String($('#firstname').val()+" "+$('#lastname').val()).trim());
	});

	// Update state/province
	$('#billing-country,#shipping-country').upstate();

	// Toggle same shipping address
	sameaddr.change(function (e,init) {
		var refocus = false,
			bc = $('#billing-country'),
			sc = $('#shipping-country'),
			prime = 'billing' == sameaddr.val() ? shipFields : billFields,
			alt   = 'shipping' == sameaddr.val() ? shipFields : billFields;

		if (sameaddr.is(':checked')) {
			prime.removeClass('half');
			alt.hide().find('.required').setDisabled(true);
		} else {
			prime.addClass('half');
			alt.show().find('.disabled:not(._important)').setDisabled(false);
			if (!init) refocus = true;
		}
		if (bc.is(':visible')) bc.trigger('change.localemenu',[init]);
		if (sc.is(':visible')) sc.trigger('change.localemenu',[init]);
		if (refocus) alt.find('input:first').focus();
	}).trigger('change',[true])
		.click(function () { $(this).change(); }); // For IE compatibility
});