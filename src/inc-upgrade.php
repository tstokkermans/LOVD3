<?php
// DMD_SPECIFIC: REMEMBER. If you add code that adds SQL for all genes, you MUST add the key first to the large array. Otherwise, the order in which upgrades are done is WRONG!!!
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-01-14
 * Modified    : 2011-04-06
 * For LOVD    : 3.0-pre-19
 *
 * Copyright   : 2004-2011 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *               Ing. Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.NL>
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

// Don't allow direct access.
if (!defined('ROOT_PATH')) {
    exit;
}

// 2009-07-17; 2.0-20; Added increased execution time to help perform large upgrades.
if ((int) ini_get('max_execution_time') < 60) {
    set_time_limit(60);
}

// How are the versions related?
$sCalcVersionFiles = lovd_calculateVersion($_SETT['system']['version']);
$sCalcVersionDB = lovd_calculateVersion($_STAT['version']);

if ($sCalcVersionFiles != $sCalcVersionDB) {
    // Version of files are not equal to version of database backend.

    // DB version greater than file version... then we have a problem.
    if ($sCalcVersionFiles < $sCalcVersionDB) {
        lovd_displayError('UpgradeError', 'Database version ' . $_STAT['version'] . ' found newer than file version ' . $_SETT['system']['version']);
    }

    define('PAGE_TITLE', 'Upgrading LOVD...');
    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    print('      Please wait while LOVD is upgrading the database backend from ' . $_STAT['version'] . ' to ' . $_SETT['system']['version'] . '.<BR><BR>' . "\n");

    // Array of changes.
    $aUpdates =
             array(
                    '3.0-pre-10' =>
                             array(
                                    'UPDATE ' . TABLE_LINKS . ' SET replace_text = "<A href=\"http://www.ncbi.nlm.nih.gov/omim/[1]#[1]Variants[2]\" target=\"_blank\">(OMIM [2])</A>" WHERE id = 4',
                                    'DELETE FROM ' . TABLE_SOURCES . ' WHERE name = "omim_disease"',
                                    'UPDATE ' . TABLE_SOURCES . ' SET name = "omim", url = "http://www.ncbi.nlm.nih.gov/omim/{{ ID }}" WHERE name = "omim_gene"',
                                    'INSERT INTO ' . TABLE_PATHOGENIC . ' (SELECT * FROM ' . TABLE_DATA_STATUS . ' WHERE id > 9)',
                                    'DELETE FROM ' . TABLE_DATA_STATUS . ' WHERE id > 9',
                                    'UPDATE ' . TABLE_SOURCES . ' SET url = "http://www.ncbi.nlm.nih.gov/nuccore/{{ ID }}" WHERE name = "genbank"',
                                  ),
                    '3.0-pre-11' =>
                             array(
                                    'ALTER TABLE ' . TABLE_GENES . ' DROP COLUMN id_uniprot',
                                    'ALTER TABLE ' . TABLE_TRANSCRIPTS . ' ADD COLUMN id_protein_uniprot VARCHAR(8) NOT NULL AFTER id_protein_ensembl',
                                    'INSERT INTO ' . TABLE_SOURCES . ' VALUES("hgnc", "http://www.genenames.org/data/hgnc_data.php?hgnc_id={{ ID }}")',
                                  ),
                    '3.0-pre-12' =>
                             array(
                                    'ALTER TABLE ' . TABLE_GENES . ' DROP COLUMN genbank',
                                    'ALTER TABLE ' . TABLE_GENES . ' DROP COLUMN genbank_uri',
                                    'ALTER TABLE ' . TABLE_SOURCES . ' CHANGE COLUMN name id VARCHAR(15) NOT NULL',
                                  ),
                    '3.0-pre-13' =>
                             array(
                                    'ALTER TABLE ' . TABLE_GENES . ' ADD COLUMN chromosome VARCHAR(2) NOT NULL AFTER name',
                                    'ALTER TABLE ' . TABLE_TRANSCRIPTS . ' DROP COLUMN chromosome',
                                    'ALTER TABLE ' . TABLE_VARIANTS . ' ADD COLUMN chromosome VARCHAR(2) NOT NULL AFTER pathogenicid',
                                    'ALTER TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' DROP COLUMN chromosome',
                                    'ALTER TABLE ' . TABLE_VARIANTS . ' ADD COLUMN ownerid SMALLINT(5) UNSIGNED ZEROFILL AFTER type',
                                    'ALTER TABLE ' . TABLE_VARIANTS . ' ADD INDEX (ownerid)',
                                    'ALTER TABLE ' . TABLE_VARIANTS . ' ADD FOREIGN KEY (ownerid) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL',
                                  ),
                    '3.0-pre-14' =>
                             array(
                                    'UPGRADING TO 3.0-pre-14 IS NOT SUPPORTED. UNINSTALL LOVD 3.0 AND REINSTALL TO GET THE LATEST.',
                                  ),
                    '3.0-pre-15' =>
                             array(
                                    'UPGRADING TO 3.0-pre-15 IS NOT SUPPORTED. UNINSTALL LOVD 3.0 AND REINSTALL TO GET THE LATEST.',
                                  ),
                    '3.0-pre-16' =>
                             array(
                                    'ALTER TABLE ' . TABLE_GENES . ' CHANGE COLUMN chrom_location chrom_band VARCHAR(20) NULL',
                                    'ALTER TABLE ' . TABLE_GENES . ' MODIFY COLUMN updated_date DATETIME NULL',
                                  ),
                    '3.0-pre-17' =>
                             array(
                                    'ALTER TABLE ' . TABLE_CURATES . ' MODIFY COLUMN userid SMALLINT(5) UNSIGNED ZEROFILL NOT NULL',
                                    'ALTER TABLE ' . TABLE_LOGS . ' MODIFY COLUMN userid SMALLINT(5) UNSIGNED ZEROFILL',
                                    'ALTER TABLE ' . TABLE_CONFIG . ' ADD COLUMN logo_uri VARCHAR(100) NOT NULL DEFAULT "gfx/LOVD_logo130x50.jpg" AFTER refseq_build',
                                  ),
                    '3.0-pre-18' =>
                             array(
                                    'ALTER TABLE ' . TABLE_SHARED_COLS . ' MODIFY COLUMN geneid VARCHAR(12)',
                                    'ALTER TABLE ' . TABLE_CONFIG . ' ADD COLUMN mutalyzer_soap_url VARCHAR(100) NOT NULL DEFAULT "http://www.mutalyzer.nl/2.0/services" AFTER logo_uri',
                                  ),
                    '3.0-pre-19' =>
                             array(
                                    'DELETE FROM ' . TABLE_COLS . ' WHERE id IN ("Patient/Patient_ID", "Screening/Template", "Screening/Technique", "Screening/Tissue")',
                                    'INSERT INTO ' . TABLE_COLS . ' VALUES ("Patient/Lab_ID",           0,  80, 1, 1, 1, "Lab\'s ID", "", "The ID given to this individual by its reference.", "The ID given to this individual by its reference, such as a hospital, diagnostic laboratory or a paper.", "VARCHAR(15)", "Lab ID||text|10", "", "", 0, 1, 0, 1, NOW(), NULL, NULL)',
                                    'INSERT INTO ' . TABLE_COLS . ' VALUES ("Screening/Template",       0,  80, 1, 1, 1, "Template",  "", "Screening performed on DNA, RNA and/or Protein level.", "Screening performed on DNA, RNA and/or Protein level.", "VARCHAR(20)", "Detection template||select|3|false|true|false", "DNA\r\nRNA\r\nProtein", "", 1, 1, 1, 1, NOW(), NULL, NULL)',
                                    'INSERT INTO ' . TABLE_COLS . ' VALUES ("Screening/Technique",      0, 200, 1, 1, 1, "Technique", "", "Technique used to detect variants.", "Technique used to reveal the variants reported.", "VARCHAR(150)", "Technique(s) used||select|5|false|true|false", "BESS = Base Excision Sequence Scanning\r\nCMC = Chemical Mismatch Cleavage\r\nDGGE = Denaturing-Gradient Gel-Electrophoresis\r\nDHPLC = Denaturing High-Performance Liquid Chromatography\r\nDOVAM = Detection Of Virtually All Mutations (SSCA variant)\r\nDSCA = Double-Strand DNA Conformation Analysis\r\nHD = HeteroDuplex analysis\r\nIHC = Immuno-Histo-Chemistry\r\nmPCR = multiplex PCR\r\nMAPH = Multiplex Amplifiable Probe Hybridisation\r\nMLPA = Multiplex Ligation-dependent Probe Amplification\r\nNGS = Next Generation Sequencing\r\nPAGE = Poly-Acrylamide Gel-Electrophoresis\r\nPCR = Polymerase Chain Reaction\r\nPTT = Protein Truncation Test\r\nRT-PCR = Reverse Transcription and PCR\r\nSEQ = SEQuencing\r\nSouthern = Southern Blotting\r\nSSCA = Single-Strand DNA Conformation Analysis (SSCP)\r\nWestern = Western Blotting", "", 1, 1, 1, 1, NOW(), NULL, NULL)',
                                    'INSERT INTO ' . TABLE_COLS . ' VALUES ("Screening/Tissue",         0, 100, 0, 0, 1, "Tissue",    "", "Tissue type used for the detection of sequence variants.", "Tissue type used for the detection of sequence variants.", "VARCHAR(25)", "Tissue||text|20", "", "", 1, 1, 1, 1, NOW(), NULL, NULL)',
                                    'INSERT INTO ' . TABLE_COLS . ' VALUES ("Screening/Date",           0,  80, 0, 0, 0, "Date",      "Format: YYYY-MM-DD.", "Date the detection technique was performed.", "Date the detection technique was performed, in YYYY-MM-DD format.", "DATE", "Date||text|10", "", "", 1, 1, 1, 1, NOW(), NULL, NULL)',
                                    'INSERT INTO ' . TABLE_COLS . ' VALUES ("VariantOnTranscript/DBID", 6, 200, 1, 1, 1, "DB-ID",     "The ID-field should start with the ID; the gene symbol followed by an underscore (_) and the ID code, usually five digits.", "Database IDentifier; When available, links to OMIM ID\'s are provided.", "Database IDentifier; When available, links to OMIM ID\'s are provided.", "VARCHAR(100)", "ID||text|40", "", "/^[A-Z][A-Z0-9]+_([0-9]{5}([a-z]{2})?|(SO|MP|e)[0-9]{1,2}((SO|MP|e)[0-9]{1,2})?b?)\\\\b/", 1, 0, 1, 1, NOW(), NULL, NULL)',
                                    'CREATE TABLE ' . TABLE_PAT2DIS . ' (patientid MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL, diseaseid SMALLINT(5) UNSIGNED ZEROFILL NOT NULL, PRIMARY KEY (patientid, diseaseid), INDEX (diseaseid), FOREIGN KEY (patientid) REFERENCES ' . TABLE_PATIENTS . ' (id) ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY (diseaseid) REFERENCES ' . TABLE_DISEASES . ' (id) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB, DEFAULT CHARACTER SET utf8',
                                    'ALTER TABLE ' . TABLE_DISEASES . ' ADD UNIQUE (id_omim)',
                                    'ALTER TABLE ' . TABLE_STATUS . ' MODIFY COLUMN update_description TEXT',
                                  ),
                  );

    // Addition for upgrade to LOVD v.3.0-pre-07.
    if ($sCalcVersionDB < lovd_calculateVersion('3.0-pre-07')) {
        // Simply reload all custom columns.
        require ROOT_PATH . 'install/inc-sql-columns.php';
        $aUpdates['3.0-pre-07'] = array_merge($aUpdates['3.0-pre-07'], $aColSQL);
    }










    // To make sure we upgrade the database correctly, we add the current version to the list...
    if (!isset($aUpdates[$_SETT['system']['version']])) {
        $aUpdates[$_SETT['system']['version']] = array();
    }

    require ROOT_PATH . 'class/progress_bar.php';
    $sFormNextPage = '<FORM action="' . $_SERVER['REQUEST_URI'] . '" method="post" id="upgrade_form">' . "\n";
    foreach ($_POST as $key => $val) {
        // Added htmlspecialchars to prevent XSS and allow values to include quotes.
        if (is_array($val)) {
            foreach ($val as $value) {
                $sFormNextPage .= '          <INPUT type="hidden" name="' . $key . '[]" value="' . htmlspecialchars($value) . '">' . "\n";
            }
        } else {
            $sFormNextPage .= '          <INPUT type="hidden" name="' . $key . '" value="' . htmlspecialchars($val) . '">' . "\n";
        }
    }
    $sFormNextPage .= '          <INPUT type="submit" id="submit" value="Proceed &gt;&gt;">' . "\n" .
                      '        </FORM>';
    // This already puts the progress bar on the screen.
    $_BAR = new ProgressBar('', 'Checking upgrade lock...', $sFormNextPage);

    define('_INC_BOT_CLOSE_HTML_', false); // Sounds kind of stupid, but this prevents the inc-bot to actually close the <BODY> and <HTML> tags.
    require ROOT_PATH . 'inc-bot.php';



    // Now we're still in the <BODY> so the progress bar can add <SCRIPT> tags as much as it wants.
    flush();



    // Try to update the upgrade lock.
    $sQ = 'UPDATE ' . TABLE_STATUS . ' SET lock_update = 1 WHERE lock_update = 0';
    $nMax = 3; // FIXME; Should be higher, this value is for dev only
    for ($i = 0; $i < $nMax; $i ++) {
        lovd_queryDB($sQ);
        $bLocked = !mysql_affected_rows();
        if (!$bLocked) {
            break;
        }

        // No update means that someone else is updating the system.
        $_BAR->setMessage('Update lock is in place, so someone else is already upgrading the database.<BR>Waiting for other user to finish... (' . ($nMax - $i) . ')');
        flush();
        sleep(1);
    }

    if ($bLocked) {
        // Other user is taking ages! Or somethings wrong...
        $_BAR->setMessage('Other user upgrading the database is still not finished.<BR>' . (isset($_GET['force_lock'])? 'Forcing upgrade as requested...' : 'This may indicate something went wrong during upgrade.'));
        if (isset($_GET['force_lock'])) {
            $bLocked = false;
        }
    } else {
        $_BAR->setMessage('Upgrading database backend...');
    }
    flush();





    if (!$bLocked) {
        // There we go...

        // This recursive count returns a higher count then we would seem to want at first glance,
        // because each version's array of queries count as one as well.
        // However, because we will run one additional query per version, this number will be correct anyway.
        $nSQL = count($aUpdates, true);

        // Actually run the SQL...
        $nSQLDone = 0;
        $nSQLDonePercentage = 0;
        $nSQLDonePercentagePrev = 0;
        $nSQLFailed = 0;
        $sSQLFailed = '';

        foreach ($aUpdates as $sVersion => $aSQL) {
            if (lovd_calculateVersion($sVersion) <= $sCalcVersionDB || lovd_calculateVersion($sVersion) > $sCalcVersionFiles) {
                continue;
            }
            $_BAR->setMessage('To ' . $sVersion . '...');

            $aSQL[] = 'UPDATE ' . TABLE_STATUS . ' SET version = "' . $sVersion . '", updated_date = NOW()';

            // Loop needed queries...
            foreach ($aSQL as $i => $sSQL) {
                $i ++;
                if (!$nSQLFailed) {
                    $q = mysql_query($sSQL); // This means that there is no SQL injection check here. But hey - these are our own queries. DON'T USE lovd_queryDB(). It complains because there are ?s in the queries.
                    if (!$q) {
                        $nSQLFailed ++;
                        // Error when running query.
                        $sError = mysql_error();
                        lovd_queryError('RunUpgradeSQL', $sSQL, $sError, false);
                        $sSQLFailed = 'Error!<BR><BR>\n\n' .
                                      'Error while executing query ' . $i . ':\n' .
                                      '<PRE style="background : #F0F0F0;">' . htmlspecialchars($sError) . '</PRE><BR>\n\n' .
                                      'This implies these MySQL queries need to be executed manually:<BR>\n' .
                                      '<PRE style="background : #F0F0F0;">\n<SPAN style="background : #C0C0C0;">' . str_pad($i, strlen(count($aSQL)), ' ', STR_PAD_LEFT) . '</SPAN> ' . htmlspecialchars($sSQL) . ';\n';

                    } else {
                        $nSQLDone ++;

                        $nSQLDonePercentage = floor(100*$nSQLDone / $nSQL); // Don't want to show 100% when an error occurs at 99.5%.
                        if ($nSQLDonePercentage != $nSQLDonePercentagePrev) {
                            $_BAR->setProgress($nSQLDonePercentage);
                            $nSQLDonePercentagePrev = $nSQLDonePercentage;
                        }

                        flush();
                        usleep(1000);
                    }

                } else {
                    // Something went wrong, so we need to print out the remaining queries...
                    $nSQLFailed ++;
                    $sSQLFailed .= '<SPAN style="background : #C0C0C0;">' . str_pad($i, strlen(count($aSQL)), ' ', STR_PAD_LEFT) . '</SPAN> ' . htmlspecialchars($sSQL) . ';\n';
                }
            }

            if ($nSQLFailed) {
                $sSQLFailed .= '</PRE>';
                $_BAR->setMessage($sSQLFailed);
                $_BAR->setMessage('After executing th' . ($nSQLFailed == 1? 'is query' : 'ese queries') . ', please try again.', 'done');
                $_BAR->setMessageVisibility('done', true);
                break;
            }
            usleep(300000);
        }

        if (!$nSQLFailed) {
            // Upgrade complete, all OK!
            lovd_writeLog('Install', 'Upgrade', 'Successfully upgraded LOVD from ' . $_STAT['version'] . ' to ' . $_SETT['system']['version'] . ', executing ' . $nSQLDone . ' quer' . ($nSQLDone == 1? 'y' : 'ies'));
            $_BAR->setProgress(100);
            $_BAR->setMessage('Successfully upgraded to ' . $_SETT['system']['version'] . '!<BR>Executed ' . $nSQLDone . ' database quer' . ($nSQLDone == 1? 'y' : 'ies') . '.');
        } else {
            // Bye bye, they should not see the form!
            print('</BODY>' . "\n" .
                  '</HTML>' . "\n");
            exit;
        }

        // Remove update lock.
        $q = lovd_queryDB('UPDATE ' . TABLE_STATUS . ' SET lock_update = 0');
    }

    // Now that this is over, let the user proceed to whereever they were going!
    if ($bLocked) {
        // Have to force upgrade...
        $_SERVER['REQUEST_URI'] .= ($_SERVER['QUERY_STRING']? '&' : '?') . 'force_lock';
    } else {
        // Remove the force_lock thing again... (might not be there, but who cares!)
        $_SERVER['REQUEST_URI'] = preg_replace('/[?&]force_lock$/', '', $_SERVER['REQUEST_URI']);
    }

    print('<SCRIPT type="text/javascript">document.forms[\'upgrade_form\'].action=\'' . str_replace('\'', '\\\'', $_SERVER['REQUEST_URI']) . '\';</SCRIPT>' . "\n");
    if ($bLocked) {
        print('<SCRIPT type="text/javascript">document.forms[\'upgrade_form\'].submit.value = document.forms[\'upgrade_form\'].submit.value.replace(\'Proceed\', \'Force upgrade\');</SCRIPT>' . "\n");
    }
    $_BAR->setMessageVisibility('done', true);
    print('</BODY>' . "\n" .
          '</HTML>' . "\n");
    exit;
}
?>
