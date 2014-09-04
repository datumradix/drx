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
     * A job for processing bounced emails from sendgrid.
     * @see https://github.com/michaelp85/TS-SendGrid-Event-Webhook-Notifier/blob/master/mod.ts_sendgrid_event_webhook_notifier.php
     */
    class SendGridEmailBounceJob extends BaseJob
    {
        /**
         * @returns Translated label that describes this job type.
         */
        public static function getDisplayName()
        {
           return Zurmo::t('SendGridModule', 'SendGrid Email Bounce Job');
        }

        /**
         * @return The type of the NotificationRules
         */
        public static function getType()
        {
            return 'SendGridEmailBounce';
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
            $sendGridPluginEnabled = (bool)ZurmoConfigurationUtil::getByModuleName('SendGridModule', 'enableSendgrid');
            $user = Yii::app()->user->userModel;
            if($sendGridPluginEnabled)
            {
                $data = array();
                $emailAccount                   = SendGridEmailAccount::getByUserAndName(Yii::app()->user->userModel);
                $bounceEventWebhookUrl          = $emailAccount->eventWebhookUrl;
                if($bounceEventWebhookUrl != null)
                {
                    $content = file_get_contents($bounceEventWebhookUrl);
                    preg_match_all('/\[{(.*?)}\]/i', $content, $matches);
                    foreach($matches[1] as $string)
                    {
                        $data[] = json_decode('{' . $string . '}', true);
                    }
                    foreach($data as $value)
                    {
                        if($value['event'] == 'bounce' || $value['event'] == 'spamreport' || $value['event'] == 'dropped')
                        {
                            if(ArrayUtil::getArrayValue($value, 'itemClass'))
                            {
                                $activityClassName          = EmailMessageActivityUtil::resolveModelClassNameByModelType($value['itemClass']);
                                $activityUtilClassName      = $activityClassName . 'Util';
                                $type                       = $activityClassName::TYPE_BOUNCE;
                                $activityData               = array('modelId'   => $value['itemId'],
                                                                    'modelType' => $value['itemClass'],
                                                                    'personId'  => $value['personId'],
                                                                    'url'       => null,
                                                                    'type'      => $type);
                                $activityCreatedOrUpdated   = $activityUtilClassName::createOrUpdateActivity($activityData);
                                $emailMessageActivities     = $activityClassName::getByTypeAndModelIdAndPersonIdAndUrl($type, $value['itemId'], $value['personId'], null);
                                $externalMessageActivityCount = ExternalApiEmailMessageActivity::getCountByTypeAndEmailMessageActivity($type, $emailMessageActivities[0]);
                                if($externalMessageActivityCount == 0)
                                {
                                    $externalApiEmailMessageActivity = new ExternalApiEmailMessageActivity();
                                    $externalApiEmailMessageActivity->emailMessageActivity = $emailMessageActivities[0];
                                    $externalApiEmailMessageActivity->api       = 'sendgrid';
                                    $externalApiEmailMessageActivity->type      = $type;
                                    $externalApiEmailMessageActivity->reason    = $value['reason'];
                                    $externalApiEmailMessageActivity->itemClass = $value['itemClass'];
                                    $externalApiEmailMessageActivity->save();
                                }
                            }
                        }
                    }
                }
                else
                {
                    echo Yii::t('SendGridModule', 'Webhook event url is missing for user ' . $user->username) . "\n";
                }
            }
            return true;
        }
    }
?>