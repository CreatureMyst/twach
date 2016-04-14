function App(wsUri)
{
    this.wsUri = wsUri;
    this.ws = null;
    this.session = null;
    this.attachmentTypes = [];
    this.userId = null;
    this.debug = true;
    this.channelName = 'app/twach';
    this.form_widgetCounter = 0;
    this.objects = {};
    this.formData = {
        attachments: [],
        message: ''
    };

    /**
     * Подгрузка всяких нужных DOM объектов в память.
     */
    this.loadObjects = function()
    {
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
                add_attachment: $(".add-attachment"),
                delete_message: $(".delete-message"),
                like_message: $(".likes")
            }
        };

        this.dump('DOM objects loaded');
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
                    // Принимаем новое сообщение
                    if(payload.message_create) {
                        _this.receiveMessage(payload);
                    }

                    // Удаление сообщения
                    var id;
                    if(id = payload.message_delete) {
                        _this.deleteMessage(id);
                    }

                    // Лайк сообщения
                    var id;
                    if(id = payload.message_like) {
                        _this.likeMessage(id, payload.likes);
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
     * Метод устанавливает ИД Юзера-клиента.
     *
     * @param id
     * @returns {App}
     */
    this.setUserId = function(id)
    {
        this.userId = id;
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
        this.loadObjects();
        this.socket().connect();
        this.socket().createSession(this.channelName);
        this.addAttachment();
        this.prepareTriggers();
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
        $prototype.find('.delete-message').attr('data-id', message.id);
        if(this.userId == message.userId) {
            $prototype.find('.delete').addClass('can_delete');
        }

        $prototype.attr('data-id', message.id);
        $prototype.find('.likes').attr('data-id', message.id);

        $.each(message.attachments, function(k, v) {
            var attach = $prototype.find('.attachments .item.type-'+ v.type).data('prototype');
            attach = attach.replace(/__RESOURCE__/g, v.resource);

            $prototype.find('.attachments').prepend(attach);
        });

        this.objects.wrappers.messages.prepend($prototype);
        this.loadObjects();
        this.prepareTriggers();
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

        function loading(action)
        {
            switch(action) {
                case 'hide':
                    _this.objects.wrappers.modal.find('.loader').hide();
                    _this.objects.wrappers.modal.find('.controls').show();
                    break;
                default:
                    _this.objects.wrappers.modal.find('.loader').show();
                    _this.objects.wrappers.modal.find('.controls').hide();
                    break;
            }
        }

        this.objects.wrappers.form.find('input[type="file"]').off('change.twach').on('change.twach', function(e) {
            var files = e.target.files;
            var formData = new FormData();

            $.each(files, function(key, val) {
                formData.append(key, val);
            });

            loading();
            $.ajax({
                url: '/app/file-upload',
                data: formData,
                type: 'POST',
                processData: false,
                contentType: false,
                success: function(filename) {
                    var attach = {
                        type: 2,
                        resource: filename
                    };

                    loading('hide');
                    _this.formData.attachments.push(attach);
                }
            });
        });

        this.objects.wrappers.form.find('form').off('submit.twach').on('submit.twach', function(e) {
            e.preventDefault();
            var $form = $(this);

            // Собираем данные с основной формы
            var rawForm = $form.serializeArray();
            _this.formData.message = rawForm[0].value;

            // Собираем аттачи
            _this.objects.wrappers.attachments.find('input').each(function(k, v) {
                if($(this).data('type') != 2) {
                    var attach = {};
                    attach.type = $(v).data('type');
                    attach.resource = $(v).val();

                    _this.formData.attachments.push(attach);
                }
            });

            _this.dump(_this.formData);
            // Пытаемся отправить все это на сервер
            var data = { event: 'message.create', data: _this.formData };
             _this.socket().publish(_this.channelName, data);
            _this.clearForm();
            _this.objects.wrappers.modal.modal('hide');
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
        this.formData = {
            message: '',
            attachments: []
        };
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

            _this.loadObjects();
            _this.activateForm();
        });
    };

    /**
     * Подготовка всяких триггеров.
     */
    this.prepareTriggers = function()
    {
        var _this = this;
        this.dump('Triggers prepared');

        // Deleting messages
        this.objects.buttons.delete_message.off('click.twach').on('click.twach', function() {
            _this.dump('Delete intention');

            var id = $(this).data('id');
            var data = {
                event: 'message.delete',
                data: { id: id }
            };

            _this.socket().publish(_this.channelName, data);
        });

        // Like messages
        this.objects.buttons.like_message.off('click.twach').on('click.twach', function() {
            _this.dump('Like intention');

            var id = $(this).data('id');
            var data = {
                event: 'message.like',
                data: { id: id }
            };

            _this.socket().publish(_this.channelName, data);
        });
    };

    /**
     * Удаление сообщения.
     *
     * @param id
     */
    this.deleteMessage = function(id)
    {
        this.objects.wrappers.messages.find('.message[data-id="'+ id +'"]').remove();
    };

    /**
     * Лайк да дизлайк.
     * 
     * @param id
     * @param likes
     */
    this.likeMessage = function(id, likes)
    {
        this.objects.wrappers.messages.find('.message .likes[data-id="'+ id +'"]').html(likes);
    };
}