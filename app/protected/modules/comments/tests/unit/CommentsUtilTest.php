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

    class CommentsUtilTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            UserTestHelper::createBasicUser('steven');
            UserTestHelper::createBasicUser('jack');
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
        }

        public function setUp()
        {
            parent::setUp();
            Yii::app()->user->userModel = User::getByUsername('super');
        }

        public function testSendNotificationOnCommentCreateOrUpdate()
        {
            $super                      = User::getByUsername('super');
            $steven                     = User::getByUsername('steven');
            $jack                       = User::getByUsername('jack');
            $conversation               = new Conversation();
            $conversation->owner        = Yii::app()->user->userModel;
            $conversation->subject      = 'My test subject2';
            $conversation->description  = 'My test description2';
            $this->assertTrue($conversation->save());
            $comment                    = new Comment();
            $comment->description       = 'This is the 1st test comment';

            //Confirm no email notifications are sitting in the queue
            $this->assertEquals(0, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(0, Yii::app()->emailHelper->getSentCount());
            //Confirm there is no inbox notification
            $this->assertEquals(0, Notification::getCount());

            //No message was sent because Steven and Jack don't have primary email address
            CommentsUtil::sendNotificationOnCommentCreateOrUpdate($conversation, $comment, array($steven, $jack));
            $this->assertEquals(0, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(0, Yii::app()->emailHelper->getSentCount());
            //Two inbox notifications sent
            $this->assertEquals(2, Notification::getCount());

            $super->primaryEmail->emailAddress   = 'super@zurmo.org';
            $steven->primaryEmail->emailAddress  = 'steven@zurmo.org';
            $jack->primaryEmail->emailAddress    = 'jack@zurmo.org';
            $this->assertTrue($super->save());
            $this->assertTrue($steven->save());
            $this->assertTrue($jack->save());

            //Two email message were sent one to Steven and one to Jack
            CommentsUtil::sendNotificationOnCommentCreateOrUpdate($conversation, $comment, array($steven, $jack));
            $this->assertEquals(2, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(0, Yii::app()->emailHelper->getSentCount());
            $emailMessages = EmailMessage::getAll();
            $emailMessage1  = $emailMessages[0];
            $emailMessage2  = $emailMessages[1];
            $this->assertCount(1, $emailMessage1->recipients);
            $this->assertCount(1, $emailMessage2->recipients);
            //Two inbox notifications created
            $this->assertEquals(4, Notification::getCount());

            //One email message was sent to Super but not to Steven
            //One inbox notification to Steven but not to Super
            NotificationTestHelper::setNotificationSettingsForUser($steven, 'ConversationComment', true, false);
            NotificationTestHelper::setNotificationSettingsForUser($super, 'ConversationComment', false, true);
            CommentsUtil::sendNotificationOnCommentCreateOrUpdate($conversation, $comment, array($steven, $super));
            $this->assertEquals(3, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(0, Yii::app()->emailHelper->getSentCount());
            $emailMessages = EmailMessage::getAll();
            $emailMessage  = $emailMessages[2];
            $this->assertEquals(1, count($emailMessage->recipients));
            $this->assertEquals(5, Notification::getCount());
            $notifications = Notification::getAll();
            $notification  = $notifications[4];
            $this->assertEquals(strval($steven), strval($notification->owner));
        }

        public function testGetMentionedUsersForNotification()
        {
            $super                      = User::getByUsername('super');
            $steven                     = User::getByUsername('steven');
            $jack                       = User::getByUsername('jack');

            $comment                    = new Comment();
            $comment->description       = 'Hello steven, How are you?';

            $mentionedUsers = CommentsUtil::getMentionedUsersForNotification($comment);
            $this->assertEmpty($mentionedUsers);

            // Second string([~ste]) whouldn't be replaced, because username need to be full
            $comment->description       = 'Hello [~steven] and [~ste], How are you?';
            $mentionedUsers = CommentsUtil::getMentionedUsersForNotification($comment);
            $this->assertNotEmpty($mentionedUsers);
            $this->assertEquals(1, count($mentionedUsers));
            $this->assertEquals($steven->id, $mentionedUsers[0]->id);

            $comment->description       = 'Hello [~steven] and [~jack] and [~super], How are you?';
            $mentionedUsers = CommentsUtil::getMentionedUsersForNotification($comment);
            $this->assertNotEmpty($mentionedUsers);
            $this->assertEquals(2, count($mentionedUsers));
            $this->assertTrue(in_array($steven->id, array($mentionedUsers[0]->id, $mentionedUsers[1]->id)));
            $this->assertTrue(in_array($jack->id, array($mentionedUsers[0]->id, $mentionedUsers[1]->id)));
        }

        public function testReplaceMentionedUsernamesWithFullNamesAndLinksInComments()
        {
            $super                      = User::getByUsername('super');
            $steven                     = User::getByUsername('steven');
            $jack                       = User::getByUsername('jack');

            $description       = 'Hello steven, How are you?';
            $modifiedDescription = CommentsUtil::replaceMentionedUsernamesWithFullNamesAndLinksInComments($description);
            $this->assertEquals($description, $modifiedDescription);

            $description       = 'Hello [~steven] and [~jack] and [~super] and [~sup], How are you?';
            $modifiedDescription = CommentsUtil::replaceMentionedUsernamesWithFullNamesAndLinksInComments($description);
            $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>"; // Not Coding Standard
            if (preg_match_all("/$regexp/siU", $modifiedDescription, $matches))
            {
                // $matches[2] = array of link addresses
                // $matches[3] = array of link text - including HTML code
                $this->assertEquals(3, count($matches[2]));
                $this->assertEquals(3, count($matches[3]));
                $this->assertTrue(strpos($matches[2][0], 'details?id=' . $steven->id) != null);
                $this->assertEquals(strval($steven), $matches[3][0]);
                $this->assertTrue(strpos($matches[2][1], 'details?id=' . $jack->id) != null);
                $this->assertEquals(strval($jack), $matches[3][1]);
                $this->assertTrue(strpos($matches[2][2], 'details?id=' . $super->id) != null);
                $this->assertEquals(strval($super), $matches[3][2]);
            }
            else
            {
                $this->fail('Usernames not replaced with links in description.');
            }
            // Ensure that existing users are replaced with names and links, while nonexisting are not
            $this->assertTrue(strpos($modifiedDescription, '[~steven]') == null);
            $this->assertTrue(strpos($modifiedDescription, '[~jack]')   == null);
            $this->assertTrue(strpos($modifiedDescription, '[~super]')  == null);
            $this->assertTrue(strpos($modifiedDescription, '[~sup]')    != null);
        }

        public function testHasUserHaveAccessToEditOrDeleteComment()
        {
            $super                      = User::getByUsername('super');
            $steven                     = User::getByUsername('steven');
            $jack                       = User::getByUsername('jack');

            Yii::app()->user->userModel = $super;
            $comment1                  = new Comment();
            $comment1->description     = 'Comment 1';
            $this->assertTrue($comment1->save());
            $this->assertTrue(CommentsUtil::hasUserHaveAccessToEditOrDeleteComment($comment1, $super));
            $this->assertFalse(CommentsUtil::hasUserHaveAccessToEditOrDeleteComment($comment1, $steven));
            $this->assertFalse(CommentsUtil::hasUserHaveAccessToEditOrDeleteComment($comment1, $jack));

            Yii::app()->user->userModel = $steven;
            $comment2                  = new Comment();
            $comment2->description     = 'Comment 2';
            $this->assertTrue($comment2->save());
            $this->assertTrue(CommentsUtil::hasUserHaveAccessToEditOrDeleteComment($comment2, $super));
            $this->assertTrue(CommentsUtil::hasUserHaveAccessToEditOrDeleteComment($comment2, $steven));
            $this->assertFalse(CommentsUtil::hasUserHaveAccessToEditOrDeleteComment($comment2, $jack));
        }
    }
?>