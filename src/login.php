<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-01-19
 * Modified    : 2010-12-14
 * For LOVD    : 3.0-pre-09
 *
 * Copyright   : 2004-2010 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 * Last edited : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/

define('ROOT_PATH', './');
require ROOT_PATH . 'inc-init.php';

// Already logged in to the system.
if ($_AUTH) {
    // Send manager and database administrator to setup, the rest to the gene page.
    header('Location: ' . lovd_getInstallURL() . ($_AUTH['level'] >= LEVEL_MANAGER? 'setup' : 'genes' . ($_AUTH['current_db']? '/' . $_AUTH['current_db'] : '')));
    exit;
}

require ROOT_PATH . 'inc-lib-form.php';
lovd_errorClean();

// Force use of cookies!
if (!isset($_COOKIE['lovd_cookie_check']) && !empty($_POST)) {
    // We might not have that checking cookie if this is the first page. So we want to complain only if the form has been submitted.
    lovd_errorAdd('', 'Cookies must be enabled before you can log in. Please enable cookies or lower your browser\'s security settings.');

} else {
    // We're now also accepting unlocking accounts.
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
        // First, retrieve account information.
        $zUser = mysql_fetch_assoc(lovd_queryDB('SELECT * FROM ' . TABLE_USERS . ' WHERE username = ?', array($_POST['username'])));

        if ($zUser) {
            // The user exists, now check account unlocking, locked accounts, successful and unsuccessful logins.

            // First, check if the user is unlocking an account (forgot password).
            if ($zUser['password_autogen'] && $zUser['password_autogen'] == md5($_POST['password']) && $_CONF['allow_unlock_accounts']) {
                // Successfully unlocking an account! Log user in.
                $_SESSION['auth'] = $zUser;
                $_AUTH = & $_SESSION['auth'];

                lovd_writeLog('Auth', 'AuthLogin', $_SERVER['REMOTE_ADDR'] . ' (' . gethostbyaddr($_SERVER['REMOTE_ADDR']) . ') successfully logged in using ' . $_POST['username'] . '/unlocking code');
                $_SESSION['last_login'] = $_AUTH['last_login'];
                // Protect against Session Fixation by regenarating the ID (available since 4.3.2), but only after 4.3.10 as it gives problems before that...
                if (function_exists('session_regenerate_id') && !(substr(phpversion(), 0, 4) == '4.3.' && substr(phpversion(), 4) < 10)) {
                    session_regenerate_id();
                    // Fix weird behaviour of session_regenerate_id() - sometimes it is not sending a new cookie.
                    setcookie(session_name(), session_id(), ini_get('session.cookie_lifetime'));
                }
                // Also update the password field, it needs to be used by the update password form.
                $_AUTH['password'] = md5($_POST['password']);
                lovd_queryDB('UPDATE ' . TABLE_USERS . ' SET password = ?, phpsessid = ?, last_login = NOW(), login_attempts = 0 WHERE id = ?', array($_AUTH['password'], session_id(), $_AUTH['id']));

                // Since this is the unlocking code, the user should be forced to change his/her password.
                $_SESSION['password_force_change'] = true;

                header('Location: ' . lovd_getInstallURL() . 'users/' . $_AUTH['id'] . '?change_password');
                exit;
            }



            // Next, check if the account is locked.
            elseif ($zUser['login_attempts'] >= 3) {
                // Account is locked!

                // Spit out error.
// DMD_SPECIFIC; if we release the data of the admin and the managers online, because of the privacy policy, then we can mention the info (or a link) here, too.
                lovd_errorAdd('', 'Your account is locked, usually because a wrong password was provided three times. ' . ($_CONF['allow_unlock_accounts']? 'Did you <A href="reset_password">forget your password</A>?' : 'Please contact a LOVD manager or the database administrator to unlock your account.'));
            }



            // Finally, log in user if the correct password has been given.
            elseif ($zUser['password'] == md5($_POST['password'])) {
                // Successfully logging in!
                $_SESSION['auth'] = $zUser;
                $_AUTH = & $_SESSION['auth'];



                lovd_writeLog('Auth', 'AuthLogin', $_SERVER['REMOTE_ADDR'] . ' (' . gethostbyaddr($_SERVER['REMOTE_ADDR']) . ') successfully logged in using ' . $_POST['username'] . '/' . str_repeat('*', strlen($_POST['password'])));
                $_SESSION['last_login'] = $_AUTH['last_login'];
                // Protect against Session Fixation by regenarating the ID (available since 4.3.2), but only after 4.3.10 as it gives problems before that...
                if (function_exists('session_regenerate_id') && !(substr(phpversion(), 0, 4) == '4.3.' && substr(phpversion(), 4) < 10)) {
                    session_regenerate_id();
                    // Fix weird behaviour of session_regenerate_id() - sometimes it is not sending a new cookie.
                    setcookie(session_name(), session_id(), ini_get('session.cookie_lifetime'));
                }
                lovd_queryDB('UPDATE ' . TABLE_USERS . ' SET password_autogen = "", phpsessid = ?, last_login = NOW(), login_attempts = 0 WHERE id = ?', array(session_id(), $_AUTH['id']));

                // Check if the user should be forced to change his/her password.
                if (!empty($_AUTH['password_force_change'])) {
                    $_SESSION['password_force_change'] = true;
                }

                // Check if referer is given, check it, then forward the user.
                if (!empty($_POST['referer']) && strpos($_POST['referer'], lovd_getInstallURL()) === 0) {
                    // Location is whithin this LOVD installation.
                    $sLocation = $_POST['referer'];
                } else {
                    // Redirect to proper location will be done somewhere else in this code.
                    $sLocation = lovd_getInstallURL() . 'login';
                }

                header('Location: ' . $sLocation);
                exit;
            }
        }



        // The bad logins end up here!
        if (!$zUser || $zUser['password'] != md5($_POST['password'])) {
            lovd_writeLog('Auth', 'AuthError', $_SERVER['REMOTE_ADDR'] . ' (' . gethostbyaddr($_SERVER['REMOTE_ADDR']) . ') tried logging in using ' . $_POST['username'] . '/' . str_repeat('*', strlen($_POST['password'])));
            lovd_errorAdd('', 'Invalid Username/Password combination.');

            // This may not actually update (user misspelled his username) but we can call the query anyway.
            if ($_CONF['lock_users']) {
                lovd_queryDB('UPDATE ' . TABLE_USERS . ' SET login_attempts = login_attempts + 1 WHERE username = ? AND level < 9', array($_POST['username']));
            }

            // Check if the user is locked, now.
            if ($zUser && $zUser['login_attempts'] >= (3-1)) {
                lovd_errorAdd('password', 'Your account is now locked, since this is the third time a wrong password was provided.');
            }

            // The "Forgot my password" option.
            if ($_CONF['allow_unlock_accounts']) {
                lovd_errorAdd('', 'Did you <A href="reset_password">forget your password</A>?');
            }
        }
    }
}



if (!$_AUTH) {
    define('PAGE_TITLE', 'Log in');
    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    // Security check will be performed when actually logging in.
    if (empty($_POST['referer'])) {
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $_POST['referer'] = $_SERVER['HTTP_REFERER'];
        } else {
            $_POST['referer'] = '';
        }
    }

    lovd_errorPrint();

    print('      <FORM action="login" method="post" id="login">' . "\n" .
         (!$_POST['referer']? '' :
          '        <INPUT type="hidden" name="referer" value="' . htmlspecialchars($_POST['referer']) . '">' . "\n") .
          '        <TABLE border="0" cellpadding="0" cellspacing="0" width="275">' . "\n" .
          '          <TR align="right">' . "\n" .
          '            <TD width="100" style="padding-right : 5px;">Username</TD>' . "\n" .
          '            <TD width="175"><INPUT type="text" name="username" size="20"></TD></TR>' . "\n" .
          '          <TR>' . "\n" .
          '            <TD colspan="2"><IMG src="gfx/trans.png" alt="" width="1" height="1"></TD></TR>' . "\n" .
          '          <TR align="right">' . "\n" .
          '            <TD width="100" style="padding-right : 5px;">Password</TD>' . "\n" .
          '            <TD width="175"><INPUT type="password" name="password" size="20"></TD></TR>' . "\n" .
          '          <TR>' . "\n" .
          '            <TD colspan="2"><IMG src="gfx/trans.png" alt="" width="1" height="1"></TD></TR>' . "\n" .
          '          <TR align="right">' . "\n" .
          '            <TD width="100">&nbsp;</TD>' . "\n" .
          '            <TD width="175"><INPUT type="submit" value="Log in"></TD></TR></TABLE>' . "\n" .
          '      </FORM>' . "\n\n" .
          '      <SCRIPT type="text/javascript">' . "\n" .
          '        document.forms[\'login\'].username.focus();' . "\n" .
          '      </SCRIPT>' . "\n\n");

    require ROOT_PATH . 'inc-bot.php';
    exit;
}
?>
