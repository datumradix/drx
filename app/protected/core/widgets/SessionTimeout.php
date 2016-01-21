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

     Yii::import('zii.widgets.jui.CJuiWidget');

    /**
     * Widget for displaying session timeout.
     */
    class SessionTimeout extends CJuiWidget
    {
        const MAX_SESSION_TIMEOUT_IN_SECONDS = 31536000; // One year

        /**
         * @var integer The number of your session timeout (in seconds).
         * The timeout value minus the countdown value determines how long until
         * the dialog appears.
         * Default: 1400
         */
        public $timeout = 1400;

        /**
         * @var integer The countdown total value (in seconds).
         * Default: 60
         */
        public $countdown;

        /**
         * @var string The title message in the dialog box.
         * Default: 'Your session is about to expire!'
         */
        public $title;

        /**
         * @var string The countdown message where {0} will be
         * used to enter the countdown value.
         * Default: 'You will be logged out in {0} seconds.'
         */
        public $message;

        /**
         * @var string The question message if they want to
         * continue using the site or not.
         * Default: 'Do you want to stay signed in?'
         */
        public $question;

        /**
         * @var string The text of the YES button to keep the session alive.
         * Default: 'Yes, Keep me signed in'
         */
        public $keepAliveButtonText;

        /**
         * @var string The text of the NO button to kill the session.
         * Default: 'No, Sign me out'
         */
        public $signOutButtonText;

        /**
         * @var string The url that will perform a GET request to keep the
         * session alive. This GET expects a 'OK' plain HTTP response.
         * Default: /keep-alive
         */
        public $keepAliveUrl;

        /**
         * @var string The url that will perform a POST request to display an error message.
         * that your session has timed out and has been logged out.
         * Default: null
         */
        public $logoutUrl;

        /**
         * @var string The redirect url after the logout happens, usually back
         * to the login url. It will also contain a next query param with the url
         * that they were when timedout and a timeout=t query param indicating
         * if it was from a timeout, this value will not be set if the user clicked
         * the 'No, Sign me out' button.
         * Default: /
         */
        public $logoutRedirectUrl;

        /**
         * @var boolean A boolean value that indicates if the countdown will
         * restart when the user clicks the 'keep session alive' button.
         * Default: true
         */
        public $restartOnYes;

        /**
         * @var integer The width of the dialog box
         * Default: 350
         */
        public $dialogWidth;

        public $cssFile = null;

        public $countdownCookieName;

        public function init()
        {
            parent::init();
            $this->getSessionTimeout();
            $this->countdown            = 60;
            $this->title                = Zurmo::t('Core', 'Your Zurmo session is about to expire?',
                                                    LabelUtil::getTranslationParamsForAllModules());
            $this->message              = Zurmo::t('Core', 'You will be logged out in {0} seconds.');
            $this->question             = Zurmo::t('Core', 'Do you want to stay signed in?');
            $this->keepAliveButtonText  = Zurmo::t('Core', 'Yes, Keep me signed in');
            $this->signOutButtonText    = Zurmo::t('Core', 'No, Sign me out');
            $this->keepAliveUrl         = Yii::app()->request->url;
            $this->logoutUrl            = Yii::app()->baseUrl . '/index.php/zurmo/default/logout';
            $this->logoutRedirectUrl    = Yii::app()->baseUrl . '/index.php/zurmo/default/logout';
            $this->countdownCookieName    = 'Countdown_' . Yii::app()->request->getHostInfo() . Yii::app()->baseUrl;
            $cs                         = Yii::app()->getClientScript();
            $baseScriptUrl              = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('application.core.widgets.assets'));
            $cs->registerScriptFile($baseScriptUrl . '/sessionTimeout/timeout-dialog.js', ClientScript::POS_HEAD);
        }

        public function run()
        {
            $options = array(
                'timeout'                   => $this->timeout,
                'countdown'                 => $this->countdown,
                'title'                     => $this->title,
                'message'                   => $this->message,
                'question'                  => $this->question,
                'keep_alive_button_text'    => $this->keepAliveButtonText,
                'sign_out_button_text'      => $this->signOutButtonText,
                'keep_alive_url'            => $this->keepAliveUrl,
                'logout_url'                => $this->logoutUrl,
                'restart_on_yes'            => $this->restartOnYes,
                'logout_redirect_url'       => $this->logoutRedirectUrl,
                'dialog_width'              => $this->dialogWidth,
                'start_countdown_timestamp_cookie_name'     => $this->countdownCookieName,
            );
            foreach ($options as $key => $value)
            {
                if ($value === null)
                {
                    unset($options[$key]);
                }
            }
            $options = CJSON::encode($options);
            Yii::app()->getClientScript()->registerScript('TimeoutDialog', "$.timeoutDialog($options);", CClientScript::POS_READY);
        }

        protected function getSessionTimeout()
        {
            $sessionCookieLifeTime      = ini_get('session.cookie_lifetime');
            $sessionGcMaxLifeTime       = ini_get('session.gc_maxlifetime');

            if (isset($sessionCookieLifeTime) && $sessionCookieLifeTime > 0)
            {
                $this->timeout          = $sessionCookieLifeTime;
            }
            else
            {
                $this->timeout          = static::MAX_SESSION_TIMEOUT_IN_SECONDS;
            }

            if (isset($sessionGcMaxLifeTime) && $sessionGcMaxLifeTime > 0 && $sessionGcMaxLifeTime < $this->timeout)
            {
                $this->timeout          = $sessionGcMaxLifeTime;
            }
        }
    }
?>