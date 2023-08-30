<?php

namespace LinkGenius;

use LinkGenius\Settings;

/**
 * Adds the Discloser to the
 */
class Discloser {
    private $has_disclosure = false;

    private function __construct()
    {
        add_shortcode('linkgenius-disclosure', function() {
            return "<linkgenius_disclosure_placeholder>";
        });
        add_action('linkgenius_links_disclosure', function() {
            echo "<linkgenius_disclosure_placeholder>";
        });

        // replace placeholders
        ob_start(function($buffer) {
            $disclosure_text = $this->has_disclosure ? $this->get_disclosure("shortcode") : "";
            return $buffer = str_replace("<linkgenius_disclosure_placeholder>", wp_kses_post($disclosure_text), $buffer);
        });
        add_action('shutdown', function() {
            ob_end_flush();
        }, 0);
    }

    public function add_disclosure() {
        if($this->has_disclosure) {
            return;
        }
        // executed only once, stored for custom disclosure through shortcode
        $this->has_disclosure = true;

        $settings = Settings::instance()->get_settings();
        switch($settings['disclosure_location']) {
            case 'bottom':
                add_filter('the_content', function($content) {
                    if($this->has_disclosure)
                        $content = $content.$this->get_disclosure("bottom");
                    return $content;
                }, 20);
                break;
            case 'top':
                add_filter('the_content', function($content) {
                    if($this->has_disclosure)
                        return $this->get_disclosure("top").$content;
                }, 20);
                break;
            default:
                return;
        }
    }

    protected function get_disclosure($location) {
        $disclosure = Settings::instance()->get_settings()['disclosure_statement'];
        $disclosure = apply_filters('linkgenius_links_disclosure_text', "<div class=\"linkgenius_link_disclosure\">".$disclosure."</div>", $location );
        return $disclosure;
    }

    public function has_disclosure() {
        return $this->has_disclosure;
    }

    public static function instance() {
        static $instance = null;
        if($instance == null)
            $instance = new self();
        return $instance;
    }
}