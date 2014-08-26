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
     Yii::import('ext.sendgrid.lib.SendGrid');
     Yii::import('ext.sendgrid.lib.Smtpapi');
     Yii::import('ext.sendgrid.lib.Unirest');
    class SendGridApiTest extends ZurmoBaseTest
    {
        protected static $apiUserName = 'msinghai';
        protected static $apiPassword = 'abc123';

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            $super = SecurityTestHelper::createSuperAdmin();
            Yii::app()->user->userModel = $super;
            SendGrid::register_autoloader();
            Smtpapi::register_autoloader();
        }

        public function testBouncedEmail()
        {
            $user = static::$apiUserName;
            $pass = static::$apiPassword;
            Yii::import('ext.sendgrid.lib.SendGrid.Email');
            Yii::import('ext.sendgrid.lib.Smtpapi.Header');
            $sendgrid = new SendGrid($user, $pass, array("turn_off_ssl_verification" => true));
            $email    = new SendGrid\Email();
            $to       = 'hellorajuj@gmail.com';
            $email->addTo($to)->
                   setFrom('rajusinghai80@gmail.com')->
                   setSubject('[sendgrid-php-example] Owl named %yourname%')->
                   setText('Owl are you doing?')->
                   setHtml('<strong>%how% are you doing?</strong>')->
                   addSubstitution("%yourname%", array("Mr. Owl"))->
                   addSubstitution("%how%", array("Owl"))->
                   addHeader('X-Sent-Using', 'SendGrid-API')->
                   addHeader('X-Transport', 'web');

            $response = $sendgrid->send($email);
            var_dump($response);
        }

        /*public function testGetBouncedEmails()
        {
            $url = 'https://api.sendgrid.com/';
            $user = static::$apiUserName;
            $pass = static::$apiPassword;

            $request =  $url . 'api/bounces.get.json?api_user=' . $user . '&api_key=' . $pass . '&date=1';

            // Generate curl request
            $curl = curl_init($request);
            // Tell curl not to return headers, but do return the response
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

            // obtain response
            if(!$response = curl_exec($curl))
            {
                trigger_error(curl_error($curl));
            }
            curl_close($curl);

            // print everything out
            $data = json_decode($response);
            $this->assertTrue(count($data) > 0);

            $request =  $url . 'api/bounces.get.json?api_user=' . $user . '&api_key=' . $pass . '&date=1&type=hard';

            // Generate curl request
            $curl = curl_init($request);
            // Tell curl not to return headers, but do return the response
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

            // obtain response
            if(!$response = curl_exec($curl))
            {
                trigger_error(curl_error($curl));
            }
            curl_close($curl);

            // print everything out
            $data = json_decode($response);
            $this->assertTrue(count($data) > 0);

            $request =  $url . 'api/bounces.get.json?api_user=' . $user . '&api_key=' . $pass . '&date=1&type=soft';

            // Generate curl request
            $curl = curl_init($request);
            // Tell curl not to return headers, but do return the response
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

            // obtain response
            if(!$response = curl_exec($curl))
            {
                trigger_error(curl_error($curl));
            }
            curl_close($curl);

            // print everything out
            $data = json_decode($response);
            $this->assertTrue(count($data) == 0);
        }*/
    }
?>