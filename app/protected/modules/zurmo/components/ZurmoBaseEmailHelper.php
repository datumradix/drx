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
    /**
     * Base email helper class. The other email helper class should extend from it.
     */
    abstract class ZurmoBaseEmailHelper extends CApplicationComponent
    {
        /*
         * Resolving Default Email Addess For Email Testing
         * @return string
         */
        public static function resolveDefaultEmailAddress($defaultEmailAddress)
        {
            return $defaultEmailAddress . '@' . StringUtil::resolveCustomizedLabel() . 'alerts.com';
        }

        /**
         * Resolve and get default from address.
         * @return string
         */
        public static function resolveAndGetDefaultFromAddress()
        {
            $defaultFromAddress = ZurmoConfigurationUtil::getByModuleName('ZurmoModule', 'defaultFromAddress');
            if ($defaultFromAddress == null)
            {
                $defaultFromAddress = static::resolveDefaultEmailAddress('notification');
                static::setDefaultFromAddress($defaultFromAddress);
            }
            return $defaultFromAddress;
        }

        /**
         * Sets default from address.
         * @param string $defaultFromAddress
         */
        public static function setDefaultFromAddress($defaultFromAddress)
        {
            assert('is_string($defaultFromAddress)');
            ZurmoConfigurationUtil::setByModuleName('ZurmoModule', 'defaultFromAddress', $defaultFromAddress);
        }

        /**
         * Resolve and get default test to address.
         * @return string
         */
        public static function resolveAndGetDefaultTestToAddress()
        {
            $defaultTestToAddress = ZurmoConfigurationUtil::getByModuleName('ZurmoModule', 'defaultTestToAddress');
            if ($defaultTestToAddress == null)
            {
                $defaultTestToAddress = static::resolveDefaultEmailAddress('testJobEmail');
                static::setDefaultTestToAddress($defaultTestToAddress);
            }
            return $defaultTestToAddress;
        }

        /**
         * Sets default test to address.
         * @param string $defaultTestToAddress
         */
        public static function setDefaultTestToAddress($defaultTestToAddress)
        {
            assert('is_string($defaultTestToAddress)');
            ZurmoConfigurationUtil::setByModuleName('ZurmoModule', 'defaultTestToAddress', $defaultTestToAddress);
        }

        /**
         * Process message as failure.
         * @param EmailMessage $emailMessage
         * @param bool $useSQL
         */
        public function processMessageAsFailure(EmailMessage $emailMessage, $useSQL = false)
        {
            throw new NotImplementedException();
        }

        /**
         * Given a user, attempt to get the user's email address, but if it is not available, then return the default
         * address.  @see EmailHelper::defaultFromAddress
         * @param User $user
         * @return string
         */
        public function resolveFromAddressByUser(User $user)
        {
            assert('$user->id >0');
            if ($user->primaryEmail->emailAddress == null)
            {
                return $this->defaultFromAddress;
            }
            return $user->primaryEmail->emailAddress;
        }

        /**
         * Call this method to process all email Messages in the queue. This is typically called by a scheduled job
         * or cron.  This will process all emails in a TYPE_OUTBOX folder or TYPE_OUTBOX_ERROR folder. If the message
         * has already been sent 3 times then it will be moved to a failure folder.
         * @param bool|null $count
         * @return bool number of queued messages to be sent
         */
        public function sendQueued($count = null)
        {
            throw new NotImplementedException();
        }

        /**
         * Send an email message. This will queue up the email to be sent by the queue sending process. If you want to
         * send immediately, consider using @sendImmediately
         * @param EmailMessage $emailMessage
         * @param bool $useSQL
         * @param bool $validate
         * @return bool|void
         * @throws FailedToSaveModelException
         * @throws NotFoundException
         * @throws NotSupportedException
         */
        public function send(EmailMessage & $emailMessage, $useSQL = false, $validate = true)
        {
            throw new NotImplementedException();
        }
    }
?>