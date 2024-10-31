<?php
/*
Plugin Name: Semisecure Login for 2.5
Plugin URI: http://www.daylightatheism.org/wordpress-plugins/semisecure-login
Description: Semisecure Login increases the security of the login process using client-side MD5 encryption on the password when a user logs in. JavaScript is required to enable encryption. This version is a revision of the original Semisecure Login plugin by James M. Allen and is designed for use with WordPress 2.5. Requires the <a href="http://wordpress.org/extend/plugins/md5-password-hashes/">MD5 Password Hashes</a> plugin for 2.5.
Version: 1.1
Author: Adam Lee
Author URI: http://www.daylightatheism.org/
*/

/*  Copyright 2008 by Adam Lee (ebonmusings@gmail.com)
    Based on the original Semisecure Login by James M. Allen (email: james.m.allen@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('login_head', array('SemisecureLogin', 'login_head'));
add_action('login_form', array('SemisecureLogin', 'login_form'));
add_action('check_password', array('SemisecureLogin', 'semisecure_authenticate'), 1, 4);


if (! class_exists('SemisecureLogin')) {
	class SemisecureLogin {
		/*
		 * Plugin hooks
		 */
		
		/*
		 * Sets the nonce, includes JavaScript for preparing the login form
		 */
		function login_head() {

			/* README - IMPORTANT NOTE
                         * Ordinarily, session_start() is the first thing that must be called on a page. 
                         * By the time WordPress' login_head() hook is invoked, the below call is too late.
                         * What this means in a practical sense is that, if you use this plugin exactly as is,
                         * the first time you go to your login page, the session will not be properly initialized
                         * and the login will fail. 
                         * If you reload the page and try again to log in, it will succeed.
                         * There's no way (that I know of) to fix this from within the plugin itself.
                         * It *can* be fixed, if you're not afraid of editing WordPress code directly.
                         * To fix it, delete the below call to @session_start().
                         * Then open wp-login.php and re-add that call at the very beginning of the file,
                         * right after the first <?php tag.
                         * This suggestion is intended for technically inclined WordPress users.
                         * I assume no responsibility for any consequences arising from its use.
                         */

			@session_start();

			// always generate a new nonce
			$_SESSION['login_nonce'] = md5(rand());
			
			?>
		<script type="text/javascript" src="<?php echo get_option('siteurl');?>/wp-content/plugins/semisecure-login/md5.js"></script>
		<script type="text/javascript">
		function hashPwd() {
			var formLogin = document.getElementById('loginform');
			
			var userLog = document.getElementById('user_login');
			var userPwd = document.getElementById('user_pass');
			
			var password = userPwd.value;
			
			semisecureMessage.innerHTML = 'Encrypting password and logging in...';
			
			var userMD5Pwd = document.createElement('input');
			userMD5Pwd.setAttribute('type', 'hidden');
			userMD5Pwd.setAttribute('id', 'user_pass_md5');
			userMD5Pwd.setAttribute('name', 'pwd_md5');
			userMD5Pwd.value = hex_md5(hex_md5(password) + '<?php echo $_SESSION['login_nonce']?>');
			formLogin.appendChild(userMD5Pwd);
			
			userPwd.value = '';
			for (var i = 0; i < password.length; i++) {
				userPwd.value += '*';
			}
			
			return true;
		}
		</script>
		<?php
		}
		
		/*
		 * Applies event handlers to the form (DOM needs to be ready before this happens)
		 */
		function login_form() {
			?>
		<p id="semisecure-message">
				<span style="background-color: #ff0; color: #000;">Semisecure Login is not enabled!</span><br />
				Please enable JavaScript and use a modern browser to ensure your password is encrypted.
		</p>
		<script language="javascript" type="text/javascript">
			var formLogin = document.getElementById('loginform');
			formLogin.setAttribute('onsubmit', 'return hashPwd();');
			
			var semisecureMessage = document.getElementById('semisecure-message');
			semisecureMessage.setAttribute('class', '');
			semisecureMessage.innerHTML = 'Semisecure Login is enabled.';
		</script>
		<?php
		}
		
		
		function semisecure_authenticate($check, $password, $hash, $userid) {
			if (!empty($_POST['pwd_md5'])) {

				@session_start();
				$user = get_userdata($userid);
				$comparison = strcmp ($_POST['pwd_md5'], md5($hash . $_SESSION['login_nonce']) );

				// expire the nonce
				$_SESSION['login_nonce'] = md5(rand());

				if($comparison == 0) { return true; }
				else { return false; }

			} else { //if(empty($_POST['pwd_md5'])) - in the absence of JavaScript
				// Hashing was already done for us in this case.
				if($check == 1) { return true; }
				else { return false; }
			}

		} //semisecure_authenticate

	}
}

?>