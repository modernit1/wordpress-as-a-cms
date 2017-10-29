jQuery(function () {

    /**
     * sitemaps_settings_field
     * js functions
     */
    jQuery('input[name="blog_public"]').on('change', function () {
        jQuery('#lana_sitemap_sitemaps').find('input').each(function () {
            var $this = jQuery(this);
            $this.attr('disabled') ? $this.removeAttr('disabled') : $this.attr('disabled', 'disabled');
        });
        jQuery('#lana_sitemap_ping').find('input').each(function () {
            var $this = jQuery(this);
            $this.attr('disabled') ? $this.removeAttr('disabled') : $this.attr('disabled', 'disabled');
        });
    });
    jQuery('#xmlsf_link').click(function (event) {
        event.preventDefault();
        jQuery('html, body').animate({
            scrollTop: jQuery("a[name='xmlsf']").offset().top - 30
        }, 1000);
    });
    jQuery('#xmlnf_link').click(function (event) {
        event.preventDefault();
        jQuery('html, body').animate({
            scrollTop: jQuery("a[name='xmlnf']").offset().top - 30
        }, 1000);
    });

    /**
     * post_types_settings_field
     * js functions
     */
    jQuery('.lana_sitemap_post_types_settings').hide();
    jQuery('.lana_sitemap_post_types_link').click(function (event) {
        event.preventDefault();

        var post_type = jQuery(this).data('post-type');
        jQuery('.lana_sitemap_post_types_settings[data-post-type="' + post_type + '"]').toggle('slow');
    });

    jQuery('#lana_sitemap_post_types_note_1_more').hide();
    jQuery('#lana_sitemap_post_types_note_1_link').click(function (event) {
        event.preventDefault();

        jQuery('#lana_sitemap_post_types_note_1_link').hide();
        jQuery('#lana_sitemap_post_types_note_1_more').show('slow');
    });

    /**
     * taxonomies_settings_field
     * js functions
     */
    jQuery(document).ready(function () {
        jQuery('#lana_sitemap_taxonomies_note_1_more').hide();
        jQuery('#lana_sitemap_taxonomies_note_1_link').click(function (event) {
            event.preventDefault();
            jQuery('#lana_sitemap_taxonomies_note_1_link').hide();
            jQuery('#lana_sitemap_taxonomies_note_1_more').show('slow');
        });
    });
});