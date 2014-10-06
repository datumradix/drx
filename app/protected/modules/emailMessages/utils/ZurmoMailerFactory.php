<?php
    abstract class ZurmoMailerFactory
    {
        const MAILER_SETTING_TYPE_PERSONAL_SENDGRID = 1;

        const MAILER_SETTING_TYPE_PERSONAL_SMTP     = 2;

        const MAILER_SETTING_TYPE_GLOBAL_SENDGRID   = 3;

        const MAILER_SETTING_TYPE_GLOBAL_SMTP   = 3;


        /*public static function resolveMailerByEmailMessage(EmailMessage $emailMessage, EmailHelper $emailHelper)
        {
            //#1 is the emailMessage->user have sendgrid personal settings configured AND is sendgrid enabled?
            //do this
                $mailer = new ZurmoSendGridMailer($emailMessage->account->user); //add your additional params

                //return ZurmoSendGridMailer

            //#2 P CSTMP
            //do this
            $mailer = new ZurmoSwiftMailer($emailHelper, $emailMessage->account);

                //return ZurmoSwiftMailer

            //#3 G SG and enabled
                //return ZurmoSendGridMailer
            $mailer = new ZurmoSendGridMailer(null);


            //#4 G SMTP
            $mailer = new ZurmoSwiftMailer($emailHelper, $emailMessage->account);

                //return ZurmoSwiftMailer

        }*/

        public static function resolveMailer(EmailMessage $emailMessage)
        {
            $user   = $emailMessage->owner;
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
            }
            if($user != null)
            {
                //Check for personal settings
                $emailAccount = EmailAccount::resolveAndGetByUserAndName($user, null, false);
                if($emailAccount != null)
                {
                    return new ZurmoSwiftMailer($emailMessage, $emailAccount);
                }
            }

            //#2 P CSTMP
            //do this
            //$mailer = new ZurmoSwiftMailer($emailHelper, $emailMessage->account);

                //return ZurmoSwiftMailer

            //#3 G SG and enabled
                //return ZurmoSendGridMailer
            //$mailer = new ZurmoSendGridMailer(null);


            //#4 G SMTP
            //$mailer = new ZurmoSwiftMailer($emailHelper, $emailMessage->account);

                //return ZurmoSwiftMailer

        }

        protected static function resolveMailerSettingsByStuff(Mailer $mailer, $emailMessage, $emailHelper, $mailerSettingType)
        {
            assert('$type is one of constants.');
        }
    }