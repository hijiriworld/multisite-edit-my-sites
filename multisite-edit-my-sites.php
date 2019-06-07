<?php
/*
 * Plugin Name: Multisite Edit My Sites
 * Plugin URI: http://hijiriworld.com/web/plugins/multisite-edit-my-sites/
 * Description: For Multisite, Edit the role of all sites for the user from the network user edit screen.
 * Version: 1.0.0
 * Author: hijiri
 * Author URI: http://hijiriworld.com/web/
 * Text Domain: hems
 * Domain Path: /languages
*/

if( ! is_multisite() ) exit; // multisite check

if( ! class_exists( 'HEMS' ) ) :

class HEMS
{
	function __construct()
	{
		
		define( 'HEMS_DIR', plugin_dir_url( __FILE__ ) );
		
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'edit_user_profile', array( $this, 'user_profile_page' ) ); // admin -> user profile
		// add_action( 'show_user_profile', array($this, 'user_profile_page')); // user -> own profile
		// add_action( 'user_profile_update_errors', array( $this, 'ValidateUserProfilePage' ), 0, 3 );

		add_action( 'profile_update', array( $this, 'profile_update' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'load_script_css' ) );
	}


	function load_plugin_textdomain()
	{
		load_plugin_textdomain( 'hems', false, basename( dirname( __FILE__ ) ).'/languages/' );
	}

	function load_script_css()
	{
		if( false !== strpos( $_SERVER[ 'REQUEST_URI' ], 'network/user-edit.php' ) )
		{
			wp_enqueue_script( 'hems-js', HEMS_DIR . 'js/script.js', array( 'jquery' ), '1.0', true );
			wp_enqueue_style( 'hems-css', HEMS_DIR . 'css/style.css', array(), '1.0', null );
		}
	}

	public function user_profile_page( $user=null )
	{

		if( !is_super_admin() ) return;
		if( false === strpos( $_SERVER[ 'REQUEST_URI' ], 'network/user-edit.php' ) ) return;

		global $wpdb, $profileuser, $blog_id, $table_prefix;
		
		$blogs = $this->override_wp_get_sites(
			array(
				'public'   => 1,
				'archived' => 0,
				'deleted'  => 0,
				'limit'    => 9999,
		));

		$my_blogs    = get_blogs_of_user( $profileuser->ID );
		$my_blog_ids = array();
		$my_roles    = array();

		foreach( $my_blogs as $my ) {

			$my_blog_ids[] = $my->userblog_id;

			// switch_to_blogを回避
			$field = ( 1 === $my->userblog_id ) ? 'capabilities' : $my->userblog_id.'_capabilities';
			$field = $table_prefix.$field;

			// 参加済みサイトの権限取得
			$my_role = get_user_meta( $profileuser->ID, $field );
			if( isset( $my_role[0] ) ) {
				foreach( $my_role[0] as $role => $val ) {
					if( true === $val ) {
						$my_roles[$my->userblog_id] = $role;
					}
				}
			}
		}

		$all_roles = get_editable_roles();
		?>

		<table class="form-table hems">
			<tr>
				<th><?php _e( 'My Sites' ); ?></th>
				<td>
					<div class="alignleft actions bulkactions">
						<select name="hems_all_select" id="hems_all_select">
							<option value=""><?php _e( 'Change role to...', 'hems' ); ?></option>
							<?php foreach( $all_roles as $role => $val ) : ?>
								<option value="<?php echo $role; ?>"><?php _e( translate_user_role($val['name']) ); ?></option>
							<?php endforeach; ?>
						</select>
						<input type="button" value="Change" name="hems_all_apply" id="hems_all_apply" class="button">							
					</div>

					<table class="wp-list-table widefat fixed striped sites">
						<thead>
							<tr>
								<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php _e( 'Select All' ); ?></label><input id="cb-select-all-2" type="checkbox"></td>
								<td class="column-sitename"><?php _e( 'Sitename', 'hems' ); ?></td>
								<td class="column-siturl"><?php _e( 'Siteurl', 'hems' ); ?></td>
								<td class="column-role"><?php _e( 'Role' ); ?></td>
							</tr>
						</thead>
						<tbody id="the-list">
							
							<?php foreach( $blogs as $blog ) : ?>
							<?php $blog_details = get_blog_details( $blog['blog_id'] ); ?>				
							<tr>
								<th scope="row" class="check-column">
									<?php
										printf( '<input type="checkbox" name="hems_check[%d][blog_id]" id="hems_check_%d" value="%d">',
											$blog['blog_id'],
											$blog['blog_id'],
											$blog['blog_id']
										);
									?>
								</th>
								<td><?php echo $blog_details->blogname; ?></td>
								<td><?php echo $blog_details->siteurl ;?></td>
								<td>
									<select name="users_sites[<?php echo $blog['blog_id']; ?>][role]">
									<?php $flg = false; ?>
									<option value="">-</option>
									<?php foreach( $all_roles as $role => $val ) : ?>
										<?php if( isset( $my_roles[$blog['blog_id']] ) && $role === $my_roles[$blog['blog_id']] ) : ?>
											<option value="<?php echo $role; ?>" selected><?php _e( translate_user_role( $val['name'] ) ); ?></option>
										<?php else : ?>
											<option value="<?php echo $role; ?>"><?php _e( translate_user_role( $val['name'] ) ); ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<?php endforeach; ?>
							
						</tbody>
						<tfoot>
							<tr>
								<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2" type="checkbox"></td>
								<td class="column-sitename"><?php _e( 'Sitename', 'hems' ); ?></td>
								<td class="column-siturl"><?php _e( 'Siteurl', 'hems' ); ?></td>
								<td class="column-role"><?php _e( 'Role' ); ?></td>
							</tr>
						</tfoot>
					</table>
					
					
				</td>
			</tr>
		</table>
		<?php
	}

	function profile_update( $user_id, $old_user_data )
	{
		if( !is_super_admin() ) return;
		if( !isset($_POST['users_sites']) || !is_array($_POST['users_sites']) ) return;

		$upd_item = serialize( $_POST['users_sites'] );

		if( !isset($old_user_data->users_sites) ||
			( isset( $old_user_data->users_sites ) && $old_user_data->users_sites != $upd_item )
		) {

			// ログイン時に自動で遷移するサイトIDの更新フラグ（権限があるサイトのうち最もサイトIDが小さいもの）
			$primary_blog_flg = TRUE;

			foreach( $_POST['users_sites'] as $blog_id => $item ) {
				if( !isset($item['role'] ) || $item['role'] === '' ){
					remove_user_from_blog( $user_id, $blog_id );
				} else {
					add_user_to_blog( $blog_id, $user_id, $item['role'] );
					if( $primary_blog_flg ){
						update_user_meta( $user_id,'primary_blog',$blog_id );
						$primary_blog_flg = FALSE;
					}
				}
			}
		}
	}

	/*
	 * wp_get_sites の使用はバージョン 4.6.0 から非推奨 の対応
	 */
	function override_wp_get_sites( $args = array() )
	{
		if( version_compare( get_bloginfo( 'version' ), '4.6.0' ) >= 0 ) {
			$sites = array();
			if (array_key_exists('limit', $args)) {
				$args['number'] = $args['limit'];
			}

			$obj_sites = get_sites($args);
			$sites = json_decode(json_encode($obj_sites), true);
		} else {
			$sites = wp_get_sites($args);
		}
		return $sites;
	}
}

$hems = new HEMS;

endif; // class_exists check
?>
