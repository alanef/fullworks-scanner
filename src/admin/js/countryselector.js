/*
 *  @copyright (c) 2023.
 *  @author     Alan Fuller (support@fullworksplugins.com)
 *  @licence    GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 *  @link       https://fullworksplugins.com
 *
 *  This file is part of a Fullworks' Plugin.
 *
 *  This WordPress plugin  is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This WordPress plugin  is  distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  Any copying of any part of this code not in compliance with the licence terms is strictly prohibited.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with the plugin.  https://www.gnu.org/licenses/gpl-3.0.en.html
 */

(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     *
     *
     */
    $(function () {

        if ($('#fullworks-vulnerability-scanner-firewall-blockcountry').length > 0) {
            $.typeahead({
                input: ".fs-country-selector",
                order: "asc",
                minLength: 1,
                hint: true,
                searchOnFocus: true,
                blurOnTab: false,
                source: {
                    data: data.countries
                },
                display: ["name"],
                templateValue: "{{name}}",
                multiselect: {
                    cancelOnBackspace: true,
                    data: data.selcountries
                },
                callback: {
                    onSubmit: function (node, form, items, event) {

                        var countrylist = '';
                        for (var i = 0; i < items.length; i++) {
                            var obj = items[i];
                            countrylist += obj.id + ',';
                        }

                        $('#fullworks-vulnerability-scanner-firewall-blockcountry').val(countrylist);

                    }
                }
            });
        }
    });

})(jQuery);
