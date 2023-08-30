<?php

namespace LinkGenius;

class Metabox
{
    public function get_general_fields($for_settings = false)
    {
        $settings = Settings::instance()->get_settings();
        $fields = [];
        $redirect_options = array(
            '301' => __('301 Permanent Redirect', 'linkgenius'),
            '302' => __('302 Temporary Redirect', 'linkgenius'),
            '307' => __('307 Temporary Redirect', 'linkgenius'),
        );
        if (!$for_settings) {
            $redirect_options = array('default' => sprintf(__('Default (%s)', 'linkgenius'), $redirect_options[$settings['general_redirect_type']]??"")) + $redirect_options;
            $fields[] = array(
                'name' => __('Slug*', 'linkgenius'),
                'id'   => 'general_slug',
                'type' => 'text_small',
                'attributes' => array(
                    'required' => 'required'
                ),
                'before_field' => site_url('/') . $settings['general_prefix'].'/',
                'desc' => __('The url to link to in your content.', 'linkgenius'),
                'sanitization_cb' => 'sanitize_title'
            );
            $fields[] = array(
                'name' => __('Target URL*', 'linkgenius'),
                'id'   => 'general_target_url',
                'type' => 'text_url',
                'attributes' => array (
                    'required' => 'required'
                ),
                'desc' => __('The target (affiliate) link.', 'linkgenius')
            );
            $fields[] = array(
                'name' => __('Order', 'linkgenius'),
                'id'   => 'general_order',
                'type' => 'text_small',
                'attributes' => array (
                    'required' => 'required',
                    'type' => 'number',
                    'data-default' => '0'
                ),
                "default" => "0",
                'desc' => __('The order for the link, used when displaying all links of a tag or category', 'linkgenius')
            );
        }
        else {
            $fields[] = array(
                'id'                => 'general_prefix',
                'name'              => __('Link Prefix', 'linkgenius'),
                'type'              => 'text',
                'default'           => 'go',
                'sanitization_cb'   => 'sanitize_title',
                'desc'              => sprintf(__('The prefix for your link, for example <i>go, recommends, out, link, affiliate</i>. The link will look like <b>%1$sprefix/slug</b>.', 'linkgenius'), site_url('/'))
            );
            $fields[] = array(
                'id'   => 'general_defaults_title',
                'name' => __('Defaults', 'linkgenius'),
                'type' => 'title',
                'desc' => __('Intro default general setings', 'linkgenius')
            );
        }
        // redirect options            
        $fields[] = array(
            'name'    => __('Redirect Type', 'linkgenius'),
            'id'      => 'general_redirect_type',
            'type'    => 'select',
            'options' => $redirect_options
        );
        $check_options = $for_settings
            ? array('type' => 'checkbox', 'default' => Settings::$DEFAULTS['general']['general_uncloak'])
            : array(
                'type' => 'select', 'options' =>
                array(
                    'default'   => sprintf(__('Default (%s)', 'linkgenius'), $settings['general_uncloak'] ? __('Enabled', 'linkgenius') : __('Disabled', 'linkgenius')),
                    '1'         => __('Enabled', 'linkgenius'),
                    '0'         => __('Disabled', 'linkgenius')
                )
            );
        $fields[] = array(
            'name'  => __('No Cloaking', 'linkgenius'),
            'id'    => 'general_uncloak',
            'desc'  => __('When checked affiliate url of LinkGenius Links will be outputted in content instead of the slug.', 'linkgenius')
        ) + $check_options;
        return $fields;
    }

    public function get_link_appearance_fields($for_settings = false)
    {
        $add_check_options = function ($field) use ($for_settings) {
            if ($for_settings) {
                $additions = array('type' => 'checkbox');
                if (isset(Settings::$DEFAULTS['appearance'][$field['id']]))
                    $additions['default'] = Settings::$DEFAULTS['appearance'][$field['id']];
                return $field + $additions;
            } else {
                $default = Settings::instance()->get_settings()[$field['id']];
                return $field + array(
                    'type' => 'select', 'options' =>
                    array(
                        'default'   => sprintf(__('Default (%s)', 'linkgenius'), $default ? __('Enabled', 'linkgenius') : __('Disabled', 'linkgenius')),
                        '1'         => __('Enabled', 'linkgenius'),
                        '0'         => __('Disabled', 'linkgenius')
                    )
                );
            }
        };
        $fields = array(
            array(
                'id'   => 'appearance_title',
                'type' => 'title',
                'desc' => __('Intro text appearance', 'linkgenius')
            ),
            array(
                'name' => ($for_settings ? __('Global CSS Classes', 'linkgenius') : __('CSS Classes', 'linkgenius')),
                'id'   => 'appearance_css_classes',
                'type' => 'text',
                'desc' => __('Comma separated list of CSS classes', 'linkgenius')
            ),
            $add_check_options(array(
                'name' => __('Open in New Tab', 'linkgenius'),
                'id'   => 'appearance_new_tab',
                'desc' => __('Open the URL in a new tab when clicked. Done by adding target="_blank" tag.', 'linkgenius')
            )),
            $add_check_options(array(
                'name' => __('Parameter Forwarding', 'linkgenius'),
                'id'   => 'appearance_parameter_forwarding'
            )),
            $add_check_options(array(
                'name' => __('Sponsored Attribute', 'linkgenius'),
                'id'   => 'appearance_sponsored_attribute'
            )),
            $add_check_options(array(
                'name' => __('Nofollow Attribute', 'linkgenius'),
                'id'   => 'appearance_nofollow_attribute'
            ))
        );
        $rel_tags = array(
            'name' => ($for_settings ? __('Global Additional Rel Tags', 'linkgenius') : __('Additional Rel Tags', 'linkgenius')),
            'id'   => 'appearance_rel_tags',
            'type' => 'text',
            'desc' => __('Comma separated list of additional rel tags', 'linkgenius')
        );
        if($for_settings) {
            // insert at third position
            array_splice($fields, 2, 0, array(
                $rel_tags,
                array(
                    'id'   => 'appearance_default_title',
                    'type' => 'title',
                    'name' => __('Default Link appearance', 'linkgenius'),
                    'desc' => __('Default settings, can be overriden per individual link.', 'linkgenius')
                )
            ));
        }
        else {
            $fields[] = $rel_tags;
        }
        return $fields;
    }

    public function get_disclosure_fields($for_settings = false)
    {
        $defaults = Settings::$DEFAULTS['disclosure'];
        $type_options = array(
            'none'              => __('None', 'linkgenius'),
            'tooltip'           => __('Tooltip', 'linkgenius'),
            'linktext'          => __('Text After Link', 'linkgenius'),
            'content_statement' => __('Content Statement', 'linkgenius'));
        $fields = array(
            array(
                'id'   => 'disclosure_title',
                'type' => 'title',
                'desc' => __('Intro text disclosure', 'linkgenius'),
            )
        );
        if($for_settings) {
            $fields[] = array(
                'id'   => 'disclosure_defaults_title',
                'type' => 'title',
                'name' => __('Default disclosure settings', 'linkgenius'),
                'desc' => __('Default settings, can be overriden per individual link.', 'linkgenius')
            );
        }

        $fields[] = array(
            'name'    => __('Disclosure Type', 'linkgenius'),
            'id'      => 'disclosure_type',
            'type'    => 'select',
            'options' => ($for_settings ? $type_options : array(
                'default' => sprintf(__('Default (%s)', 'linkgenius'), 
                    $type_options[Settings::instance()->get_settings()['disclosure_type']]??"")
                ) + $type_options
            ),
            'default' => $for_settings ? $defaults['disclosure_type'] : 'default'
        );

        if ($for_settings) {
            $fields = array_merge($fields, array(
                array(
                    'name'  => __('Default Disclosure Tooltip', 'linkgenius'),
                    'id'    => 'disclosure_tooltip',
                    'type'  => 'text',
                    'desc'  => __('default_tooltip_desc', 'linkgenius'),
                    'default' => $defaults['disclosure_tooltip']
                ),
                array(
                    'name'  => __('Text After Link', 'linkgenius'),
                    'id'    => 'disclosure_text_after',
                    'type'  => 'text',
                    'desc'  => 'after_link_text_desc',
                    'default' => $defaults['disclosure_text_after']
                ),
                array(
                    'id'   => 'disclosure_content_title',
                    'type' => 'title',
                    'name' => __('Content disclosure settings', 'linkgenius'),
                ),
                array(
                    'name'    => __('Content Disclosure Location', 'linkgenius'),
                    'id'      => 'disclosure_location',
                    'type'    => 'select',
                    'options' => array(
                        'bottom'            => __('End of Post', 'linkgenius'),
                        'top'               => __('Beginning of Post', 'linkgenius'),
                        'custom'            => __('Custom (Via Shortcode or Action)', 'linkgenius')
                    ),
                    'default'  => $defaults['disclosure_location']
                ),
                array(
                    'name'  => __('Content Disclosure Text', 'linkgenius'),
                    'id'    => 'disclosure_statement',
                    'type'  => 'textarea',
                    'default'  => $defaults['disclosure_statement']
                )
            ));
        } else {
            $fields = array_merge($fields, array(
                array(
                    'name'       => __('Disclosure Text', 'linkgenius'),
                    'id'         => 'disclosure_tooltip',
                    'type'       => 'text',
                    'attributes' => array(
                        'placeholder'             => sprintf(__('Default: %s', 'linkgenius'), $defaults['disclosure_tooltip']),
                        'data-conditional-id'     => 'disclosure_type',
                        'data-conditional-value'  => 'tooltip'
                    ),
                ),
                array(
                    'name'  => __('Text After Link', 'linkgenius'),
                    'id'    => 'disclosure_text_after',
                    'type'  => 'text',
                    'desc'  => __('after_link_text_desc', 'linkgenius'),
                    'attributes' => array(
                        'placeholder'             => sprintf(__('Default: %s', 'linkgenius'), $defaults['disclosure_text_after']),
                        'data-conditional-id'     => 'disclosure_type',
                        'data-conditional-value'  => 'linktext'
                    ),
                ),
            ));
        }
        return $fields;
    }

    public function get_analytics_fields($for_settings = false)
    {
        $fields = array();
        if($for_settings) {
            $fields[] = array(
                'id'   => 'tracking_defaults_title',
                'type' => 'title',
                'name' => __('Default GA tracking settings', 'linkgenius'),
                'desc' => __('Default settings, can be overriden per individual link.', 'linkgenius')
            );
        }
        $fields = array_merge($fields, array(
            array(
                'name' => __('Enabled', 'linkgenius'),
                'id'   => 'tracking_enabled',
                'type' => 'checkbox',
                'default' => Settings::$DEFAULTS['tracking']['tracking_enabled']??'1'
            ),
            array(
                'name' => __('Event Name', 'linkgenius'),
                'id'   => 'tracking_name',
                'type' => 'text',
                'default' => Settings::$DEFAULTS['tracking']['tracking_name']??""
            ),
            array(
                'name'    => __('Event Parameters', 'linkgenius'),
                'id'      => 'tracking_parameters',
                'type'    => 'textarea_small',
                'default' => Settings::$DEFAULTS['tracking']['tracking_parameters']??""
            )
        ));
        return $fields;
    }
}
