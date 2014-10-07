<?php
    abstract class ZurmoMailerFactory
    {
        const MAILER_SETTING_TYPE_PERSONAL_SENDGRID = 1;

        const MAILER_SETTING_TYPE_PERSONAL_SMTP     = 2;

        const MAILER_SETTING_TYPE_GLOBAL_SENDGRID   = 3;

        const MAILER_SETTING_TYPE_GLOBAL_SMTP   = 3;

        /**
         * Resolve mailer by email message.
         * @param EmailMessage $emailMessage
         * @return \ZurmoSwiftMailer|\ZurmoSendGridMailer
         */
        public static function resolveMailer(EmailMessage $emailMessage)
        {
            $sendGridEmailHelper = Yii::app()->sendGridEmailHelper;
            $apiUser             = $sendGridEmailHelper->apiUsername;
            $apiPassword         = $sendGridEmailHelper->apiPassword;
            $user                = $emailMessage->owner;
            //Sendgrid enabled
            $sendGridPluginEnabled = (bool)ZurmoConfigurationUtil::getByModuleName('SendGridModule', 'enableSendgrid');
            if($sendGridPluginEnabled && $user != null)
            {
                //Check for personal settings
                $emailAccount = SendGridEmailAccount::resolveAndGetByUserAndName($user, null, false);
                if($emailAccount != null)
                {
                    return new ZurmoSendGridMailer($emailMessage, $emailAccount);
                }
                else
                {
                    //Check for personal settings
                    $emailAccount = EmailAccount::resolveAndGetByUserAndName($user, null, false);
                    if($emailAccount != null)
                    {
                        return new ZurmoSwiftMailer($emailMessage, $emailAccount);
                    }
                    else
                    {
                        if($apiUser != null && $apiPassword != null)
                        {
                            return new ZurmoSendGridMailer($emailMessage, null);
                        }
                        else
                        {
                            return new ZurmoSwiftMailer($emailMessage, null);
                        }
                    }
                }
            }
            if($user != null)
            {
                //Check for personal settings
                $emailAccount = EmailAccount::resolveAndGetByUserAndName($user, null, false);
                if($emailAccount != null)
                {
                    return new ZurmoSwiftMailer($emailMessage, $emailAccount);
                }
                else
                {
                    if($apiUser != null && $apiPassword != null)
                    {
                        return new ZurmoSendGridMailer($emailMessage, null);
                    }
                    else
                    {
                        return new ZurmoSwiftMailer($emailMessage, null);
                    }
                }
            }
            if($sendGridPluginEnabled && $apiUser != null && $apiPassword != null)
            {
                return new ZurmoSendGridMailer($emailMessage, null);
            }
            else
            {
                return new ZurmoSwiftMailer($emailMessage, null);
            }
        }
    }