<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2011-02-16
 * Modified    : 2017-11-30
 * For LOVD    : 3.0-21
 *
 * Copyright   : 2004-2017 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.nl>
 *               Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *               Daan Asscheman <D.Asscheman@LUMC.nl>
 *               M. Kroon <m.kroon@lumc.nl>
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
// Require parent class definition.
require_once ROOT_PATH . 'class/object_custom.php';





class LOVD_Phenotype extends LOVD_Custom {
    // This class extends the basic Object class and it handles the Link object.
    var $sObject = 'Phenotype';
    var $bShared = true;





    function __construct ($sObjectID = '', $nID = '')
    {
        // Default constructor.
        global $_AUTH;

        // SQL code for loading an entry for an edit form.
        // FIXME; change owner to owned_by_ in the load entry query below.
        $this->sSQLLoadEntry = 'SELECT p.*, ' .
                               'uo.name AS owner ' .
                               'FROM ' . TABLE_PHENOTYPES . ' AS p ' .
                               'LEFT JOIN ' . TABLE_USERS . ' AS uo ON (p.owned_by = uo.id) ' .
                               'WHERE p.id = ?';

        // SQL code for viewing an entry.
        $this->aSQLViewEntry['SELECT']   = 'p.*, ' .
                                           'i.statusid AS individual_statusid, ' .
                                           'd.symbol AS disease, ' .
                                           'uo.name AS owned_by_, ' .
                                           'uc.name AS created_by_, ' .
                                           'ue.name AS edited_by_';
        $this->aSQLViewEntry['FROM']     = TABLE_PHENOTYPES . ' AS p ' .
                                           'LEFT OUTER JOIN ' . TABLE_INDIVIDUALS . ' AS i ON (p.individualid = i.id) ' .
                                           'LEFT OUTER JOIN ' . TABLE_DISEASES . ' AS d ON (p.diseaseid = d.id) ' .
                                           'LEFT OUTER JOIN ' . TABLE_USERS . ' AS uo ON (p.owned_by = uo.id) ' .
                                           'LEFT OUTER JOIN ' . TABLE_USERS . ' AS uc ON (p.created_by = uc.id) ' .
                                           'LEFT OUTER JOIN ' . TABLE_USERS . ' AS ue ON (p.edited_by = ue.id)';
        $this->aSQLViewEntry['GROUP_BY'] = 'p.id';

        // SQL code for viewing the list of phenotypes
        $this->aSQLViewList['SELECT']   = 'p.*, ' .
                                          'ds.name AS status, ' .
                                          'uo.name AS owned_by_, ' .
                                          'CONCAT_WS(";", uo.id, uo.name, uo.email, uo.institute, uo.department, IFNULL(uo.countryid, "")) AS _owner';
        $this->aSQLViewList['FROM']     = TABLE_PHENOTYPES . ' AS p ' .
                                          'LEFT OUTER JOIN ' . TABLE_USERS . ' AS uo ON (p.owned_by = uo.id) ' .
                                          'LEFT OUTER JOIN ' . TABLE_DATA_STATUS . ' AS ds ON (p.statusid = ds.id)';

        $this->sObjectID = $sObjectID;
        $this->nID = $nID;

        // Run parent constructor to find out about the custom columns.
        parent::__construct();

        // List of columns and (default?) order for viewing an entry.
        $this->aColumnsViewEntry = array_merge(
                 array(
                        'individualid_' => 'Individual ID',
                        'disease_' => 'Associated disease',
                      ),
                 $this->buildViewEntry(),
                 array(
                        'owned_by_' => 'Owner name',
                        'status' => array('Phenotype data status', LEVEL_COLLABORATOR),
                        'created_by_' => array('Created by', LEVEL_COLLABORATOR),
                        'created_date_' => array('Date created', LEVEL_COLLABORATOR),
                        'edited_by_' => array('Last edited by', LEVEL_COLLABORATOR),
                        'edited_date_' => array('Date last edited', LEVEL_COLLABORATOR),
                      ));

        // List of columns and (default?) order for viewing a list of entries.
        $this->aColumnsViewList = array_merge(
                 array(
                        'id_' => array(
                                    'view' => array('Phenotype ID', 110, 'style="text-align : right;"'),
                                    'db'   => array('p.id', 'ASC', true)),
                      ),
                 $this->buildViewList(),
                 array(
                        'owned_by_' => array(
                                    'view' => array('Owner', 160),
                                    'db'   => array('uo.name', 'ASC', true)),
                        'owner_countryid' => array(
                                    'view' => false,
                                    'db'   => array('uo.countryid', 'ASC', true)),
                        'status' => array(
                                    'view' => array('Status', 70),
                                    'db'   => array('ds.name', false, true),
                                    'auth' => LEVEL_COLLABORATOR),
                        'individualid' => array(
                                    'view' => array('Individual ID', 70, 'style="text-align : right;"'),
                                    'db'   => array('p.individualid', 'ASC', true)),
                        'diseaseid' => array(
                                    'view' => array('Disease ID', 70, 'style="text-align : right;"'),
                                    'db'   => array('p.diseaseid', 'ASC', true)),
                      ));

        $this->sSortDefault = 'id_';

        // Because the information is publicly available, remove some columns for the public.
        $this->unsetColsByAuthLevel();
    }





    function checkFields ($aData, $zData = false, $aOptions = array())
    {
        // Mandatory fields.
        $this->aCheckMandatory =
                 array(
                        'owned_by',
                        'statusid',
                      );
        parent::checkFields($aData, $zData, $aOptions);

        lovd_checkXSS();
    }





    function getForm ()
    {
        // Build the form.

        // If we've built the form before, simply return it. Especially imports will repeatedly call checkFields(), which calls getForm().
        if (!empty($this->aFormData)) {
            return parent::getForm();
        }

        global $_AUTH, $_DB, $_SETT;

        if (ACTION == 'edit') {
            global $zData;
            $_POST['diseaseid'] = $zData['diseaseid'];
        }

        if (!empty($_POST['diseaseid'])) {
            $sDisease = $_DB->query('SELECT name FROM ' . TABLE_DISEASES . ' WHERE id = ?', array($_POST['diseaseid']))->fetchColumn();
        } else {
            $sDisease = 'all diseases';
        }

        if ($_AUTH['level'] >= LEVEL_CURATOR) {
            $aSelectOwner = $_DB->query('SELECT id, CONCAT(name, " (#", id, ")") as name_id FROM ' . TABLE_USERS .
                ' ORDER BY name')->fetchAllCombine();
            $aFormOwner = array('Owner of this data', '', 'select', 'owned_by', 1, $aSelectOwner, false, false, false);
            $aSelectStatus = $_SETT['data_status'];
            if (lovd_getProjectFile() == '/import.php') {
                // During an import the status pending is allowed, therefore only status in progress is unset.
                unset($aSelectStatus[STATUS_IN_PROGRESS]);
            } else {
                unset($aSelectStatus[STATUS_PENDING], $aSelectStatus[STATUS_IN_PROGRESS]);
            }
            $aFormStatus = array('Status of this data', '', 'select', 'statusid', 1, $aSelectStatus, false, false, false);
        } else {
            $aFormOwner = array();
            $aFormStatus = array();
        }

        // FIXME; right now two blocks in this array are put in, and optionally removed later. However, the if() above can build an entire block, such that one of the two big unset()s can be removed.
        // A similar if() to create the "authorization" block, or possibly an if() in the building of this form array, is easier to understand and more efficient.
        // Array which will make up the form table.
        $this->aFormData = array_merge(
                 array(
                        array('POST', '', '', '', '35%', '14', '65%'),
                        array('', '', 'print', '<B>Phenotype information related to ' . $sDisease . '</B>'),
                        'hr',
                      ),
                 $this->buildForm(),
                 array(
                        'hr',
      'general_skip' => 'skip',
           'general' => array('', '', 'print', '<B>General information</B>'),
       'general_hr1' => 'hr',
             'owner' => $aFormOwner,
            'status' => $aFormStatus,
       'general_hr2' => 'hr',
                        'skip',
     'authorization' => array('Enter your password for authorization', '', 'password', 'password', 20),
                      ));

        if (ACTION == 'create' || (ACTION == 'publish' && GET)) {
            // When creating, or when publishing without any changes, unset the authorization.
            unset($this->aFormData['authorization']);
        }
        if ($_AUTH['level'] < LEVEL_CURATOR) {
            unset($this->aFormData['general_skip'], $this->aFormData['general'], $this->aFormData['general_hr1'], $this->aFormData['owner'], $this->aFormData['status'], $this->aFormData['general_hr2']);
        }

        return parent::getForm();
    }





    function prepareData ($zData = '', $sView = 'list')
    {
        // Prepares the data by "enriching" the variable received with links, pictures, etc.
        global $_AUTH, $_SETT;

        if (!in_array($sView, array('list', 'entry'))) {
            $sView = 'list';
        }

        // Makes sure it's an array and htmlspecialchars() all the values.
        $zData = parent::prepareData($zData, $sView);

        if ($sView == 'entry') {
            $zData['individualid_'] = '<A href="individuals/' . $zData['individualid'] . '">' . $zData['individualid'] . '</A>';
            if ($_AUTH['level'] >= LEVEL_COLLABORATOR) {
                $zData['individualid_'] .= ' <SPAN style="color : #' . $this->getStatusColor($zData['individual_statusid']) . '">(' . $_SETT['data_status'][$zData['individual_statusid']] . ')</SPAN>';
            }
            $zData['disease_'] = '<A href="diseases/' . $zData['diseaseid'] . '">' . $zData['disease'] . '</A>';
            if (!empty($zData['Phenotype/Age']) && preg_match('/^([<>])?(\d+y)(\d+m)?(\d+d)?(\?)?$/', htmlspecialchars_decode($zData['Phenotype/Age']), $aMatches)) {
                $aMatches = $aMatches + array_fill(0, 5, ''); // Fill $aMatches with enough values.
                $nYears = (int) $aMatches[2];
                $nMonths = (int) $aMatches[3];
                $nDays = (int) $aMatches[4];
                $sAge  = (!$nYears? '' : $nYears . ' year' . ($nYears == 1? '' : 's'));
                $sAge .= (!$nMonths? '' : ($sAge? ', ' : '') . $nMonths . ' month' . ($nMonths == 1? '' : 's'));
                $sAge .= (!$nDays? '' : ($sAge? ', ' : '') . $nDays . ' day' . ($nDays == 1? '' : 's'));
                $zData['Phenotype/Age'] .= ' (' . (!$aMatches[1]? '' : ($aMatches[1] == '>'? 'later than' : 'before') . ' ') . (empty($aMatches[5])? '' : 'approximately ') . $sAge . ')';
            }
        }

        return $zData;
    }





    function setDefaultValues ()
    {
        global $_AUTH;

        $_POST['statusid'] = STATUS_OK;
        $_POST['owned_by'] = $_AUTH['id'];
        $this->initDefaultValues();
    }
}
?>
