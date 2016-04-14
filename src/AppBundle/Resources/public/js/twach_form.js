// keep track of how many email fields have been rendered
var widgetCount = 0;

$(function() {
    $('.add-attachment').click(function(e) {
        e.preventDefault();

        var list = $('#message_attachments');
        var type = $(this).data('type');

        var newWidget = list.attr('data-prototype');
        newWidget = newWidget.replace(/__name__/g, widgetCount);

        var $widget = $(newWidget);

        var $option = $widget.find('option[value="'+ type +'"]').attr('selected', 'selected');
        $widget.find('label').remove();
        $widget.find('select').hide();
        $widget.find('input')
            .addClass('form-control')
            .attr('placeholder', $option.html())
            .attr('data-type', type)
        ;

        if(type == 2) {
            $widget.find('input').attr('type', 'file');
        }

        widgetCount++;

        var newLi = $('<p></p>').html($widget);
        newLi.appendTo(list);
    });
});

function clearForm()
{
    var $form_wrapper = $("#twach_form");
    $form_wrapper.find('form')[0].reset();

    var attachments = $('#message_attachments');
    attachments.html('');

    widgetCount = 0;
}

function submitForm($form)
{
    var data = [];

    $form.serializeArray().map(function(x) {
        data[x.name] = x.value;
    });

    data.attachments = [];

    $form.find('#message_attachments').each(function(k, v) {
        var attach = {};
        attach.type = $(v).data('type');
        attach.value = $(v).val();

        data.attachments.push(attach);
    });

    console.log(data);

    clearForm();
}