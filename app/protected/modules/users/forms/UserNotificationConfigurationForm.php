<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2013 Zurmo Inc.
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
     * "Copyright Zurmo Inc. 2013. All rights reserved".
     ********************************************************************************/

    /**
     * Form to all editing and viewing of a user's configuration notification values in the user interface.
     */
    class UserNotificationConfigurationForm extends CFormModel
    {
        /**
         * Is set in order to properly route action elements in view.
         */
        private $user;

        public $inboxAndEmailNotificationSettings;

        public function __get($name)
        {
            try
            {
                parent::__get($name);
            }
            catch (CException $exception)
            {
                list($settingName, $type) = UserNotificationUtil::getSettingNameAndTypeBySuffixedConfigurationAttribute($name);
                if (isset($this->inboxAndEmailNotificationSettings[$settingName][$type]))
                {
                    return $this->inboxAndEmailNotificationSettings[$settingName][$type];
                }
                return null;
            }
        }

        public function __set($name, $value)
        {
            if (property_exists($this, $name))
            {
                return $this->$name = $value;
            }
            else
            {
                list($settingName, $type) = UserNotificationUtil::getSettingNameAndTypeBySuffixedConfigurationAttribute($name);
                return $this->inboxAndEmailNotificationSettings[$settingName][$type] = $value;
            }
        }

        public function attributeNames()
        {
            return array_merge(parent::attributeNames(),  UserNotificationUtil::getAllNotificationSettingAttributes());
        }

        public function __construct($user)
        {
            assert('$user instanceof User');
            assert('is_int($user->id) && $user->id > 0');
            $this->user = $user;
        }

        public function getUser()
        {
            return $this->user;
        }

        /**
         * When getId is called, it is looking for the user model id for the user
         * who's configuration values are being edited.
         */
        public function getId()
        {
            return $this->user->id;
        }

        public function rules()
        {
            return array(
                array('data', 'safe'),
            );
        }

        public function attributeLabels()
        {
            $labels = array();
            foreach ($this->attributeNames() as $name)
            {
                list($settingName, $type) = UserNotificationUtil::getSettingNameAndTypeBySuffixedConfigurationAttribute($name);
                $notificationRulesClassName = str_replace('enable', '', $settingName) . 'Rules';
                if (@class_exists($notificationRulesClassName))
                {
                    $rule = NotificationRulesFactory::createNotificationRulesByType(str_replace('NotificationRules', '', $notificationRulesClassName));
                    $labels[$settingName] = $rule->getDisplayName();
                }
            }
            return $labels;
        }
    }
?>