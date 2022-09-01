<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/../../3rdparty/client.php';
require_once __DIR__  . '/../../3rdparty/camera.php';
require_once __DIR__  . '/../../3rdparty/jeezvizV2Camera.php';
require_once __DIR__  . '/../../3rdparty/JeezvizV2UserAgent.php';

class jeezvizv2 extends eqLogic {
   
    /*     * ***********************Methode static*************************** */

   public static function cron5() {
      log::add("jeezvizv2","debug","================ Debut cron ================");
      $allEqlogic = eqLogic::byType('jeezvizv2');
      foreach ($allEqlogic as $eqLogic){
         $refreshCmd = $eqLogic->getCmd('action', 'refresh');
         if (is_object($refreshCmd))
         {
            $refreshCmd->execute();
         }
      }
      log::add("jeezvizv2","debug","================ Fin cron ================");
   }
   

   public function postSave() {
      log::add('jeezvizv2', 'debug', '============ Début postSave ==========');
      $defaultActions=array("refresh" => "Rafraichir", 
                  "moveup" => "Haut", 
                  "movedown" => "Bas", 
                  "moveleft" => "Gauche", 
                  "moveright" => "Droite", 
                  "privacyOn" => "Mode Privé On", 
                  "privacyOff" => "Mode Privé Off", 
                  "alarmNotifyOn" => "Activer les notifications", 
                  "alarmNotifyOff" => "Désactiver les notifications",
                  "alarmNotifyIntense" => "Notifications : intences",
                  "alarmNotifyLogiciel" => "Notifications : Rappels léger",
                  "alarmNotifySilence" => "Notifications : Silence");
      $defaultBinariesInfos=array("hik" => "Hikvision",                              
                        "offlineNotify" => "Notification de déconnection",
                        "status" => "Etat");
      $defaultNumericInfos=array("casPort" => "Port CAS",
                        "offlineTimestamp" => "Déconnectée depuis (Timestamp)");
      $defaultOtherInfos=array("name" => "Nom",
                        "deviceSerial" => "Numéro de série",
                        "fullSerial" => "Numéro de série complet",
                        "deviceType" => "Type d'équipement",
                        "devicePicPrefix" => "Url de l'Image",
                        "version" => "Version",
                        "supportExt" => "Extension supportées",
                        "userDeviceCreateTime" => "Date de création",
                        "casIp" => "IP CAS",
                        "channelNumber" => "Canal",
                        "deviceCategory" => "Catégorie",
                        "deviceSubCategory" => "Sous Catégorie",
                        "ezDeviceCapability" => "EzDeviceCapability",
                        "customType" => "Custom Type",
                        "offlineTime" => "Déconnectée depuis",
                        "accessPlatform" => "Accès plateforme",
                        "deviceDomain" => "Domaine",
                        "instructionBook" => "Mode d'emploi");
                        
      foreach ($defaultActions as $key => $value) {
         $this->createCmd($value, $key, 'action', 'other');
      }
      foreach ($defaultBinariesInfos as $key => $value) {
         $this->createCmd($value, $key, 'info', 'binary');
      }
      foreach ($defaultNumericInfos as $key => $value) {
         $this->createCmd($value, $key, 'info', 'numeric');
      }
      foreach ($defaultOtherInfos as $key => $value) {
         $this->createCmd($value, $key, 'info', 'string');
      }
   }
   public function createCmd($cmdName, $logicalID, $type, $subType)
   {
      $getDataCmd = $this->getCmd(null, $logicalID);
      if (!is_object($getDataCmd))
      {
         // Création de la commande
         $cmd = new jeezvizCmd();
         // Nom affiché
         $cmd->setName($cmdName);
         // Identifiant de la commande
         $cmd->setLogicalId($logicalID);
         // Identifiant de l'équipement
         $cmd->setEqLogic_id($this->getId());
         // Type de la commande
         $cmd->setType($type);
         $cmd->setSubType($subType);
         // Visibilité de la commande
         $cmd->setIsVisible(1);
         // Sauvegarde de la commande
         $cmd->save();
      }
   }

   public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = '/tmp/dependancy_jeezvizv2_in_progress';
		$return['state'] = 'ok';
		if (exec('sudo pip3 list | grep -E "pyezviz" | wc -l') < 1) {
			$return['state'] = 'nok';
		}
		return $return;
	}

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_dependancy_install');
      
      log::add('jeezvizv2_dependancy_install', 'debug', '============ Début install dépendances ==========');
      log::add('jeezvizv2_dependancy_install', 'debug', 'script : '.dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('jeezvizv2') . '/dependance');
      log::add('jeezvizv2_dependancy_install', 'debug', 'log'.log::getPathToLog(__CLASS__ . '_dependancy_install'));
     
		return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('jeezvizv2') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '__dependancy_install'));
	}

}

class jeezvizv2Cmd extends cmd {

   public function execute($_options = array()) {
      log::add('jeezvizv2', 'debug', '============ Début execute ==========');
      if ($this->getType() != 'action') {
         return;
      }
      
      log::add('jeezvizv2', 'debug', 'Fonction execute démarrée');
      log::add('jeezvizv2', 'debug', 'EqLogic_Id : '.$this->getEqlogic_id());
      log::add('jeezvizv2', 'debug', 'Name : '.$this->getName());

      $jeezvizObj = jeezvizv2::byId($this->getEqlogic_id());
      $serial=$jeezvizObj->getConfiguration('serial');
      
      log::add('jeezvizv2', 'debug', 'Serial : '.$serial);         

      $EzvizV2Client = new EzvizV2Client();
      #$EzvizV2Client->get_PAGE_LIST();
      $EzvizV2Camera = new EzvizV2Camera($EzvizV2Client, $serial);
      $EzvizV2CameraNew = new EzvizV2CameraNew($serial);
      
      switch (strtoupper($this->getLogicalId()))
      {
         case "REFRESH":
            $this->RefreshCamera($EzvizV2CameraNew);
            break;
         case "PRIVACYON":
            log::add('jeezvizv2', 'debug', "PRIVACYON");
            $EzvizV2Camera->switch_privacy_mode(1);
            break;
         case "PRIVACYOFF":
            log::add('jeezvizv2', 'debug', "PRIVACYOFF");    
            $EzvizV2Camera->switch_privacy_mode(0);
            break;
         case "ALARMNOTIFYON":
            log::add('jeezvizv2', 'debug', "ALARMNOTIFYON");
            #$EzvizV2Camera->alarm_notify(1);    
            $EzvizV2Camera->alarm_notify(1);
            break;
         case "ALARMNOTIFYOFF":
            log::add('jeezvizv2', 'debug', "ALARMNOTIFYOFF");    
            #$EzvizV2Camera->alarm_notify(0);
            $EzvizV2Camera->alarm_notify(0);
            break;
         case "ALARMNOTIFYINTENSE":
            log::add('jeezvizv2', 'debug', "ALARMNOTIFYINTENSE");    
            $EzvizV2Camera->alarm_sound(1);
            break;          
         case "ALARMNOTIFYLOGICIEL":
            log::add('jeezvizv2', 'debug', "ALARMNOTIFYLOGICIEL");    
            $EzvizV2Camera->alarm_sound(0);
            break;         
         case "ALARMNOTIFYSILENCE":
            log::add('jeezvizv2', 'debug', "ALARMNOTIFYSILENCE");    
            $EzvizV2Camera->alarm_sound(2);
            break;
         case "GETSTATUS":
            log::add('jeezvizv2', 'debug', "GETSTATUS");
            log::add('jeezvizv2', 'debug', var_dump($EzvizV2Camera->status()));
            break;
         case "MOVEUP":
            log::add('jeezvizv2', 'debug', "MOVEUP");
            $EzvizV2Camera->move("up");
            break;
         case "MOVEDOWN":
            log::add('jeezvizv2', 'debug', "MOVEDOWN");
            $EzvizV2Camera->move("down");
            break;
         case "MOVELEFT":
            log::add('jeezvizv2', 'debug', "MOVELEFT");
            $EzvizV2Camera->move("left");
            break;
         case "MOVERIGHT":
            log::add('jeezvizv2', 'debug', "MOVERIGHT");
            $EzvizV2Camera->move("right");
            break;
      }
      log::add('jeezvizv2', 'debug', '============ Fin execute ==========');

   }
   public function RefreshCamera($EzvizV2Camera)
   {
      log::add('jeezvizv2', 'debug', '============ Début refresh ==========');
      try {
         $retour=$EzvizV2Camera->Load();
         $jeezvizObj = jeezvizv2::byId($this->getEqlogic_id());
         foreach($retour as $key => $value) {
            $this->SaveCmdInfo($jeezvizObj, $key, $value);
         }
      } catch (Exception $e) {
         log::add('jeezvizv2', 'debug', $e->getMessage());      
      }
      log::add('jeezvizv2', 'debug', '============ Fin refresh ==========');
   }
   public function SaveCmdInfo($jeezvizObj, $key, $value, $parentKey=null){
      log::add('jeezvizv2', 'debug', 'Vérification de la clef '.$parentKey.$key);
      if (is_array($value))
      {
         $parentKey= $parentKey.$key.'_';
         foreach($value as $key1 => $value1) {                    
            $this->SaveCmdInfo($jeezvizObj, $key1, $value1);
         }          
      }
      else
      {
         log::add('jeezvizv2', 'debug', 'Recherche de la commande '.$parentKey.$key);
         $infoCmd = $jeezvizObj->getCmd('info', $parentKey.$key);
         if (is_object($infoCmd))
         { 
            log::add('jeezvizv2', 'debug', 'Mise à jour de la commande '.$parentKey.$key.' à '.$value);
            $infoCmd->event($value);
         }
         else
         {
            log::add('jeezvizv2', 'debug', 'Création de la commande '.$parentKey.$key.' à '.$value);
            // Création de la commande
            $cmd = new jeezvizCmd();
            // Nom affiché
            $cmd->setName($cmdName);
            // Identifiant de la commande
            $cmd->setLogicalId($logicalID);
            // Identifiant de l'équipement
            $cmd->setEqLogic_id($this->getId());
            // Type de la commande
            $cmd->setType('info');
            $cmd->setSubType('string');
            // Visibilité de la commande
            $cmd->setIsVisible(1);
            // Sauvegarde de la commande
            $cmd->save();
            log::add('jeezvizv2', 'debug', 'Mise à jour de la commande '.$parentKey.$key.' à '.$value);
            $infoCmd->event($value);
         }
      }
   }
      
   public function postSave() {
      /*$jeezvizObj = jeezvizv2::byId($this->getEqlogic_id());
      $refreshCmd = $jeezvizObj->getCmd('action', 'refresh');
      if (is_object($refreshCmd))
      {
         $refreshCmd->execute();
      }*/
   }
}


