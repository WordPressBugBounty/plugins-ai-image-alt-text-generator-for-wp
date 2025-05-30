
jQuery(document).ready(function($) {

    const pluginSlug = boomdevs_widget_data_image.plugin_slug;
    const version = boomdevs_widget_data_image.version;
    const storageKey = 'boomdevs_skip_' + pluginSlug + '_' + version;
    const wrapperSelector = $('.boomdevs-notification-wrapper[data-plugin-slug="' + pluginSlug + '"]');
   
    // If skip is NOT set, then show the widget
    if (localStorage.getItem(storageKey) !== 'true') {
        wrapperSelector.show();
    } 
    // Skip For Now click handler
    $(document).on('click', '.no-thanks-btn-image', function(e) {
        e.preventDefault();
        localStorage.setItem(storageKey, 'true');
        wrapperSelector.hide();
    });

    // No Thanks click handler (temporary hide)
    $(document).on('click', '.skip-for-now-btn-image', function(e) {
        e.preventDefault();
        wrapperSelector.hide();
    });
});

