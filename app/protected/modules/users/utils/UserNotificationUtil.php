<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2014 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU Affero General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
     * details.
     *
     * You should have received a copy of the GNU Affero General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 27 North Wacker Drive
     * Suite 370 Chicago, IL 60606. or at email address contact@zurmo.com.
     *
     * The interactive user interfaces in original and modified versions
     * of this program must display Appropriate Legal Notices, as required under
     * Section 5 of the GNU Affero General Public License version 3.
     *
     * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
     * these Appropriate Legal Notices must retain the display of the Zurmo
     * logo and Zurmo copyright notice. If the display of the logo is not reasonably
     * feasible for technical reasons, the Appropriate Legal Notices must display the words
     * "Copyright Zurmo Inc. 2014. All rights reserved".
     ********************************************************************************/

    class UserNotificationUtil
    {
        public static function isEnabledByUserAndNotificationNameAndType(User $user, $name, $type)
        {
            assert('$type == "inbox" || $type == "email"');
            $notificationSettings = static::getNotificationSettingsByUser($user);
            if (isset($notificationSettings[$name][$type]))
            {
                return $notificationSettings[$name][$type];
            }
            else 
            {
                return false;
            }
        }
        
        public static function getNotificationSettingsByUser(User $user)
        {
            $defaultNotificationSettings = static::getNotificationSettingsDefaultValues();
            $notificationSettings = UserNotificationUtil::resolveAndGetValue($user, 'inboxAndEmailNotificationSettings', false);
            
            if(is_array($notificationSettings) && !empty($notificationSettings))
            {
                foreach($notificationSettings as $notificationName => $notificationSetting)
                {
                    $defaultNotificationSettings[$notificationName] = $notificationSetting;
                }
            }
            
            return $defaultNotificationSettings;
        }
        
        public static function getAllNotificationSettingNames()
        {
            $defaultNotificationSettings = static::getNotificationSettingsDefaultValues();
            return array_keys($defaultNotificationSettings);
        }
        
        public static function getNotificationSettingsDefaultValues()
        {
            $defaultNotificationSettings = array(
                'enableStuckMonitorJobNotification'                     => array('inbox'=>false, 'email'=>false),
                'enableStuckJobsNotification'                           => array('inbox'=>false, 'email'=>false),
                'enableJobCompletedWithErrorsNotification'              => array('inbox'=>false, 'email'=>false),
                'enableNewZurmoVersionAvailableNotification'            => array('inbox'=>false, 'email'=>false),
                'enableEmailMessageOwnerNotExistNotification'           => array('inbox'=>false, 'email'=>false),
                'enableWorkflowValidityCheckNotification'               => array('inbox'=>false, 'email'=>false),
                'enableWorkflowMaximumDepthNotification'                => array('inbox'=>false, 'email'=>false),
                'enableConversationInvitesNotification'                 => array('inbox'=>false, 'email'=>false),
                'enableConversationNewCommentNotification'              => array('inbox'=>false, 'email'=>false),
                'enableNewMissionNotification'                          => array('inbox'=>false, 'email'=>false),
                'enableMissionStatusChangeNotification'                 => array('inbox'=>false, 'email'=>false),
                'enableMissionNewCommentNotification'                   => array('inbox'=>false, 'email'=>false),
                'enableNewTaskNotification'                             => array('inbox'=>false, 'email'=>false),
                'enableDeliveredTaskNotification'                       => array('inbox'=>false, 'email'=>false),
                'enableAcceptedTaskNotification'                        => array('inbox'=>false, 'email'=>false),
                'enableRejectedTaskNotification'                        => array('inbox'=>false, 'email'=>false),
                'enableTaskOwnerChangeNotification'                     => array('inbox'=>false, 'email'=>false),
                'enableTaskNewCommentNotification'                      => array('inbox'=>false, 'email'=>false),
                'enableNewProjectNotification'                          => array('inbox'=>false, 'email'=>false),
                'enableProjectTaskAddedNotification'                    => array('inbox'=>false, 'email'=>false),
                'enableProjectTaskNewCommentNotification'               => array('inbox'=>false, 'email'=>false),
                'enableProjectTaskStatusChangeNotification'             => array('inbox'=>false, 'email'=>false),
                'enableArchivedProjectNotification'                     => array('inbox'=>false, 'email'=>false),
                'enableGameRewardRedeemedNotification'                  => array('inbox'=>false, 'email'=>false),
                'enableExportProcessCompletedNotification'              => array('inbox'=>false, 'email'=>false),
                'enableEmailMessageArchivingEmailAddressNotMatchingNotification' 
                                                                        => array('inbox'=>false, 'email'=>false),
                'enableRemoveApiTestEntryScriptFileNotification'        => array('inbox'=>true, 'email'=>false),
                'enableEnableMinifyNotification'                        => array('inbox'=>true, 'email'=>false),
                'enableClearAssetsFolderNotification'                   => array('inbox'=>true, 'email'=>false),
            );
            return $defaultNotificationSettings;
        }
        
        public static function resolveAndGetValue(User $user, $key, $returnBoolean = true)
        {
            assert('$user instanceOf User && $user->id > 0');
            assert('is_string($key)');
            assert('is_bool($returnBoolean)');
            $value = ZurmoConfigurationUtil::getByUserAndModuleName($user, 'ZurmoModule', $key);
            return ($returnBoolean)? (bool) $value : $value;
        }

        public static function setValue(User $user, $value, $key, $saveBoolean = true)
        {
            assert('is_bool($saveBoolean)');
            assert('is_string($key)');
            $value = ($saveBoolean)? (bool) $value : $value;
            ZurmoConfigurationUtil::setByUserAndModuleName($user, 'ZurmoModule', $key, $value);
        }
        
        /**
         * Set notifications settings to be all disabled
         *
         * @param User $user
         */
        public static function setNotificationSettingsAllDisabledForUser($user)
        {
            $notificationSettingsNames = static::getAllNotificationSettingNames();
            $defaultNotificationSettings = array();
            foreach($notificationSettingsNames as $settingName)
            {
                $defaultNotificationSettings[$settingName] = array('inbox'=>false, 'email'=>false);
            }
            static::setValue($user, $defaultNotificationSettings, 'inboxAndEmailNotificationSettings', false);
        }
    }
?>