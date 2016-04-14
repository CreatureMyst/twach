function App(wsUri)
{
    this.wsUri = wsUri;
    this.ws = null;
    this.session = null;
    this.attachmentTypes = [];
    this.debug = true;
    this.channelName = 'app/twach';
    this.form_widgetCounter = 0;
    this.objects = {
        wrappers: {
            attachments: $("#message_attachments"),
            form: $("#twach_form"),
            modal: $("#twach_modal"),
            messages: $(".messages"),
            message_prototype: $(".message.prototype")
        },
        buttons: {
            form_submit: $("#twach-form-submit"),
            add_attachment: $(".add-attachment")
        }
    };

    /**
     * Метод для работы с WS.
     *
     * @returns {object}
     */
    this.socket = function()
    {
        var _this = this;

        return {
            /**
             * Метод создает коннект к серверу.
             */
            connect: function() {
                _this.ws = WS.connect(_this.wsUri);
                if(_this.ws) {
                    _this.dump('Connected');
                }
            },

            /**
             * Метод инициализирует сессию к указанному каналу.
             *
             * @param channel
             */
            createSession: function(channel) {
                _this.ws.on('socket/connect', function(session) {
                    _this.session = session;
                    _this.dump('Session created');

                    _this.socket().subscribe(channel);
                    _this.activateForm();
                });

                _this.ws.on('socket/disconnect', function(error) {
                    _this.dump('Disconnected');
                    _this.dump(error);
                });
            },

            /**
             * Метод отправляет данные на сервер в указанный канал.
             *
             * @param channel
             * @param data
             */
            publish: function(channel, data)
            {
                var result = _this.session.publish(channel, data);
                _this.dump('Data published');

                return result;
            },

            /**
             * Метод подписывается на канал и принимает данные.
             *
             * @param channel
             */
            subscribe: function(channel)
            {
                _this.session.subscribe(channel, function(uri, payload) {
                    if(payload.message_create) {
                        // Принимаем новое сообщение
                        _this.receiveMessage(payload);
                    }
                });
            }
        }
    };

    /**
     * Метод устанавливает массив типов аттачей.
     *
     * @param types
     * @returns {App}
     */
    this.setAttachmentTypes = function(types)
    {
        this.attachmentTypes = types;
        return this;
    };

    /**
     * Метод выплевывает дебаг-инфу в консоль,
     * если включен дебаг-режим.
     *
     * @param message
     * @param level
     * @returns {boolean}
     */
    this.dump = function(message, level)
    {
        if(!this.debug) {
            return false;
        }

        switch(level) {
            case 'error':
                console.error('APP:', message);
                break;
            default:
                console.log('APP:', message);
                break;
        }
    };

    /**
     * Инициализация приложения.
     */
    this.load = function()
    {
        this.socket().connect();
        this.socket().createSession(this.channelName);
        this.addAttachment();
    };

    /**
     * Метод собирает вид сообщения и выводит его перед остальными.
     *
     * @param message
     */
    this.createMessage = function(message)
    {
        var $prototype = this.objects.wrappers.message_prototype.clone();

        $prototype.removeClass('prototype');
        $prototype.find('.user').html(message.username);
        $prototype.find('.text').html(message.text);
        $prototype.find('.date').html(message.createdAt);

        this.objects.wrappers.messages.prepend($prototype);
    };

    /**
     * Метод формирует и отрисовывает сообщение из данных с сокета.
     *
     * @param data
     */
    this.receiveMessage = function(data)
    {
        var message = $.parseJSON(data.message_create);
        this.createMessage(message);
    };

    /**
     * Метод обрабатывает форму и отправляет данные на сервер.
     */
    this.activateForm = function()
    {
        var _this = this;

        this.objects.buttons.form_submit.off('click.twach').on('click.twach', function() {
            var $form = _this.objects.wrappers.form.find('form');
            var data = {};

            // Собираем данные с основной формы
            $form.serializeArray().map(function(x) {
                data[x.name] = x.value;
            });

            // Собираем аттачи
            data.attachments = [];
            _this.objects.wrappers.attachments.find('input').each(function(k, v) {
                var attach = {};
                attach.type = $(v).data('type');
                attach.resource = $(v).val();

                data.attachments.push(attach);
            });

            // Пытаемся отправить все это на сервер
            data = { event: 'message.create', data: data };
            _this.socket().publish(_this.channelName, data);
            _this.clearForm();
            _this.objects.wrappers.modal.modal('hide');
            _this.dump(data.data);
        })
    };

    /**
     * Метод очищает форму сообщения.
     */
    this.clearForm = function()
    {
        this.objects.wrappers.form.find('form')[0].reset();
        this.objects.wrappers.attachments.html('');
        this.form_widgetCounter = 0;
    };

    this.addAttachment = function()
    {
        var _this = this;

        this.objects.buttons.add_attachment.click(function(e) {
            e.preventDefault();

            var list = _this.objects.wrappers.attachments;
            var type = $(this).data('type');

            var newWidget = list.attr('data-prototype');
            newWidget = newWidget.replace(/__name__/g, _this.form_widgetCounter);

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

            _this.form_widgetCounter++;

            var newLi = $('<p></p>').html($widget);
            newLi.appendTo(list);
        });
    }
}