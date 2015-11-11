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
    class SendGridLogUtilTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
        }

        public function setUp()
        {
            parent::setUp();
        }

        public function testWriteLog()
        {
            $rawData = '[{"sg_event_id":"8rQZ-LulQcObW_2WyUTLpg","zurmoToken":"' . md5(ZURMO_TOKEN) . '","sg_message_id":"14826db909d.7e66.8eff7e.filter-297.4955.5401C16C1B.0","event":"processed","itemClass":"CampaignItem","email":"abc@yahoo.com","itemId":31,"smtp-id":"<14826db909d.7e66.8eff7e@ismtpd-002.sjc1.sendgrid.net>","timestamp":1409401197,"personId":42}]'; // Not Coding Standard
            SendGridLogUtil::writeLog('test', $rawData);
            $logPath = SendGridLogUtil::getLogFilePath('test');
            $this->assertTrue(file_exists($logPath));
            $testContent = file_get_contents($logPath);
            $this->assertEquals($rawData, $testContent);
            @unlink($logPath);

            //Failure test
            $rawData = '[{"sg_event_id":"8rQZ-LulQcObW_2WyUTLpg","sg_message_id":"14826db909d.7e66.8eff7e.filter-297.4955.5401C16C1B.0","event":"processed","itemClass":"CampaignItem","email":"abc@yahoo.com","itemId":31,"smtp-id":"<14826db909d.7e66.8eff7e@ismtpd-002.sjc1.sendgrid.net>","timestamp":1409401197,"personId":42}]'; // Not Coding Standard
            SendGridLogUtil::writeLog('test', $rawData);
            $logPath = SendGridLogUtil::getLogFilePath('test');
            $this->assertFalse(file_exists($logPath));
        }
    }
?>