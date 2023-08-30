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
        ob_start();
        add_action('shutdown', function() {
            $final = '';

            // We'll need to get the number of ob levels we're in, so that we can iterate over each, collecting
            // that buffer's output into the final output.
            $levels = ob_get_level();

            for ($i = 0; $i < $levels; $i++) {
                $final .= ob_get_clean();
            }
            $disclosure_text = $this->has_disclosure ? $this->get_disclosure("shortcode") : "";
            $final = str_replace("<linkgenius_disclosure_placeholder>", $disclosure_text, $final);
            echo $final;
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