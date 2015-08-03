<?php
/*
Plugin Name: Site info widgets
Plugin URI: http://premium.wpmudev.org/
Description: Show information of a subsite in dashboard widget
Author: Ashok (WPMU DEV)
Version: 1.0.0
Author URI: http://premium.wpmudev.org/
*/

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) wp_die( __( 'Sorry Cowboy! Find a different place to hack!', 'siw' ) );

if( ! is_multisite() ) {
	add_action( 'admin_notices', 'not_compatible_notice' );
	function not_compatible_notice() {
		?>
		<div class="error">
		    <p><?php __e( 'This plugin is only multisite compatible.', 'siw' ) ?></p>
		</div>
		<?php
	}
}
elseif( ! class_exists( 'Site_Info_Widgets' ) ){
    /**
     * Defining class
     */
    class Site_Info_Widgets{
        
        /**
         * global @wpdb;
         */
        private $_db;
        
        /**
         * Singleton Instance of this class
         */
        private static $_instance;

        
        /**
         * Class construct
         */
        public function __construct() {
            global $wpdb;
            $this->_db = $wpdb;
            
            // Checking if this is not main site
            // We are not showing this widget for main site
            if( ! is_main_site() ){
                add_action( 'wp_dashboard_setup', array( &$this, 'site_info_widgets_cb' ) );
                add_action( 'admin_head', array( &$this, 'siw_style' ) );
            }
        }
        
        /**
         * Singleton instance
         */
        public static function get_instance() {
            if ( ! self::$_instance instanceof Site_Info_Widgets ) {
                self::$_instance = new Site_Info_Widgets();
            }
            return self::$_instance;
        }
        
        /**
         * Widget callback function
         *
         * @see https://codex.wordpress.org/Dashboard_Widgets_API
         */
        public function site_info_widgets_cb() {
            wp_add_dashboard_widget(
                'blog_info_widget',                                 // Widget slug.
                __( 'This Blog', 'siw' ),                           // Title.
                array( &$this, 'blog_info_widget_cb' )              // Display function.
            );
            
            wp_add_dashboard_widget(
                'user_info_widget',                                 // Widget slug.
                __( 'My Account', 'siw' ),                           // Title.
                array( &$this, 'user_info_widget_cb' )              // Display function.
            );
        }
        
        /**
         * "This blog" widget callback
         */
        public function blog_info_widget_cb() {
            $comments_count = wp_count_comments();
            ?>
            <table cellpadding="5" cellspacing="0" width="100%" class="siw_tbl">
                <tr>
                    <th><?php _e( 'Title', 'siw' ); ?></th>
                    <td><?php bloginfo( 'name' ); ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Tagline', 'siw' ); ?></th>
                    <td><?php bloginfo( 'description' ) ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Theme', 'siw' ); ?></th>
                    <td><?php echo get_current_theme(); ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Privacy', 'siw' ); ?></th>
                    <td><?php echo get_option( 'blog_public' ) == 1 ? __( 'Blog is visible to everyone, including search engines (like Google, Sphere, Technorati) and archivers.', 'siw' ) : __( 'Blog is invisible to search engines, but allow normal visitors.', 'siw' ); ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Comments', 'siw' ); ?></th>
                    <td><?php printf( __( '%s Pending | %s Spam', 'siw' ), $comments_count->moderated, $comments_count->spam ); ?></td>
                </tr>
            </table>
            <?php
            // Check if WPMU pro sites plugin activated
            if( class_exists( 'ProSites' ) ){
                global $psts;
                $blog_id = get_current_blog_id();
                $levels        = (array) get_site_option( 'psts_levels' );
                $current_level = $psts->get_level( $blog_id );
                $expire = $psts->get_expire( $blog_id );
                $trialing = ProSites_Helper_Registration::is_trial( $blog_id );
		$active_trial = $trialing ? __( '(Active trial)', 'siw') : '';
                $quota = get_space_allowed();
                $used = get_space_used();
                if ( $used > $quota )
                    $percentused = '100';
                else
                    $percentused = ( $used / $quota ) * 100;
                    
                $used = round( $used, 2 );
                $percentused = number_format( $percentused );
                ?>
                <table cellpadding="5" cellspacing="0" width="100%" class="siw_tbl">
                    <tr>
                        <th><?php _e( 'Level', 'siw' ); ?></th>
                        <td>
                            <?php echo $current_level . ' - ' . @$levels[ $current_level ]['name']; ?>
                            <a class="manage_pro" href="<?php echo $psts->checkout_url( $blog_id ); ?>"><?php _e( 'Manage Pro Account', 'siw' ); ?></a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Renewal Date', 'siw' ); ?></th>
                        <td><?php echo date_i18n( get_option( 'date_format' ), $expire ) . ' ' . $active_trial; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Storage', 'siw' ); ?></th>
                        <td>
                            <?php
                                echo $percentused;
                                _e( '% used of ', 'siw' );
                                echo number_format_i18n( $quota );
                                _e( 'M total', 'siw' );
                            ?>
                        </td>
                    </tr>
                </table>
                <?php
            }
        }
        
        /**
         * "My Account" widget callback
         */
        public function user_info_widget_cb() {
            $user_id = get_current_user_id();
            $user = new WP_User( $user_id );
            $blogs = get_blogs_of_user( $user_id );
            ?>
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td valign="top" width="110">
                        <?php echo get_avatar( $user_id ); ?>
                    </td>
                    <td valign="top">
                        <table cellpadding="5" cellspacing="0" width="100%" class="siw_tbl">
                            <tr>
                                <th><?php _e( 'Username', 'siw' ); ?></th>
                                <td><?php echo $user->user_login; ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Display Name', 'siw' ); ?></th>
                                <td>
                                    <?php echo $user->display_name; ?>
                                    <a class="manage_pro" href="<?php echo admin_url( 'profile.php' ); ?>"><?php _e( 'Update Profile', 'siw' ) ?></a>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Email', 'siw' ); ?></th>
                                <td>
                                    <?php echo $user->user_email; ?>
                                    <a class="manage_pro" href="<?php echo admin_url( 'my-sites.php' ); ?>"><?php _e( 'Manage Blogs', 'siw' ) ?></a>
                                </td>
                            </tr>
                        </table>
                        <p><?php printf( __( 'You are a member of %s blog(s).', 'siw' ), count( $blogs ) ); ?></p>
                    </td>
                </tr>
            </table>
            <?php
        }
        
        /**
         * SIW Styles
         */
        public function siw_style() {
            ?>
            <style>
            .siw_tbl{margin-bottom: 10px;}
            .siw_tbl tr th{width: 25%; text-align: left; vertical-align: top; border-right: 1px solid #409ed0}
            .siw_tbl tr td{padding-left: 15px;}
            .manage_pro{background-image: -moz-linear-gradient(center bottom , #3b85ad, #419ece) !important; border: 1px solid #409ed0 !important; border-radius: 4px;color: #fff; display: block; font-size: 11px; font-weight: bold; height: 18px !important; line-height: 18px !important; margin: 0 1px; padding: 0 30px !important; float: right;}
            .manage_pro:hover{color: #fff !important}
            </style>
            <?php
        }
        
        
        
    }
    
    add_action( 'plugins_loaded', 'siw_init' );
    function siw_init() {
        return Site_Info_Widgets::get_instance();
    }
}

