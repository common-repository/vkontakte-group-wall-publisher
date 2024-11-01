<?php

/*
Plugin Name: Skylark Vkontakte Group Wall Auto-Post
Plugin URI:
Description: Automatic post on the Vkontakte Group Wall
Version: 0.4.6.0
Author: Oleg Zhavoronkin
Author URI: http://webstudy.com.ua
*/


add_option("skylark_vk_application_id", '', "Vkontakte application id", 'no'); // default value
add_option("skylark_vk_group_id", '', "Vkontakte group id", 'no'); // default value
add_option("skylark_vk_autopost_on_publish", '', "Automatically prompt the user with the post to wall dialog", 'no'); // default value
add_option("skylark_vk_use_excerpt_text", '', "Use excerpt as a default post text", 'no'); // default value
add_option("skylark_vk_use_excerpt_length", 250, "Excerpt Length", 'no'); // default value
add_option("skylark_vk_admin_email", '', "Admin email", 'no'); // default value
add_option("skylark_vk_admin_password", '', "Admin password", 'no'); // default value
add_option("skylark_vk_use_bot", '', "Use bot to make a wall post", 'no'); // default value
add_option("skylark_vk_phone_num", '', "Last 4 digits of your mobile phone registered ion Vkontakte", 'no'); // default value


// WP 3.0+
// add_action( 'add_meta_boxes', 'myplugin_add_custom_box' );

// backwards compatible
add_action('admin_init', 'myplugin_add_custom_box', 1);

add_action('save_post', 'mysave');
add_action('save_page', 'mysave');

function mysave($post_id)
{
	if (!headers_sent()) {
		setcookie('vk_post_saved', 1);
	}
	return $post_id;
}

function myplugin_add_custom_box()
{
	add_meta_box('vgwp', 'VKontakte Group Wall Publisher', 'add_custom_wall_publish_option', 'post');
	add_meta_box('vgwp', 'VKontakte Group Wall Publisher', 'add_custom_wall_publish_option', 'page');
}


function add_custom_wall_publish_option($post)
{
	$botUse = get_option('skylark_vk_use_bot');
	$botUse = !empty($botUse);

	if (!$botUse && $post->post_status != 'publish') {
		echo 'Post/Page should be Published first!';
		return false;
	}

	$vkontakteGroupId = get_option('skylark_vk_group_id');
	$vkontakteApplicationId = get_option('skylark_vk_application_id');
	$autopost = get_option('skylark_vk_autopost_on_publish') && isset($_COOKIE['vk_post_saved']) && $_COOKIE['vk_post_saved'] == 1;
	$excerptUse = get_option('skylark_vk_use_excerpt_text');
	$autopostUse = get_option('skylark_vk_autopost_on_publish');

	$link = get_permalink($post->ID);

	$title = get_the_title();

	$excerpt = html_entity_decode(get_the_excerpt());
	if (empty($excerpt)) {
		$excerpt = get_the_title();
	} else if (strlen($excerpt > 150)) {
		$excerpt = substr($excerpt, 0, 147) . '...';
	}

	?>

<?php if (!$botUse) { ?>

<script src="http://vk.com/js/api/openapi.js" type="text/javascript"></script>
<script type="text/javascript">
	VK.init({
		apiId: <?= $vkontakteApplicationId;?>
	});
	function postToWall() {

		VK.Auth.getLoginStatus(function (response) {
			if (response.session) {
				var title = '<?= $title ?>';
				var excerpt = '<?= $excerpt ?>'

				if (document.getElementById('vgwp_publish_custom_text').checked) {
					var title = document.getElementById('vgwp_publish_text').value;
				}
				if (document.getElementById('vgwp_publish_excerpt_text').checked) {
					var title = excerpt;
				}

				VK.Api.call('wall.post', {owner_id:-<?=$vkontakteGroupId?>, from_group:1, message:title, attachments:'<?=$link;?>'}, function (r) {
					if (r.error && r.error.error_code == '10008') {
						VK.Auth.logout();
						postToWall();
					}
				});
			} else {
				VK.Auth.login(function (authR) {
					if (authR.session) {
						postToWall();
					}
				});
			}
		});
	}
</script>
<?php } ?>
<table>
	<?php if ($botUse) { ?>
	<tr>
		<th scope="row" style="text-align:right; vertical-align:top;">
			Опции публикации на стену группы ВКонтакте
		</th>
		<td>
			<input type="radio" name="vgwp_publish" checked="1" value="1" id="var1"/><label for="var1">Запостить на стену ВК только в случае если это первая публикация поста</label><br/><br/>
			<input type="radio" name="vgwp_publish" value="2" <?php if (!empty($autopostUse)) { ?>checked="1"<?php }?>
				   id="var2"/><label for="var2">Обязательно запостить на стену ВК (даже если это просто обновление поста)</label><br/><br/>
			<input type="radio" name="vgwp_publish" value="3" id="var3"/><label for="var3">Не постить на стену ВК</label>
		</td>
	</tr>
	<?php } ?>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<th scope="row" style="text-align:right; vertical-align:top;">
			Текст сообщения которое будет отправлено на стену ВК
		</th>
		<td>
			<input type="radio" name="vgwp_publish_text_variant" <?php if (empty($excerptUse)) { ?>checked="1"<?php }?>
				   value="1" checked="1" id="var4"/><label for="var4">Только заголовок</label><br/><br/>
			<input type="radio" id="vgwp_publish_excerpt_text"
				   name="vgwp_publish_text_variant" <?php if (!empty($excerptUse)) { ?>checked="1"<?php }?>  value="2"/><label
				for="vgwp_publish_excerpt_text">Краткое содержание</label><br/><br/>
			<input type="radio" id="vgwp_publish_custom_text" name="vgwp_publish_text_variant" value="3"/><label
				for="vgwp_publish_custom_text">Собственный текст (введите ниже)</label><br/><br/>
			<textarea id="vgwp_publish_text" name="vgwp_publish_text" stype="width:300px;height:200px"></textarea>
		</td>
	</tr>
	<?php if (!$botUse) { ?>
	<tr>
		<th scope="row" style="text-align:right; vertical-align:top;">
		</th>
		<td>
			<a href="javascript:void(0)" onclick="postToWall()" class="button-primary">Запостить на ВК</a>
			<?php if ($autopost) { ?>
			<script type="text/javascript">postToWall();</script>
			<?php } ?>
			<script type="text/javascript">document.cookie = 'vk_post_saved=0';</script>
		</td>
	</tr>
	<?php } ?>
</table>
<?php

}

function skylark_vkontakte_wall_post_options_page()
{
	if ($_POST) {
		if ($_POST['skylark_vk_application_id']) {
			update_option('skylark_vk_application_id', $_POST['skylark_vk_application_id']);
		}
		if (isset($_POST['skylark_vk_use_excerpt_length'])) {
			update_option('skylark_vk_use_excerpt_length', $_POST['skylark_vk_use_excerpt_length']);
		} else {
			update_option('skylark_vk_use_excerpt_length', 250);
		}
		if ($_POST['skylark_vk_group_id']) {
			update_option('skylark_vk_group_id', $_POST['skylark_vk_group_id']);
		}
		if ($_POST['skylark_vk_autopost_on_publish']) {
			update_option('skylark_vk_autopost_on_publish', $_POST['skylark_vk_autopost_on_publish']);
		} else {
			update_option('skylark_vk_autopost_on_publish', '');
		}

		if ($_POST['skylark_vk_use_excerpt_text']) {
			update_option('skylark_vk_use_excerpt_text', $_POST['skylark_vk_use_excerpt_text']);
		} else {
			update_option('skylark_vk_use_excerpt_text', '');
		}

		if ($_POST['skylark_vk_use_bot']) {
			update_option('skylark_vk_use_bot', $_POST['skylark_vk_use_bot']);
		} else {
			update_option('skylark_vk_use_bot', '');
		}

		if ($_POST['skylark_vk_admin_email']) {
			update_option('skylark_vk_admin_email', $_POST['skylark_vk_admin_email']);
		}
		if ($_POST['skylark_vk_admin_password']) {
			update_option('skylark_vk_admin_password', $_POST['skylark_vk_admin_password']);
		}
		if ($_POST['skylark_vk_phone_num']) {
			update_option('skylark_vk_phone_num', $_POST['skylark_vk_phone_num']);
		}
	}


	$vkontakteApplicationId = get_option('skylark_vk_application_id');
	$vkontakteGroupId = get_option('skylark_vk_group_id');
	$autopost = get_option('skylark_vk_autopost_on_publish');
	$excerptUse = get_option('skylark_vk_use_excerpt_text');
	$excerptLength = get_option('skylark_vk_use_excerpt_length');
	$botUse = get_option('skylark_vk_use_bot');
	$vkontakteEmail = get_option('skylark_vk_admin_email');
	$vkontaktePassword = get_option('skylark_vk_admin_password');
	$phoneNum = get_option('skylark_vk_phone_num');


	$autopostText = '';
	if ($autopost) {
		$autopostText = 'checked="checked"';
	}

	$excerptUseText = '';
	if ($excerptUse) {
		$excerptUseText = 'checked="checked"';
	}
	$botUseText = '';
	$botUseHide = '';
	$botUseShow = '';
	if ($botUse) {
		$botUseText = 'checked="checked"';
		$botUseHide = 'style="display:none"';
	} else {
		$botUseShow = 'style="display:none"';
	}

	print '
	<div class="wrap">
	<h2>Настройки Vkontakte Group Wall Post</h2>
	<form method="post" action="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '">

	<p>ID Группы или Официальной страницы VK</p>
	<input type="text" id="skylark_vk_group_id" name="skylark_vk_group_id" value="' . $vkontakteGroupId . '">

	<div id="appid" ' . $botUseHide . '>
		<p>ID приложения на VK</p>
		<input type="text" id="skylark_vk_application_id" name="skylark_vk_application_id" value="' . $vkontakteApplicationId . '">
	</div>


	<p>Кроспостинг непосредственно с вашего сервера. Данная опция значительно расширяет функционал плагина.</p>
	<input type="checkbox" id="skylark_vk_use_bot" onchange="if(this.checked){document.getElementById(\'appid\').style.display=\'none\';document.getElementById(\'usercredid\').style.display=\'block\';}else{document.getElementById(\'appid\').style.display=\'block\';document.getElementById(\'usercredid\').style.display=\'none\';}" name="skylark_vk_use_bot" value="1" ' . $botUseText . '>
	<label for="skylark_vk_use_bot">Использовать бота</label>

	<div id="usercredid" ' . $botUseShow . '>
		<p>Логин\Email пользователя VK</p>
		<input type="text" id="skylark_vk_admin_email" name="skylark_vk_admin_email" value="' . $vkontakteEmail . '">

		<p>Пароль пользователя VK</p>
		<input type="text" id="skylark_vk_admin_password" name="skylark_vk_admin_password" value="' . $vkontaktePassword . '">

		<p>Последние 4 цифры мобильного телефона с которым вы регистрировались на VK (необходимо для прохождения доп. проверки, в случае если  сервер территориально находится в другой стране, нежели страна регистрации пользователя VK)</p>
		<input type="text" id="skylark_vk_phone_num" name="skylark_vk_phone_num" value="' . $phoneNum . '">
	</div>

	<p>Автоматически всегда постить на стену вконтакте</p>
	<input type="checkbox" id="skylark_vk_autopost_on_publish" name="skylark_vk_autopost_on_publish" value="1" ' . $autopostText . '>
	<label for="skylark_vk_autopost_on_publish">Постить автоматически</label>

	<p>Использовать краткое содержание в качестве текста поста</p>
	<input type="checkbox" id="skylark_vk_use_excerpt_text" name="skylark_vk_use_excerpt_text" value="1" ' . $excerptUseText . '>
	<label for="skylark_vk_use_excerpt_text">Использовать краткое содержание</label>

	<p>Если поле краткого содержания поста не заполнено, то будет использован отрезок текста самого поста от начала поста, соответствующей длины.</p>
	<p>Если указать значение равное либо меньше 0, то будет использован весь текст поста.</p>
	<input type="text" id="skylark_vk_use_excerpt_length" name="skylark_vk_use_excerpt_length" value="' . $excerptLength . '"><br />
	<label for="skylark_vk_use_excerpt_length">Длина краткого содержания поста</label>

	<p class="submit" style="width:420px;"><input type="submit" value="Submit &raquo;" /></p>
	</form>
	</div>
	';
}

function skylark_vk_group_post_admin_page()
{
	add_submenu_page('options-general.php', 'Vkontakte Group Wall Post', 'Vkontakte Group Wall Post', 9, 'skylark-vk-groupwall-post.php', 'skylark_vkontakte_wall_post_options_page');
}

function skylark_post_on_vkontakte_wall_forced($post_ID)
{
	if (isset($_POST['vgwp_publish']) && $_POST['vgwp_publish'] == 2) {
		return skylark_post_on_vkontakte_wall($post_ID);
	}

	return $post_ID;
}

function showAuthDialog()
{
	$query = http_build_query(array(
		'client_id' => 2827604,
		'scope' => 'offline, wall',
		'redirect_uri' => 'http://api.vk.com/blank.html',
		'display' => 'page',
		'response_type' => 'token'
	));

	$link = 'http://api.vk.com/oauth/authorize?' . $query;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $link);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

	curl_setopt($ch, CURLOPT_REFERER, 'vk.com');
	$otvet = curl_exec($ch);
	curl_close($ch);
	echo $otvet; die();
}

function requestVK($method, $params)
{
	$params['access_token'] = 'd938dda9d966d9bad966d9bab8d94dfceedd966d967f9ba11e0907e84ce54b3';

	$query = http_build_query($params);

	$link = 'https://api.vk.com/method/' . $method . '?' . $query;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $link);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

	curl_setopt($ch, CURLOPT_REFERER, 'vk.com');
	$otvet = curl_exec($ch);
	curl_close($ch);
	echo $otvet; die();
}

function skylark_post_on_vkontakte_wall($post_ID)
{
//	showAuthDialog();
	requestVK('wall.post', array(
		'owner_id' => '-29056835',
		'message' => 'Message from new API',
		'from_group' => 1,

	));

	return $post_ID;
}

function skylark_post_on_vkontakte_wall_bu($post_ID)
{
	$botUse = get_option('skylark_vk_use_bot');
	if (empty($botUse)) {
		return $post_ID;
	}

	if (!isset($_POST['vgwp_publish']) || $_POST['vgwp_publish'] == 3) {
		return $post_ID;
	}

	global $post;

	if ($post->toWallPublished) {
		return $post_ID;
	}

	$mail = get_option('skylark_vk_admin_email');
	$pass = get_option('skylark_vk_admin_password');
	$phoneNum = get_option('skylark_vk_phone_num');
	$group_id = get_option('skylark_vk_group_id');

	$link = get_permalink($post->ID);

	$excerptLength = get_option('skylark_vk_use_excerpt_length');

	if ($_POST['vgwp_publish_text_variant'] == 1) {
		$text = get_the_title() . "\n" . "$link";
	} else if ($_POST['vgwp_publish_text_variant'] == 3 && !empty($_POST['vgwp_publish_text'])) {
		$text = $_POST['vgwp_publish_text'] . "\n" . "$link";
	} else if ($_POST['vgwp_publish_text_variant'] == 2) {
		$excerpt = $_POST['excerpt'];
		if (empty($excerpt)) {
			$excerpt = strip_tags($_POST['content']);
			if ($excerptLength > 0) {
				$excerpt = substr($excerpt, 0, $excerptLength);
			}
		} else if (strlen($excerpt > $excerptLength)) {
			$excerpt = substr($excerpt, 0, $excerptLength) . '...';
		}
		$text = $excerpt . "\n" . "$link";
	}

	$text = stripslashes(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));

	$otvet = skylark_vk_connect("http://login.vk.com/?act=login&email=$mail&pass=$pass");
	If (!preg_match("/hash=(.*?)&/", $otvet, $hash)) {
		return $post_ID;
	}

	preg_match_all('/Set-Cookie: (.*?);/', $otvet, $cookies);

	$cookie = implode('; ', $cookies[1]);

	$otvet = skylark_vk_connect("http://vk.com/login.php?act=slogin&fast=1&hash=" . $hash[1] . "&s=1");
	preg_match_all('/Set-Cookie: (.*?);/', $otvet, $cookies);
	$cookie .= '; ' . implode('; ', $cookies[1]);

	$check = skylark_vk_connect("http://vk.com/friends.php?filter=recent", $cookie);

	if (substr_count($check, '/login.php?act=security_check&to=') > 0) {
		$check = skylark_vk_connect("http://vk.com/login.php?act=security_check&to=", $cookie);
		preg_match("/hash: \'(.*?)\'/", $check, $shash);
		$check = skylark_vk_connect("http://vk.com/login.php", $cookie, "act=security_check&code=" . $phoneNum . "&hash=" . $shash[1]);
	}

	$pageconnect = skylark_vk_connect("http://vk.com/club$group_id", $cookie);

	preg_match('/"share":({(.*?)})/', $pageconnect, $share_init);
	preg_match('/"post_hash":"(.*?)","media_types"/', $pageconnect, $post_hash);
	preg_match('/id: ([\d]+),/', $pageconnect, $vkUserId);

	$shareData = json_decode($share_init[1]);

	if (empty($post_hash[1])) {
		$pageconnect = skylark_vk_connect("http://vk.com/public$group_id", $cookie);
		preg_match('/"post_hash":"(.*?)","media_types"/', $pageconnect, $post_hash);
	}

	if ($shareData !== null) {
		$data = array(
			'act' => 'do_add_share',
			'mid' => $vkUserId[1],
			'aid' => -2,
			'gid' => 0,
			'vk' => 1,
			'from_host' => 'vk.com',
			'file' => 'http://webstudy.com.ua/wp-content/uploads/2011/11/languages.png',
			'hash' => $shareData->hash,
			'rhash' => $shareData->hash,
			'index' => 1,
			'extra' => 0
		);
		/*$data = array(
			'act' => 'parse_share',
			'from_host' => 'vk.com',
			'mid' => $vkUserId[1],
			'hash' => $shareData->hash,
			'rhash' => $shareData->hash,
			'url' => urlencode($link)
		);*/

		$str = '';
		foreach ($data as $param => $value) {
			$str .= '&' . $param . '=' . rawurlencode($value);
		}

		$parseShareJs = skylark_vk_connect($shareData->url . '?' . ltrim('&', $str), $cookie);

		var_dump($shareData->url . '?' . ltrim('&', $str), $parseShareJs);die();
	}

	$data = array(
		'act' => 'post',
		'al' => 1,
		'message' => $text,
		'official' => 1,
		'to_id' => '-' . $group_id,
		'type' => 'all',
		'hash' => $post_hash[1],
		'photo_url' => 'http://webstudy.com.ua/wp-content/uploads/2011/11/languages.png'
	);

	skylark_vk_connect("http://vk.com/al_wall.php", $cookie, $data);

	$post->toWallPublished = true;

	return $post_ID;
}

add_action('pending_to_publish', 'skylark_post_on_vkontakte_wall');
add_action('draft_to_publish', 'skylark_post_on_vkontakte_wall');
add_action('new_to_publish', 'skylark_post_on_vkontakte_wall');

add_action('publish_post', 'skylark_post_on_vkontakte_wall_forced');


add_action('admin_menu', 'skylark_vk_group_post_admin_page');

function skylark_vk_connect($link, $cookie = null, $post = null)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $link);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 0);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
	if ($cookie !== null)
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	if ($post !== null) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt($ch, CURLOPT_REFERER, 'vk.com');
	$otvet = curl_exec($ch);
	curl_close($ch);
	return $otvet;
}