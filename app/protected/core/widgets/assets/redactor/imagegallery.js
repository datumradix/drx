if (!RedactorPlugins) var RedactorPlugins = {};

RedactorPlugins.imagegallery = function() {
	return {
        init: function () {
            if (!this.opts.urlForImageGallery || !this.opts.linkForInsertClass)
            {
                return;
            }
            var button = this.button.add('zurmoImage', 'Image Gallery');
            this.button.addCallback(button, this.imagegallery.imageGalleryButton);
            this.modal.addCallback('zurmoImage', this.imagegallery.loadModal);
        },
        loadModal: function () {
            var callback = $.proxy(this.imagegallery.insertFromGalleryModal, this);
            var url = this.opts.urlForImageGallery;
            var linkForInsertSelector = '.' + this.opts.linkForInsertClass;
            $.ajax({
                url: url,
                type: "GET",
                success: function (data) {
                    $('#redactor-modal-body').empty().append(data);
                    $('#redactor-modal-body').off('click', linkForInsertSelector);
                    $('#redactor-modal-body').on('click', linkForInsertSelector, callback);
                },
                error: function (xhr, status) {
                    alert("Sorry, there was a problem!");
                }
            });
        },
        imageGalleryButton: function () {
            this.modal.load('zurmoImage', this.opts.curLang.image, 800);
            this.modal.show('zurmoImage');
            $('#redactor-modal').addClass('ui-dialog redactor-image-modal');
            $('#redactor-modal').animate({top: '41', left: '35%'}, 50);
        },
        insertFromGalleryModal: function (event) {
            var element = event.target;
            var imageurl = $(element).data('url');
            this.image.insert({'filelink': imageurl});
            this.modal.close();
        }
    };
};