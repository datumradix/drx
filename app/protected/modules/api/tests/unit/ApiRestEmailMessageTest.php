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
    * Test EmailMessage related API functions.
    */
    class ApiRestEmailMessageTest extends ApiRestTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            $super = User::getByUsername('super');
            $super->primaryEmail->emailAddress = 'senderTest@example.com';
            $super->save();
        }
        
        public function testCreateEmailMessage()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            // Test with at least one existing recipient
            $data['subject']                = 'Test 1 Subject';
            $data['textContent']            = 'Test 1 Text Content';
            $data['htmlContent']            = 'Test 1 Html Content';
            $data['sentFrom']['email']      = 'senderTest@example.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name' => 'TO1', 'email' => 'to1@example.com'),
                    array('name' => 'TO2', 'email' => 'to2@example.com')
                ),
                'cc' => array(
                    array('name' => 'CC1', 'email' => 'cc1@example.com'),
                    array('name' => 'CC2', 'email' => 'cc2@example.com')
                )
            );
            
            $contact1 = ContactTestHelper::createContactByNameForOwner('TestContact1', $super);
            $contact1->primaryEmail->emailAddress = 'to1@example.com';
            $contact1->save();
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertArrayHasKey('id', $response['data']);
            $emailMessageId     = $response['data']['id'];
            $emailMessage = EmailMessage::getById($emailMessageId);
            
            $this->assertEquals('Test 1 Subject', $emailMessage->subject);
            $this->assertEquals('Test 1 Text Content', $emailMessage->content->textContent);
            $this->assertEquals('Test 1 Html Content', $emailMessage->content->htmlContent);
            $this->assertEquals('senderTest@example.com', $emailMessage->sender->fromAddress);
            $this->assertEquals(EmailFolder::TYPE_ARCHIVED, $emailMessage->folder->type);
            $this->assertEquals(4, count($emailMessage->recipients));
            $this->assertEquals($data['recipients']['to'][0]['email'], $emailMessage->recipients[0]->toAddress);
            $this->assertEquals($data['recipients']['to'][0]['name'], $emailMessage->recipients[0]->toName);
            $this->assertEquals(EmailMessageRecipient::TYPE_TO, $emailMessage->recipients[0]->type);
            $this->assertEquals($data['recipients']['to'][1]['email'], $emailMessage->recipients[1]->toAddress);
            $this->assertEquals($data['recipients']['to'][1]['name'], $emailMessage->recipients[1]->toName);
            $this->assertEquals(EmailMessageRecipient::TYPE_TO, $emailMessage->recipients[1]->type);
            $this->assertEquals($data['recipients']['cc'][0]['email'], $emailMessage->recipients[2]->toAddress);
            $this->assertEquals($data['recipients']['cc'][0]['name'], $emailMessage->recipients[2]->toName);
            $this->assertEquals(EmailMessageRecipient::TYPE_CC, $emailMessage->recipients[2]->type);
            $this->assertEquals($data['recipients']['cc'][1]['email'], $emailMessage->recipients[3]->toAddress);
            $this->assertEquals($data['recipients']['cc'][1]['name'], $emailMessage->recipients[3]->toName);
            $this->assertEquals(EmailMessageRecipient::TYPE_CC, $emailMessage->recipients[3]->type);

            // Test without existing recipient
            $data['subject']                = 'Test 2 Subject';
            $data['textContent']            = 'Test 2 Text Content';
            $data['htmlContent']            = 'Test 2 Html Content';
            $data['sentFrom']['email']      = 'senderTest@example.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO11','email'=>'to11@example.com'),
                    array('name'=>'TO21','email'=>'to21@example.com')
                ),
            );
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertArrayHasKey('id', $response['data']);
            $emailMessageId     = $response['data']['id'];
            $emailMessage = EmailMessage::getById($emailMessageId);
            
            $this->assertEquals('Test 2 Subject', $emailMessage->subject);
            $this->assertEquals('Test 2 Text Content', $emailMessage->content->textContent);
            $this->assertEquals('Test 2 Html Content', $emailMessage->content->htmlContent);
            $this->assertEquals('senderTest@example.com', $emailMessage->sender->fromAddress);
            $this->assertEquals(EmailFolder::TYPE_ARCHIVED_UNMATCHED, $emailMessage->folder->type);
            $this->assertEquals(2, count($emailMessage->recipients));
        }
        
        public function testCreateEmailMessageWithoutRecipients()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data['subject']                = 'Test 1 Subject';
            $data['textContent']            = 'Test 1 Text Content';
            $data['htmlContent']            = 'Test 1 Html Content';
            $data['sentFrom']['email']      = 'senderTest@example.com';
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals('No recipients found.', $response['message']);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
        }
        
        public function testCreateEmailMessageWithoutSender()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data['subject']        = 'Test 1 Subject';
            $data['textContent']    = 'Test 1 Text Content';
            $data['htmlContent']    = 'Test 1 Html Content';
            $data['recipients']     = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@example.com'),
                    array('name'=>'TO2','email'=>'to2@example.com')
                ),
            );
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals('Sender not found.', $response['message']);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
        }
        
        public function testCreateEmailMessageWithoutBody()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data['subject']                = 'Test 1 Subject';
            $data['sentFrom']['email']      = 'senderTest@example.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@example.com'),
                    array('name'=>'TO2','email'=>'to2@example.com')
                ),
            );
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('No email content found.', $response['message']);
        }
        
        public function testCreateEmailMessageWithSpecificOwner()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $billy  = User::getByUsername('billy');
            
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data['subject']                = 'Test 1 Subject';
            $data['textContent']            = 'Test 1 Text Content';
            $data['htmlContent']            = 'Test 1 Html Content';
            $data['sentFrom']['email']      = 'senderTest@example.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@example.com'),
                    array('name'=>'TO2','email'=>'to2@example.com')
                ),
            );
            $data['owner']['id']    = $billy->id;
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('id', $response['data']);
            $emailMessageId     = $response['data']['id'];
            $emailMessage = EmailMessage::getById($emailMessageId);
            
            $this->assertEquals('Test 1 Subject', $emailMessage->subject);
            $this->assertEquals('Test 1 Text Content', $emailMessage->content->textContent);
            $this->assertEquals('Test 1 Html Content', $emailMessage->content->htmlContent);
            $this->assertEquals('senderTest@example.com', $emailMessage->sender->fromAddress);
            $this->assertEquals($billy->id, $emailMessage->owner->id);
            $this->assertEquals(2, count($emailMessage->recipients));
        }
        
        public function testCreateEmailMessageWithSpecificSentDateTime()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data['subject']                = 'Test 1 Subject';
            $data['textContent']            = 'Test 1 Text Content';
            $data['htmlContent']            = 'Test 1 Html Content';
            $data['sentDateTime']           = '2015-01-01 00:00:01';
            $data['sentFrom']['email']      = 'senderTest@example.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@example.com'),
                    array('name'=>'TO2','email'=>'to2@example.com')
                ),
            );
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('id', $response['data']);
            $emailMessageId     = $response['data']['id'];
            $emailMessage = EmailMessage::getById($emailMessageId);
            
            $this->assertEquals('Test 1 Subject', $emailMessage->subject);
            $this->assertEquals('Test 1 Text Content', $emailMessage->content->textContent);
            $this->assertEquals('Test 1 Html Content', $emailMessage->content->htmlContent);
            $this->assertEquals('2015-01-01 00:00:01', $emailMessage->sentDateTime);
            $this->assertEquals('senderTest@example.com', $emailMessage->sender->fromAddress);
            
            //Test with invalid sentDateTime
            $data['subject']                = 'Test 1 Subject';
            $data['textContent']            = 'Test 1 Text Content';
            $data['htmlContent']            = 'Test 1 Html Content';
            $data['sentDateTime']           = '2015-01-01 00:0';//invalid DateTime
            $data['sentFrom']['email']      = 'senderTest@example.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@example.com'),
                    array('name'=>'TO2','email'=>'to2@example.com')
                ),
            );
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('Model was not created.', $response['message']);
            $this->assertArrayHasKey('sentDateTime', $response['errors']);
            $this->assertEquals('Sent Date Time must be datetime.', $response['errors']['sentDateTime'][0]);
        }
        
        public function testCreateEmailMessageWithBinaryAttachments()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            
            $authenticationData = $this->login();
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );

            $data['subject']                = 'Test 1 Subject';
            $data['textContent']            = 'Test 1 Text Content';
            $data['htmlContent']            = 'Test 1 Html Content';
            $data['sentFrom']['email']      = 'senderTest@example.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@example.com'),
                    array('name'=>'TO2','email'=>'to2@example.com')
                ),
            );
            $pathToFiles = Yii::getPathOfAlias('application.modules.api.tests.unit.files');
            $filePath_1    = $pathToFiles . DIRECTORY_SEPARATOR . 'table.csv';
            $filePath_2    = $pathToFiles . DIRECTORY_SEPARATOR . 'image.png';
            $filePath_3    = $pathToFiles . DIRECTORY_SEPARATOR . 'text.txt';
            $filePath_4    = $pathToFiles . DIRECTORY_SEPARATOR . 'text.abc';
            $data['attachments']             = array(
                array('fileName'=>'table.csv','fileData'=>file_get_contents($filePath_1)),
                array('fileName'=>'image.png','fileData'=>file_get_contents($filePath_2)),
                array('fileName'=>'text.txt','fileData'=>file_get_contents($filePath_3)),
                array('fileName'=>'text.abc','fileData'=>file_get_contents($filePath_4)), //extension not allowed
            );
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('id', $response['data']);
            $emailMessageId     = $response['data']['id'];
            $emailMessage = EmailMessage::getById($emailMessageId);
            
            $this->assertEquals('Test 1 Subject', $emailMessage->subject);
            $this->assertEquals('Test 1 Text Content', $emailMessage->content->textContent);
            $this->assertEquals('Test 1 Html Content', $emailMessage->content->htmlContent);
            $this->assertEquals('senderTest@example.com', $emailMessage->sender->fromAddress);
            $this->assertEquals(3, count($emailMessage->files));
            $this->assertEquals($data['attachments'][0]['fileName'], $emailMessage->files[0]->name);
            $this->assertEquals(filesize($filePath_1), $emailMessage->files[0]->size);
            $this->assertEquals(md5_file($filePath_1), md5($emailMessage->files[0]->fileContent->content));
            $this->assertEquals($data['attachments'][1]['fileName'], $emailMessage->files[1]->name);
            $this->assertEquals(filesize($filePath_2), $emailMessage->files[1]->size);
            $this->assertEquals(md5_file($filePath_2), md5($emailMessage->files[1]->fileContent->content));
            $this->assertEquals($data['attachments'][2]['fileName'], $emailMessage->files[2]->name);
            $this->assertEquals(filesize($filePath_3), $emailMessage->files[2]->size);
            $this->assertEquals(md5_file($filePath_3), md5($emailMessage->files[2]->fileContent->content));
        }
        
        protected function getApiControllerClassName()
        {
            Yii::import('application.modules.emailMessages.controllers.EmailMessageApiController', true);
            return 'EmailMessagesEmailMessageApiController';
        }

        protected function getModuleBaseApiUrl()
        {
            return 'emailMessages/emailMessage/api/';
        }
    }
?>