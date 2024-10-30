<?php
/*
Plugin Name: Interview
Plugin URI: https://www.interviewform.com
description: Plugin that allows to quickly create video/webcam interviews for guest posts purposes. Create questions and send the interview request to a guest so he answers to your  question with his webcam. Easy way to create fresh and original content for your blog.
Version: 1.01
Author: proxymis
Author URI: https://www.proxymis.com
License: GPL2
*/
define('INTERVIEW_PREFIX', 'proxymis');
define('INTERVIEW_TABLE_NAME', 'proxymis_interview');
define('INTERVIEW_QUESTIONS_TABLE_NAME', 'proxymis_questions');
define('INTERVIEW_USERS_ANSWERS_TABLE_NAME', 'proxymis_interview_users_answers');
define('INTERVIEW_USER_INTERVIEW_TABLE_NAME', 'proxymis_user_interview');
define('INTERVIEW_ROWS_PER_PAGE', '10');
define('INTERVIEW_CATEGORY', 'Interviews');
define('INTERVIEW_REQUEST_CATEGORY', 'Interviews Requests');


class InterviewSettings
{
	private $settings_options;
	public static $debug = true;

	public function __construct()
	{
		add_action('admin_menu', array($this, 'settings_add_plugin_page'));

	}

	public function settings_add_plugin_page()
	{
		add_menu_page(
			'🎙️ Interview',
			'Interview',
			'manage_options',
			'interviewSettings',
			array($this, 'settings_create_admin_page'),
			'dashicons-microphone',
			66 // position
		);
	}

	public function settings_create_admin_page() {
		$cache = (InterviewSettings::$debug)?'?cache='.time():'';
		wp_enqueue_style('interviewAdmin.css', plugins_url( '/css/interviewAdmin.css'.$cache, __FILE__ ) );
		wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');
		wp_enqueue_style('summernote.css', plugins_url('js/summernote/summernote-lite.min.css', __FILE__), null, '1.0');
		wp_enqueue_script('interviewAdmin', plugins_url('js/interviewAdmin.js'.$cache, __FILE__), array('jquery', 'jquery-ui-droppable','jquery-ui-draggable', 'jquery-ui-sortable'), '', false);
		wp_enqueue_script('summernote.js', plugins_url('js/summernote/summernote-lite.min.js', __FILE__), array('jquery'), '', false);
		wp_enqueue_script('thickbox', null, ['jquery']);

		add_thickbox();
		$current_user = wp_get_current_user();
		$params = [
			'ajaxurl'   => admin_url('admin-ajax.php'),
			'nonce'     => wp_create_nonce('interview_nonce'),
			'lang'      => $GLOBALS['lang'],
			'email'		=> $current_user->user_email
		];
		wp_localize_script('interviewAdmin', 'params', $params);
		$this->settings_options = get_option('settings_option_name'); ?>
		<?php
			$uploadFolder = __DIR__."/upload/";
			if (!is_writable( $uploadFolder)):?>
			<div class="interviewError">
				<?php echo sprintf(esc_html($GLOBALS['lang']['folderIsNotWritable']), $uploadFolder);?>
			</div>
		<?php endif;?>

		<div id="interviewAdminContainer" class="wrap">
			<?php settings_errors(); ?>
			<input type="radio" name="tabs" id="tab1" checked />
			<label for="tab1"><span class="dashicons dashicons-list-view"></span> <?php echo esc_html($GLOBALS['lang']['Interviews']);?></label>

			<input type="radio" name="tabs" id="tab2" />
			<label for="tab2"><span class="dashicons dashicons-admin-generic"></span> <?php echo esc_html($GLOBALS['lang']['IntegrationHelp']);?></label>

			<select name="langSelection" id="langSelection">
				<?php foreach (glob(__DIR__."/lang/*.json") as $file):?>
					<?php $lang = basename($file, '.json');?>
					<option <?php if ($_SESSION[INTERVIEW_PREFIX.'lang'] === $lang) echo 'selected';?>  value="<?php echo esc_attr($lang)?>"><?php echo esc_attr($lang)?></option>
				<?php endforeach;?>
			</select>

			<div class="tab content1">
				<div>
					<div id="playInterviewContainer" style="display:none;">
						<div id="playInterviewContent"></div>
					</div>

					<div id="inviteInterviewContainer" style="display:none;">
						<h3><?php echo esc_html($GLOBALS['lang']['InviteAnUserForInterview']);?></h3>
						<form id="formInviteInterview">
							<div id="interviewInviteContainer">
								<input type="hidden" id="inviteInterviewId">

								<div>
									<span class="dashicons dashicons-warning"></span>
									<?php echo ($GLOBALS['lang']['MakeSureYourWordpressAbleSendEmails']);?>
								</div>

								<div>
									<label for="inviteEmails">
										<?php echo ($GLOBALS['lang']['EmailDestinations']);?>
									</label>
									<input autocomplete="off" type="text" required placeholder="<?php echo esc_html($GLOBALS['lang']['Enter emails to send'])?>" id="inviteEmails" name="inviteEmails">
								</div>

								<div>
									<label for="inviteSubject"><?php echo esc_html($GLOBALS['lang']['Email subject']);?></label>
									<input autocomplete="off" type="text" required placeholder="<?php echo esc_html($GLOBALS['lang']['Email subject. Ex: interview requested']);?>" id="inviteSubject" name="inviteSubject">
								</div>

								<div>
									<label for="inviteInterviewMessage"><?php echo ($GLOBALS['lang']['Email content']);?></label>
									<textarea name="inviteInterviewMessage" id="inviteInterviewMessage"
											  placeholder="<?php echo sprintf(esc_html($GLOBALS['lang']['Email messageInterviewUrl is']), get_site_url());?>" required></textarea>
								</div>
								<div>
									<button type="button" id="inviteInterviewSendBtn"><span class="dashicons dashicons-email-alt2"></span> <?php echo esc_html($GLOBALS['lang']['Send interview request'])?></button>
								</div>
							</div>
						</form>
					</div>

					<div id="editInterviewContainer" style="display:none;">
						<h3><?php echo esc_html($GLOBALS['lang']['Edit the current interview']);?></h3>
						<form id="formEditInterview">
							<div id="interviewEditContainer">
								<input type="hidden" id="editInterviewId">
								<div><input type="text" id="editInterviewTitle" name="editInterviewTitle" required placeholder="<?php echo esc_html($GLOBALS['lang']['Enter interview title']);?>"></div>
								<div><textarea name="editInterviewDescription" id="editInterviewDescription" placeholder="<?php echo esc_html($GLOBALS['lang']['Interview description']);?>" required></textarea></div>
								<div><textarea name="editInterviewIndications" id="editInterviewIndications" placeholder="<?php echo esc_html($GLOBALS['lang']['Interview indication']);?>"></textarea></div>
								<h3><?php echo esc_html($GLOBALS['lang']['Interview questions']);?>
									<button type="button" id="editAddInterviewQuestion"><span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html($GLOBALS['lang']['Add question']);?></button>
								</h3>
								<div id="editQuestionsContainer"></div>
								<div>
									<button type="button" id="saveEditInterviewBtn"><span class="dashicons dashicons-saved"></span> <?php echo esc_html($GLOBALS['lang']['Save this interview'])?></button>
								</div>
							</div>
						</form>
					</div>

					<div id="addNewInterviewContainer" style="display:none;">
							<h3><?php echo esc_html($GLOBALS['lang']['Fill the form to add a new interview']);?></h3>
						<form id="formAddInterview">
							<div id="interviewAddContainer">
								<div><input type="text" required placeholder="<?php echo esc_html($GLOBALS['lang']['Enter interview title. Example'])?>"  id="interviewTitle" name="interviewTitle"></div>
								<div><textarea name="interviewDescription" id="interviewDescription" placeholder="<?php echo esc_html($GLOBALS['lang']['Interview description. Example'])?>" required></textarea></div>
								<div><textarea name="interviewIndications" id="interviewIndications" placeholder="<?php echo esc_html($GLOBALS['lang']['Interview indication Example'])?>"></textarea></div>
								<h3><?php echo esc_html($GLOBALS['lang']['Interview questions']);?>
									<button type="button" id="addInterviewQuestion"><span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html($GLOBALS['lang']['Add question']);?></button>
								</h3>
							</div>
							<div id="questionsContainer"></div>
							<div>
								<button type="button" id="saveInterviewBtn"><span class="dashicons dashicons-saved"></span> <?php echo esc_html($GLOBALS['lang']['Save this interview']);?></button>
							</div>
						</form>
					</div>

					<div style="margin: 20px 0;">
						<a id="interviewAddNewBtn" title="<?php echo esc_html($GLOBALS['lang']['Add new interview'])?>" href="#TB_inline?&inlineId=addNewInterviewContainer" class="thickbox">
							<span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html($GLOBALS['lang']['Create a new interview']);?>
						</a>
					</div>
					<div id="interviewTableContainer"></div>
				</div>
			</div>
			<div class="tab content2">
				<?php include_once "lang/help{$GLOBALS['country']}.html";?>
			</div>
		</div>
	<?php }

}
$table = $wpdb->prefix . INTERVIEW_TABLE_NAME;
register_activation_hook(__FILE__, 'interview_plugin_activate');
register_deactivation_hook(__FILE__, 'interview_plugin_deactivate');

add_action('init',			 		'setup_session');
add_action('wp_enqueue_scripts', 	'interview_load_js_scripts');
add_action('pre_get_posts', 		'exclude_interview_requests_home');

add_shortcode( 'interview', 		'interview_shortcode');
add_shortcode( 'interviewPublish', 	'interviewPublish_shortcode' );

add_action("wp_ajax_interview_insert", "interview_insert");
add_action("wp_ajax_interview_update", "interview_update");
add_action("wp_ajax_interview_delete_record", "interview_delete_record");
add_action("wp_ajax_interview_duplicate_record", "interview_duplicate_record");
add_action("wp_ajax_interview_get", "interview_get");

add_action("wp_ajax_interview_save_question", "interview_save_question");
add_action("wp_ajax_nopriv_interview_save_question", "interview_save_question");

add_action("wp_ajax_interview_change_lang", "interview_change_lang");
add_action("wp_ajax_interview_get_with_answers", "interview_get_with_answers");
add_action("wp_ajax_interview_get_records", "interview_get_records");
add_action("wp_ajax_interview_play_record", "interview_play_record");
add_action("wp_ajax_interview_invite", "interview_invite");
add_action("wp_ajax_interview_create_post", "interview_create_post");
add_action("wp_ajax_send_email_interview_over", "send_email_interview_over");
add_action("wp_ajax_nopriv_send_email_interview_over", "send_email_interview_over");

function setup_session() {
	if(!session_id()) {
		session_start();
		if (!isset($_SESSION[INTERVIEW_PREFIX.'lang'])) {
			$_SESSION[INTERVIEW_PREFIX.'lang'] = 'en';
		}
	}
	$GLOBALS['country'] = sanitize_text_field($_SESSION[INTERVIEW_PREFIX.'lang']);
	$GLOBALS['lang'] = json_decode(file_get_contents(__DIR__."/lang/{$GLOBALS['country']}.json"), true);

}
function interviewPublish_shortcode( $atts ) {
	return get_template_interview_get_with_answers($atts['id'], $atts['email']);
}

function interview_change_lang() {
	$lang = sanitize_text_field($_POST['lang']);
	$_SESSION[INTERVIEW_PREFIX.'lang'] = $lang;
}

function exclude_interview_requests_home($query) {
	if ($query->is_home() && $query->is_main_query()) {
		$categoryId = get_cat_ID(INTERVIEW_REQUEST_CATEGORY);
		$query->set( 'cat', -$categoryId );
	}
}
function interview_shortcode( $atts ) {
	global $wpdb;
	$forbiddenContent = $GLOBALS['lang']['forbiddenContent'];
	if (!isset($_REQUEST['email']) || !isset($_REQUEST['token'])) {
		return $forbiddenContent;
	}
	$email = sanitize_email($_REQUEST['email']);
	$token = sanitize_text_field($_REQUEST['token']);
	$table = $wpdb->prefix . INTERVIEW_USER_INTERVIEW_TABLE_NAME;
	$sql = "SELECT * FROM $table WHERE email = '$email' AND token = '$token'";
	$user = $wpdb->get_row($sql);
	if (!$user) {
		return $forbiddenContent;
	}

	wp_enqueue_script( 'videoRecorder', plugins_url( '/js/videoRecorder.js?cache='.time() , __FILE__ ), array('jquery') );
	wp_enqueue_script( 'interview', plugins_url( '/js/interview.js?cache='.time() , __FILE__ ),  array('jquery') );
	wp_enqueue_style( 'interview.css', plugins_url( '/css/interview.css?cache=' .time() , __FILE__ ), false);
	$a = shortcode_atts([
		'id' => 1,
	], $atts );
	$interviewid = $a['id'];
	$res = getInterview($interviewid, $user->email);
	$params = [
		'ajaxurl'   	=> admin_url('admin-ajax.php'),
		'nonce'     	=> wp_create_nonce('interview_nonce'),
		'interview'		=> json_encode($res),
		'uploadURL'		=> plugins_url( '/upload/save-video.php',__FILE__),
		'currentUser'	=> $user,
		'lang'			=>  $GLOBALS['lang'],
	];
	wp_localize_script('interview', 'params', $params);
	$content = "<div id='interviewContainer'></div>";
	return $content;
}

function interview_load_js_scripts() {
	wp_enqueue_style('dashicons');
}

function interview_plugin_deactivate()
{
	global $wpdb;
	$db_table_name = $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$sql = "DROP TABLE IF EXISTS $db_table_name";
	//$wpdb->query($sql);
}

function interview_plugin_activate()
{
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	wp_create_category( INTERVIEW_REQUEST_CATEGORY );
	wp_create_category( INTERVIEW_CATEGORY );

	$db_table_name = $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $db_table_name (
			  `id` int(11) NOT NULL,
			  `title` varchar(255) NOT NULL,
			  `description` text NOT NULL,
			  `indications` text NOT NULL,
			  `invitation` text NOT NULL,
			  `password` varchar(50) NOT NULL,
			  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `userCanRepeat` tinyint(1) NOT NULL DEFAULT '0',
			  `video` tinyint(1) NOT NULL DEFAULT '1'
			  
        ) $charset_collate;";
	dbDelta($sql);
	$sql = "ALTER TABLE `$db_table_name`ADD PRIMARY KEY (`id`);";
	$wpdb->query ($sql);

	$sql = "ALTER TABLE `$db_table_name` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";
	$wpdb->query ($sql);


	$db_table_name = $wpdb->prefix . INTERVIEW_QUESTIONS_TABLE_NAME;
	$sql = "CREATE TABLE $db_table_name (
			`id` int(11) NOT NULL,
			`interviewid` int(11) NOT NULL,
			`question` text NOT NULL,
			`secondsMax` int(11) NOT NULL DEFAULT '60',
			`secondsBeforeStart` int(11) NOT NULL DEFAULT '3',
			`canRepeat` tinyint(1) NOT NULL DEFAULT '0',
			`ordre` int(11) NOT NULL DEFAULT '0'
			) $charset_collate;";
	dbDelta($sql);
	$sql = "ALTER TABLE `$db_table_name` ADD PRIMARY KEY (`id`),   ADD KEY `interviewid` (`interviewid`);";
	$wpdb->query ($sql);
	$sql = "ALTER TABLE `$db_table_name` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";
	$wpdb->query ($sql);

	$db_table_name = $wpdb->prefix . INTERVIEW_USERS_ANSWERS_TABLE_NAME;
	$sql = "CREATE TABLE $db_table_name (
			  `id` int(11) NOT NULL,
			  `interviewid` int(11) NOT NULL,
			  `questionid` int(11) NOT NULL,
			  `userid` int(11) NOT NULL,
			  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL,
			  `filename` varchar(255) NOT NULL,
			  `done` tinyint(1) NOT NULL DEFAULT '0'
			) $charset_collate;";
	dbDelta($sql);

	$sql = "ALTER TABLE `$db_table_name`
			  ADD PRIMARY KEY (`id`),
			  ADD UNIQUE KEY `unik` (`questionid`,`userid`),
			  ADD KEY `interviewid` (`interviewid`),
			  ADD KEY `questionid` (`questionid`),
			  ADD KEY `userid` (`userid`);";
	$wpdb->query ($sql);

	$sql = "ALTER TABLE `$db_table_name` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";
	$wpdb->query ($sql);


	$db_table_name = $wpdb->prefix . INTERVIEW_USER_INTERVIEW_TABLE_NAME;
	$sql = "CREATE TABLE $db_table_name (
			`id` int(11) NOT NULL,
			`interviewid` int(11) NOT NULL,
			`email` varchar(60) NOT NULL,
			`token` varchar(80) NOT NULL
			) $charset_collate;";
	dbDelta($sql);
	$sql = "ALTER TABLE `$db_table_name`   ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unik` (`interviewid`,`email`), ADD KEY `interviewid` (`interviewid`);";
	$wpdb->query ($sql);
	$sql = "ALTER TABLE `$db_table_name` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";
	$wpdb->query ($sql);

}

function send_email_interview_over() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	$interviewid 	= intval($_POST['interviewid']);
	$email		 	= sanitize_email($_POST['email']);
	$interview 		= getInterview($interviewid);
	$interview		= $interview['interview'];
	$user 			= getUserByEmail( $email);
	$to 			= get_option('admin_email');
	$subject 		= sprintf(esc_html($GLOBALS['lang']['Interview X completed by Y']), $interview->title, $user->email);
	$headers 		= array('Content-Type: text/html; charset=UTF-8');
	$message 		= get_template_interview_get_with_answers($interviewid, $email);
	$message.= $GLOBALS['lang']['Message Interview Is Over'];

	//$message 		= $subject;
	wp_mail( $to, $subject, $message, $headers);
	exit("sent $message to $to");
}

function getUserByEmail($email) {
	global $wpdb;
	$tUsers	= $wpdb->prefix . INTERVIEW_USER_INTERVIEW_TABLE_NAME;
	$sql	= "SELECT id, email FROM $tUsers WHERE email='$email' ";
	return $wpdb->get_row($sql);
}

function interview_create_post() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	global $wpdb;
	$interviewid 	= intval($_POST['interviewid']);
	$email 			= sanitize_email($_POST['email']);
	$user 			= getUserByEmail($email);
	$tableInterview = $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$sql = esc_sql("SELECT * FROM $tableInterview WHERE id=$interviewid");
	$interview 			= $wpdb->get_row($sql);
	$interviewTitle 	= "{$interview->title}";
	$interviewContent	=  get_template_interview_get_with_answers($interview->id, $user->email);
	//exit($interviewContent);

	$postarr = [
		'post_author'	=>	$user->ID,
		'post_content'	=>	$interviewContent,
		'post_title'	=>	$interviewTitle,
	];
	//kses_remove_filters();
	$postid = wp_insert_post($postarr);
	//kses_init_filters();
	exit(get_preview_post_link($postid));
}

function interview_invite() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	global $wpdb;
	$message 		= stripslashes(($_POST['message']));
	$subject 		= "🎤 " . stripslashes(sanitize_text_field($_POST['subject']))." 🎤";
	$inviteEmails 	= stripslashes(sanitize_text_field($_POST['inviteEmails']));
	$interviewid 	= intval($_POST['inviteInterviewId']);

	$emails 		= explode(',', $inviteEmails);
	$headers 		= ['Content-Type: text/html; charset=UTF-8'];
	$interview		= getInterview($interviewid);
	$interview		= $interview['interview'];
	$title			= "Interview {$interviewid}";
	$categoryId 	= get_cat_ID(INTERVIEW_REQUEST_CATEGORY);

	// create interview request post $interviewid
	$postarr = [
			'post_content'	=>	"[interview id={$interviewid}]",
			'post_title'	=>	$title,
			'post_category'	=>	[$categoryId],
			'post_status'	=>	'publish'
	];
	// create if does not exist !
	$postid = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_title = '$title' AND post_type='post' AND post_status<>'trash'");
	if (!$postid) {
		$postid	 = wp_insert_post($postarr);
	}

	// update invitation field
	$table = $table = $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$data = ['invitation'	=>$message];
	$wpdb->update($table, $data, [ 'id' => $interviewid ], ['%s', '%s']);

	$table = $wpdb->prefix . INTERVIEW_USER_INTERVIEW_TABLE_NAME;
	foreach($emails as $email) {
		$token = uniqid();
		$data = [
			'token'     	=> $token,
			'email'			=> filter_var($email,FILTER_VALIDATE_EMAIL),
			'interviewid'	=> $interviewid,
		];
		$format = ['%s', '%s', '%s'];
		$wpdb->replace($table, $data, $format);

		$link = get_permalink($postid)."?email=$email&token=$token";
		$message = str_replace('[[url]]', $link, $message);
		$originalMessage = $message;
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			wp_mail( $email, $subject, $originalMessage, $headers);
		}
	}
	exit();
}

function interview_play_record() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	global $wpdb;
	$table		= $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$uid    	= intval($_POST['uid']);
	$sql		= "SELECT data FROM $table WHERE uid = $uid";
	$results	= $wpdb->get_results($wpdb->prepare($sql));
	echo json_encode($results);
	exit();
}

function getInterview($interviewid, $email='') {
	global $wpdb;
	$tableInterview 	 = $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$tableQuestions		 = $wpdb->prefix . INTERVIEW_QUESTIONS_TABLE_NAME;
	$tableUserQuestions	 = $wpdb->prefix . INTERVIEW_USERS_ANSWERS_TABLE_NAME;
	$tUsers	= $wpdb->prefix . INTERVIEW_USER_INTERVIEW_TABLE_NAME;

	$interviewid = intval($interviewid);
	$sql = esc_sql(" SELECT * FROM  $tableInterview WHERE id=$interviewid ");
	$interview = $wpdb->get_row($sql);
	$sql = esc_sql(" SELECT * FROM $tableQuestions WHERE interviewid = $interviewid order by ordre ASC ");
	$questions = $wpdb->get_results($sql);
	$isOver = false;

	if ($email) {
		$sql	= "SELECT * FROM $tUsers WHERE email='$email' and interviewid={$interviewid}";
		$user = $wpdb->get_row($sql);
		$totalQuestions = $wpdb->get_var("SELECT count(id) from $tableQuestions WHERE interviewid = {$interviewid}");
		$totalAnswered = $wpdb->get_var("SELECT count(id) from $tableUserQuestions WHERE userid={$user->id} AND done = 1");
		$isOver = ($totalQuestions > 0 && $totalAnswered == $totalQuestions);
	}
	$res = [
		'interview'	=>	$interview,
		'questions'	=>	$questions,
		'isOver'	=>	$isOver,
		'path'		=>  plugin_dir_url(__FILE__).'upload/'
	];
	return $res;
}

function interview_save_question() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	global $wpdb;

	$table = $wpdb->prefix . INTERVIEW_USERS_ANSWERS_TABLE_NAME;
	$data = [
		'filename'     	=> 	sanitize_text_field($_POST['filename']),
		'userid'		=> 	intval($_POST['userid']),
		'questionid'	=> 	intval($_POST['questionid']),
		'interviewid'	=> 	intval($_POST['interviewid']),
		'answer'		=> 	sanitize_text_field($_POST['answer']),
		'done'			=>	1
	];
	$format = array('%s', '%s', '%s', '%s', '%s', '%s');
	$wpdb->replace($table, $data, $format);
}

function get_template_interview_get_with_answers($interviewid, $email) {
	global $wpdb;
	$interview 	= getInterview($interviewid);
	$interview 	= $interview['interview'];
	$tUsers = $wpdb->prefix . INTERVIEW_USER_INTERVIEW_TABLE_NAME;
	$sql	= "SELECT * FROM $tUsers WHERE email='$email' and interviewid={$interviewid}";
	$user = $wpdb->get_row($sql);
	$tQuestion 	= $wpdb->prefix .INTERVIEW_QUESTIONS_TABLE_NAME;
	$_interview_users_answers = $wpdb->prefix.INTERVIEW_USERS_ANSWERS_TABLE_NAME;
	$sql = "SELECT  $tQuestion.question, $_interview_users_answers.filename, $_interview_users_answers.answer
	FROM
		$tQuestion 
	    LEFT JOIN $_interview_users_answers ON $_interview_users_answers.questionid = $tQuestion.id
		WHERE $tQuestion.interviewid = {$interview->id} and $_interview_users_answers.userid={$user->id}";
	$answers = $wpdb->get_results($sql);
	ob_start();?>

	<h1><?php echo esc_html($interview->title)?></h1>
	<h3><?php echo esc_html($interview->description)?></h3>
	<?php foreach ($answers as $answer):?>
		<div style="padding:30px;">
			<div style="font-weight:bold;font-size:1.5em;padding: 20px 0;"><?php echo stripslashes(esc_html($answer->question))?></div>
			<div>
				<video style="max-width:800px;background: #000;width: 100%;height: auto;" class="interviewPlayer" controls src="<?php echo plugin_dir_url(__FILE__)?>upload/<?php echo esc_html($answer->filename)?>"></video>
			</div>
			<div class="answer">
				<?php echo stripslashes (esc_html($answer->answer))?>
			</div>
		</div>
	<?php endforeach;
	return ob_get_clean();
}

function interview_get_with_answers() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	$template = get_template_interview_get_with_answers(intval($_POST['interviewid']), sanitize_text_field($_POST['email']));
	exit($template);
}
function interview_get() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	$res = getInterview(intval($_POST['id']));
	exit(json_encode($res));
}
function interview_get_records() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	global $wpdb;
	$tInterview = $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$tInterviewAnswers = $wpdb->prefix . INTERVIEW_USERS_ANSWERS_TABLE_NAME;
	$tUsers = $wpdb->prefix . INTERVIEW_USER_INTERVIEW_TABLE_NAME;

	$page 	= intval($_POST['page']);
	$start 	= intval(($page) * INTERVIEW_ROWS_PER_PAGE);
	$sql 	= "SELECT SQL_CALC_FOUND_ROWS $tInterview.id, $tInterview.title, $tInterview.date, group_concat(DISTINCT $tUsers.email) as users
			FROM $tInterview
			LEFT JOIN $tInterviewAnswers ON $tInterviewAnswers.interviewid = $tInterview.id
			LEFT JOIN $tUsers ON $tInterviewAnswers.userid = $tUsers.id
			GROUP by $tInterview.id
			ORDER BY $tInterview.id DESC 
			LIMIT $start, ".INTERVIEW_ROWS_PER_PAGE;

	$sql = str_replace(array("\n", "\r"), '', $sql);
	$sql = esc_sql($sql);

	$rows 		= $wpdb->get_results($sql);
	$numberRows = $wpdb->get_var('SELECT FOUND_ROWS()');
	$res 		= [
					'rows'          =>$rows,
					'numberRows'    =>$numberRows,
					'INTERVIEW_ROWS_PER_PAGE' =>INTERVIEW_ROWS_PER_PAGE
	];
	exit(json_encode($res));
}
function interview_duplicate_record() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	global $wpdb;
	$tableI = $wpdb->prefix . INTERVIEW_TABLE_NAME;

	$id = intval($_POST['id']);
	$sql = "INSERT INTO $tableI(`title`, `description`, `indications`, `invitation`, `password`, `date`, `userCanRepeat`) 
	SELECT  `title`, `description`, `indications`, `invitation`, `password`, `date`, `userCanRepeat` FROM $tableI 
	WHERE id=$id";
	$wpdb->query($sql);
	$insertedId = $wpdb->insert_id;

	$sql = "INSERT INTO wp_proxymis_questions(interviewid, question, secondsMax, secondsBeforeStart, canRepeat, ordre) 
	SELECT $insertedId, question, secondsMax, secondsBeforeStart, canRepeat, ordre  FROM wp_proxymis_questions
	WHERE interviewid = $id";
	$wpdb->query($sql);
}
function interview_delete_record() {
	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	global $wpdb;
	$table = $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$id = intval($_POST['id']);
	$wpdb->delete($table, [ 'id' => $id ] );
	$tableQuestion = $wpdb->prefix . INTERVIEW_QUESTIONS_TABLE_NAME;
	$wpdb->delete($tableQuestion, [ 'interviewid' => $id ] );
}
function interview_update() {

	if (!wp_verify_nonce($_POST['nonce'], 'interview_nonce')) die('Nonce value cannot be verified.');
	global $wpdb;
	$table = $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$interviewid = intval($_POST['id']);
	$data = [
		'title'     	=> stripslashes(sanitize_text_field($_POST['editInterviewTitle'])),
		'description'	=> stripslashes(sanitize_text_field($_POST['editInterviewDescription'])),
		'indications'	=> stripslashes(sanitize_text_field($_POST['editInterviewIndications'])),
	];

	$wpdb->update($table, $data, [ 'id' => $interviewid ], ['%s', '%s']);

	$questions = isset( $_POST['questions'] ) ? array_map( 'sanitize_text_field', (array) $_POST['questions'] ) : array();
	$questions = array_map( 'stripslashes', $questions );
	$ids = isset( $_POST['ids'] ) ?  array_map( 'intval', (array) $_POST['ids'] ) : array();
	$secondsMax = isset( $_POST['secondsMax'] ) ? array_map( 'intval', (array) $_POST['secondsMax'] ) : array();
	$ordres = isset( $_POST['ordre'] ) ? array_map( 'intval', (array) $_POST['ordre'] ) : array();
	$table 		= $wpdb->prefix . INTERVIEW_QUESTIONS_TABLE_NAME;

	$questionsiIds = implode(',', $ids);
	$sql = "delete from $table where interviewid = $interviewid AND id not in ($questionsiIds)";
	$wpdb->query($sql);

	foreach($questions as $index =>$question) {
		$seconds 	= $secondsMax[$index];
		$ordre 		= $ordres[$index];
		$id 		= $ids[$index];
		$data = [
			'id'     		=> intval($id),
			'question'     	=> stripslashes(sanitize_text_field($question)),
			'secondsMax'	=> intval($seconds),
			'interviewid'	=> intval($interviewid),
			'ordre'			=> intval($ordre),
		];
		$format = array('%s', '%s', '%s', '%s', '%s');
		$wpdb->replace($table, $data, $format);
	}
}
function interview_insert() {
	$nonce = $_POST['nonce'];
	if (!wp_verify_nonce($nonce, 'interview_nonce')) {
		die('Nonce value cannot be verified.');
	}
	global $wpdb;
	$table = $wpdb->prefix . INTERVIEW_TABLE_NAME;
	$invitation = $GLOBALS['lang']['Invitation Email'];
	$data = [
		'title'     	=> stripslashes(sanitize_text_field($_POST['interviewTitle'])),
		'description'	=> stripslashes(sanitize_text_field($_POST['interviewDescription'])),
		'indications'	=> stripslashes(sanitize_text_field($_POST['interviewIndications'])),
		'invitation'	=> $invitation
	];

	$format = array('%s', '%s', '%s');
	$wpdb->insert($table, $data, $format);
	$id = $wpdb->insert_id;
	$questions = isset( $_POST['questions'] ) ? array_map( 'sanitize_text_field', (array) $_POST['questions'] ) : array();
	$questions = array_map( 'stripslashes', $questions );
	$secondsMax = isset( $_POST['secondsMax'] ) ? array_map( 'intval', (array) $_POST['secondsMax'] ) : array();
	$ordres = isset( $_POST['ordre'] ) ? array_map( 'intval', (array) $_POST['ordre'] ) : array();
	$table = $wpdb->prefix . INTERVIEW_QUESTIONS_TABLE_NAME;
	foreach($questions as $index =>$question) {
		$seconds 	= $secondsMax[$index];
		$ordre 		= $ordres[$index];
		$data 		= [
			'question'     	=> stripslashes(sanitize_text_field($question)),
			'secondsMax'	=> stripslashes(sanitize_text_field($seconds)),
			'interviewid'	=> stripslashes(sanitize_text_field($id)),
			'ordre'			=> stripslashes(sanitize_text_field($ordre)),
		];
		$format = array('%s', '%s', '%s', '%s', '%s');
		$wpdb->insert($table, $data, $format);
	}
	exit($id);
}

if (is_admin()) {
	$settings = new InterviewSettings();
}