<?php

/*
   ----------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2011 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ----------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 2 of the License, or
   any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with FusionInventory.  If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------
   Original Author of file: David DURIEUX
   Co-authors of file:
   Purpose of file:
   ----------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class PluginFusinvinventoryImport_User extends CommonDBTM {


   /**
   * Add User
   *
   * @param $type value "add" or "update"
   * @param $items_id integer id of the computer
   * @param $dataSection array all values of the section
   *
   * @return id of the computer or false
   *
   **/
   function AddUpdateItem($type, $items_id, $dataSection) {
      global $DB;

      $Computer = new Computer();

      $a_lockable = PluginFusioninventoryLock::getLockFields('glpi_computers', $items_id);

      if (!isset($_SESSION["plugin_fusinvinventory_userdefined"])) {
         $_SESSION["plugin_fusinvinventory_userdefined"] = 0;
      }

      $Computer->getFromDB($items_id);
      $input = array();
      $input['id'] = $Computer->fields['id'];
      if (isset($dataSection['LOGIN'])) {
         if (!in_array('contact', $a_lockable)) {
            if ($_SESSION["plugin_fusinvinventory_userdefined"] == 0) {
               $input['contact'] = $dataSection['LOGIN'];
            } else {
               $input['contact'] .= "/".$dataSection['LOGIN'];
            }
         }
         $input['contact'] = preg_replace("/^\//", "", $input['contact']);
         if ((!in_array('users_id', $a_lockable))
                 AND ($_SESSION["plugin_fusinvinventory_userdefined"] == 0)) {
            $query = "SELECT `id`
                      FROM `glpi_users`
                      WHERE `name` = '" . $dataSection['LOGIN'] . "';";
            $result = $DB->query($query);
            if ($DB->numrows($result) == 1) {
               $input["users_id"] = $DB->result($result, 0, 0);
               $_SESSION["plugin_fusinvinventory_userdefined"] = 1;
               $Computer->update($input);
               return $DB->result($result, 0, 0);
            }
         }
         $Computer->update($input);
         return "-".$dataSection['LOGIN'];
      }
      return "";
   }



   /**
   * Delete user
   *
   * @param $items_id integer id of the user or -username
   * @param $idmachine integer id of the computer
   *
   * @return nothing
   *
   **/
   function deleteItem($items_id, $idmachine) {
      $Computer = new Computer();
      $User = new User();

      $input = array();

      if (!isset($_SESSION["plugin_fusinvinventory_userdefined"])) {
         $_SESSION["plugin_fusinvinventory_userdefined"] = 0;
      }
      $Computer->getFromDB($idmachine);
      $input = array();
      $input['id'] = $Computer->fields['id'];
      if (!strstr($items_id, "-")) {
         $User->getFromDB($items_id);
         //$Computer->fields["users_id"] = 0;
         $_SESSION["plugin_fusinvinventory_userdefined"] = 0;
      }
      $username = preg_replace("/^-/", "", $items_id);
      if (is_numeric($username)) {
         $User->getFromDB($items_id);
         $username = $User->getField("name");
      }
      if (strstr($Computer->fields['contact'], "/".$username)) {
         $input['contact'] = str_replace("/".$username, "", $Computer->fields['contact']);
      } else {
         $input['contact'] = str_replace($username, "", $Computer->fields['contact']);
      }
      $input['contact'] = preg_replace("/^\//", "", $input['contact']);
      $Computer->update($input);
   }
}

?>