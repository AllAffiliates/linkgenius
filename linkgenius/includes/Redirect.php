<?php
namespace LinkGenius;

class Redirect {
    function __construct()
    {
        add_action( 'template_redirect', array( $this, 'maybe_redirect' ) );
    }
    /**
     * Redirects the user to the target URL. Takes in account the redirect type, "nofollow" and "sponsored" attributes and parameter forwarding settings.
     *
     * @return void
     */
    public function maybe_redirect()
    {
        $post = get_post();

        if ($post && 'linkgenius_link' === $post->post_type) {
            $data = get_post_meta($post->ID);
            $data = apply_filters('linkgenius_links/before_redirect', $data);
            if ($data === null) {
                return false;
            }
            $data = array_map(fn($v) => $v[0], $data);
            $data = apply_filters("linkgenius_before_redirect", $data);
            if(empty($data['general_target_url'])) {
                return false;
            }
            $settings = Settings::instance()->get_settings();
            $target_url = $data['general_target_url'];
            $get_val = fn($key) => ($data[$key] === 'default' ? $settings[$key] : $data[$key]);
            
            $redirect_type = $get_val('general_redirect_type');

            $target_url = str_replace( '@', '%40', $target_url );
            $target_url = str_replace( '|', '%7C', $target_url );
            // Parameter forwarding
            if($get_val('appearance_parameter_forwarding'))
            {
                $target_url = add_query_arg($_GET, $target_url);
            }            

            // Robot tags
            $robot_tags = [];
            if($get_val('appearance_nofollow_attribute') === '1') {
                $robot_tags[] = 'nofollow';
                $robot_tags[] = 'noindex';
            }
            if($get_val('appearance_sponsored_attribute') === '1') {
                $robot_tags[] = 'sponsored';
            }
            header('X-Robots-Tag: '.implode(', ', $robot_tags));


            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
            header('Pragma: no-cache');
            header('X-Redirect-By:  LinkGenius (by https://all-affiliates.com)');
            wp_redirect( $target_url, intval($redirect_type));
            flush();
            do_action("linkgenius_after_redirect", $data);
            exit();
        }
    }
}