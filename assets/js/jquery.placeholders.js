;(function($)
{
    $.fn.extend({
        placeholders: function() {
            if (! ('placeholder' in document.createElement('input'))) {
                var $forms = this.find('form'),
                    $els = this.find('[placeholder]'),
                    $passwords = $els.filter('[type=password]');

                $passwords.attr('placeholder', $passwords.eq(0).attr('placeholder'));

                this.on('focus', '[placeholder]', function() {
                    var $this = $(this);

                    if ($this.val() == $this.attr('placeholder')) {
                        $this.val('').removeClass('placeholder');
                    }
                });

                this.on('blur', '[placeholder]', function() {
                    var $this = $(this);

                    if (($this.val() == '') && ($this.attr('placeholder'))) {
                        $this.val($this.attr('placeholder')).addClass('placeholder');
                    }
                });

                $els.each(function() {
                    $(this).trigger('focus').trigger('blur');
                });

                $forms.on('submit', function() {
                    $(this).find('.placeholder').val('').removeClass('placeholder');
                });
            }

            return this;
        }
    });
})
(jQuery);