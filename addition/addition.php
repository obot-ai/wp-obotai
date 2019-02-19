<?php
/*
Plugin Name: Addition
Plugin URI: 
Description: WebChatを設置するプラグイン
Version: 1.0.0
Author: Mariana Ocean Japan
Author URI: https://obot-ai.com/
License: GPL2
*/

/*  Copyright 2019 Mariana Ocean Japan Co., Ltd. (email : obotai@marianaocean.com)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class AdditionSetting {
	public $moj_db_version = '1.0';
	
	function __construct() {
		register_activation_hook( __FILE__, array($this, 'moj_install'));
		// 管理メニューに追加するフック
		add_action('admin_menu', array($this, 'add_pages'));
		add_shortcode( 'test', array( 'AdditionShortCord', 'shortcode_test' ) );
		add_action('wp_footer', array($this, 'my_admin_footer_function'));
	}
	
	function moj_install() {
		global $wpdb;
		global $moj_db_version;
		
		$table_name = $wpdb->prefix . 'moj_table';
		$charset_collate = $wpdb->get_charset_collate();
		
        $sql = "CREATE TABLE $table_name (
			moj_id mediumint(9) NOT NULL AUTO_INCREMENT,
			moj_key text NOT NULL,
			moj_url text NOT NULL,
			UNIQUE KEY moj_id (moj_id)
		) $charset_collate;";
		
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
        update_option('moj_db_version', $moj_db_version);
    }

	function add_pages() {
		add_menu_page('webchat設定', 'webchat設定', 'level_8', __FILE__, array($this,'addition_option_page'), '');
	}
	
	public function addition_option_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'moj_table';
		
		$sql = "SELECT * FROM ".$table_name;
		$results = $wpdb->get_results($sql);
		$sql_desc = "SELECT * FROM ".$table_name." ORDER BY moj_id DESC";
		$results_desc = $wpdb->get_results($sql_desc);
		
		if ( isset($_POST['addition_options'])) {
			check_admin_referer('shoptions');
			?>
				<div id="message" class="updated notice is-dismissible">
					<p><strong><?php _e('保存しました'); ?></strong></p>
				</div>
			<?php
		}
		?>
			<div class="wrap">
				<div id="icon-edit-comments" class="icon32"><br /></div>
				<h2>Webchat設定</h2>
				<form action="http://wp-plugin.obot-ai.com/wp-admin/admin.php?page=addition%2Faddition.php" method="post">
					<?php
						wp_nonce_field('shoptions');
					?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								<label for="inputtext">発行ID</label>
							</th>
							<td>
								<input name="addition_options[text]" 
									type="text"
									id="inputtext"
									value=""
									class="regular-text"
								/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="inputtext">登録URL</label>
							</th>
							<td>
								<input name="addition_options[url]" 
									type="text"
									id="inputtext"
									value=""
									class="regular-text"
								/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">設定</th>
							<td><ul style="list-style:none;">
								<li>【シークレットキー】</li>
								<li>
									<?php
										foreach ($results_desc as $value) {
											if($value->moj_key){
												// 最新のものだけ表示する
												echo $value->moj_key;
												break;
											}
										}
									?>
								</li>
								<li>【非表示ページ】</li>
								<?php
									foreach ($results as $value) {
										if($value->moj_url){
											// 存在する時だけ表示する
											echo "<li>".urldecode($value->moj_url)."</li>";	
										}
									}
								?>
							</ul></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit"
							name="Submit"
							class="button-primary"
							value="Webchatを設定"
						/>
					</p>
					<?php 
						$wpdb->insert(
							$table_name,
							array(
								'moj_key' => $_POST['addition_options']['text'],
								'moj_url' => $_POST['addition_options']['url']
							)
						);
					?>
				</form>
			<!-- /.wrap --></div>
		<?php
	}

	public function my_admin_footer_function() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'moj_table';
		
		$sql = "SELECT * FROM ".$table_name;
		$results = $wpdb->get_results($sql);
		$sql_desc = "SELECT * FROM ".$table_name." ORDER BY moj_id DESC";
		$results_desc = $wpdb->get_results($sql_desc);
		
		// 現在地
		$now_url = get_permalink();
		$now_url = urldecode($now_url);
		// URL登録
		$url_list = [];
		foreach ($results as $value) {
			$url_list[] = urldecode($value->moj_url);
		}
		// シークレットキー登録
		foreach ($results_desc as $value) {
			if($value->moj_key){
				// 最新のものだけ登録する
				$addition_cord = $value->moj_key;
				break;
			}
		}
		
		if(in_array($now_url, $url_list)){
			// 現在地が登録URLに含まれる場合チャットは非表示
			exit;
		}else{
			// チャット表示
			$short_cord = '[test test_id='.$addition_cord.']';
			echo do_shortcode($short_cord);
		}
	}
}

class AdditionShortCord {	
	function shortcode_test($atts){
		$atts = shortcode_atts(
			array(
				'test_id' => '未設定'	//初期値
			), 
			$atts,
			'test'	//ショートコード名
		);
		
		if( $atts['test_id'] == '未設定'){
			$msg = "IDが未設定です";
		} else {
			$arr = [
				'<!DOCTYPE html>',
				'<html><head><meta charset="UTF-8">',
				'<style>',
				'* {margin: 0px; box-sizing: border-box;}',
				'#webchat,',
				'#webchat > * {border: 1px solid #cccccc; height: 400px; max-width: 100%;}',
				'</style></head>',
				'<body><div id="webchat" >',
				'<script src="https://cdn.botframework.com/botframework-webchat/latest/webchat.js"></script>',
				'<script>',
				'window.WebChat.renderWebChat({',
				"directLine: window.WebChat.createDirectLine({ secret: '".$atts['test_id']."' }),",
				"user: { id: 'userid' }",
				"}, document.getElementById('webchat'));",
				'</script></div></body></html>',
			];
			$msg = implode('', $arr);
		}	
		return $msg;
	}
}

$additionsetting = new AdditionSetting;
$additionshortcord = new AdditionShortCord;

?>