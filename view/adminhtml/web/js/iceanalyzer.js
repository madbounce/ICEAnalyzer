require(['jquery', 'mage/template', 'jquery/ui', 'mage/translate', 'mage/loader', 'loaderAjax'], function ($) {

    var preloader_data = jQuery('#html-body').attr('data-mage-init');
    var obj = jQuery.parseJSON(preloader_data);
    var preloader_src = obj.loader.icon;
    var template = '<div id = "custom_loader" class="loading-mask" data-role="loader_custom" style="display: block;"><div class="popup popup-loading"><div class="popup-inner"><img alt="Loading..." src="{src_url}">Please wait...</div></div></div>';
    var preloader_template = template.replace('{src_url}', preloader_src);
    var refresh_url = false;
    // var button = '<a href="javascript:void(0)" id="reload_button" title="Reload" type="button" class=" action-default page-actions-buttons"><span class=""><span>Reload</span></span></a>';
    var button = '<button id="reload_button" title="Reload" type="button" class="primary"><span class=""><span>Reload</span></span></button>';
    var tests_url = false;
    //check elements on page
    var check_empty_field = jQuery(".empty_field").length;


    if (jQuery('#iceshop_iceanalyzer_performance_tests_performance_tests_field').length > 0) {
        tests_url = $('#iceshop_iceanalyzer_performance_tests_performance_tests_field').find('option')[0].value;
    }

    if (check_empty_field > 0) {
        //clear groups
        jQuery(".empty_field").each(function () {
            jQuery(this).parents('tbody').html('');
        });
    }

    jQuery(document).ready(function () {
        getTabsContent();
    });

    /*jQuery(window).on('load', function () {
     getTabsContent();
     });*/

    if (tests_url != false) {
        $('body').on('click', '#testsRun', function () {
            jQuery('#html-body').append(preloader_template);
            $.ajax({
                url: tests_url,
                type: 'get',
                dataType: 'json',
                context: jQuery('.accordion'),
                // showLoader: true
            }).done(function (response) {
                for (var key in response) {
                    var button = jQuery('#testsRun').parents('tr')[0];
                    jQuery("#iceshop_iceanalyzer_" + key).find('tbody').html(response[key]);
                    jQuery("#iceshop_iceanalyzer_" + key).find('tbody').append(button);
                    jQuery('#custom_loader').remove();
                }
            }).fail(function (response) {
                alert('There is some error with Ajax request');
                jQuery('#custom_loader').remove();
            });
        });


    }


    function getTabsContent() {
        //check url for send ajax request
        var check_existing_url = jQuery("#iceshop_iceanalyzer_server_info_path_url").length;

        if (check_existing_url > 0) {
            jQuery('#html-body').append(preloader_template);
            jQuery(document).ready(function () {
                var url = jQuery("#iceshop_iceanalyzer_server_info_path_url").find('option')[0].value;
                //ajax request
                // url = url + '?isAjax=true';
                refresh_url = url;
                $.ajax({
                    url: url,
                    type: 'get',
                    dataType: 'json',
                    context: jQuery('#html-body'),
                    // showLoader: true
                }).done(function (response) {
                    for (var key in response) {
                        jQuery("#iceshop_iceanalyzer_" + key).find('tbody').html(response[key]);
                    }
                    var has_problems = jQuery('.has_problems').length;
                    if (has_problems > 0) {
                        jQuery('.iceanalyzer_problems_digest').css('display', 'block');
                        if (jQuery('.section-config.iceanalyzer_problems_digest').hasClass('active') == false) {
                            // jQuery('.section-config.iceanalyzer_problems_digest').addClass('active')
                            jQuery('#iceshop_iceanalyzer_problems_digest-head').click();
                        }
                    }
                    jQuery('#save').remove();
                    jQuery('.page-actions').append(button);
                    jQuery('#custom_loader').remove();
                }).fail(function (response) {
                    alert('There is some error with Ajax request');
                    jQuery('#custom_loader').remove();
                })

                ;
            });
        }
    }

    jQuery(document).on('click', '#reload_button', function () {
        jQuery('#html-body').append(preloader_template);
        if (refresh_url != false) {
            $.ajax({
                url: refresh_url,
                type: 'get',
                dataType: 'json',
                context: jQuery('#html-body'),
                // showLoader: true
            }).done(function (response) {
                for (var key in response) {
                    jQuery("#iceshop_iceanalyzer_" + key).find('tbody').html(response[key]);
                }
                var has_problems = jQuery('.has_problems').length;
                if (has_problems > 0) {
                    jQuery('.iceanalyzer_problems_digest').css('display', 'block');
                    if (jQuery('.section-config.iceanalyzer_problems_digest').hasClass('active') == false) {
                        // jQuery('.section-config.iceanalyzer_problems_digest').addClass('active')
                        jQuery('#iceshop_iceanalyzer_problems_digest-head').click();
                    }
                    jQuery('#reload_button').remove();
                    jQuery('.page-actions').append(button);

                }
                jQuery('#custom_loader').remove();
            }).fail(function (response) {
                alert('There is some error with Ajax request');
                jQuery('#custom_loader').remove();
            });
        }
    });


});