<?php
/*
Plugin Name: ObotAI
Plugin URI: 
Description: ObotAIのWebChatを設置するプラグイン
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

class ObotAISetting {
    public $obotai_db_version = '1.0';

    function __construct() {
        register_activation_hook( __FILE__, array($this, 'obotai_install'));
        // 管理メニューに追加するフック
        add_action('admin_menu', array($this, 'add_obotai_page'));
        add_shortcode( 'obotai_code', array( 'ObotAISettingCord', 'obotai_shortcode' ) );
        add_action('wp_head', array($this, 'obotai_head_function'));
        add_action('wp_footer', array($this, 'obotai_footer_function'));
    }

    function obotai_install() {
        global $wpdb;
        global $obotai_db_version;

        $table_name = $wpdb->prefix . 'obotai_setting';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            obotai_key text NOT NULL,
            url text NOT NULL,
            css text NOT NULL,
            valid text NOT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option('obotai_db_version', $obotai_db_version);
    }

    function add_obotai_page() {
        add_menu_page('ObotAI ウェブチャット設定', 'ObotAI', 'level_8', __FILE__, array($this,'obotai_option_page'), '');
    }

    public function obotai_option_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'obotai_setting';

        if ( isset($_POST['obotai_options'])) {
            check_admin_referer('shoptions');
            // テーブル初期化
            $sql = "DELETE FROM ".$table_name;
            $wpdb->query($sql);
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
            <table class="table">
                <form action="" method="post">
<?php
                    wp_nonce_field('shoptions');
                    // テーブルに格納（url,css以外）
                    $wpdb->insert(
                        $table_name,
                        array(
                            'obotai_key' => $_POST['obotai_options']['key'],
                            'valid' => $_POST['obotai_options']['valid']
                        )
                    );
                    // 登録url,cssを行に分割
                    $array['url'] = explode("\n", $_POST['obotai_options']['url']);
                    $array['css'] = explode("\n", $_POST['obotai_options']['css']);
                    // 各行から空白を削除
                    $array['url'] = array_map('trim', $array['url']);
                    $array['css'] = array_map('trim', $array['css']);
                    // 文字数が0の行を取り除く
                    $array['url'] = array_filter($array['url'], 'strlen');
                    $array['css'] = array_filter($array['css'], 'strlen');
                    // indexを詰める
                    $array['url'] = array_values($array['url']);
                    $array['css'] = array_values($array['css']);
                    // テーブルに格納（url,css）
                    for($i=0, $j=0; ; $i++, $j++){
                        if($i<count($array['url']) || $j<count($array['css'])){
                            if(!$array['url'][$i]){
                                $array['url'][$i] = '';
                            }else{
                                // エンコード（urlのみ）
                                $array['url'][$i] = urlencode($array['url'][$i]);
                                // 記号を元に戻す
                                $array['url'][$i] = str_ireplace('%3a', ':', $array['url'][$i]);
                                $array['url'][$i] = str_ireplace('%2f', '/', $array['url'][$i]);
                                $array['url'][$i] = str_ireplace('%25', '%', $array['url'][$i]);
                            }
                            if(!$array['css'][$j]){
                                $array['css'][$j] = '';
                            }
                            $wpdb->insert(
                                $table_name,
                                array(
                                    'url' => $array['url'][$i],
                                    'css' => $array['css'][$j]
                                )
                            );
                        }else{
                            break;
                        }
                    }
                    // データベース昇順出力
                    $sql = "SELECT obotai_key,url,css,valid FROM ".$table_name;
                    $results = $wpdb->get_results($sql);
?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="inputtext">シークレットキー</label>
                        </th>
                        <td>
                            <input
                                name="obotai_options[key]"
                                type="text"
                                size="100"
                                value="<?php echo $results[0]->obotai_key ?>"
                            />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="inputtext">非表示ページ</label>
                        </th>
                        <td>
                            <textarea
                                    name="obotai_options[url]" 
                                    rows="10"
                                    cols="100"
                            ><?php
                                for($i=1; $i<count($results); $i++){
                                    if($results[$i]->url != ''){
                                        if($i>1){
                                            echo "\n";
                                        }
                                        echo urldecode($results[$i]->url);
                                    }
                                } 
                            ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="inputtext">CSS設定</label>
                        </th>
                        <td>
                            <textarea
                                    name="obotai_options[css]" 
                                    rows="5"
                                    cols="100"
                            ><?php
                                for($i=1; $i<count($results); $i++){
                                    if($results[$i]->css != ''){
                                        if($i>1){
                                            echo "\n";
                                        }
                                        echo $results[$i]->css;
                                    }
                                } 
                            ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">表示設定</th>
                        <td>
                            <input
                                name="obotai_options[valid]"
                                type="radio"
                                value="valid"
<?php
                                if( $results[0]->valid == 'valid' ){
?>
                                    checked
<?php
                                }
?>
                            >表示
                            <input
                                name="obotai_options[valid]"
                                type="radio"
                                value="unvalid"
<?php
                                if( $results[0]->valid != 'valid' ){
?>
                                    checked
<?php
                                }
?>
                            >非表示
                        </td>
                    </tr>
                    <p class="submit">
                        <input
                            name="save"
                            type="submit"
                            class="button-primary"
                            value="保存"
                        />
                    </p>
                </form>
            </table>
        <!-- /.wrap --></div>
<?php
    }

    public function obotai_head_function() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'obotai_setting';

        // データベース昇順出力
        $sql = "SELECT css FROM ".$table_name;
        $results = $wpdb->get_results($sql);

        $arr_head = [
            '<style>',
            '* {margin: 0px; box-sizing: border-box;}',
            '#bot,',
            '#bot > * {',
            'border: 1px solid #cccccc; height: 400px; max-width: 100%;}',
            '.css-1tdb3h1 img{max-height:100% !important; width:auto !important;}',
            '</style>',
        ];
        if( $results[1]->css){
            for($i=1; $i<count($results); $i++) {
                $arr_css[] = '<link href="'.$results[$i]->css.'" rel="stylesheet" />';
            }
        }

        if(!empty($arr_css)){
            $arr_head = array_merge($arr_head, $arr_css);
        }
        $arr_head = implode('', $arr_head);
        echo $arr_head;
    }

    public function obotai_footer_function() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'obotai_setting';

        // データベース昇順出力
        $sql = "SELECT obotai_key,url,valid FROM ".$table_name;
        $results = $wpdb->get_results($sql);

        // 現在地
        if(is_front_page() || is_home()){
            $now_url = home_url();
        }else{
            $now_url = get_permalink();
        }
        $now_url = "/".preg_quote($now_url, '/')."/i";        // 大文字小文字区別しないようにしておく
        // URL登録
        $url_list = [];
        foreach ($results as $value) {
            $url_list[] = $value->url;
        }

        if( $results[0]->valid == 'valid' ){
            // ウェブチャット表示設定時
            if(preg_grep($now_url, $url_list)){
                // 現在地が登録URLに含まれる場合チャットは非表示
                return;
            }else{
                // チャット表示
                $short_cord = '[obotai_code obotai_code_id='.$results[0]->obotai_key.']';
                echo do_shortcode($short_cord);
            }
        }else{
            // ウェブチャット非表示設定時
            return;
        }
    }
}

class ObotAISettingCord {
    function obotai_shortcode($atts){
        $atts = shortcode_atts(
            array(
                'obotai_code_id' => '未設定'    //初期値
            ),
            $atts,
            'obotai_code'    //ショートコード名
        );

        if( $atts['obotai_code_id'] == '未設定'){
            $msg = "IDが未設定です";
        } else {
            $arr_footer = [
                '<div id="bot" >',
                '<script src="//cdn.botframework.com/botframework-webchat/latest/webchat.js"></script>',
                '<script>',
                'const styleOptions = {',
                'bubbleBackground: "rgba(217, 217, 217, 0.15)",',
                'bubbleFromUserBackground: "rgba(0, 150, 130, 1)",',
                'bubbleFromUserTextColor: "white",',
                'bubbleMaxWidth: 600,',
                'avatarSize: 50, ',
                'botAvatarImage: "", ',
                'botAvatarInitials: "Bot", ',
                'userAvatarImage: "", ',
                'userAvatarInitials: "", ',
                'hideSendBox: false,',
                'hideUploadButton: true,',
                'sendBoxButtonColor: "#767676",', 
                'sendBoxButtonColorOnDisabled: "#CCC",',
                'sendBoxButtonColorOnFocus: "#333",',
                'sendBoxButtonColorOnHover: "#333",',
                'sendBoxHeight: 40,',
                'suggestedActionTextColor: "black",',
                'suggestedActionBorder: "olid 2px #009682",',
                'suggestedActionHeight: 30,',
                '};',
                'window.WebChat.renderWebChat({',
                "directLine: window.WebChat.createDirectLine({ secret: '".$atts['obotai_code_id']."' }),",
                "user: { id: 'userid' },",
                "styleOptions",
                "}, document.getElementById('bot'));",
                '</script></div>',
            ];
            $arr_footer = implode('', $arr_footer);
        }
        return $arr_footer;
    }
}

$obotaisetting = new ObotAISetting;
$obotaisettingcord = new ObotAISettingCord;

?>