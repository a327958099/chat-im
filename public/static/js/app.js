
$(function () {

    if(window.ActiveXObject || "ActiveXObject" in window) 
    {
        alert("目前暂不支持IE内核的浏览器，请切换到谷歌浏览器或其他浏览器的极速模式");
    }

    /**
     * 表情
     */
    let face = [];

    for (let f = 0; f < 99; f++) 
    {
        face.push('<div class="talk" data-icon="[/:' + f + ']">');
        face.push('<img src="/static/face/' + f + '.gif">');
        face.push('</div>');
    }

    $('.face').html(face.join(''));

    $('.talk').on('click', function() 
    {
        $('#input').val( $('#input').val() + $(this).attr('data-icon') )
        document.getElementById('input').focus();
    })


    // 显示隐藏
    $('.face-btn,.face').on('click', function(e) 
    {
        e.stopPropagation();
        $('.face').show();

        $(document).on('click',function(event)
        {
            $('.face').hide();
            $(document).unbind();
        })
    })


    // 基础回调函数
    var returnCode = function(data, callback) 
    {
        if(data.code == 401 || data.code == 204)
        {
            alert(data.msg);
        }
        else
        {
            callback(data);
        }
    };

    // 会话置顶
    var chatTop = function(chatId) 
    {
        let tag = $('[chatid="'+chatId+'"]').prop("outerHTML");
            $('[chatid="'+chatId+'"]').remove();
            $('.user_list').prepend( tag );
    };

    // 获取会话
    var getChat = function()
    {
        let param = arguments;

        $.ajax({
            type: 'POST',
            url: '/index/chat/list',
            dataType: 'json',
            success: function(box1)
            {
                returnCode(box1, function(message) 
                {
                    $('.user_list').html( template('chat-template', message.data) );
                    $('#chat-list').html( JSON.stringify(message.data) );
                    $('.desc').find('br').remove();

                    // 是否在新会话标注提醒
                    if(param.length > 0)
                    {
                        // 会话置顶
                        chatTop(param[0]);
                        $('.toast-' + param[0]).html( '<div class="num">1</div>' );
                    }
                });
            }
        });
    }

    // 获取会话消息
    var getChatMsg = function(chatId) 
    {
        $.ajax({
            type: 'POST',
            url: '/index/msg/list',
            dataType: 'json',
            data: {
                chatId: chatId
            },
            success: function(json)
            {
                returnCode(json, function(message) 
                {
                    let data = message.data,
                        count= data.length,
                        chatList = $('#chat-list').html(),
                        num;


                    // 获取会话列表/联系人
                    try{
                        chatList = JSON.parse(chatList);
                        num = chatList.length;
                    }
                    catch(e)
                    {
                        console.log(e, '数据格式有误');
                    }

                    // 取消息头像
                    for (let index = 0; index < count; index++) 
                    {
                        for (let i = 0; i < num; i++) 
                        {
                            if(data[index].form == chatList[i].id)
                            {
                                data[index].headimgurl = chatList[i].headimgurl;
                                break;
                            }
                        }
                    }

                    // 渲染消息
                    $('.content').html( template('msg-template', data) );

                    if(count > 0)
                    {
                        // 会话重置最后一条消息
                        $('[chatid="'+chatId+'"]').find('.desc').first().html(data[count-1].content);
                        $('.desc').find('br').remove();

                        // 滚动条置底
                        $('.content').scrollTop($('.content')[0].scrollHeight);
                    }                    
                });
            }
        });
    };

    // 发起连接
    let websocketHeartbeatJs = new WebsocketHeartbeatJs({
        url: 'ws://im.skeep.cc:8282',
        pingTimeout: 15000,
        pongTimeout: 10000,
        reconnectTimeout: 2000,
        pingMsg: 'ping'
    });

    // 接收消息
    websocketHeartbeatJs.onmessage = function (e) {

        let json = JSON.parse(e.data);

        // 客户端绑定
        if(json.type === 'bind')
        {
            $.ajax({
                type: 'POST',
                url: '/index/bind/create',
                data: {
                    client_id: json.msg
                },
                dataType: 'json',
                success: function(box)
                {
                    returnCode(box, function(msg)
                    {
                        if(msg.code == 200)
                        {
                            // 拉取会话
                            getChat();
                        }
                        else
                        {
                            alert(msg.msg);
                        }
                    });
                }
            });
        }

        // 撤回消息通知
        if(json.type == 'back')
        {
            // console.log(json);
            // 若当前会话需拉取消息
            if(  json.chatId == (getCurrentChat()).chatId )
            {
                $('[msgid="'+json.msgId+'"]').find('span').html('<em style="font-size:12px; color:#999;">对方撤回一条消息</em>');
            }
            else
            {
                $('[chatid="'+json.chatId+'"]').find('.desc').first().html('对方撤回一条消息');
            }
        }

        // 消息处理
        if(json.type === 'text')
        {
            let chat = getCurrentChat(),
                num, htm, key = '.toast-' + json.msg;

            // 会话置顶
            chatTop(json.msg);

            // 如果消息为当前会话，则直接所有拉取消息，否则只做提醒并拉取最后一条消息
            if(chat.chatId == json.msg)
            {
                getChatMsg(json.msg);
            }
            else
            {
                /**
                 * 重新生成会话
                 */

                // 如果没有此会话，则重新拉取会话（注：如果传会话ID，则指在新会话上标注提醒）
                if( $(key).length == 0 )
                {
                    getChat(json.msg);
                    return;
                }

                /**
                 * 提醒会话
                 */
                if($(key).html() == '')
                {
                    htm = '<div class="num">1</div>';
                }
                else
                {
                    num = $(key).find('.num').first().html();
                    num = parseInt(num) + 1;
                    htm = num >= 10 ? '<div class="num">··</div>' : '<div class="num">'+num+'</div>';
                }
                
                $(key).html(htm);

                // 取最后一条消息
                $.ajax({
                    type: 'POST',
                    url: '/index/msg/get_last_msg',
                    dataType: 'json',
                    data: {
                        chatId: json.msg
                    },
                    success: function(res)
                    {
                        returnCode(res, function(data) 
                        {
                            if(data.code == 200)
                            {
                                $('[chatid="'+json.msg+'"]').find('.desc').first().html(data.content);
                                $('.desc').find('br').remove();
                            }
                        });
                    }
                });

            }
        }
    };

    // 设置当前会话
    $('.user_list').on('click', 'li', function() 
    {
        let nickname = $(this).attr('nickname'),
            chatId = $(this).attr('chatid'),
            to = $(this).attr('uid');

        // 修改对话框昵称
        $('.nickname').html( nickname );

        // 切换并选中会话状态
        $('.user_list').find('li').removeClass();
        $(this).addClass('user_active');

        // 当前会话数据
        let chat = {
            chatId: chatId,
            to: to
        };

        // 清空消息提醒
        $(this).find('.user_time').html('');

        // 存储设置
        $('#current-chat').html(JSON.stringify(chat));

        // 拉取当前会话消息
        getChatMsg(chatId);
    })

    // 获取当前会话
    var getCurrentChat = function() 
    {
        let chat = $('#current-chat').html();
            
        try{
            chat = JSON.parse(chat);
        }
        catch(e)
        {
            console.log(e, '当前会话不存在');
            chat = {};
        }
        
        return chat;
    };

    // 发送消息
    $('#send').on('click', function() 
    {
        let content = $('#input').val(),
            chat = getCurrentChat();

        let data = {
            msgId: Date.now(),
            chatId: chat.chatId,
            to: chat.to,
            content: content
        };

        // 清空消息
        $('#input').val('');

        $.ajax({
            type: 'POST',
            url: '/index/send/push',
            dataType: 'json',
            data: data,
            success: function(res)
            {
                returnCode(res, function(data) 
                {
                    if(data.code == 200)
                    {
                        getChatMsg(chat.chatId);

                        // 会话置顶
                        chatTop(chat.chatId);
                    }
                    else
                    {
                        alert(data.msg);
                    }
                });
            }
        });
    })

    // 撤回消息
    $('.content').on('click', '.back', function() 
    {
        var node = $(this).parent(),
            chatId = node.attr('chatId'),
            msgId = node.attr('msgId');

        $.ajax({
            type: 'POST',
            url: '/index/msg/back',
            dataType: 'json',
            data: {
                chatId: chatId,
                msgId: msgId
            },
            success: function(res)
            {
                returnCode(res, function(data) 
                {
                    if(data.code == 200)
                    {
                        getChatMsg(chatId);
                    }
                    else
                    {
                        alert(data.msg);
                    }
                });
            }
        });
    })

    // 输入框绑定回车键
    document.getElementById('input').addEventListener('keydown', function(event) 
    { 
        if(!event.shiftKey && event.keyCode == 13)
        {
            event.cancelBubble=true;
            event.preventDefault();
            event.stopPropagation();
            $('#send').click();
            $('.face').hide();
        }  
    });
})