/**
 * @file
 * Some useful javascripty UI stuff.
 */

(function ($, window) {

    /**
     * Domain name (without the www. at the beginning) of the current page.
     *
     * @type string
     */
    var hostname = window.location.hostname.replace('www.', '');

    /**
     * Add a confirm handler to any link with a data-confirm attribute.
     */
    function confirm() {
        var $this = $(this);
        $this.click(function () {
            return window.confirm($this.data('confirm'));
        });
    }

    /**
     * Before leaving the page, check that all forms have been saved.
     *
     * @param event e
     *
     * @returns {String}
     */
    function windowBeforeUnload(e) {
        var clean = true;
        $('form').each(function () {
            var $form = $(this);
            if ($form.data('dirty')) {
                clean = false;
            }
        });
        if (!clean) {
            var message = 'You have unsaved changes.';
            e.returnValue = message;
            return message;
        }
    }

    /**
     * Called when a form element changes to mark the form dirty.
     *
     * Javascript will prompt the user to save before leaving the page.
     *
     * @returns null
     */
    function formDirty() {
        var $form = $(this);
        $form.data('dirty', false);
        $form.on('change', function () {
            $form.data('dirty', true);
        });
        $form.on('submit', function () {
            $(window).unbind('beforeunload');
        });
    }

    /**
     * Open a link in a popup.
     *
     * @param {event} e
     *
     * @returns {null}
     */
    function formPopup(e) {
        e.preventDefault();
        var url = $(this).prop('href');
        window.open(url, "_blank", "toolbar=no,scrollbars=yes,resizable=yes,top=60,left=60,width=500,height=600");
    }

    /**
     * Configure a simple, one-input, symfony collection.
     */
    function simpleCollection() {
        $('.collection-simple').collection({
            init_with_n_elements: 1,
            allow_up: false,
            allow_down: false,
            add: '<a href="#" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-plus"></span></a>',
            remove: '<a href="#" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-minus"></span></a>',
            add_at_the_end: false,
            after_add: function (collection, element) {
                $(element).find('.select2entity').select2entity();
                $(element).find('.select2-container').css('width', '100%');
                return true;
            },
        });
    }

    /**
     * Configure a complex, multi-input, symfony collection.
     */
    function complexCollection() {
        $('.collection-complex').collection({
            allow_up: false,
            allow_down: false,
            add: '<a href="#" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-plus"></span></a>',
            remove: '<a href="#" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-minus"></span></a>',
            add_at_the_end: true,
            after_add: function (collection, element) {
                $(element).find('.select2entity').select2entity();
                $(element).find('.select2-container').css('width', '100%');
                return true;
            },
        });
    }

    /**
     * Style links that go off site a bit differently and open them in a new window.
     *
     * @returns {undefined}
     */
    function link() {
        if (this.hostname.replace('www.', '') === hostname) {
            return;
        }
        $(this).attr('target', '_blank');
    }

    /**
     * Do some things to make file uploads a bit less terrible.
     *
     * @returns void
     */
    function uploadControls() {
        var $input = $(this);
        $input.change(function () {
            if ($input.data('maxsize') && $input.data('maxsize') < this.files[0].size) {
                alert('The selected file is too big.');
            }
            $('#filename').val($input.val().replace(/.*\\/, ''));
        });
    }

    function nameInputs() {
        $("input").each(function () {
            var $this = $(this);
            if ($this.attr('type') === 'radio' || $this.attr('type') === 'checkbox') {
                $this.after('<span class="widgetname">' + $(this).attr('name') + '=' + $this.attr('value') + '</span>');
            }
            else {
                $this.after('<span class="widgetname">' + $(this).attr('name') + '</span>');
            }
        });
        $("select").each(function(){
            var $this = $(this);
            var opts = '<br/>';
            $this.find('option').each(function(){
                opts += ' * ' + $(this).text() + ' => ' + $(this).attr('value') + "<br/>";
            });
            $this.after('<span class="widgetname">' + $(this).attr('name') + opts + '</span>');

        });
    }

    /*
     * Set up the stuff.
     */
    $(document).ready(function () {
        $(window).bind('beforeunload', windowBeforeUnload);
        $('form').each(formDirty);
        $('input:file').each(uploadControls);
        $("*[data-confirm]").each(confirm);
        $("a.popup").click(formPopup);
        $("a").each(link);
        if (typeof $().collection === 'function') {
            simpleCollection();
            complexCollection();
        }
        // nameInputs()
    });

})(jQuery, window);
