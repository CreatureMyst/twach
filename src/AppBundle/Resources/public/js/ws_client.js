var ws = WS.connect('ws://127.0.0.1:8080');

ws.on('socket/connect', function(session) {
    console.log('Connected');

    session.subscribe('app/twach', function(uri, payload) {
        console.log(payload);   // TODO: clear garbage

        if(payload.message) {
            var message = $.parseJSON(payload.message);
            createMessage(message);
        }
    });

    $("#twach-form-submit").off('click.twach').on('click.twach', function() {
        var $form = $("#twach-form").find('form');
        var data = submitForm($form);

        // $form.serializeArray().map(function(x){data[x.name] = x.value;});
        session.publish('app/twach', data);

        $("#twach-modal").modal('hide');
    })
});

ws.on('socket/disconnect', function(error) {
    console.log('Disconnected');
    $("#twach-form-submit").off('click.twach');
});

function createMessage(message)
{
    var $prototype = $(".message.prototype").clone();
    var $messages = $(".messages");

    $prototype.removeClass('prototype');
    $prototype.find('.user').html(message.username);
    $prototype.find('.text').html(message.text);
    $prototype.find('.date').html(message.createdAt);

    $messages.prepend($prototype);
}