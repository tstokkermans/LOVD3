<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-03-19
 * Modified    : 2016-09-23
 * For LOVD    : 3.0-18
 *
 * Copyright   : 2004-2016 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *               Ing. Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.nl>
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

// Log out of the system.
if (!$_AUTH) {
    header('Location: ' . lovd_getInstallURL());
    exit;
}

$_DB->query('UPDATE ' . TABLE_USERS . ' SET phpsessid = "" WHERE id = ?', array($_AUTH['id']), false);
$nSec = time() - strtotime($_AUTH['last_login']);
$sCurrDB = $_SESSION['currdb']; // Temp storage.
$aMapping = $_SESSION['mapping']; // Temp storage.
$_SESSION = array(); // Delete variables both from $_SESSION and from session file.
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 172800); // 'Delete' the cookie.
}
session_destroy();   // Destroy session, delete the session file.
$_AUTH = false;

// Reinitiate... To store some information back into the array.
@session_start(); // On some Ubuntu distributions this can cause a distribution-specific error message when session cleanup is triggered.
session_regenerate_id();
// Fix weird behaviour of session_regenerate_id() - sometimes it is not sending a new cookie.
setcookie(session_name(), session_id(), ini_get('session.cookie_lifetime'));
$_SESSION['currdb'] = $sCurrDB; // Put it back.
$_SESSION['mapping'] = $aMapping; // Put it back.
header('Refresh: 5; url=' . lovd_getInstallURL());
define('PAGE_TITLE', 'Log out');
$_T->printHeader();
$_T->printTitle();

print('      You have been logged out successfully.<BR>' . "\n");

$aTimes =
         array(
                array( 1, 'sec', 'sec'),
                array(60, 'min', 'min'),
                array(60, 'hr',  'hrs'),
                array(24, 'day', 'days'),
              );

foreach ($aTimes as $n => $aTime) {
    if ($n) {
        $aTimes[$n][0] = $aTime[0] * $aTimes[$n-1][0];
    }
}
$aTimes = array_reverse($aTimes);

$sPrint = '';
foreach ($aTimes as $n => $aTime) {
    if ($nSec >= $aTime[0]) {
        $nAmount = floor($nSec / $aTime[0]);
        $nSec = $nSec % $aTime[0];
        $sPrint .= ($sPrint? ', ' : '') . $nAmount . ' ' . ($nAmount == 1? $aTime[1] : $aTime[2]);
    }
}

print('      You\'ve been online for ' . $sPrint . '.' . "\n\n");

$_T->printFooter();
?>
