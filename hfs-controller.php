<?php
/*
  Plugin Name: HFS Controller
  Plugin URI: http://wordpress.org/extend/plugins/hfs-controller/
  Description: A plugin that gives you control over different templates for header, footer and even sidebar.
  Author: IndiaNIC
  Author URI: http://profiles.wordpress.org/indianic
  Version: 1.1
 */

class HFSController {

  function __construct() {

    /* Save all header, footer and sidebar template in array */
    $this->header_files = array();
    $this->footer_files = array();
    $this->sidebar_files = array();

    if (!is_readable(get_template_directory())) {
      chmod(get_template_directory(), 0777);
    }

    if ($handle = opendir(get_template_directory())) {
      $i = 1;
      $j = 1;
      $k = 1;
      while (false !== ($entry = readdir($handle))) {
        if (strpos($entry, '.php') !== false) {
          if (strpos($entry, 'header') !== false) {
            $this->header_files[$i]['name'] = $entry;
            $header_val = str_replace(".php", "", $entry);
            if ($header_val == 'header') {
              $this->header_files[$i]['val'] = "";
            } else {
              $header_val = str_replace("header-", "", $header_val);
              $this->header_files[$i]['val'] = $header_val;
            }
            $i++;
          } else if (strpos($entry, 'footer') !== false) {
            $this->footer_files[$j]['name'] = $entry;
            $footer_val = str_replace(".php", "", $entry);
            if ($footer_val == 'footer') {
              $this->footer_files[$j]['val'] = "";
            } else {
              $footer_val = str_replace("footer-", "", $footer_val);
              $this->footer_files[$j]['val'] = $footer_val;
            }
            $j++;
          } else if (strpos($entry, 'sidebar') !== false) {
            $this->sidebar_files[$k]['name'] = $entry;
            $sidebar_val = str_replace(".php", "", $entry);
            if ($sidebar_val == 'sidebar') {
              $this->sidebar_files[$k]['val'] = "";
            } else {
              $sidebar_val = str_replace("sidebar-", "", $sidebar_val);
              $this->sidebar_files[$k]['val'] = $sidebar_val;
            }
            $k++;
          }
        }
      }
      closedir($handle);
    }

    $this->header_files = array_reverse($this->header_files); /* all header template save in array */
    $this->footer_files = array_reverse($this->footer_files); /* all footer template save in array */
    $this->sidebar_files = array_reverse($this->sidebar_files); /* all sidebar template save in array */
    $this->header_files_counter = sizeof($this->header_files); /* header template count */
    $this->footer_files_counter = sizeof($this->footer_files); /* footer template count */
    $this->sidebar_files_counter = sizeof($this->sidebar_files); /* sidebar template count */


    add_action('admin_menu', array($this, 'hfs_controller_plugin')); /* plugin guideline */
    add_action('add_meta_boxes', array($this, 'hfs_controller_options')); /* add hfs option in edit post / page section */
    add_action('save_post', array($this, 'hfs_controller_save_postdata')); /* save hfs option when update post / page */
    add_action('category_add_form_fields', array($this, 'field_in_category_section')); /* add hfs option in add category section */
    add_action('create_category', array($this, 'add_field_in_category')); /* save hfs option when add new category */
    add_action('edit_category_form_fields', array($this, 'field_in_category_section')); /* add hfs option in edit category section */
    add_action('edited_category', array($this, 'update_field_in_category')); /* save hfs option when update category */

    add_action('template_include', array($this, 'wp_template_include')); /* save hfs option when update category */
  }

  public function field_in_category_section($tag) {
    wp_nonce_field('field_in_category_section', 'hfs_controller_edit_cat_box_nonce');
    $hvalue = get_option("header_template_value$tag->term_id");
    $fvalue = get_option("footer_template_value$tag->term_id");
    $svalue = get_option("sidebar_template_value$tag->term_id");
    echo "<tr><th colspan='2'><h3>Select HFS Template</h3></th></tr>";
    echo "<tr class='form-field'><th scope='row' valign='top'><label for='header_template'>";
    _e("for Header", 'header_controller_textdomain');
    echo "</label></th>";
    echo "<td><select id='header_template' name='header_template'";
    if ($this->header_files_counter == 1)
      echo " disabled ";
    echo ">";
    foreach ($this->header_files as $k => $v) {
      echo "<option";
      if (esc_attr($hvalue) == $v['val'])
        echo " selected ";
      echo " value={$v['val']}>{$v['name']}</option>";
    }
    echo "</select><p class='description'>Set header template for {$tag->name} category</p></td></tr>";
    echo "<tr class='form-field'><th scope='row' valign='top'><label for='footer_template'>";
    _e("for Footer", 'footer_controller_textdomain');
    echo "</label></th>";
    echo "<td><select id='footer_template' name='footer_template'";
    if ($this->footer_files_counter == 1)
      echo " disabled ";
    echo ">";
    foreach ($this->footer_files as $k => $v) {
      echo "<option";
      if (esc_attr($fvalue) == $v['val'])
        echo " selected ";
      echo " value={$v['val']}>{$v['name']}</option>";
    }
    echo "</select><p class='description'>Set footer template for {$tag->name} category</p></td></tr>";
    echo "<tr class='form-field'><th scope='row' valign='top'><label for='sidebar_template'>";
    _e("for Sidebar", 'sidebar_controller_textdomain');
    echo "</label></th>";
    echo "<td><select id='sidebar_template' name='sidebar_template'";
    if ($this->sidebar_files_counter == 1)
      echo " disabled ";
    echo ">";
    foreach ($this->sidebar_files as $k => $v) {
      echo "<option";
      if (esc_attr($svalue) == $v['val'])
        echo " selected ";
      echo " value={$v['val']}>{$v['name']}</option>";
    }
    echo "</select><p class='description'>Set sidebar template for {$tag->name} category</p></td></tr>";
  }

  public function add_field_in_category($term_id) {
    $cat_meta = get_option("category_$term_id");
    $nonce = $_POST['hfs_controller_edit_cat_box_nonce'];
    if (isset($_POST['header_template'])) {
      $hvalue = $_POST['header_template'];
      if (wp_verify_nonce($nonce, 'field_in_category_section')) {
        add_option("header_template_value$term_id", $hvalue);
      }
    }
    if (isset($_POST['footer_template'])) {
      $fvalue = $_POST['footer_template'];
      if (wp_verify_nonce($nonce, 'field_in_category_section')) {
        add_option("footer_template_value$term_id", $fvalue);
      }
    }
    if (isset($_POST['sidebar_template'])) {
      $svalue = $_POST['sidebar_template'];
      if (wp_verify_nonce($nonce, 'field_in_category_section')) {
        add_option("sidebar_template_value$term_id", $svalue);
      }
    }
  }

  public function update_field_in_category($term_id) {
    $cat_meta = get_option("category_$term_id");
    $nonce = $_POST['hfs_controller_edit_cat_box_nonce'];
    if (isset($_POST['header_template'])) {
      $hvalue = $_POST['header_template'];
      if (wp_verify_nonce($nonce, 'field_in_category_section')) {
        update_option("header_template_value$term_id", $hvalue);
      }
    }
    if (isset($_POST['footer_template'])) {
      $fvalue = $_POST['footer_template'];
      if (wp_verify_nonce($nonce, 'field_in_category_section')) {
        update_option("footer_template_value$term_id", $fvalue);
      }
    }
    if (isset($_POST['sidebar_template'])) {
      $svalue = $_POST['sidebar_template'];
      if (wp_verify_nonce($nonce, 'field_in_category_section')) {
        update_option("sidebar_template_value$term_id", $svalue);
      }
    }
  }

  public function hfs_controller_plugin() {
    add_menu_page('HFS Controller Title', 'HFS Controller', 'administrator', 'hfs-controller', array($this, 'hfs_controller_custom_menu_page'));
  }

  public function hfs_controller_custom_menu_page() {
    ?>
    <div class="wrap">
      <div class="icon32" id="icon-plugins"><br></div>
      <h2>HFS Controller <a class="add-new-h2">Guideline</a></h2>
      <h3 class="title">A plugin that gives you control over different templates for header, footer and even sidebar.</h3>
      <p>You can certainly create multiple headers by creating different files for it in WordPress.<br />
        Nevertheless, the process is a bit lengthy when you have lots of pages with different header, footer and sidebar. Overcoming the entire process, our HFS controller sets you free from all these hassles.<br />
        You just need to install this plugin and it will appear right there on the editing page, post and category.<br />
        You can set header, footer and sidebar from wp-admin and dont need to bother about coding.</p>
      <p>Just follow installation step.</p>
      <h3>Installation Step</h3>
      <ol>
        <li>Upload <code>hfs-controller</code> to the <code>/wp-content/plugins/</code> directory.</li>
        <li>Activate the plugin through the Plugins menu in WordPress.</li>
        <li>We guess you have multiple header files like <code>header.php</code>, <code>header-home.php</code>, <code>header-page.php</code><br />
          If you dont know about multiple header functionality then please visit <a target="_blank" href="http://codex.wordpress.org/Function_Reference/get_header">http://codex.wordpress.org/Function_Reference/get_header</a><br />
          Same process for <code>get_footer()</code> and <code>get_sidebar()</code> <a target="_blank" href="http://codex.wordpress.org/Function_Reference/get_footer">http://codex.wordpress.org/Function_Reference/get_footer</a> , <a target="_blank" href="http://codex.wordpress.org/Function_Reference/get_sidebar">http://codex.wordpress.org/Function_Reference/get_sidebar</a><br />
          Now editing Post, Page and Category page you can see <code>Select HFS Template</code> options.</li>
      </ol>
    </div>
    <br /><br />
    <img src="<?php echo plugins_url('screenshot-1.png', __FILE__); ?>" style="border:solid 1px #ddd;" />
    <br /><br />
    <img src="<?php echo plugins_url('screenshot-2.png', __FILE__); ?>" style="border:solid 1px #ddd;" />
    <br /><br />
    <img src="<?php echo plugins_url('screenshot-3.png', __FILE__); ?>" style="border:solid 1px #ddd;" />
    <?php
  }

  public function hfs_controller_options() {
    $screens = array('post', 'page');
    foreach ($screens as $screen) {
      add_meta_box('hfscontroller', __('Select HFS Template', 'hfs-controller-textdomain'), array($this, 'hfs_controller_option_box'), $screen, 'side', 'high');
    }
  }

  public function hfs_controller_option_box($post) {
    wp_nonce_field('hfs_controller_option_box', 'hfs_controller_option_box_nonce');
    $hvalue = get_post_meta($post->ID, 'header_template_value', true);
    $fvalue = get_post_meta($post->ID, 'footer_template_value', true);
    $svalue = get_post_meta($post->ID, 'sidebar_template_value', true);
    echo "<table>";
    echo "<tr><td><label for='header_template'>";
    _e("for Header", 'header_controller_textdomain');
    echo "</label></td>";
    echo "<td><select id='header_template' name='header_template'";
    if ($this->header_files_counter == 1)
      echo " disabled ";
    echo ">";
    foreach ($this->header_files as $k => $v) {
      echo "<option";
      if (esc_attr($hvalue) == $v['val'])
        echo " selected ";
      echo " value={$v['val']}>{$v['name']}</option>";
    }
    echo "</select></td></tr>";
    echo "<tr><td><label for='footer_template'>";
    _e("for Footer", 'footer_controller_textdomain');
    echo "</label></td>";
    echo "<td><select id='footer_template' name='footer_template'";
    if ($this->footer_files_counter == 1)
      echo " disabled ";
    echo ">";
    foreach ($this->footer_files as $k => $v) {
      echo "<option";
      if (esc_attr($fvalue) == $v['val'])
        echo " selected ";
      echo " value={$v['val']}>{$v['name']}</option>";
    }
    echo "</select></td></tr>";
    echo "<tr><td><label for='sidebar_template'>";
    _e("for Sidebar", 'sidebar_controller_textdomain');
    echo "</label></td>";
    echo "<td><select id='sidebar_template' name='sidebar_template'";
    if ($this->sidebar_files_counter == 1)
      echo " disabled ";
    echo ">";
    foreach ($this->sidebar_files as $k => $v) {
      echo "<option";
      if (esc_attr($svalue) == $v['val'])
        echo " selected ";
      echo " value={$v['val']}>{$v['name']}</option>";
    }
    echo "</select></td></tr>";
    echo "</table>";
  }

  public function hfs_controller_save_postdata($post_id) {

    if (!isset($_POST['hfs_controller_option_box_nonce']))
      return $post_id;

    $nonce = $_POST['hfs_controller_option_box_nonce'];

    if (!wp_verify_nonce($nonce, 'hfs_controller_option_box'))
      return $post_id;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
      return $post_id;

    if ('page' == $_POST['post_type']) {

      if (!current_user_can('edit_page', $post_id))
        return $post_id;
    } else {

      if (!current_user_can('edit_post', $post_id))
        return $post_id;
    }

    $hdata = sanitize_text_field($_POST['header_template']);
    $fdata = sanitize_text_field($_POST['footer_template']);
    $sdata = sanitize_text_field($_POST['sidebar_template']);
    update_post_meta($post_id, 'header_template_value', $hdata);
    update_post_meta($post_id, 'footer_template_value', $fdata);
    update_post_meta($post_id, 'sidebar_template_value', $sdata);
  }

  public function wp_template_include($template) {
    
    global $paged, $page, $post, $wp_query;

    $temp_template_file = get_temp_dir() . basename($template);

    $headerName = $footerName = $sidebarName = false;
    if (is_category()) {
      $headerName = get_option('header_template_value' . $wp_query->get_queried_object()->term_id);
      $footerName = get_option('footer_template_value' . $wp_query->get_queried_object()->term_id);
      $sidebarName = get_option('sidebar_template_value' . $wp_query->get_queried_object()->term_id);
    } else {
      $headerName = get_post_meta($post->ID, 'header_template_value', true);
      $footerName = get_post_meta($post->ID, 'footer_template_value', true);
      $sidebarName = get_post_meta($post->ID, 'sidebar_template_value', true);
    }
    
    if($headerName) {
      $headerName = '"'.$headerName.'"';
    }
    
    if($footerName) {
      $footerName = '"'.$footerName.'"';
    }
    
    if($sidebarName) {
      $sidebarName = '"'.$sidebarName.'"';
    }

    $search = array("get_header()", "get_header( )", "get_header ()", "get_header ( )", "get_footer()", "get_footer( )", "get_footer ()", "get_footer ( )", "get_sidebar()", "get_sidebar( )", "get_sidebar ()", "get_sidebar ( )");
    $replace = array("get_header({$headerName})", "get_header({$headerName})", "get_header({$headerName})", "get_header({$headerName})", "get_footer({$footerName})", "get_footer({$footerName})", "get_footer({$footerName})", "get_footer({$footerName})", "get_sidebar({$sidebarName})", "get_sidebar({$sidebarName})", "get_sidebar({$sidebarName})", "get_sidebar({$sidebarName})");

    file_put_contents($temp_template_file, str_replace($search, $replace, file_get_contents($template)));
    return $temp_template_file;
    
  }

}

$HFSController = new HFSController();
?>