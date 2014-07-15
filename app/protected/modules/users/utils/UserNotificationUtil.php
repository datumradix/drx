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

        public static function getAllNotificationSettingAttributes()
        {
            $defaultNotificationSettings = array_keys(static::getNotificationSettingsDefaultValues());
            $notificationSettingNames = array();
            foreach ($defaultNotificationSettings as $defaultNotificationSetting)
            {
                $notificationSettingNames[] = $defaultNotificationSetting . 'Inbox';
                $notificationSettingNames[] = $defaultNotificationSetting . 'Email';
            }
            return $notificationSettingNames;
        }

        public static function getNotificationSettingsDefaultValues()
        {
            $defaultNotificationSettings = array();
            $modules = Module::getModuleObjects();
            foreach ($modules as $module)
            {
                $rulesClassNames = $module::getAllClassNamesByPathFolder('rules');
                foreach ($rulesClassNames as $ruleClassName)
                {
                    $classToEvaluate     = new ReflectionClass($ruleClassName);
                    if (is_subclass_of($ruleClassName, 'NotificationRules') && !$classToEvaluate->isAbstract())
                    {
                        $rule = new $ruleClassName();
                        if ($rule->canBeConfiguredByUser())
                        {
                            $defaultValues = array('inbox' => $rule->getDefaultValue('inbox'), 'email' => $rule->getDefaultValue('email'));
                            $defaultNotificationSettings[static::getConfigurationAttributeByNotificationType($rule->getType())] = $defaultValues;
                        }
                    }
                }
            }
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
            $notificationSettingsAttributes = UserNotificationUtil::getAllNotificationSettingAttributes();
            $defaultNotificationSettings = array();
            foreach($notificationSettingsAttributes as $attribute)
            {
                list($settingName, $type) = UserNotificationUtil::getSettingNameAndTypeBySuffixedConfigurationAttribute($attribute);
                $defaultNotificationSettings[$settingName][$type] = false;
            }
            static::setValue($user, $defaultNotificationSettings, 'inboxAndEmailNotificationSettings', false);
        }

        /**
         * Based on the current user, return the NotificationRules types and their display labels.
         * Only include notification rules that the user has a right to access its corresponding module.
         * @return array of notification rules types and display labels.
         */
        public static function getNotificationRulesTypesForCurrentUserByModule()
        {
            $notificationRulesTypes = array();
            $modules = Module::getModuleObjects();
            foreach ($modules as $module)
            {
                $rulesClassNames = $module::getAllClassNamesByPathFolder('rules');
                foreach ($rulesClassNames as $ruleClassName)
                {
                    $classToEvaluate     = new ReflectionClass($ruleClassName);
                    if (is_subclass_of($ruleClassName, 'NotificationRules') && !$classToEvaluate->isAbstract())
                    {
                        $rule = new $ruleClassName();
                        $addToArray       = true;
                        try
                        {
                            $moduleClassNames = $rule->getModuleClassNames();
                            foreach ($moduleClassNames as $moduleClassNameToCheckAccess)
                            {
                                if (!RightsUtil::canUserAccessModule($moduleClassNameToCheckAccess,
                                        Yii::app()->user->userModel) ||
                                    !RightsUtil::
                                        doesUserHaveAllowByRightName($moduleClassNameToCheckAccess,
                                            $moduleClassNameToCheckAccess::getCreateRight(),
                                            Yii::app()->user->userModel) ||
                                    ($rule->isSuperAdministratorNotification() && !Yii::app()->user->userModel->isSuperAdministrator())
                                    || !$rule->canBeConfiguredByUser())
                                {
                                    $addToArray = false;
                                }
                            }
                        }
                        catch (NotImplementedException $exception)
                        {
                            $addToArray = false;
                        }
                        if ($addToArray)
                        {
                            $label = $module::getModuleLabelByTypeAndLanguage('Plural');
                            $notificationRulesTypes[$label][$rule->getType()] = $rule->getDisplayName();
                        }
                    }
                }
            }
            return $notificationRulesTypes;
        }

        /**
         * The element type to be used on the @see UserNotificationConfigurationEditView
         * @param $type
         * @return string
         */
        public static function getConfigurationElementTypeByNotificationType($type)
        {
            assert('is_string($type)');
            return 'BaseNotification';
        }

        /**
         * The attribute used on the @see UserNotificationConfigurationEditView
         * @param $type
         * @return string
         */
        public static function getConfigurationAttributeByNotificationType($type)
        {
            assert('is_string($type)');
            return 'enable' . $type . 'Notification';
        }

        /**
         * The tooltip id used on @see UserNotificationConfigurationEditView
         * @param $attribute
         * @return string
         */
        public static function getTooltipIdByAttribute($attribute)
        {
            assert('is_string($attribute)');
            $notificationType = preg_replace("/enable(.*)Notification/", "$1", $attribute);
            $rule = NotificationRulesFactory::createNotificationRulesByType($notificationType);
            return $rule->getTooltipId();
        }

        /**
         * The tooltip title used on @see UserNotificationConfigurationEditView
         * @param $attribute
         * @return string
         */
        public static function getTooltipTitleByAttribute($attribute)
        {
            assert('is_string($attribute)');
            $notificationType = preg_replace("/enable(.*)Notification/", "$1", $attribute);
            $rule = NotificationRulesFactory::createNotificationRulesByType($notificationType);
            return $rule->getTooltipTitle();
        }

        public static function getSettingNameAndTypeBySuffixedConfigurationAttribute($suffixedConfigurationAttribute)
        {
            assert('is_string($suffixedConfigurationAttribute)');
            $matches = array();
            preg_match("/(.*)(Email|Inbox)$/", $suffixedConfigurationAttribute, $matches);
            if (count($matches) == 3)
            {
                return array($matches[1], strtolower($matches[2]));
            }
            else
            {
                return $suffixedConfigurationAttribute;
            }
        }
    }
?>