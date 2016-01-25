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

    class ProjectsNotificationUtilTest extends ZurmoBaseTest
    {
        protected static $super;

        protected static $steve;

        protected static $sally;

        protected static $katie;

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            Yii::app()->user->userModel = SecurityTestHelper::createSuperAdmin();
            self::$super = Yii::app()->user->userModel;
            self::$steve = UserTestHelper::createBasicUserWithEmailAddress('steve');
            NotificationTestHelper::setNotificationSettingsForUser(self::$steve, 'NewTaskNotificationRules');
            self::$sally = UserTestHelper::createBasicUserWithEmailAddress('sally');
            self::$katie = UserTestHelper::createBasicUserWithEmailAddress('katie');
        }

        public function setUp()
        {
            parent::setUp();
            Yii::app()->user->userModel = User::getByUsername('super');
            EmailMessage::deleteAll();
            Notification::deleteAll();
        }

        public function testNewNotificationWhenNewProjectTaskIsCreated()
        {
            $project                    = new Project();
            $project->name              = 'project-'. __FUNCTION__;
            $project->owner             = self::$steve;
            $task                       = new Task();
            $task->name                 = 'task-' . __FUNCTION__;
            $task->owner                = self::$sally;

            $this->assertEquals(0, EmailMessage::getCount());
            $this->assertEquals(0, Notification::getCount());

            ProjectsNotificationUtil::submitProjectNotificationMessage(
                $project, ProjectAuditEvent::TASK_ADDED, $task, self::$sally);

            $emailMessages = EmailMessage::getAll();
            $notifications = Notification::getAll();
            $this->assertCount(1, $emailMessages);
            $this->assertCount(1, $notifications);
            $this->assertEquals('NEW TASK: task-'. __FUNCTION__ . ' for PROJECT: project-' . __FUNCTION__,
                                $emailMessages[0]->subject);
            $this->assertContains("New task, task-" . __FUNCTION__ . ", was created for project, 'project-" . __FUNCTION__ . "'. Created by sally sallyson",
                                  $emailMessages[0]->content->textContent);
            $this->assertContains("New task, task-" . __FUNCTION__ . ", was created for project, 'project-" . __FUNCTION__ . "'. Created by sally sallyson",
                                  $emailMessages[0]->content->htmlContent);
            $this->assertContains("New task, task-" . __FUNCTION__ . ", was created for project, 'project-" . __FUNCTION__ . "'. Created by sally sallyson",
                                  $notifications[0]->notificationMessage->textContent);
            $this->assertContains("New task, task-" . __FUNCTION__ . ", was created for project, 'project-" . __FUNCTION__ . "'. Created by sally sallyson",
                                  $notifications[0]->notificationMessage->htmlContent);
        }

        public function testNewNotificationWhenNewProjectIsCreated()
        {
            $project                    = new Project();
            $project->name              = 'project-' . __FUNCTION__;
            $project->owner             = self::$steve;

            $this->assertEquals(0, EmailMessage::getCount());
            $this->assertEquals(0, Notification::getCount());

            ProjectsNotificationUtil::submitProjectNotificationMessage(
                $project, ProjectAuditEvent::PROJECT_CREATED);

            $emailMessages = EmailMessage::getAll();
            $notifications = Notification::getAll();
            $this->assertCount(1, $emailMessages);
            $this->assertCount(1, $notifications);
            $this->assertEquals('PROJECT: project-'. __FUNCTION__,
                $emailMessages[0]->subject);
            $this->assertContains("The project, 'project-" . __FUNCTION__ . "', is now owned by you.",
                                  $emailMessages[0]->content->textContent);

            $this->assertContains("The project, 'project-" . __FUNCTION__ . "', is now owned by you.",
                                  $emailMessages[0]->content->htmlContent);

            $this->assertContains("The project, 'project-" . __FUNCTION__ . "', is now owned by you.",
                                 $notifications[0]->notificationMessage->textContent);

            $this->assertContains("The project, 'project-" . __FUNCTION__ . "', is now owned by you.",
                                  $notifications[0]->notificationMessage->htmlContent);
        }

        public function testNewNotificationWhenProjectIsArchived()
        {
            $project                    = new Project();
            $project->name              = 'project-' . __FUNCTION__;
            $project->owner             = self::$steve;
            $project->status            = Project::STATUS_ARCHIVED;

            $this->assertEquals(0, EmailMessage::getCount());
            $this->assertEquals(0, Notification::getCount());

            ProjectsNotificationUtil::submitProjectNotificationMessage(
                $project, ProjectAuditEvent::PROJECT_ARCHIVED);

            $emailMessages = EmailMessage::getAll();
            $notifications = Notification::getAll();
            $this->assertCount(1, $emailMessages);
            $this->assertCount(1, $notifications);
            $this->assertEquals('PROJECT: project-'. __FUNCTION__,
                $emailMessages[0]->subject);
            $this->assertContains("The project, 'project-" . __FUNCTION__ . "', is now archived.",
                                  $emailMessages[0]->content->textContent);

            $this->assertContains("The project, 'project-" . __FUNCTION__ . "', is now archived.",
                                  $emailMessages[0]->content->htmlContent);

            $this->assertContains("The project, 'project-" . __FUNCTION__ . "', is now archived.",
                                 $notifications[0]->notificationMessage->textContent);

            $this->assertContains("The project, 'project-" . __FUNCTION__ . "', is now archived.",
                                  $notifications[0]->notificationMessage->htmlContent);
        }
    }
?>