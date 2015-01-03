<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2015 Zurmo Inc.
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
     * "Copyright Zurmo Inc. 2015. All rights reserved".
     ********************************************************************************/

    /**
     * Model for user's sendgrid email accounts.
     */
    class SendGridEmailAccount extends Item
    {
        const DEFAULT_NAME    = 'Default';

        public function __toString()
        {
            if (trim($this->name) == '')
            {
                return Zurmo::t('Core', '(Unnamed)');
            }
            return $this->name;
        }

        /**
         * @return string
         */
        public static function getModuleClassName()
        {
            return 'SendGridModule';
        }

        /**
         * @param User $user
         * @param mixed $name null or String representing the email account name
         */
        public static function getByUserAndName(User $user, $name = null)
        {
            if ($name == null)
            {
                $name = self::DEFAULT_NAME;
            }
            else
            {
                //For now Zurmo does not support multiple email accounts
                throw new NotSupportedException();
            }
            assert('is_string($name)');
            $bean = ZurmoRedBean::findOne(SendGridEmailAccount::getTableName(),
                               "_user_id = ? AND name = ?", array($user->id, $name));
            assert('$bean === false || $bean instanceof RedBean_OODBBean');
            if ($bean === false)
            {
                throw new NotFoundException();
            }
            else
            {
                $emailAccount = self::makeModel($bean);
            }
            return $emailAccount;
        }

        /**
         * Attempt to get the email account for a given user. If it does not exist, make a default SendGridEmailAccount
         * and return it.
         * @param User $user
         * @param mixed $name null or String representing the email account name
         * @param bool $decrypt
         * @return SendGridEmailAccount
         */
        public static function resolveAndGetByUserAndName(User $user, $name = null, $decrypt = true)
        {
            try
            {
                $emailAccount = static::getByUserAndName($user, $name);
                if($decrypt === true)
                {
                    $emailAccount->apiPassword = ZurmoPasswordSecurityUtil::decrypt($emailAccount->apiPassword);
                }
            }
            catch (NotFoundException $e)
            {
                $emailAccount                    = new SendGridEmailAccount();
                $emailAccount->user              = $user;
                $emailAccount->name              = self::DEFAULT_NAME;
                $emailAccount->fromName          = $user->getFullName();
                if ($user->primaryEmail->id > 0 && $user->primaryEmail->emailAddress != null)
                {
                    $emailAccount->fromAddress       = $user->primaryEmail->emailAddress;
                }
            }
            return $emailAccount;
        }

        /**
         * @return array
         */
        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'name',
                    'fromAddress',
                    'fromName',
                    'replyToAddress',
                    'apiUsername',
                    'apiPassword',
                    'eventWebhookUrl',
                    'eventWebhookFilePath'
                ),
                'relations' => array(
                    'messages' => array(static::HAS_MANY, 'EmailMessage', static::NOT_OWNED,
                                            static::LINK_TYPE_SPECIFIC, 'sendgridAccount'),
                    'user'     => array(static::HAS_ONE,  'User'),
                ),
                'rules'     => array(
                                  array('apiUsername',               'required'),
                                  array('apiPassword',               'required'),
                                  array('fromName',                  'required'),
                                  array('fromAddress',               'required'),
                                  array('eventWebhookUrl',           'required'),
                                  array('eventWebhookFilePath',      'required'),
                                  array('name',                      'type',      'type' => 'string'),
                                  array('fromName',                  'type',      'type' => 'string'),
                                  array('apiUsername',               'type',      'type' => 'string'),
                                  array('apiPassword',               'type',      'type' => 'string'),
                                  array('fromName',                  'length',    'max' => 64),
                                  array('apiUsername',               'length',    'max' => 64),
                                  array('apiPassword',               'length',    'max' => 128),
                                  array('fromAddress',               'email'),
                                  array('replyToAddress',            'email'),
                                  array('eventWebhookUrl',           'url'),
                                  array('eventWebhookFilePath',      'url'),
                )
            );
            return $metadata;
        }

        /**
         * @return boolean
         */
        public static function isTypeDeletable()
        {
            return true;
        }

        /**
         * Returns the display name for the model class.
         * @param null | string $language
         * @return dynamic label name based on module.
         */
        protected static function getLabel($language = null)
        {
            return Zurmo::t('SendGridModule', 'SendGrid Email Account', array(), null, $language);
        }

        /**
         * Returns the display name for plural of the model class.
         * @param null | string $language
         * @return dynamic label name based on module.
         */
        protected static function getPluralLabel($language = null)
        {
            return Zurmo::t('SendGridModule', 'SendGrid Email Accounts', array(), null, $language);
        }

        /**
         * @return array
         */
        protected static function translatedAttributeLabels($language)
        {
            return array_merge(parent::translatedAttributeLabels($language),
                array(
                    'fromAddress'               => Zurmo::t('SendGridModule', 'From Address',                  array(), null, $language),
                    'fromName'                  => Zurmo::t('SendGridModule', 'From Name',                     array(), null, $language),
                    'messages'                  => Zurmo::t('Core',                'Messages',                      array(), null, $language),
                    'name'                      => Zurmo::t('Core',                'Name',                          array(), null, $language),
                    'apiPassword'               => Zurmo::t('SendGridModule', 'Api Password',             array(), null, $language),
                    'apiUsername'               => Zurmo::t('SendGridModule', 'Api Username',             array(), null, $language),
                    'replyToAddress'            => Zurmo::t('SendGridModule', 'Reply To Address',              array(), null, $language),
                    'user'                      => Zurmo::t('UsersModule',         'User',                          array(), null, $language),
                    'eventWebhookUrl'           => Zurmo::t('SendGridModule', 'Event data log file url<br/><small>(e.g. http://xyz.com/dump.log. <br/>Name of the log file should be what is given in webhook file below.)</small>',             array(), null, $language),
                    'eventWebhookFilePath'      => Zurmo::t('SendGridModule', 'Event webhook file path<br/><small>(e.g. http://xyz.com/testwebhook.php)</small>',       array(), null, $language),
                )
            );
        }

        /**
         * Encrypt password beforeSave
         * @return void
         */
        public function afterValidate()
        {
            parent::afterValidate();
            if ($this->apiPassword !== null && $this->apiPassword !== '')
            {
                $this->apiPassword = ZurmoPasswordSecurityUtil::encrypt($this->apiPassword);
             }
        }
    }
?>