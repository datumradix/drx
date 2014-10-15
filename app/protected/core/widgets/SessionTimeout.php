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
     * Widget for displaying session timeout.
     */
    class SessionTimeout extends ZurmoWidget
    {
        /**
         * Initialize the SessionTimeout Widget
         */
        public function init()
        {
            parent::init();
        }

        public function run()
        {
            $idleTime = 1200000;
            $initialSessionTimeoutMessage = Zurmo::t('Core', 'Your session will expire in <span id=\"sessionTimeoutCountdown\"></span>&nbsp;seconds.<br/><br />Click on <b>OK</b> to continue your session.');
            $redirectAfter = 60;
            $redirectTo = Yii::app()->baseUrl . '/index.php/zurmo/default/logout';
            $expiredMessage = Zurmo::t('Core', 'Your session has expired.  You are being logged out for security reasons.');
            $cs = Yii::app()->getClientScript();
            //Register session timeout script and css
            self::registerSessionTimeoutScriptAndCss();
            // Begin Not Coding Standard
            $script =  "var idleTime = {$idleTime};
                        var initialSessionTimeoutMessage = '{$initialSessionTimeoutMessage}';
                        var sessionTimeoutCountdownId = 'sessionTimeoutCountdown';
                        var redirectAfter = {$redirectAfter};
                        var redirectTo = '{$redirectTo}'; // URL to relocate the user to once they have timed out
                        var expiredMessage = '{$expiredMessage}'; // message to show user when the countdown reaches 0
                        var running = false; // var to check if the countdown is running
                        var timer; // reference to the setInterval timer so it can be stopped
                        $(document).ready(function() {
                            // create the warning window and set autoOpen to false
                            var sessionTimeoutWarningDialog = $(\"#sessionTimeoutWarning\");
                            $(sessionTimeoutWarningDialog).html(initialSessionTimeoutMessage);
                            $(sessionTimeoutWarningDialog).dialog({
                                title: 'Session Expiration Warning',
                                autoOpen: false,	// set this to false so we can manually open it
                                closeOnEscape: false,
                                draggable: false,
                                height: 260,
                                minHeight: 50,
                                modal: true,
                                beforeclose: function() { // bind to beforeclose so if the user clicks on the \"X\" or escape to close the dialog, it will work too
                                    // stop the timer
                                    clearInterval(timer);

                                    // stop countdown
                                    running = false;
                                },
                                buttons: {
                                    width: 350,
                                    OK: function() {
                                        // stop the timer
                                        clearInterval(timer);

                                        // stop countdown
                                        running = false;

                                        // close dialog
                                        $(this).dialog('close');
                                    }
                                },
                                resizable: false,
                                open: function() {
                                    // scrollbar fix for IE
                                    $('body').css('overflow','hidden');
                                },
                                close: function() {
                                    // stop the timer
                                        clearInterval(timer);

                                    // stop countdown
                                    running = false;

                                    // reset overflow
                                    $('body').css('overflow','auto');
                                }
                            }); // end of dialog


                            // start the idle timer
                            $.idleTimer(idleTime);

                            // bind to idleTimer's idle.idleTimer event
                            $(document).bind(\"idle.idleTimer\", function(){
                                // if the user is idle and a countdown isn't already running
                                if($.data(document,'idleTimer') === 'idle' && !running){
                                    var counter = redirectAfter;
                                    running = true;

                                    // intialisze timer
                                    $('#'+sessionTimeoutCountdownId).html(redirectAfter);
                                    // open dialog
                                    $(sessionTimeoutWarningDialog).dialog('open');

                                    // create a timer that runs every second
                                    timer = setInterval(function(){
                                        counter -= 1;

                                        // if the counter is 0, redirect the user
                                        if(counter === 0) {
                                            $(sessionTimeoutWarningDialog).html(expiredMessage);
                                            $(sessionTimeoutWarningDialog).dialog('disable');
                                            window.location = redirectTo;
                                        } else {
                                            $('#'+sessionTimeoutCountdownId).html(counter);
                                        };
                                        $(sessionTimeoutWarningDialog).dialog('open');
                                    }, 1000);
                                };
                            });

                        });";
            // End Not Coding Standard
            $cs->registerScript('loadSessionTimeoutScript', $script, ClientScript::POS_END);
        }

        /**
         * Registers script and css file
         */
        protected static function registerSessionTimeoutScriptAndCss()
        {
            $cs            = Yii::app()->getClientScript();
            $baseScriptUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('application.core.widgets.assets'));
            $cs->registerScriptFile($baseScriptUrl . '/sessionTimeout/jquery.idletimer.js', ClientScript::POS_HEAD);
        }
    }
?>