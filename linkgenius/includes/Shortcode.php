<?php
namespace LinkGenius;

class Shortcode {
    private $has_tooltip = false;
    function __construct()
    {
        // for disclosure shortcode see Disclosure.php
        add_shortcode('linkgenius-link', array($this, 'linkgenius_link_shortcode'));
        add_shortcode('linkgenius-list', array($this, 'linkgenius_list_shortcode'));
        add_action("wp_enqueue_scripts", array($this, "maybe_enqueue_styles"));
    }

    function maybe_enqueue_styles() {
      if($this->has_tooltip) {
        wp_enqueue_style('linkgenius-tooltip', plugin_dir_url(__FILE__).'../assets/css/tooltip.css');
      }
    }

    function linkgenius_link_shortcode($atts, $content) {
        $atts = shortcode_atts(array(
          'id' => ''
        ), $atts);
      
        $link_id = intval($atts['id']);
      
        // Retrieve all metadata for the link
        $link_metadata = get_post_meta($link_id, '');
        if($link_metadata === null || !isset($link_metadata['general_target_url'][0])) {
          return "Link not found";
        }

        $link_metadata = array_map(fn($v) => $v[0], $link_metadata);
        $link_metadata = apply_filters('linkgenius_shortcode_link_metadata', $link_metadata, $link_id);

        // Retrieve global settings
        $settings = Settings::instance()->get_settings();
        $is_enabled = fn($key) => ($link_metadata[$key] === 'default' ? ($settings[$key]) : $link_metadata[$key] === '1');
        
        
        $attributes = array(
          "href" => esc_url($is_enabled('general_uncloak') ? $link_metadata['general_target_url'] : get_permalink($link_id))
        );
        if($is_enabled('appearance_new_tab')) {
            $attributes['target'] = "_blank";
        }
        $rel_tags = trim($settings['appearance_rel_tags']);
        if($is_enabled('appearance_sponsored_attribute')) {
          $rel_tags .= " sponsored";
        }
        if($is_enabled('appearance_nofollow_attribute')) {
          $rel_tags .= " nofollow";
        }
        $rel_tags .= ' '.trim($link_metadata['appearance_rel_tags']??"");
        $rel_tags = esc_attr(trim($rel_tags));
        if(!empty($rel_tags)) {
          $attributes['rel'] = $rel_tags;
        }
        $classes = esc_attr(trim(trim($settings['appearance_css_classes']).' '.trim($link_metadata['appearance_css_classes']??"")));
        if(!empty($classes)) {
          $attributes['class'] = $classes;
        }
        $attributes = array_merge($attributes, $link_metadata['atts']??[]);
        
        if ($link_metadata['disclosure_type'] === 'tooltip') {
          $attributes['class'] = trim(($attributes['classes'] ?? '')." linkgenius-tooltip");
          $content .= ""
              ."<span class='linkgenius-tooltiptext'>"
              . ($link_metadata['disclosure_tooltip'] ?? $settings['disclosure_tooltip'])
              ."</span>";
          $this->has_tooltip = true;
        }

        // Output the link
        $output = array_reduce(array_keys($attributes), fn($carry, $k) => $carry . " ".$k . "='". $attributes[$k]."'", "<a")
            .">".$content."</a>";

        if($link_metadata['disclosure_type'] === 'linktext') {
          $output .= $link_metadata['disclosure_text_after'] ?? $settings['disclosure_text_after'];
        }
        else if($link_metadata['disclosure_type'] === 'content_statement') {
          Discloser::instance()->add_disclosure();
        }
        return $output;
    }
    
    function linkgenius_list_shortcode($atts, $content) {
        $atts = shortcode_atts(array(
          'category' => '',
          'tag' => '',
          'sort'  => 'order'
        ), $atts);
        if(empty($content)) {
          $content = ", ";
        }
        /**
         * @var \WP_Post[] $links
         */
        $links = [];
        if(!empty($atts['category']) || !empty($atts['tag'])) {
            $taxonomy_type = !empty($atts['category']) ? 'category' : 'tag';
            $args = array(
              'post_type' => CPT::TYPE_LINK,
              'tax_query' => array(
                array(
                  'taxonomy' => $taxonomy_type === 'category' ? CPT::TYPE_CATEGORY : CPT::TYPE_TAG,
                  'field' => 'slug',
                  'terms' => $atts[$taxonomy_type]
                )
              ),
              'posts_per_page' => -1
            );
            if($atts['sort'] === 'title') {
              $args['orderby'] = 'title';
              $args['order'] = 'ASC';
            }
            else {
              $args['meta_key'] = 'general_order';  // Meta key for sorting
              $args['orderby'] = 'meta_value_num';  // Sort by meta value as numeric
              $args['order'] = 'ASC';  // Order in ascending order
            }
            $links = get_posts($args);
        }
        else {
          return __("You must specify a category or tag", 'linkgenius');
        }

        
        $matches = [];
        $output = '';
        $content = str_replace(['&#8217;', '&#8221;'], ['\'', '"'], wp_specialchars_decode($content));        
        if(preg_match("/(?<prelist>.*){links}(?<prelink>.*){link}(?<postlink>.*){\/links}(?<postlist>.*)/us",$content, $matches)) {
          $prelist = $matches['prelist'];
          $prelink = $matches['prelink'];
          $postlink = $matches['postlink'];
          $postlist = $matches['postlist'];
          $output = $prelist;
          foreach($links as $link) {
            $output .= $prelink.
                $this->linkgenius_link_shortcode(array('id' => $link->ID), $link->post_title)
                .$postlink;
          }
          $output .= $postlist;

        }
        else {
          $output = implode($content, array_map(fn($l) => $this->linkgenius_link_shortcode(array('id' => $l->ID), $l->post_title), $links));
        }
        return $output;
    }
}