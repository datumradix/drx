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
     * A job for processing email activities performed on the email send using sendgrid.
     * @see https://github.com/michaelp85/TS-SendGrid-Event-Webhook-Notifier/blob/master/mod.ts_sendgrid_event_webhook_notifier.php
     */
    class SendGridEmailEventsJob extends BaseJob
    {
        /**
         * @returns Translated label that describes this job type.
         */
        public static function getDisplayName()
        {
           return Zurmo::t('SendGridModule', 'SendGrid Email Events Job');
        }

        /**
         * @return The type of the NotificationRules
         */
        public static function getType()
        {
            return 'SendGridEmailEvents';
        }

        public static function getRecommendedRunFrequencyContent()
        {
            return Zurmo::t('SendGridModule', 'Every 5 minute.');
        }

        /**
         * (non-PHPdoc)
         * @see BaseJob::run()
         */
        public function run()
        {
            $sendGridEmailAccounts = SendGridEmailAccount::getAll();
            foreach($sendGridEmailAccounts as $sendGridEmailAccount)
            {
                if($sendGridEmailAccount->eventWebhookUrl != null)
                {
                    $this->processEventData($sendGridEmailAccount->eventWebhookUrl, $sendGridEmailAccount->eventWebhookFilePath);
                }
            }
            //Global
            $globalWebHookUrl = Yii::app()->sendGridEmailHelper->eventWebhookUrl;
            if($globalWebHookUrl != null)
            {
                $this->processEventData($globalWebHookUrl, Yii::app()->sendGridEmailHelper->eventWebhookFilePath);
            }
            return true;
        }

        /**
         * Resolve and update event information by status of the message.
         * @param array $value
         */
        protected static function resolveAndUpdateEventInformationByStatus(& $value)
        {
            if($value['event'] == 'spamreport')
            {
                $value['type']      = EmailMessageActivity::TYPE_SPAM;
                $value['reason']    = Zurmo::t('SendGridModule', 'Marked as spam');
            }
            if($value['event'] == 'bounce')
            {
                if(strpos($value['status'], "4") == 0)
                {
                    $value['type'] = EmailMessageActivity::TYPE_SOFT_BOUNCE;
                }
                if(strpos($value['status'], "5") == 0)
                {
                    $value['type'] = EmailMessageActivity::TYPE_HARD_BOUNCE;
                }
            }
            if($value['event'] == 'dropped')
            {
                $models = ExternalApiEmailMessageActivity::getByEmailAddress($value['email'], "sendgrid", false);
                if(count($models) == 1)
                {
                    $value['type'] = $models[0]->type;
                }
                else
                {
                    $value['type'] = EmailMessageActivity::TYPE_BOUNCE;
                }
            }
        }

        /**
         * Process event data.
         * @param string $eventWebhookUrl
         * @param string $eventWebhookFilePath
         * @return void
         */
        protected function processEventData($eventWebhookUrl, $eventWebhookFilePath)
        {
            if($eventWebhookUrl != null)
            {
                $content = $this->resolveFileContent($eventWebhookUrl);
                preg_match_all('/\[{(.*?)}\]/i', $content, $matches);
                $data = array();
                foreach($matches[1] as $string)
                {
                    $data[] = json_decode('{' . $string . '}', true);
                }
                foreach($data as $value)
                {
                    if($value['event'] == 'bounce' || $value['event'] == 'spamreport' || $value['event'] == 'dropped')
                    {
                        if(ArrayUtil::getArrayValue($value, 'itemClass', false) !== false &&
                            ArrayUtil::getArrayValue($value, 'itemId', false) !== false)
                        {
                            $this->processActivityInformation($value);
                        }
                    }
                }
                $this->deleteFileContent($eventWebhookFilePath);
            }
        }

        /**
         * Deletes file content.
         * @param string $eventWebhookFilePath
         * @return void
         */
        protected function deleteFileContent($eventWebhookFilePath)
        {
            // Initialize cURL
            $ch = curl_init();
            // Set URL on which you want to post the Form and/or data
            curl_setopt($ch, CURLOPT_URL, $eventWebhookFilePath);
            // Set post to true
            curl_setopt($ch, CURLOPT_POST, true);
            // Data+Files to be posted
            curl_setopt($ch, CURLOPT_POSTFIELDS, urlencode(''));
            // Pass TRUE or 1 if you want to wait for and catch the response against the request made
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/plain'));
            // Execute the request
            $response = curl_exec($ch);
        }

        /**
         * Save external api email message activity.
         * @param EmailMessageActivity $emailMessageActivity
         * @param array $value
         */
        protected function saveExternalApiEmailMessageActivity(EmailMessageActivity $emailMessageActivity, $value)
        {
            $externalApiEmailMessageActivity = new ExternalApiEmailMessageActivity();
            $externalApiEmailMessageActivity->emailMessageActivity = $emailMessageActivity;
            $externalApiEmailMessageActivity->api           = 'sendgrid';
            $externalApiEmailMessageActivity->type          = $value['type'];
            $externalApiEmailMessageActivity->reason        = $value['reason'];
            $externalApiEmailMessageActivity->itemClass     = $value['itemClass'];
            $externalApiEmailMessageActivity->itemId        = $value['itemId'];
            $externalApiEmailMessageActivity->emailAddress  = $value['email'];
            $externalApiEmailMessageActivity->save();
        }

        /**
         * Resolve file content.
         * @param string $eventWebhookUrl
         * @return string
         */
        protected function resolveFileContent($eventWebhookUrl)
        {
            return file_get_contents($eventWebhookUrl);
        }

        /**
         * Get activity type by event.
         * @param array $value
         * @return string
         */
        protected function getActivityTypeByEvent($value)
        {
            if($value['event'] == 'bounce' || $value['event'] == 'dropped')
            {
                $type   = EmailMessageActivity::TYPE_BOUNCE;
            }
            else
            {
                $type   = EmailMessageActivity::TYPE_SPAM;
            }
            return $type;
        }

        /**
         * Process actiity information.
         * @param array $value
         */
        protected function processActivityInformation($value)
        {
            $type                       = $this->getActivityTypeByEvent($value);
            $activityClassName          = EmailMessageActivityUtil::resolveModelClassNameByModelType($value['itemClass']);
            $activityUtilClassName      = $activityClassName . 'Util';
            $activityData               = array('modelId'   => $value['itemId'],
                                                                'modelType' => $value['itemClass'],
                                                                'personId'  => $value['personId'],
                                                                'url'       => null,
                                                                'type'      => $type);
            if($activityUtilClassName::createOrUpdateActivity($activityData))
            {
                //$activityClassName=CampaignItemActivity
                $emailMessageActivities     = $activityClassName::getByTypeAndModelIdAndPersonIdAndUrl($type, $value['itemId'], $value['personId'], null);
                self::resolveAndUpdateEventInformationByStatus($value);
                $externalMessageActivityCount = ExternalApiEmailMessageActivity::getByTypeAndEmailMessageActivity($value['type'], $emailMessageActivities[0], "sendgrid");
                if($externalMessageActivityCount == 0)
                {
                    $this->saveExternalApiEmailMessageActivity($emailMessageActivities[0], $value);
                }
            }
        }
    }
?>