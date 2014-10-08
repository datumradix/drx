<?php
    class ZurmoMailerFactory
    {
        protected $emailMessage;

        protected $emailAccount;

        protected $sendGridEmailAccount;

        protected $sendGridPluginEnabled;

        public function __construct(EmailMessage $emailMessage)
        {
            $this->emailMessage = $emailMessage;
            //Sendgrid enabled
            $this->sendGridPluginEnabled = (bool)ZurmoConfigurationUtil::getByModuleName('SendGridModule', 'enableSendgrid');
        }

        /**
         * Resolve mailer by email message.
         * @param EmailMessage $emailMessage
         * @return \ZurmoSwiftMailer|\ZurmoSendGridMailer
         */
        public function resolveMailer()
        {
            $apiUser             = Yii::app()->sendGridEmailHelper->apiUsername;
            $apiPassword         = Yii::app()->sendGridEmailHelper->apiPassword;
            $user                = $this->emailMessage->owner;
            if($user != null)
            {
                $this->sendGridEmailAccount = SendGridEmailAccount::resolveAndGetByUserAndName($user, null, false);
                $this->emailAccount         = EmailAccount::resolveAndGetByUserAndName($user, null, false);
            }
            if($this->sendGridPluginEnabled && $user != null)
            {
                //Should user settings be used
                if($this->shouldSendGridUserSettingsBeUsed())
                {
                    print "Personal Sendgrid with sendgrid";
                    return new ZurmoSendGridMailer($this->emailMessage, $this->sendGridEmailAccount);
                }
                else
                {
                    //Check for personal settings
                    if($this->shouldCustomUserSettingsBeUsed())
                    {
                        print "Personal Custom with sendgrid";
                        return new ZurmoSwiftMailer($this->emailMessage, $this->emailAccount);
                    }
                    else
                    {
                        if($apiUser != null && $apiPassword != null)
                        {
                            print "Global Sendgrid with sendgrid";
                            return new ZurmoSendGridMailer($this->emailMessage, null);
                        }
                        else
                        {
                            print "Global settings with sendgrid";
                            return new ZurmoSwiftMailer($this->emailMessage, null);
                        }
                    }
                }
            }
            elseif($user != null && $this->shouldUserSettingsBeUsed() === true)
            {
                return new ZurmoSwiftMailer($this->emailMessage, $this->emailAccount);
            }
            elseif($this->sendGridPluginEnabled && $apiUser != null && $apiPassword != null)
            {
                return new ZurmoSendGridMailer($this->emailMessage, null);
            }
            else
            {
                return new ZurmoSwiftMailer($this->emailMessage, null);
            }
        }

        /**
         * Should sendgrid user settings be used to send email.
         * @param EmailMessage $emailMessage
         * @return boolean
         */
        protected function shouldSendGridUserSettingsBeUsed()
        {
            if($this->sendGridEmailAccount == null)
            {
                return false;
            }
            $itemData       = EmailMessageUtil::getCampaignOrAutoresponderDataByEmailMessage($this->emailMessage);
            if($itemData != null)
            {
                $useAutoresponderOrCampaignOwnerMailSettings = (bool)ZurmoConfigurationUtil::getByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings');
                if($this->sendGridEmailAccount->apiUsername != ''
                    && $this->sendGridEmailAccount->apiPassword != ''
                        && $useAutoresponderOrCampaignOwnerMailSettings === true)
                {
                    $this->updateMailerDetailsForEmailMessage('sendgrid', 'personal');
                    return true;
                }
                return false;
            }
            elseif($this->sendGridEmailAccount->apiUsername != ''
                        && $this->sendGridEmailAccount->apiPassword != '')
            {
                $this->updateMailerDetailsForEmailMessage('sendgrid', 'personal');
                return true;
            }
            return false;
        }

        /**
         * Should sendgrid user settings be used to send email.
         * @param EmailMessage $emailMessage
         * @return boolean
         */
        protected function shouldCustomUserSettingsBeUsed()
        {
            if($this->emailAccount == null)
            {
                return false;
            }
            $itemData       = EmailMessageUtil::getCampaignOrAutoresponderDataByEmailMessage($this->emailMessage);
            if($itemData != null)
            {
                $useAutoresponderOrCampaignOwnerMailSettings = (bool)ZurmoConfigurationUtil::getByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings');
                if($this->emailAccount->useCustomOutboundSettings === true && $this->emailAccount->outboundHost
                        && $this->emailAccount->outboundUsername && $this->emailAccount->outboundPassword
                            && $useAutoresponderOrCampaignOwnerMailSettings === true)
                {
                    $this->updateMailerDetailsForEmailMessage('smtp', 'personal');
                    return true;
                }
            }
            elseif($this->emailAccount->useCustomOutboundSettings === true && $this->emailAccount->outboundHost
                        && $this->emailAccount->outboundUsername && $this->emailAccount->outboundPassword)
            {
                $this->updateMailerDetailsForEmailMessage('smtp', 'personal');
                return true;
            }
            return false;
        }

        /**
         * Updates mailer details for email message
         * @param string $mailerType
         * @return void
         */
        protected function updateMailerDetailsForEmailMessage($mailerType, $mailerSettings)
        {
            $emailMessage                   = $this->emailMessage;
            $emailMessage->mailerType       = $mailerType;
            $emailMessage->mailerSettings   = $mailerSettings;
            $emailMessage->save();
            $this->emailMessage = $emailMessage;
        }
    }