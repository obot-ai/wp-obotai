<?php
/*
Plugin Name: ObotAI
Plugin URI: 
Description: ObotAIのWebChatを設置するプラグイン
Version: 2.0.0
Author: ObotAI
Author URI: https://obot-ai.com/
License: GPLv3
*/

/*  Copyright 2020 ObotAI. (email : info@obot-ai.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

class ObotAISetting {
    public $obotai_db_version = '2.0';

    function __construct() {
        register_activation_hook( __FILE__, array($this, 'obotai_install') );
        // 管理メニューに追加するフック
        add_action( 'admin_menu', array($this, 'add_obotai_page') );
        add_shortcode( 'obotai_code', array( 'ObotAISettingCord', 'obotai_shortcode' ) );
        add_action( 'wp_footer', array($this, 'obotai_footer_function'), 100 );
    }

    function obotai_install() {
        global $wpdb;
        global $obotai_db_version;

        $table_name = $wpdb->prefix . 'obotai_setting';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `cid` varchar(30) NOT NULL DEFAULT '',
            `exclude_urls` text NOT NULL DEFAULT '',
            `extra_js` text NOT NULL DEFAULT '',
            `visible` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY id (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option('obotai_db_version', $obotai_db_version);
    }

    function add_obotai_page() {
        add_menu_page('ウェブチャット設定', 'ObotAI', 'level_8', __FILE__, array($this,'obotai_option_page'), '');
    }

    public function obotai_option_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'obotai_setting';

        if ( isset($_POST['obotai_options'])) {
            check_admin_referer('obotai-settings');
            // テーブル初期化
            $sql = "DELETE FROM ".$table_name;
            $wpdb->query($sql);
            // エスケープ解除
            $POST = array_map( 'stripslashes_deep', $_POST);
            $wpdb->insert(
                $table_name,
                array(
                    'cid' => $POST['obotai_options']['cid'],
                    'exclude_urls' => $POST['obotai_options']['exclude_urls'],
                    'extra_js' => $POST['obotai_options']['extra_js'],
                    'visible' => $POST['obotai_options']['visible']
                )
            );
?>
            <div id="message" class="updated notice is-dismissible">
                <p><strong><?php _e('保存しました'); ?></strong></p>
            </div>
<?php
        }
?>
        <div class="wrap">
            <div id="icon-edit-comments" class="icon32"><br /></div>
            <h2>ObotAI ウェブチャット設定</h2>
            <form action="" method="post">
<?php
                    wp_nonce_field('obotai-settings');

                    // データベース読み込み
                    $sql = "SELECT cid,exclude_urls,extra_js,visible FROM $table_name LIMIT 1";
                    $results = $wpdb->get_results($sql);
?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cid">CID</label>
                        </th>
                        <td>
                            <input
                                id="cid" 
                                name="obotai_options[cid]"
                                type="text"
                                size="30"
                                value="<?php echo sanitize_text_field( $results[0]->cid ) ?>"
                            >
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="exclude_urls">除外URL</label>
                        </th>
                        <td>
                            <textarea
                                    id="exclude_urls" 
                                    name="obotai_options[exclude_urls]" 
                                    rows="10"
                                    cols="100"
                            ><?php
                                echo $results[0]->exclude_urls
                            ?></textarea>
                            <p class="description">
                                URLが除外URLに一致した場合、チャットボットを表示しません。
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="extra_js">追加JavaScript</label>
                        </th>
                        <td>
                            <textarea
                                    id="extra_js" 
                                    name="obotai_options[extra_js]" 
                                    rows="10"
                                    cols="100"
                            ><?php
                                echo $results[0]->extra_js
                            ?></textarea>
                            <p class="description">
                                メニューのカスタマイズなど、obotaiHooksを記述します。カスタマイズしない場合は空のままで問題ありません。
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">表示設定</th>
                        <td>
                            <input
                                name="obotai_options[visible]"
                                type="radio"
                                value="1"
<?php
                                if( $results[0]->visible == 1 ){
?>
                                    checked
<?php
                                }
?>
                            >表示
                            <input
                                name="obotai_options[visible]"
                                type="radio"
                                value="0"
<?php
                                if( $results[0]->visible != 1 ){
?>
                                    checked
<?php
                                }
?>
                            >非表示
                        </td>
                    </tr>
            </table>
            <p class="submit">
                <input
                    id="submit" 
                    name="save"
                    type="submit"
                    class="button button-primary"
                    value="変更を保存"
                >
            </p>
        </form>
        <!-- /.wrap --></div>
<?php
    }

    public function obotai_footer_function() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'obotai_setting';

        // データベース読み込み
        $sql = "SELECT cid,exclude_urls,extra_js,visible FROM $table_name LIMIT 1";
        $results = $wpdb->get_results($sql);

        // 非表示の場合は出力しない
        if ($results[0]->visible == 0) {
            return;
        }

        // 除外URL一覧
        $exclude_urls = explode("\n", $results[0]->exclude_urls);
        $exclude_urls = array_map('trim', $exclude_urls);
        $exclude_urls = array_filter($exclude_urls, 'strlen');
        $exclude_urls = array_values($exclude_urls);

        // 現在のURL
        if(is_front_page() || is_home()){
            $now_url = home_url();
        }else{
            $now_url = get_permalink();
        }
        // 除外URLにマッチする場合は出力しない
        foreach ($exclude_urls as $url) {
            $pattern = "/".preg_quote($url, '/')."/i";
            if (preg_match($pattern, $now_url)){
                return;
            }
        }

        // ウェブチャット表示設定時
        $short_cord = '[obotai_code obotai_code_id='.$results[0]->cid.']';
        echo do_shortcode($short_cord);
    }
}

class ObotAISettingCord {
    function obotai_shortcode($atts){
        global $wpdb;

        $params = shortcode_atts(
            array(
                'obotai_code_id' => ''    //初期値
            ),
            $atts,
            'obotai_code'    //ショートコード名
        );
        // CIDがない場合の出力
        if ($params['obotai_code_id'] == '') {
            return "<!-- obotai CIDが未設定です -->";
        }

        $table_name = $wpdb->prefix . 'obotai_setting';
        $sql = "SELECT extra_js FROM " . $table_name . " WHERE cid='" . $params['obotai_code_id'] . "' LIMIT 1";
        $results = $wpdb->get_results($sql);

        if( $atts['obotai_code_id'] == '未設定'){
            return "<!-- obotai CIDが未設定です -->";
        }

        $arr_footer = '<script defer src="https://app.webchat.obotai.com/loader/?cid='.$atts['obotai_code_id'].'"></script>';
        if ($results[0]->extra_js !== '') {
            $arr_footer = $arr_footer . '<script>' . $results[0]->extra_js . '</script>';
        }
        return $arr_footer;
    }
}

$obotaisetting = new ObotAISetting;
$obotaisettingcord = new ObotAISettingCord;

?>
