<?php
$lanorg_registration_form = array(
	array(
		'type' => 'text',
		'key' => 'nickname',
		'label' => 'Choissez un pseudonyme :',
		'validator' => array('empty', 'username_exists', 'username_valid'),
	),
	array(
		'type' => 'text',
		'key' => 'firstname',
		'label' => 'Prénom :',
		'validator' => 'empty',
	),
	array(
		'type' => 'text',
		'key' => 'lastname',
		'label' => 'Nom :',
		'validator' => 'empty',
	),
	array(
		'type' => 'text',
		'key' => 'email',
		'label' => 'Courriel :',
		'validator' => array('empty', 'email_exists', 'email_valid'),
	),
	array(
		'type' => 'text',
		'key' => 'password',
		'label' => 'Mot de passe :',
		'password' => true,
		'validator' => 'empty',
	),
);

$lanorg_login_form = array(
	array(
		'type' => 'text',
		'key' => 'nickname',
		'label' => 'Pseudonyme :',
		'validator' => array('empty'),
	),
	array(
		'type' => 'text',
		'key' => 'password',
		'label' => 'Mot de passe :',
		'password' => true,
		'validator' => 'empty',
	),
);

// Get the HTML markup for the registration form
// Called from the template
function lanorg_get_registration_form_markup()
{
	global $lanorg_registration_form;
	return lanorg_form($lanorg_registration_form);

}

// Get the HTML markup for the login form
// Called from the template
function lanorg_get_login_form_markup(&$error = NULL)
{
	global $lanorg_login_form;

	$values = array();
	$errors = array();

	$html_markup = lanorg_form($lanorg_login_form, $values, $errors);

	$error = NULL;

	if (count($errors) == 0) {
		lanorg_login_user($error);
	}
	
	return $html_markup;
}

// Shortcode handler for the registration form
function lanorg_shortcode_registration_form($attr) {
		global $lanOrg;

		wp_enqueue_style('lanorg-form');

		// Turn on output buffering to capture form markup
		ob_start();

		$lanOrg->render_template('lanorg-register.php');

		// Get buffered content
		$content = ob_get_clean();
		return $content;
}

function lanorg_login_user(&$error_message = '')
{
	global $lanorg_login_form;
	$success = FALSE;
	$values = array();
	if (lanorg_form_post($lanorg_login_form, $values, $lanOrg->form_prefix)) {
		$errors = array();
		if (lanorg_form_validation($lanorg_login_form, $values, $errors)) {
			$user_info = array(
				'user_login' => $values['nickname'],
				'user_password' => $values['password'],
			);

			$user = wp_signon($user_info);

			if (!is_wp_error($user)) {
				wp_redirect(home_url());
				$success = TRUE;
			}
			else {
				$error_message = 'Nom d\'utilisateur ou mot de passe incorrect';
			}
		}
	}
	return $success;
}

function lanorg_process_registration_form()
{
	global $lanorg_registration_form;

	$values = array();
	if (lanorg_form_post($lanorg_registration_form, $values, $lanOrg->form_prefix)) {
		$errors = array();
		if (lanorg_form_validation($lanorg_registration_form, $values, $errors)) {
			wp_insert_user(array(
				'user_login' => $values['nickname'],
				'first_name' => $values['firstname'],
				'last_name' => $values['lastname'],
				'user_email' => $values['email'],
				'user_pass' => $values['password'],
			));

			wp_redirect(home_url());
			exit;
		}
	}

}

?>