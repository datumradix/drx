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
                if($this->shouldUserSettingsBeUsed())
                {
                    return new ZurmoSendGridMailer($this->emailMessage, $this->emailAccount);
                }
                else
                {
                    //Check for personal settings
                    if($this->emailAccount != null)
                    {
                        return new ZurmoSwiftMailer($this->emailMessage, $this->emailAccount);
                    }
                    else
                    {
                        if($apiUser != null && $apiPassword != null)
                        {
                            return new ZurmoSendGridMailer($this->emailMessage, null);
                        }
                        else
                        {
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
         * Should user settings be used to send email.
         * @param EmailMessage $emailMessage
         * @return boolean
         */
        protected function shouldUserSettingsBeUsed()
        {
            $itemData       = EmailMessageUtil::getCampaignOrAutoresponderDataByEmailMessage($this->emailMessage);
            if($itemData != null)
            {
                list($itemId, $itemClass, $personId) = $itemData;
                $campaignOrAutoresponderItem = $itemClass::getById($itemId);
                $useAutoresponderOrCampaignOwnerMailSettings = (bool)ZurmoConfigurationUtil::getByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings');
                if($this->sendGridPluginEnabled)
                {
                    if($this->sendGridEmailAccount == null)
                    {
                        return false;
                    }
                    if($this->sendGridEmailAccount->apiUsername != ''
                        && $this->sendGridEmailAccount->apiPassword != ''
                            && $useAutoresponderOrCampaignOwnerMailSettings === true)
                    {
                        $this->updateMailerDetailsForEmailMessage($campaignOrAutoresponderItem, $itemClass, 'sendgrid');
                        return true;
                    }
                    return false;
                }
                else
                {
                    if($this->emailAccount == null)
                    {
                        return false;
                    }
                    if($this->emailAccount->useCustomOutboundSettings === true && $this->emailAccount->outboundHost
                            && $this->emailAccount->outboundUsername && $this->emailAccount->outboundPassword
                                && $useAutoresponderOrCampaignOwnerMailSettings === true)
                    {
                        $this->updateMailerDetailsForEmailMessage($campaignOrAutoresponderItem, $itemClass, 'smtp');
                        return true;
                    }
                    return false;
                }
            }
            return true;
        }

        /**
         * Updates mailer details for email message
         * @param Item $campaignOrAutoresponderItem
         * @param string $itemClass
         * @param string $mailerType
         */
        protected function updateMailerDetailsForEmailMessage($campaignOrAutoresponderItem, $itemClass, $mailerType)
        {
            if($itemClass == 'CampaignItem')
            {
                $associatedCampaign          = $campaignOrAutoresponderItem->campaign;
                //If not already updated
                if($associatedCampaign->mailer == null)
                {
                    $associatedCampaign->mailer         = $mailerType;
                    $associatedCampaign->useOwnerSmtp   = true;
                    $associatedCampaign->save();
                }
            }
        }
    }