<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2016-11-22
 * Modified    : 2016-11-23
 * For LOVD    : 3.0-18
 *
 * Copyright   : 2004-2016 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
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



class LOVD_API_Submissions {
    // This class defines the LOVD API object handling submissions.

    private $API;                     // The API object.
    private $nMaxPOSTSize = 1048576;  // The maximum POST size allowed (1MB).

    private $aRepeatableElements = array(
        'varioml' => array(
            'db_xref',
            'individual',
            'phenotype',
            'variant',
            'pathogenicity',
            'variant_detection',
            'seq_changes',
            'gene',
        ),
    );





    function __construct (&$oAPI)
    {
        // Links the API to the private variable.

        if (!is_object($oAPI) || !is_a($oAPI, 'LOVD_API')) {
            return false;
        }

        $this->API = $oAPI;
        return true;
    }





    private function cleanVarioMLData (&$aInput)
    {
        // Cleans the VarioML-formatted data by making sure all elements that
        //  can be repeated in the specs, are presented as an array.
        // Function will call itself, while traversing through the array.

        if (is_array($aInput)) {
            foreach ($aInput as $sKey => $Value) {
                // Attributes or text values can never be repeated, so check only possible arrays.
                if ($sKey{0} != '@' && $sKey{0} != '#') {
                    // Check if this key is listed as one that can be repeated.
                    if (in_array((string) $sKey, $this->aRepeatableElements['varioml'])) {
                        // This element can be repeated. Make sure it's a proper array of values.
                        if (!is_array($Value) || !isset($Value[0])) {
                            $aInput[$sKey] = $Value = array($Value);
                        }
                    }
                    if (is_array($Value)) {
                        $this->cleanVarioMLData($Value);
                        $aInput[$sKey] = $Value;
                    }
                }
            }
        }

        return true;
    }





    private function jsonDecode ($sInput)
    {
        // Attempts to decode the given JSON string, and handles any error.
        // Returns the array if successfully decoded, but throws any errors
        //  directly to the output.

        $aJSONErrors = array(
            JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX => 'Syntax error',
        );
        if (PHP_VERSION_ID >= 50303) {
            $aJSONErrors[JSON_ERROR_UTF8] = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            if (PHP_VERSION_ID >= 50500) {
                $aJSONErrors[JSON_ERROR_RECURSION] = 'One or more recursive references in the value to be encoded';
                $aJSONErrors[JSON_ERROR_INF_OR_NAN] = 'One or more NAN or INF values in the value to be encoded';
                $aJSONErrors[JSON_ERROR_UNSUPPORTED_TYPE] = 'A value of a type that cannot be encoded was given';
            } else {
                // This makes sure they can be referenced, but can never occur.
                define('JSON_ERROR_RECURSION', 0);
                define('JSON_ERROR_INF_OR_NAN', 0);
                define('JSON_ERROR_UNSUPPORTED_TYPE', 0);
            }
        } else {
            // This makes sure they can be referenced, but can never occur.
            define('JSON_ERROR_UTF8', 0);
        }

        // Attempt to decode.
        $aInput = json_decode($sInput, true);

        // If not successful, try if a non-UTF8 string is the error.
        if ($aInput === NULL && json_last_error() == JSON_ERROR_UTF8) {
            // Encode to UTF8, and try again.
            $aInput = json_decode(utf8_encode($sInput), true);
        }

        if ($aInput === NULL) {
            // Handle errors.
            $this->API->aResponse['errors'][] = 'Error parsing JSON input. Error: ' . $aJSONErrors[json_last_error()] . '.';
            $this->API->nHTTPStatus = 400; // Send 400 Bad Request.
            return false;
        }

        // If we're still here, we have properly decoded data.
        return $aInput;
    }





    public function processPOST ()
    {
        // Handle POST requests for submissions.

        // Check if we're receiving data at all over POST.
        $sInput = file_get_contents('php://input');
        if (!$sInput) {
            // No data received.
            $this->API->aResponse['errors'][] = 'No data received.';
            $this->API->nHTTPStatus = 400; // Send 400 Bad Request.
            return false;
        }

        // Check the size of the data. Can not be larger than $this->nMaxPOSTSize.
        if (strlen($sInput) > $this->nMaxPOSTSize) {
            $this->API->aResponse['errors'][] = 'Payload too large. Maximum data size: ' . $this->nMaxPOSTSize . ' bytes.';
            $this->API->nHTTPStatus = 413; // Send 413 Payload Too Large.
            return false;
        }

        // If we have data, do a quick check if it could be JSON.
        // First, content type. Also accept an often made error (application/x-www-form-urlencoded data).
        if (isset($_SERVER['CONTENT_TYPE']) && !in_array($_SERVER['CONTENT_TYPE'], array('application/json', 'application/x-www-form-urlencoded'))) {
            $this->API->aResponse['errors'][] = 'Unsupported media type. Expecting: application/json.';
            $this->API->nHTTPStatus = 415; // Send 415 Unsupported Media Type.
            return false;
        }

        // Remove comments from JSON file, and trim.
        $sInput = trim(preg_replace('/\/\*.+\*\//Us', '', $sInput));

        // Then, check the first character. Should be an '{'.
        if ($sInput{0} != '{') {
            // Can't be JSON...
            $this->API->aResponse['errors'][] = 'Unsupported media type. Expecting: application/json.';
            $this->API->nHTTPStatus = 415; // Send 415 Unsupported Media Type.
            return false;
        }

        // If it appears to be JSON, have PHP try and convert it into an array.
        $aInput = $this->jsonDecode($sInput);
        // If $aInput is false, we failed somewhere. Function should have set response and HTTP status.
        if ($aInput === false) {
            return false;
        }

        return (
            // Clean up the JSON, not forcing minimum data requirements yet.
            // This makes traversing the array easier.
            $this->cleanVarioMLData($aInput) &&

            // Check for minimum data set.
            $this->verifyVarioMLData($aInput)

            // Do a quick check on the data, which is specific for the VarioML format.

            // Convert into the LOVD3 output file.
        );
    }





    private function verifyVarioMLData (&$aInput)
    {
        // Verifies if the VarioML data is complete; Is the source OK, and the
        //  authorization OK? Is there at least one individual with variants?
        // At least one screening present?
        global $_SETT, $_STAT;

        // FIXME: If we'd have a proper VarioML JSON schema, like:
        // https://github.com/VarioML/VarioML/blob/master/json/examples/vreport.json-schema
        //  then we could use something like this library:
        // https://github.com/justinrainbow/json-schema
        //  to preprocess the VarioML, which saves us a lot of code. More info:
        // http://json-schema.org/

        // First, check if this file is actually meant for this LSDB.
        if (!isset($aInput['lsdb']) || !isset($aInput['lsdb']['@id'])) {
            // Without the proper LSDB info, we can't authorize this addition.
            $this->API->aResponse['errors'][] = 'VarioML error: LSDB root element not found, or has no ID.';
            $this->API->nHTTPStatus = 422; // Send 422 Unprocessable Entity.
            return false;
        }

        if ($aInput['lsdb']['@id'] != md5($_STAT['signature'])) {
            // Data file is not meant for this LSDB.
            $this->API->aResponse['errors'][] = 'VarioML error: LSDB ID in file does not match this LSDB. ' .
                'Submit your file to the correct LSDB, or if sure you want to submit here, ' .
                'request the LSDB ID from the admin: ' . $_SETT['admin']['name'] . ' <' . $_SETT['admin']['email'] . '>.';
            $this->API->nHTTPStatus = 422; // Send 422 Unprocessable Entity.
            return false;
        }
        $aInput = $aInput['lsdb']; // Simplifying our code.

        // Then, check the source info and the authorization.
        if (!isset($aInput['source']) || !isset($aInput['source']['name']) || !isset($aInput['source']['email'])) {
            $this->API->aResponse['errors'][] = 'VarioML error: Source element not found, or no contact information.';
            $this->API->nHTTPStatus = 422; // Send 422 Unprocessable Entity.
            return false;
        }
        if (!isset($aInput['source']['db_xref'])) {
            $this->API->aResponse['errors'][] = 'VarioML error: Authorization IDs not found.';
            $this->API->nHTTPStatus = 422; // Send 422 Unprocessable Entity.
            return false;
        }


        return true;
    }
}
?>
