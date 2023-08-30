<?php

namespace LinkGenius;

use function HFG\setting;

class Settings
{
    public static $DEFAULTS;
    public const PARENT_SLUG = 'edit.php?post_type='.CPT::TYPE_LINK;
    public const OPTIONS_PREFIX = 'linkgenius_options';
    public const TAB_GROUP = 'linkgenius_options';

    private function __construct()
    {
        add_action('cmb2_admin_init', [$this, 'linkgenius_options_metabox']);
        add_action('admin_menu', [$this, 'modify_admin_menu'], 100);
        add_action('update_option_'.self::OPTIONS_PREFIX.'_general', [$this, 'maybe_flush_rewrite_rules'], 10, 2); 
    }

    function modify_admin_menu()
    {
        global $submenu;

        // Remove the Settings_hidden submenu items
        if (isset($submenu[self::PARENT_SLUG])) {
            foreach ($submenu[self::PARENT_SLUG] as $key => $item) {
                if ('Settings_hidden' === $item[0]) {
                    unset($submenu[self::PARENT_SLUG][$key]);
                }
            }
        }

        // Highlight the Settings submenu item when on the Settings2 page
        if (
            isset($_GET['page']) && str_starts_with($_GET['page'], self::OPTIONS_PREFIX)
            && isset($_GET['post_type']) && $_GET['post_type'] === CPT::TYPE_LINK
        ) {
            foreach ($submenu[self::PARENT_SLUG] as $key => $item) {
                if ('Settings' === $item[0]) {
                    $submenu[self::PARENT_SLUG][$key][4] = ($submenu[self::PARENT_SLUG][$key][4] ?? '') . ' current';
                    break;
                }
            }
        }
    }

    /**
     * Hook in and register a metabox to handle a theme options page and adds a menu item.
     */
    function linkgenius_options_metabox()
    {
        $metabox = new Metabox();

        /**
         * Registers default options page menu item and form.
         */
        $args = array(
            'id'            => self::OPTIONS_PREFIX . '_general',
            'title'         => __('Settings', 'linkgenius'),
            'menu_title'    => __('Settings', 'linkgenius'),
            'object_types'  => array('options-page'),
            'option_key'    => self::OPTIONS_PREFIX . '_general',
            'tab_group'     => self::TAB_GROUP,
            'tab_title'     => __('General', 'linkgenius'),
            'parent_slug'   => self::PARENT_SLUG,
        );
        $general_options = new_cmb2_box($args);
        $fields = $metabox->get_general_fields(true);
        foreach ($fields as $field) {
            $general_options->add_field($field);
        }
        $general_options = apply_filters('linkgenius_links_settings_metabox', $general_options);

        /**
         * Registers disclosure options page, and set main item as parent.
         */
        $args = array(
            'id'           => self::OPTIONS_PREFIX . '_appearance',
            'title'        => __('Appearance', 'linkgenius'),
            'menu_title'   => 'Settings_hidden', // Use menu title, & not title to hide main h2.
            'object_types' => array('options-page'),
            'option_key'   => self::OPTIONS_PREFIX . '_appearance',
            'parent_slug'  => self::PARENT_SLUG,
            'tab_group'    => self::TAB_GROUP,
            'tab_title'    => __('Appearance', 'linkgenius'),
        );
        $appearance_options = new_cmb2_box($args);

        $fields = $metabox->get_link_appearance_fields(true);
        foreach ($fields as $field) {
            $appearance_options->add_field($field);
        }
        $appearance_options = apply_filters('linkgenius_links_settings_metabox', $appearance_options);


        /**
         * Registers disclosure options page, and set main item as parent.
         */
        $args = array(
            'id'           => self::OPTIONS_PREFIX . '_disclosure',
            'title'        => __('Disclosure', 'linkgenius'),
            'menu_title'   => 'Settings_hidden', // Use menu title, & not title to hide main h2.
            'object_types' => array('options-page'),
            'option_key'   => self::OPTIONS_PREFIX . '_disclosure',
            'parent_slug'  => self::PARENT_SLUG,
            'tab_group'    => self::TAB_GROUP,
            'tab_title'    => __('Disclosure', 'linkgenius'),
        );

        $disclosure_options = new_cmb2_box($args);

        $fields = $metabox->get_disclosure_fields(true);
        foreach ($fields as $field) {
            $disclosure_options->add_field($field);
        }
        $disclosure_options = apply_filters('linkgenius_links_settings_metabox', $disclosure_options);
    }

    public static function instance()
    {
        static $instance = null;
        if($instance == null)
            $instance = new self();
        return $instance;
    }

    /**
     * @return array the array containing the settings
     *   - 'general': An array of general configuration options.
     *     - 'general_prefix': The prefix to use for general settings.
     *     - 'general_redirect_type': The redirect type to use.
     *     - 'general_uncloak': Whether or not to uncloak links.
     *   - 'appearance': An array of appearance configuration options.
     *     - 'appearance_css_classes': The CSS classes to apply.
     *     - 'appearance_new_tab': Whether or not to open links in a new tab.
     *     - 'appearance_parameter_forwarding': Whether or not to forward parameters.
     *     - 'appearance_sponsored_attribute': Whether or not to add a sponsored attribute.
     *     - 'appearance_nofollow_attribute': Whether or not to add a nofollow attribute.
     *     - 'appearance_rel_tags': The rel tags to apply.
     *   - 'disclosure': An array of disclosure configuration options.
     *     - 'disclosure_type': The type of disclosure to use.
     *     - 'disclosure_tooltip': The tooltip text to display.
     *     - 'disclosure_text_after': The text to append.
     *     - 'disclosure_location': The location of the disclosure text.
     *     - 'disclosure_statement': The disclosure statement to use.
     *   - 'tracking': An array of tracking configuration options.
     */
    public function get_settings()
    {
        static $options = null;
        if ($options === null) {
            $options = [];
            $def = self::$DEFAULTS;
            foreach (self::$DEFAULTS as $option_name => $option_defaults) {
                $real_options = get_option(self::OPTIONS_PREFIX . "_" . $option_name, $option_defaults);
                $options = array_merge($option_defaults, $options, $real_options);
                foreach ($option_defaults as $k => $v) {
                    if (is_bool($option_defaults[$k])) {
                        if (!isset($real_options[$k])) {
                            $options[$k] = false;
                        } else if ($options[$k] === 'on') {
                            $options[$k] = true;
                        }
                    }
                }
            }
        }
        return $options;
    }

    public function maybe_flush_rewrite_rules($old_val, $new_val) {
        if($old_val['general_prefix'] ?? "" !== $new_val['general_prefix']) {
            add_option('linkgenius_should_flush', true);
        }
        var_dump($old_val);
    }
}
Settings::$DEFAULTS = [
    'general' => [
        'general_prefix' => "out",
        'general_redirect_type' => '301',
        'general_uncloak' => false,
    ],
    'appearance' => [
        'appearance_css_classes' => '',
        'appearance_new_tab' => true,
        'appearance_parameter_forwarding' => false,
        'appearance_sponsored_attribute' => true,
        'appearance_nofollow_attribute' => true,
        'appearance_rel_tags' => '',
    ],
    'disclosure' => [
        'disclosure_type'  => 'none',
        'disclosure_tooltip' => __('Affiliate Link', 'linkgenius'),
        'disclosure_text_after' => __(' (Affiliate Link)', 'linkgenius'),
        'disclosure_location' =>  'bottom',
        'disclosure_statement' => __('default_content_disclosure_text', 'linkgenius'),
    ],
    'tracking' => [
        'tracking_enabled' => '1',
        'tracking_name' => 'linkgenius',
        'tracking_parameters' => "'category': '%category%'\r\n'url':'%url%'"
    ]
];