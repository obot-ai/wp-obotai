<?php
/*
Plugin Name: ObotAI
Plugin URI: 
Description: ObotAIのWebChatを設置するプラグイン
Version: 1.0.0
Author: Mariana Ocean Japan
Author URI: https://obot-ai.com/
License: GPLv3
*/

/*  Copyright 2019 MARIANA OCEAN JAPAN Co., Ltd. (email : obotai@marianaocean.com)

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
    public $obotai_db_version = '1.0';

    function __construct() {
        register_activation_hook( __FILE__, array($this, 'obotai_install') );
        // 管理メニューに追加するフック
        add_action( 'admin_menu', array($this, 'add_obotai_page') );
        add_shortcode( 'obotai_code', array( 'ObotAISettingCord', 'obotai_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array($this, 'obotai_head_function') );
        add_action( 'wp_footer', array($this, 'obotai_footer_function'), 100 );
    }

    function obotai_install() {
        global $wpdb;
        global $obotai_db_version;

        $table_name = $wpdb->prefix . 'obotai_setting';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name text NOT NULL,
            obotai_key text NOT NULL,
            user text NOT NULL,
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
                            'name' => $_POST['obotai_options']['name'],
                            'obotai_key' => $_POST['obotai_options']['key'],
                            'user' => $_POST['obotai_options']['user'],
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
                    $sql = "SELECT name,obotai_key,user,url,css,valid FROM ".$table_name;
                    $results = $wpdb->get_results($sql);
?>
                     <tr valign="top">
                        <th scope="row">
                            <label for="inputtext">Bot名</label>
                        </th>
                        <td>
                            <input
                                name="obotai_options[name]"
                                type="text"
                                size="100"
                                placeholder="Chat Bot"
                                value="<?php echo $results[0]->name ?>"
                            />
                        </td>
                    </tr>
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
                            <label for="inputtext">ユーザー名</label>
                        </th>
                        <td>
                            <input
                                name="obotai_options[user]"
                                type="text"
                                size="100"
                                placeholder="お客様"
                                value="<?php echo $results[0]->user ?>"
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

    function obotai_head_function() {
        wp_enqueue_style( 'obotai-botchat', plugins_url( 'css/obotai_botchat.css', __FILE__ ), array() );
        wp_enqueue_style( 'obotai-botchat-typed', plugins_url( 'css/obotai_botchat_typed.css', __FILE__ ), array() );
        wp_enqueue_script( 'jquery-ui-draggable' );

        // ユーザーが用意したcssを登録
        global $wpdb;
        $table_name = $wpdb->prefix . 'obotai_setting';
        $sql = "SELECT css FROM ".$table_name;
        $results = $wpdb->get_results($sql);

        if( $results[1]->css){
            for($i=1; $i<count($results); $i++) {
                wp_enqueue_style( 'obotai-css-'.$i, $results[$i]->css );
            }
        }
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
        global $wpdb;
        $table_name = $wpdb->prefix . 'obotai_setting';
        $sql = "SELECT name,user FROM ".$table_name;
        $results = $wpdb->get_results($sql);

        if($results[0]->name){
            $name = $results[0]->name;
        }else{
            $name = "Chat Bot";
        }
        if($results[0]->user){
            $user = $results[0]->user;
        }else{
            $user = "お客様";
        }

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
                '<div id="bot_toggle">',
                '<img src="'. plugins_url( 'img/obotai_icon.svg', __FILE__ ) . '" class="pc_main">',
                '<img src="'. plugins_url( 'img/obotai_icon_sp.svg', __FILE__ ) . '" class="sp_main"></div>',
                '<div id="bot" >',
                '<script src="//cdn.botframework.com/botframework-webchat/latest/botchat.js"></script>',
                '<script>',
                'BotChat.App({',
                "directLine: { secret: '".$atts['obotai_code_id']."' },",
                "user: { id: '".$user."' }, bot: { id: 'botid' }, resize: 'window', chatTitle: '".$name."', showUploadButton: false",
                "}, document.getElementById('bot'));",
                '/* トグル表示 */',
                '(function($){',
                "$('#bot').draggable({ handle: '.wc-header' });",
                "$('#bot_toggle').on('click', function(){",
                "$('#bot').css('visibility')=='hidden' ? $('#bot').css({visibility:'visible'}).animate({opacity: 1}, 500) : $('#bot').css({visibility:'hidden'}).animate({opacity: 0}, 500);",
                "});",
                "})(jQuery)",
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