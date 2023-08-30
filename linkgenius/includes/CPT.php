<?php
namespace LinkGenius;

use LinkGenius\Metabox;

class CPT {
    const TYPE_LINK = 'linkgenius_link';
    const TYPE_CATEGORY = 'linkgenius_category';
    const TYPE_TAG = 'linkgenius_tag';

    private function __construct() { 
        add_action( 'init', [$this, 'register_linkgenius_link_post_type'] );
        add_action( 'init', [$this, 'register_linkgenius_categories'] );
        add_action( 'init', [$this, 'register_linkgenius_tags'] );
        add_action( 'cmb2_admin_init', [$this, 'add_linkgenius_link_metaboxes'] );
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_linkgenius_link_admin_styles'] );
        add_action( 'save_post', [$this, 'update_linkgenius_link_slug'], 100);


        add_filter( 'manage_edit-linkgenius_link_columns', [$this, 'add_linkgenius_link_columns'] );
        add_action( 'manage_linkgenius_link_posts_custom_column', [$this, 'show_linkgenius_link_columns'], 10, 2 );
        // category shortcodes
        add_filter( 'manage_edit-linkgenius_category_columns', [$this, 'add_shortcode_column'] );
        add_action( 'manage_linkgenius_category_custom_column', [$this, 'show_category_shortcode_column'], 10, 3 );
        add_action( 'linkgenius_category_term_edit_form_top', [$this, 'add_category_shortcode_field'], 0 );
        // tag shortcodes
        add_filter( 'manage_edit-linkgenius_tag_columns', [$this, 'add_shortcode_column'] );
        add_action( 'manage_linkgenius_tag_custom_column', [$this, 'show_tag_shortcode_column'], 10, 3 );
        add_action( 'linkgenius_tag_term_edit_form_top', [$this, 'add_tag_shortcode_field'], 0 );
    
        add_filter( 'tag_row_actions', [$this, 'remove_view_row_actions'], 10, 2);
    }



    //
    // POST TYPES
    //

    // Register LinkGenius custom post type
    function register_linkgenius_link_post_type() {
        $labels = array(
            'name'               => __( 'LinkGenius Links', 'text-domain' ),
            'singular_name'      => __( 'LinkGenius Link', 'text-domain' ),
            'menu_name'          => __( 'LinkGenius', 'text-domain' ),
            'name_admin_bar'     => __( 'LinkGenius Link', 'text-domain' ),
            'add_new'            => __( 'Add New', 'text-domain' ),
            'add_new_item'       => __( 'Add New LinkGenius Link', 'text-domain' ),
            'new_item'           => __( 'New LinkGenius Link', 'text-domain' ),
            'edit_item'          => __( 'Edit LinkGenius Link', 'text-domain' ),
            'view_item'          => __( 'View LinkGenius Link', 'text-domain' ),
            'all_items'          => __( 'All Links', 'text-domain' ),
            'search_items'       => __( 'Search LinkGenius Links', 'text-domain' ),
            'parent_item_colon'  => __( 'Parent LinkGenius Links:', 'text-domain' ),
            'not_found'          => __( 'No LinkGenius Links found.', 'text-domain' ),
            'not_found_in_trash' => __( 'No LinkGenius Links found in Trash.', 'text-domain' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => Settings::instance()->get_settings()['general_prefix'], "with_front" => false, 'pages' => false ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_icon'           => (new Assets)->getIcon('#a0a5aa', '#a0a5aa'),
            'menu_position'      => null,
            'supports'           => array( 'title'),
            'show_in_rest'       => true
        );
        if(get_option('linkgenius_should_flush', false)) {
            delete_option('linkgenius_should_flush');
            flush_rewrite_rules();
        }
        register_post_type( self::TYPE_LINK, $args );
    }

    // Register Categories for LinkGenius custom post type
    function register_linkgenius_categories() {
        $labels = array(
            'name'                       => __( 'Categories', 'text-domain' ),
            'singular_name'              => __( 'Category', 'text-domain' ),
            'menu_name'                  => __( 'Categories', 'text-domain' ),
            'all_items'                  => __( 'All Categories', 'text-domain' ),
            'edit_item'                  => __( 'Edit Category', 'text-domain' ),
            'view_item'                  => __( 'View Category', 'text-domain' ),
            'update_item'                => __( 'Update Category', 'text-domain' ),
            'add_new_item'               => __( 'Add New Category', 'text-domain' ),
            'new_item_name'              => __( 'New Category Name', 'text-domain' ),
            'parent_item'                => __( 'Parent Category', 'text-domain' ),
            'parent_item_colon'          => __( 'Parent Category:', 'text-domain' ),
            'search_items'               => __( 'Search Categories', 'text-domain' ),
            'popular_items'              => __( 'Popular Categories', 'text-domain' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'text-domain' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'text-domain' ),
            'choose_from_most_used'      => __( 'Choose from the most used categories', 'text-domain' ),
            'not_found'                  => __( 'No categories found.', 'text-domain' ),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'rewrite'                    => array( 'slug' => __( 'linkgenius-category', 'text-domain' ) ),
            'show_in_rest'               => true

        );

        register_taxonomy( self::TYPE_CATEGORY, self::TYPE_LINK, $args );
    }

    // Register Tags for LinkGenius custom post type
    function register_linkgenius_tags() {
        $labels = array(
            'name'                       => __( 'Tags', 'text-domain' ),
            'singular_name'              => __( 'Tag', 'text-domain' ),
            'menu_name'                  => __( 'Tags', 'text-domain' ),
            'all_items'                  => __( 'All Tags', 'text-domain' ),
            'edit_item'                  => __( 'Edit Tag', 'text-domain' ),
            'view_item'                  => __( 'View Tag', 'text-domain' ),
            'update_item'                => __( 'Update Tag', 'text-domain' ),
            'add_new_item'               => __( 'Add New Tag', 'text-domain' ),
            'new_item_name'              => __( 'New Tag Name', 'text-domain' ),
            'parent_item'                => __( 'Parent Tag', 'text-domain' ),
            'parent_item_colon'          => __( 'Parent Tag:', 'text-domain' ),
            'search_items'               => __( 'Search Tags', 'text-domain' ),
            'popular_items'              => __( 'Popular Tags', 'text-domain' ),
            'separate_items_with_commas' => __( 'Separate tags with commas', 'text-domain' ),
            'add_or_remove_items'        => __( 'Add or remove tags', 'text-domain' ),
            'choose_from_most_used'      => __( 'Choose from the most used tags', 'text-domain' ),
            'not_found'                  => __( 'No tags found.', 'text-domain' ),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'rewrite'                    => array( 'slug' => __( 'linkgenius-tag', 'text-domain' ) ),
            'show_in_rest'               => true,
        );

        register_taxonomy( self::TYPE_TAG, self::TYPE_LINK, $args );
    }



    //
    //  CUSTOM COLUMNS
    //

    function add_linkgenius_link_columns($columns) {
       $offset = 2;
       return array_slice($columns, 0, $offset, true) + array('link'=> __('Target URL')) + array_slice($columns, $offset, count($columns) - 1, true);
    }

    function show_linkgenius_link_columns($column_name, $post_id) {
        if ($column_name == 'link') {
            $link = get_post_meta($post_id, 'general_target_url', true);
            if ($link) {
                echo '<a href="' . esc_url($link) . '" target="_blank">' . esc_url($link) . '</a>';
            }
        }
    }

    // Add custom input field that will copy the shortcode to display the LinkGenius Links ([linkgenius category="{slug}"]) in this category when clicked.
    // Add column to manage taxonomy page
    function add_shortcode_column( $columns ) {
        $offset = 2;
        return array_slice($columns, 0, $offset, true) + array("shortcode" => __("Shortcode")) + array_slice($columns, $offset, count($columns) - 1, true);
    }

    function show_category_shortcode_column($content, $column_name, $term_id) {
        return $this->show_shortcode_column($content, $column_name, $term_id, 'category');
    }

    function show_tag_shortcode_column($content, $column_name, $term_id) {
        return $this->show_shortcode_column($content, $column_name, $term_id, 'tag');
    }

    // Show custom input field in column
    function show_shortcode_column( $content, $column_name, $term_id, $type ) {
        if ( 'shortcode' !== $column_name ) {
            return $content;
        }

        $shortcode = sprintf('[linkgenius %s="%s"]', $type,  get_term($term_id)->slug);
		$html = sprintf( '<input class="linkgenius-copy-shortcode" type="text" style="width:100%%" value="%s" onclick="this.select()" readonly>', esc_attr($shortcode) );
        return $html;
    }

    function add_category_shortcode_field($tag) {
        $this->add_shortcode_field($tag, 'category');
    }

    function add_tag_shortcode_field($tag) {
        $this->add_shortcode_field($tag, 'tag');
    }
    // Add custom input field to add/edit category page
    function add_shortcode_field($tag, $type) {
        $shortcode = sprintf('[linkgenius %s="%s"]', $type, $tag->slug);
        printf('<table class="form-table">
            <tr class="form-field form-required term-name-wrap">
                <th scope="row"><label for="%1$s-shortcode">Shortcode</label></th>
                <td><input type="text" id="%1$s-shortcode" class="linkgenius-copy-shortcode" name="shortcode" value="%2$s" onclick="this.select()" readonly></td>
            </tr>
            </table>', $type, esc_attr($shortcode));
    }

    // Remove view from row actions of category and tag
    function remove_view_row_actions($actions, $tag) {
        if( $tag->taxonomy === self::TYPE_CATEGORY || $tag->taxonomy === self::TYPE_TAG )
        {
            unset( $actions['view'] );
        }
        return $actions;
    }

    // Add metaboxes for LinkGenius custom post type
    function add_linkgenius_link_metaboxes() {
        $metabox = new Metabox();

        // Properties Metabox
        $properties_metabox = new_cmb2_box(array(
            'id'           => 'linkgenius_link_properties_metabox',
            'object_types' => array(self::TYPE_LINK), 
            'context'      => 'after_title',
            'priority'     => 'high',
        ));
        $fields = $metabox->get_general_fields();
        foreach($fields as $field) {
            $properties_metabox->add_field($field);
        }
        $properties_metabox = apply_filters("linkgenius_link_metabox", $properties_metabox);

        // Link appearance Metabox
        $link_appearance_metabox = new_cmb2_box(array(
            'id'           => 'linkgenius_link_appearance_metabox',
            'title'        => __('Link Appearance', 'linkgenius'),
            'object_types' => array(self::TYPE_LINK), 
            'context'      => 'normal',
            'priority'     => 'high',
        ));
        $fields = $metabox->get_link_appearance_fields();
        foreach($fields as $field) {
            $link_appearance_metabox->add_field($field);
        }
        $link_appearance_metabox = apply_filters("linkgenius_link_metabox", $link_appearance_metabox);

        // Disclosure Metabox
        $disclosure_metabox = new_cmb2_box(array(
            'id'           => 'linkgenius_link_disclosure_metabox',
            'title'        => __('Link Disclosure', 'linkgenius'),
            'object_types' => array(self::TYPE_LINK), 
            'context'      => 'normal',
            'priority'     => 'high'
        ));
        $fields = $metabox->get_disclosure_fields();
        foreach($fields as $field) {
            $disclosure_metabox->add_field($field);
        }
        $disclosure_metabox = apply_filters("linkgenius_link_metabox", $disclosure_metabox);

        // Autolink Metabox
        $autolink_metabox = new_cmb2_box(array(
            'id'           => 'linkgenius_link_autolink_metabox',
            'title'        => __('Auto Link (Pro)', 'linkgenius'),
            'object_types' => array(self::TYPE_LINK), 
            'context'      => 'normal',
            'priority'     => 'high',
            'classes'      => 'linkgenius-pro'
        ));

        $autolink_metabox->add_field(array(
            'id'      => 'autolink_title',
            'type'    => 'title',
            'desc'    => __('Intro text autolink', 'linkgenius')
        ));

        $autolink_metabox->add_field(array(
            'name' => __('Order', 'linkgenius'),
            'id'   => 'autolink_order',
            'type' => 'text_small',
                'attributes' => array (
                    'required' => 'required',
                    'type' => 'number',
                    'data-default' => '0'
                ),
            "default" => "0",
            'desc' => __('A higher order means earlier executing when dealing with conflicing keywords or urls', 'linkgenius')
        ));

        $autolink_metabox->add_field(array(
            'name' => __('Keywords', 'linkgenius'),
            'id'   => 'autolink_keywords',
            'type' => 'textarea_small',
            'desc' => __('Enter keywords that will automatically create a link to this LinkGenius link if they occur in the page or post content. One keyword per line, case-insensitive.', 'linkgenius')
        ));

        $autolink_metabox->add_field(array(
            'name' => __('URLs', 'linkgenius'),
            'id'   => 'autolink_urls',
            'type' => 'textarea_small',
            'desc' => __('Enter URLs that will automatically be replaced with this LinkGenius link. One url per line.', 'linkgenius')
        ));
        $autolink_metabox = apply_filters("linkgenius_link_metabox", $autolink_metabox);

        // Expiration Metabox
        $expiration_metabox = new_cmb2_box(array(
            'id'           => 'linkgenius_link_expiration_metabox',
            'title'        => __('Link Expiration (Pro)', 'linkgenius'),
            'object_types' => array(self::TYPE_LINK), 
            'context'      => 'normal',
            'priority'     => 'high',
            'classes'      => 'linkgenius-pro'
        ));

        $expiration_metabox->add_field(array(
            'id'      => 'expiration_title',
            'type'    => 'title',
            'desc'    => __('Intro text expiration', 'linkgenius')
        ));

        $expiration_metabox->add_field(array(
            'name' => __('Expiration Date', 'linkgenius'),
            'id'   => 'expiration_date',
            'type' => 'text_datetime_timestamp',
            'desc' => __('Date after which link expires. (optional)', 'linkgenius')
        ));

        $expiration_metabox->add_field(array(
            'name' => __('Expiration Clicks', 'linkgenius'),
            'id'   => 'expiration_clicks',
            'type' => 'text_small',
            'attributes' => array(
                'type' => 'number',
                'pattern' => '\d*',
            ),
            'sanitization_cb' => 'absint',
            'escape_cb'       => 'absint',
            'desc' => __('Number of clicks after which the link expires. (optional)', 'linkgenius')
        ));

        $expiration_metabox->add_field(array(
            'name' => __('Redirect After Expiry', 'linkgenius'),
            'id'   => 'redirect_after_expiry',
            'type' => 'text_url',
            'desc' => __('The url to redirect to after link expired (used only if date or clicks are set).', 'linkgenius'),
            'default' => '/',
            'attributes' => array(
                'required' => 'required'
            )
        ));
        $expiration_metabox = apply_filters("linkgenius_link_metabox", $expiration_metabox);

        // Geolocation Metabox
        $geolocation_metabox = new_cmb2_box(array(
            'id'           => 'linkgenius_link_geolocation_metabox',
            'title'        => __('Geolocation Redirects (Pro)', 'linkgenius'),
            'object_types' => array(self::TYPE_LINK), 
            'context'      => 'normal',
            'priority'     => 'high',
            'classes'      => 'linkgenius-pro'
        ));

        $geolocation_metabox->add_field(array(
            'id'      => 'geolocation_title',
            'type'    => 'title',
            'desc'    => __('Intro text Geolocation', 'linkgenius')
        ));

        $geolocation_redirects_group = $geolocation_metabox->add_field(array(
            'id'          => 'geolocation_redirects',
            'type'        => 'group',
            'description' => __('Geolocation Redirects', 'linkgenius'),
            'repeatable'  => true,
            'options'     => array(
                'group_title'   => __('Redirect {#}', 'linkgenius'),
                'add_button'    => __('Add Another Redirect', 'linkgenius'),
                'remove_button' => __('Remove Redirect', 'linkgenius'),
            ),
        ));

        $geolocation_metabox->add_group_field($geolocation_redirects_group, array(
            'name' => __('Country Code', 'linkgenius'),
            'id'   => 'country_code',
            'type' => 'text',
        ));

        $geolocation_metabox->add_group_field($geolocation_redirects_group, array(
            'name' => __('Affiliate URL', 'linkgenius'),
            'id'   => 'affiliate_url',
            'type' => 'text_url',
        ));
        $geolocation_metabox = apply_filters("linkgenius_link_metabox", $geolocation_metabox);

        // GA Link Tracking Metabox
        $ga_tracking_metabox = new_cmb2_box(array(
            'id'           => 'linkgenius_link_ga_tracking_metabox',
            'title'        => __('GA Link Tracking (Pro)', 'linkgenius'),
            'object_types' => array(self::TYPE_LINK), 
            'context'      => 'normal',
            'priority'     => 'high',
            'classes'      => 'linkgenius-pro'
        ));

        $fields = array_merge(array(array(
            'id'   => 'tracking_title',
            'type' => 'title',
            'desc' => __('Intro text GA tracking', 'linkgenius')
        )),
        $metabox->get_analytics_fields());
        foreach($fields as $field) {
            $ga_tracking_metabox->add_field($field);
        }
        $ga_tracking_metabox = apply_filters("linkgenius_link_metabox", $ga_tracking_metabox);
    }

    function enqueue_linkgenius_link_admin_styles($hook) {
        // Check if we are editing the desired post type
        $current_screen = get_current_screen();

        // Check if the current screen is for editing an linkgenius_link post type
        if ($current_screen && $current_screen->post_type === self::TYPE_LINK) {
            wp_enqueue_style('linkgenius-link-styles', plugins_url("../assets/css/linkgenius-admin.css", __FILE__));
            if($current_screen->base === 'post') {
                wp_enqueue_script('linkgenius-link-scripts', plugins_url("../assets/js/linkgenius-posttype.js", __FILE__), array('jquery'), '1.0', true);
            }
        }
    }

    function update_linkgenius_link_slug($post_id) {
        $post = get_post($post_id);
    
        if ($post->post_type === self::TYPE_LINK) {
            // Check if this is an autosave or a revision
            if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
                return;
            }
            $current_slug = $post->post_name;
            $custom_slug = get_post_meta($post_id, 'general_slug', true);
            $custom_slug = sanitize_title($custom_slug);
            if($current_slug === $custom_slug || empty($custom_slug))
                return;
            
            
            // DO NOT RETURN ANYWHERE FROM HERE ON OUT BECAUSE REMOVE_ACTION NEEDS TO BE READDED
            // Temporarily remove the save_post action
            remove_action( 'save_post', [$this, 'update_linkgenius_link_slug'], 100);
            // Update the post's slug
            $update_args = array(
                'ID' => $post_id,
                'post_name' => $custom_slug
            );
            wp_update_post($update_args);
            // Update the custom field value if necessary
            $new_slug = get_post($post_id)->post_name;
            if ($new_slug !== $custom_slug) {
                update_post_meta($post_id, 'general_slug', $new_slug);
            }
            // Add the save_post action back again
            add_action( 'save_post', [$this, 'update_linkgenius_link_slug'], 100);

        }
    }

    public static function instance() {
        static $instance = null;
        if($instance == null)
            $instance = new self();
        return $instance;
    }
}