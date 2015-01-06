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

    class SendGridLogUtil
    {
        /**
         * Write log for send
         * @param string $username
         */
        public static function writeLog($username)
        {
            $logFile = static::getLogFilePath($username);
            $fp = fopen($logFile, 'a+');
            chmod($logFile, 0777);
            flock($fp, LOCK_EX);
            fwrite($fp, print_r($HTTP_RAW_POST_DATA, true));
            flock($fp, LOCK_UN);
            fclose($fp);
            echo "ok";
        }

        /**
         * Get log file path.
         * @param string $username
         */
        public static function getLogFilePath($username)
        {
            $logDir = Yii::app()->getRuntimePath() . DIRECTORY_SEPARATOR . 'sendgridLogs';
            if(!file_exists($logDir))
            {
                mkdir($logDir);
            }
            return $logDir . DIRECTORY_SEPARATOR . $username . '-sendgrid.log';
        }
    }
?>