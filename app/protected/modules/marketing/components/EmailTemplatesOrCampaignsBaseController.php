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

    abstract class EmailTemplatesOrCampaignsBaseController extends ZurmoModuleController
    {
        const EMAIL_CONFIGURATION_FILTER_PATH =
            'application.modules.emailMessages.controllers.filters.EmailConfigurationCheckControllerFilter';

        abstract protected function getSendTestEmailUtil();

        public function filters()
        {
            return array_merge(parent::filters(),
                array(
                    array(self::EMAIL_CONFIGURATION_FILTER_PATH . ' + modalSendTest',
                        'controller'            => $this,
                        'renderWithoutPageView' => true,
                    ),
                )
            );
        }

        public function actionModalSendTest()
        {
            $this->throwAccessDeniedExceptionIfModuleIsInaccessible();
            Yii::app()->getClientScript()->setToAjaxMode();
            $form = new SendTestEmailForm();
            $sendTestEmailModalEditView = new SendTestEmailModalEditView($this->getId(),
                                                                        $this->getModule()->getId(),
                                                                        $form);
            $view = new ModalView($this, $sendTestEmailModalEditView);
            echo $view->render();
        }

        public function actionValidateSendTestEmail()
        {
            $this->throwAccessDeniedExceptionIfModuleIsInaccessible();
            if (!Yii::app()->request->isPostRequest)
            {
                throw new CHttpException(400);
            }
            $form   = new SendTestEmailForm();
            $ajax   = Yii::app()->request->getPost('ajax');
            if ($ajax == 'send-test-form') {
                $form->setAttributes($_POST[get_class($form)]);
                $errorData = array();
                if (!$form->validate()) {
                    foreach ($form->getErrors() as $attribute => $errors) {
                        $errorData[ZurmoHtml::activeId($form, $attribute)] = $errors;
                    }
                }
                echo CJSON::encode($errorData);
            }
            Yii::app()->end(0, false);
        }

        public function actionSendTestEmail()
        {
            $this->throwAccessDeniedExceptionIfModuleIsInaccessible();
            if (!Yii::app()->request->isPostRequest)
            {
                throw new CHttpException(400);
            }
            $recipientData  = Yii::app()->request->getPost('SendTestEmailForm');
            $sourceData     = Yii::app()->request->getPost('sendTestEmailData');
            if (!($this->validatePostRecipientData($recipientData) && $this->validatePostSourceData($sourceData)))
            {
                throw new CHttpException(400);
            }
            $sendTestEmailUtilClassName     = $this->getSendTestEmailUtil();
            $sendTestEmailUtil              = new $sendTestEmailUtilClassName();
            try
            {
                $sendTestEmailUtil->sendTestEmail($recipientData, $sourceData);
            }
            catch (NotFoundException $e)
            {
                // couldn't find relevant campaign or emailTemplate.
                // most reasonable thing would be to throw a 404 error.
                throw new CHttpException(404);
            }
            Yii::app()->getClientScript()->setToAjaxMode();
            $this->renderSuccessMessage();
            Yii::app()->end(0, false);
        }

        protected function renderSuccessMessage()
        {
            $messageContent     = Zurmo::t('MarketingModule', 'Test Email was successfully sent');
            //$content = ZurmoHtml::tag('div', array('class' => 'modal-result-message'), $message);
            $messageView        = new TestEmailSentView($messageContent);
            $view               = new ModalView($this,
                                    $messageView,
                                    'modalContainer',
                                    Zurmo::t('EmailMessagesModule', 'Send Test Email Results')
                                );
            echo $view->render();
        }

        protected function throwAccessDeniedExceptionIfModuleIsInaccessible()
        {
            if (!static::ensureUserCanAccessModule())
            {
                throw new CHttpException(403);
            }
        }

        protected function ensureUserCanAccessModule()
        {
            return RightsUtil::canUserAccessModule(get_class($this->getModule()), Yii::app()->user->userModel);
        }

        protected function validatePostRecipientData(array $recipientData)
        {
            return (!empty($recipientData) && (isset($recipientData['contactId']) || isset($recipientData['emailAddress'])));
        }

        protected function validatePostSourceData(array $sourceData)
        {
            return (!empty($sourceData) &&
                ($this->validatePostSourceDataHasValidIdAndClass($sourceData) ||
                    $this->validatePostSourceDataHasOneOfTheRequiredPropertiesSet($sourceData)));
        }

        protected function validatePostSourceDataHasValidIdAndClass(array $sourceData)
        {
            return (isset($sourceData['id'], $sourceData['class']) &&
                    ($sourceData['class'] == 'EmailTemplate' || $sourceData['class'] == 'Campaign'));
        }

        protected function validatePostSourceDataHasOneOfTheRequiredPropertiesSet(array $sourceData)
        {
            return (isset($sourceData['subject']) &&
                    (isset($sourceData['textContent']) ||
                        isset($sourceData['htmlContent']) ||
                        isset($sourceData['serializedData'])));
        }
    }
?>