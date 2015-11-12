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

    class NotificationSubscriberUtilTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $user                   = UserTestHelper::createBasicUser('Tilly');
            TaskTestHelper::createTaskByNameForOwner('My Task', $super);
            AccountTestHelper::createAccountByNameForOwner('anAccount', $super);
        }

        public function setUp()
        {
            parent::setUp();
            Yii::app()->user->userModel = User::getByUsername('super');
        }

        /**
         * @covers markUserHasReadLatest
         */
        public function testMarkUserHasReadLatest()
        {
            $super                     = User::getByUsername('super');
            $steven                    = UserTestHelper::createBasicUser('steven');

            $task = new Task();
            $task->name = 'MyTest';
            $nowStamp = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
            $this->assertTrue($task->save());

            $task = Task::getById($task->id);
            $user = Yii::app()->user->userModel;
            $notificationSubscriber = new NotificationSubscriber();
            $notificationSubscriber->person = $steven;
            $notificationSubscriber->hasReadLatest = false;
            $task->notificationSubscribers->add($notificationSubscriber);
            $this->assertTrue($task->save());

            $id = $task->id;
            $task->forget();
            unset($task);

            $task = Task::getById($id);
            $this->assertEquals(0, $task->notificationSubscribers->offsetGet(0)->hasReadLatest);
            $this->assertEquals(0, $task->notificationSubscribers->offsetGet(1)->hasReadLatest);
            //After running for super, nothing will change.
            NotificationSubscriberUtil::markUserHasReadLatest($task, $steven);
            NotificationSubscriberUtil::markUserHasReadLatest($task, $super);
            $id = $task->id;
            $task->forget();
            unset($task);

            $task = Task::getById($id);
            foreach ($task->notificationSubscribers as $position => $subscriber)
            {
                $this->assertEquals(1, $subscriber->hasReadLatest);
            }
        }

        /**
         * @covers getSubscriberData
         * @covers renderSubscriberImageAndLinkContent
         */
        public function testGetSubscriberData()
        {
            $user  = User::getByUsername('steven');

            $tasks  = Task::getByName('MyTest');
            $task   = $tasks[0];

            $content = NotificationSubscriberUtil::getSubscriberData($task);
            $this->assertContains('gravatar', $content);
            $this->assertContains('users/default/details', $content);
        }

        /**
         * @covers getSubscribers
         */
        public function testGetSubscribers()
        {
            $user  = User::getByUsername('steven');

            $tasks  = Task::getByName('MyTest');
            $task   = $tasks[0];

            $subscribers = NotificationSubscriberUtil::getModelSubscribers($task);
            $found = false;
            foreach ($subscribers as $subscriber)
            {
                if ($subscriber->id == $user->id)
                {
                    $found = true;
                }
            }
            $this->assertTrue($found);
        }

        /**
         * @covers resolveSubscribeUrl
         */
        public function testResolveSubscriptionLink()
        {
            $tasks  = Task::getByName('MyTest');
            $task   = $tasks[0];
            $sally  = UserTestHelper::createBasicUser('sally');
            $maggi  = UserTestHelper::createBasicUser('maggi');
            $task->owner = $sally;
            $task->requestedByUser = $maggi;
            $task->save();
            if (NotificationSubscriberUtil::doNotificationSubscribersContainPerson($task, Yii::app()->user->userModel) === false)
            {
                $notificationSubscriber = new NotificationSubscriber();
                $notificationSubscriber->person = Yii::app()->user->userModel;
                $task->notificationSubscribers->add($notificationSubscriber);
                $task->save();
            }
            $link = NotificationSubscriberUtil::getKanbanSubscriptionLink($task, 0);
            $this->assertContains('unsubscribe-task-link', $link);
            $modelDerivationPathToItem = RuntimeUtil::getModelDerivationPathToItem('User');
            foreach ($task->notificationSubscribers as $notificationSubscriber)
            {
                $user = $notificationSubscriber->person->castDown(array($modelDerivationPathToItem));
                if ($user->id == Yii::app()->user->userModel->id)
                {
                    $task->notificationSubscribers->remove($notificationSubscriber);
                }
            }
            $task->save();
            $link = NotificationSubscriberUtil::getKanbanSubscriptionLink($task, 0);
            $this->assertContains('subscribe-task-link', $link);
        }

        /**
         * @covers resolveDetailSubscribeUrl
         */
        public function testResolveDetailSubscriptionLink()
        {
            $tasks  = Task::getByName('MyTest');
            $task   = $tasks[0];
            if (NotificationSubscriberUtil::doNotificationSubscribersContainPerson($task, Yii::app()->user->userModel) === false)
            {
                $notificationSubscriber = new NotificationSubscriber();
                $notificationSubscriber->person = Yii::app()->user->userModel;
                $task->notificationSubscribers->add($notificationSubscriber);
                $task->save();
            }
            $link = NotificationSubscriberUtil::getDetailSubscriptionLink($task, 0);
            $this->assertContains('detail-unsubscribe-task-link', $link);
            $modelDerivationPathToItem = RuntimeUtil::getModelDerivationPathToItem('User');
            foreach ($task->notificationSubscribers as $index => $notificationSubscriber)
            {
                $user = $notificationSubscriber->person->castDown(array($modelDerivationPathToItem));
                if ($user->id == Yii::app()->user->userModel->id)
                {
                    $task->notificationSubscribers->remove($notificationSubscriber);
                }
            }
            $task->save();
            $link = NotificationSubscriberUtil::getDetailSubscriptionLink($task, 0);
            $this->assertContains('detail-subscribe-task-link', $link);
        }

        /**
         * @covers addSubscriber
         */
        public function testAddSubscriberToModel()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $user = User::getByUsername('tilly');
            $task = new Task();
            $task->name = 'MyTest';
            $task->owner = $user;
            $nowStamp = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
            $this->assertTrue($task->save());
            $this->assertEquals($user, $task->owner);

            //There would be two here as default subscribers are added
            $this->assertEquals(2, count($task->notificationSubscribers));
            $user = Yii::app()->user->userModel;
            NotificationSubscriberUtil::addSubscriber($user, $task);
            $task->save();
            $this->assertEquals(2, count($task->notificationSubscribers));
        }

        /**
         * @covers resolveAndRenderTaskCardDetailsSubscribersContent
         */
        public function testResolveAndRenderTaskCardDetailsSubscribersContent()
        {
            $hellodear      = UserTestHelper::createBasicUser('hellodear');
            $task           = new Task();
            $task->name     = 'MyCardTest';
            $task->owner    = $hellodear;
            $this->assertTrue($task->save());

            $task = Task::getById($task->id);
            $user = Yii::app()->user->userModel;
            NotificationSubscriberUtil::addSubscriber($hellodear, $task);
            $this->assertTrue($task->save());
            $content = NotificationSubscriberUtil::resolveAndRenderTaskCardDetailsSubscribersContent($task);
            $this->assertContains('gravatar', $content);
            $this->assertContains('users/default/details', $content);
            $this->assertContains('hellodear', $content);
            $this->assertContains('task-owner', $content);
        }

        /**
         * @covers TasksUtil::processSubscriptionRequest
         */
        public function testProcessSubscriptionRequest()
        {
            $modelDerivationPathToItem = RuntimeUtil::getModelDerivationPathToItem('User');

            $mark                   = UserTestHelper::createBasicUser('mark');
            $jim                    = UserTestHelper::createBasicUser('jim');

            $task = TaskTestHelper::createTaskByNameForOwner('SubTask', Yii::app()->user->userModel);
            $this->assertEquals(1, count($task->notificationSubscribers));
            $subscribedUser = $task->notificationSubscribers[0]->person->castDown(array($modelDerivationPathToItem));
            $this->assertEquals(Yii::app()->user->userModel->id, $subscribedUser->id);

            $task = NotificationSubscriberUtil::processSubscriptionRequest($task, $mark);
            $this->assertEquals(2, count($task->notificationSubscribers));
            $subscribedUser1 = $task->notificationSubscribers[0]->person->castDown(array($modelDerivationPathToItem));
            $subscribedUser2 = $task->notificationSubscribers[1]->person->castDown(array($modelDerivationPathToItem));
            $this->assertTrue(in_array(Yii::app()->user->userModel->id, array($subscribedUser1->id, $subscribedUser2->id)));
            $this->assertTrue(in_array($mark->id, array($subscribedUser1->id, $subscribedUser2->id)));

            $task = NotificationSubscriberUtil::processSubscriptionRequest($task, $jim);
            $this->assertEquals(3, count($task->notificationSubscribers));
            $subscribedUser1 = $task->notificationSubscribers[0]->person->castDown(array($modelDerivationPathToItem));
            $subscribedUser2 = $task->notificationSubscribers[1]->person->castDown(array($modelDerivationPathToItem));
            $subscribedUser3 = $task->notificationSubscribers[2]->person->castDown(array($modelDerivationPathToItem));
            $this->assertTrue(in_array(Yii::app()->user->userModel->id, array($subscribedUser1->id, $subscribedUser2->id, $subscribedUser3->id)));
            $this->assertTrue(in_array($mark->id, array($subscribedUser1->id, $subscribedUser2->id, $subscribedUser3->id)));
            $this->assertTrue(in_array($jim->id, array($subscribedUser1->id, $subscribedUser2->id, $subscribedUser3->id)));
        }

        /**
         * @covers TasksUtil::processUnsubscriptionRequest
         * @depends testProcessSubscriptionRequest
         */
        public function testProcessUnsubscriptionRequest()
        {
            $modelDerivationPathToItem = RuntimeUtil::getModelDerivationPathToItem('User');

            $maria                   = UserTestHelper::createBasicUser('maria');
            $john                    = UserTestHelper::createBasicUser('john');

            $task = TaskTestHelper::createTaskByNameForOwner('SubTask2', Yii::app()->user->userModel);
            $task = NotificationSubscriberUtil::processSubscriptionRequest($task, $maria);
            $task = NotificationSubscriberUtil::processSubscriptionRequest($task, $john);
            // Just check number of subscribed users, checking if their ids are done in testProcessTaskSubscriptionRequest
            $this->assertEquals(3, count($task->notificationSubscribers));

            $task = NotificationSubscriberUtil::processUnsubscriptionRequest($task, $maria);
            $this->assertEquals(2, count($task->notificationSubscribers));
            $subscribedUser1 = $task->notificationSubscribers[0]->person->castDown(array($modelDerivationPathToItem));
            $subscribedUser2 = $task->notificationSubscribers[1]->person->castDown(array($modelDerivationPathToItem));
            $this->assertTrue(in_array(Yii::app()->user->userModel->id, array($subscribedUser1->id, $subscribedUser2->id)));
            $this->assertTrue(in_array($john->id, array($subscribedUser1->id, $subscribedUser2->id)));

            $task = NotificationSubscriberUtil::processUnsubscriptionRequest($task, $john);
            $this->assertEquals(1, count($task->notificationSubscribers));
            $subscribedUser = $task->notificationSubscribers[0]->person->castDown(array($modelDerivationPathToItem));
            $this->assertEquals(Yii::app()->user->userModel->id, $subscribedUser->id);
        }
    }
?>
