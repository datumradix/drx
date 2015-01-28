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
            $super->primaryEmail->emailAddress = 'senderTest@zurmo.com';
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
            $data['sentFrom']['email']      = 'senderTest@zurmo.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@zurmo.com'),
                    array('name'=>'TO2','email'=>'to2@zurmo.com')
                ),
            );
            
            $contact1 = ContactTestHelper::createContactByNameForOwner('TestContact1', $super);
            $contact1->primaryEmail->emailAddress = 'to1@zurmo.com';
            $contact1->save();
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertArrayHasKey('id', $response['data']);
            $emailMessageId     = $response['data']['id'];
            $emailMessage = EmailMessage::getById($emailMessageId);
            
            $this->assertEquals('Test 1 Subject', $emailMessage->subject);
            $this->assertEquals('Test 1 Text Content', $emailMessage->content->textContent);
            $this->assertEquals('Test 1 Html Content', $emailMessage->content->htmlContent);
            $this->assertEquals('senderTest@zurmo.com', $emailMessage->sender->fromAddress);
            $this->assertEquals(EmailFolder::TYPE_ARCHIVED, $emailMessage->folder->type);
            $this->assertEquals(2, count($emailMessage->recipients));
            // Test without existing recipient
            $data['subject']                = 'Test 2 Subject';
            $data['textContent']            = 'Test 2 Text Content';
            $data['htmlContent']            = 'Test 2 Html Content';
            $data['sentFrom']['email']      = 'senderTest@zurmo.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO11','email'=>'to11@zurmo.com'),
                    array('name'=>'TO21','email'=>'to21@zurmo.com')
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
            $this->assertEquals('senderTest@zurmo.com', $emailMessage->sender->fromAddress);
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
            $data['sentFrom']['email']      = 'senderTest@zurmo.com';
            
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
                    array('name'=>'TO1','email'=>'to1@zurmo.com'),
                    array('name'=>'TO2','email'=>'to2@zurmo.com')
                ),
            );
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals('User sender not found.', $response['message']);
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
            $data['sentFrom']['email']      = 'senderTest@zurmo.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@zurmo.com'),
                    array('name'=>'TO2','email'=>'to2@zurmo.com')
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
            $data['sentFrom']['email']      = 'senderTest@zurmo.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@zurmo.com'),
                    array('name'=>'TO2','email'=>'to2@zurmo.com')
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
            $this->assertEquals('senderTest@zurmo.com', $emailMessage->sender->fromAddress);
            $this->assertEquals($billy->id, $emailMessage->owner->id);
            $this->assertEquals(2, count($emailMessage->recipients));
        }
        
        public function testCreateEmailMessageWithAttachments()
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
            $data['sentFrom']['email']      = 'senderTest@zurmo.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@zurmo.com'),
                    array('name'=>'TO2','email'=>'to2@zurmo.com')
                ),
            );
            $data['attachments']             = array(
                array('fileName'=>'Attachment1.txt','fileData'=>"aaa \n 222 \n oeoe"),
                array('fileName'=>'Attachment2.txt','fileData'=>"Test content file attachment 2"),
                array('fileName'=>'Attachment3.txt','fileData'=>"BBB \n AAA"),
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
            $this->assertEquals('senderTest@zurmo.com', $emailMessage->sender->fromAddress);
            $this->assertEquals(3, count($emailMessage->files));
        }
        
        public function testCreateEmailMessageWithNotAllowedAttachmentExtensions()
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
            $data['sentFrom']['email']      = 'senderTest@zurmo.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@zurmo.com'),
                    array('name'=>'TO2','email'=>'to2@zurmo.com')
                ),
            );
            $data['attachments']             = array(
                array('fileName'=>'Attachment1.txt','fileData'=>"aaa \n 222 \n oeoe"),
                array('fileName'=>'Attachment2.txtaa','fileData'=>"Test content file attachment 2"),
                array('fileName'=>'Attachment3.txtbb','fileData'=>"BBB \n AAA"),
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
            $this->assertEquals('senderTest@zurmo.com', $emailMessage->sender->fromAddress);
            $this->assertEquals(1, count($emailMessage->files));
        }
        
        public function testGetEmailMessage()
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
            $data['sentFrom']['email']      = 'senderTest@zurmo.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@zurmo.com'),
                    array('name'=>'TO2','email'=>'to2@zurmo.com')
                ),
            );
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('id', $response['data']);
            $emailMessageId     = $response['data']['id'];
            $emailMessage = EmailMessage::getById($emailMessageId);
            
            $response = $this->createApiCallWithRelativeUrl('read/' . $emailMessageId, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('data', $response);
        }
        
        public function testDeleteEmailMessage()
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
            $data['sentFrom']['email']      = 'senderTest@zurmo.com';
            $data['recipients']             = array(
                'to' => array(
                    array('name'=>'TO1','email'=>'to1@zurmo.com'),
                    array('name'=>'TO2','email'=>'to2@zurmo.com')
                ),
            );
            
            $response = $this->createApiCallWithRelativeUrl('create/', 'POST', $headers, array('data' => $data));
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('id', $response['data']);
            $emailMessageId     = $response['data']['id'];
            $emailMessage = EmailMessage::getById($emailMessageId);
            
            $response = $this->createApiCallWithRelativeUrl('read/' . $emailMessageId, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            $this->assertArrayHasKey('data', $response);
            
            $response = $this->createApiCallWithRelativeUrl('delete/' . $emailMessageId, 'DELETE', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_SUCCESS, $response['status']);
            
            $response = $this->createApiCallWithRelativeUrl('read/' . $emailMessageId, 'GET', $headers);
            $response = json_decode($response, true);
            $this->assertEquals(ApiResponse::STATUS_FAILURE, $response['status']);
            $this->assertEquals('The ID specified was invalid.', $response['message']);
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