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

    /**
     * Render a file upload element that can allow for multiple file uploads and calls ajax to upload the files to
     * the server as you add them.
     * Utilizes file upload plugin here: https://github.com/blueimp/jQuery-File-Upload
     */
    class MentionInput extends ZurmoWidget
    {
        public $scriptFile = array('lib/jquery.events.input.js',
            'lib/jquery.elastic.js', 'jquery.mentionsInput.js', 'lib/underscore.js');

        public $cssFile    = array('jquery.mentionsInput.css');

        public $assetFolderName = 'mentionInput';

        /**
         * Data to pass to the file upload script.
         * @var array
         */
        public $htmlOptions;

        /**
         * Name of the file input field.
         * @var string
         */
        public $inputName;

        /**
         * Id of the file input field.
         * @var string
         */
        public $inputId;

        public $callBackUrl = '';

        /**
         * Initializes the widget.
         * This method will publish assets if necessary.
         * It will also register jquery and JUI JavaScript files and the theme CSS file.
         * If you override this method, make sure you call the parent implementation first.
         */
        public function init()
        {
            Yii::app()->getClientScript()->registerCoreScript('jquery.ui');
            parent::init();
        }

        public function run()
        {
            $id = $this->getId();
            // Begin Not Coding Standard
            $javaScript = <<<EOD
$('textarea').mentionsInput({
    onDataRequest:function (mode, query, callback) {
      var data = [
        { id:1, name:'Kenneth Auchenberg', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
        { id:2, name:'Jon Froda', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
        { id:3, name:'Anders Pollas', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
        { id:4, name:'Kasper Hulthin', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
        { id:5, name:'Andreas Haugstrup', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
        { id:6, name:'Pete Lacey', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
        { id:7, name:'kenneth@auchenberg.dk', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
        { id:8, name:'Pete Awesome Lacey', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' },
        { id:9, name:'Kenneth Hulthin', 'avatar':'http://cdn0.4dots.com/i/customavatars/avatar7112_1.gif', 'type':'contact' }
      ];

      data = _.filter(data, function(item) { return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1 });

      callback.call(this, data);
    },
	onCaret: true
  });

  $('.get-syntax-text').click(function() {
    $('textarea.mention').mentionsInput('val', function(text) {
      alert(text);
    });
  });

  $('.get-mentions').click(function() {
    $('textarea.mention').mentionsInput('getMentions', function(data) {
      alert(JSON.stringify(data));
    });
  }) ;

EOD;
            // End Not Coding Standard
            Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $id, $javaScript);
        }
    }
?>