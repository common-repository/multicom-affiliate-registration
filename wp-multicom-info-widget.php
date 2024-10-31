<?php

class WP_MultiCOM_Referral_Widget extends WP_Widget {
  public $args = array(
    'before_title' => '<h4 class="widgettitle">',
    'after_title' => '</h4>',
    'before_widget' => '<div class="widget-wrap">',
    'after_widget' => '</div>'
  );

  function __construct() {
    parent::__construct(
      'wp-multicom-referrals-widget',
      __('WP MultiCOM Referral Info', WP_MultiCOM_Constant::$TEXT_DOMAIN),
      array(
        'description' => __('This widget shows info about current referral.', WP_MultiCOM_Constant::$TEXT_DOMAIN)
      )
    );

    add_action('widgets_init', function () {
      register_widget('WP_MultiCOM_Referral_Widget');
    });
  }

  /**
   * This function is responsible for initializing the widget
   *
   * @param array $args - Widget config array
   * @param array $instance
   * @return void
   */
  public function widget($args, $instance) {
    $WP_MultiCOM = new WP_MultiCOM();

    $title = apply_filters('widget_title', $instance['title']);

    // Before and after widget arguments are defined by themes
    echo $args['before_widget'];
    if (!empty($title)) {
      echo $args['before_title'] . $title . $args['after_title'];
    }

    // Add support to W3TC, to load referral info by ajax
    if (defined( 'W3TC' )) {
      echo '<div class="load-multicom-referral-ajax"></div>';
    } else {
      echo $WP_MultiCOM->get_widget_referral_info();
    }
    echo $args['after_widget'];
  }

  /**
   * Print the form to the widget section
   *
   * @param array $instance
   * @return void
   */
  public function form($instance) {
    if (isset($instance['title'])) {
      $title = $instance['title'];
    } else {
      $title = __('Title', WP_MultiCOM_Constant::$TEXT_DOMAIN);
    }
?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
<?php
  }

  /**
   * Update the widget value
   *
   * @param array $new_instance
   * @param array $old_instance
   * @return array
   */
  public function update($new_instance, $old_instance)
  {
    $instance = array();
    $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';

    return $instance;
  }
}

new WP_MultiCOM_Referral_Widget();
