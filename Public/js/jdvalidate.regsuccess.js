function isEmail(str) {
    return new RegExp("^\\w+((-\\w+)|(\\.\\w+))*\\@[A-Za-z0-9]+((\\.|-)[A-Za-z0-9]+)*\\.[A-Za-z0-9]+$").test(str);
}
//��ʼ���ʼ�Url
function initEmailLoginUrl(email) {
    var loginUrl = getEmailLoginUrl(email);
    if (loginUrl != null) {
        $("#emailLogin").attr("href", loginUrl);
        $("#emailLogin").show();
    } else {
        $("#emailLogin").hide();
    }
}
var emailLoginUrlArrar = ['@gmail.com=http://mail.google.com/',
    '@163.com=http://mail.163.com/',
    '@126.com=http://mail.126.com/',
    '@hotmail.com=http://www.hotmail.com/',
    '@sina.com=http://mail.sina.com/',
    '@vip.sina.com=http://mail.sina.com/',
    '@tom.com=http://mail.tom.com/',
    '@qq.com=http://mail.qq.com/',
    '@139.com=http://mail.10086.cn/',
    '@msn.com=https://login.live.com/login.srf',
    '@sohu.com=http://mail.sohu.com/'];

function getEmailLoginUrl(email) {

    email = email.toLowerCase();
    if (email == "" || !isEmail(email)) {
        return null;
    }
    var index = email.indexOf("@");
    var emailSurfix = email.substring(index, email.length);
    for (var i = 0; i < emailLoginUrlArrar.length; i++) {
        if (emailLoginUrlArrar[i].indexOf(emailSurfix) == 0) {
            return emailLoginUrlArrar[i].split("=")[1];
        }
    }
    return null;
}

function getKey() {
    return  $("#authKey").val();
}


var oldNick = $("#nicknameInput").val();
(function () {
    var reviseNickname = $('.reg-nickname-revise'),
        regNickname = $('#changeNickname');
    var usernamePrompt = {
        onFocus: "4-20λ�ַ����������ġ�Ӣ�ġ����ּ���_������-�����",
        succeed: "",
        isNull: "�������û��ǳ�",
        error: {
            beUsed: "���ǳ��ѱ�ʹ�ã������",
            badLength: "�ǳƳ���ֻ����4-20λ�ַ�֮��",
            badFormat: "�ǳ�ֻ�������ġ�Ӣ�ġ����ּ���_������-�����",
            fullNumberName: "�ǳƲ���ȫΪ����",
            bannedWord: "�ǳư����˷Ƿ���"
        }
    }
    regNickname.click(function () {
        var self = $(this);
        $("#username_error").empty();
        self.parent().hide();
        reviseNickname.show().focus();
        return false;
    });
    //focus
    reviseNickname.find('.text').focus(function () {
        $(this).addClass('hover');
        if ($('#username_error').length <= 0) {
            var div = $('<div id="username_error"></div>');
            $(this).parent().append(div);
        }
        var uError = $('#username_error');
        uError.html(usernamePrompt.onFocus);
        uError.addClass('focus').removeClass('error');
    });
    reviseNickname.find('.text').blur(function () {
        $(this).removeClass('hover');
        var uError = $('#username_error');
        uError.html('');
    });
    //nickname save
    reviseNickname.find('.j_save').click(function () {
        nicknameParentNode = regNickname.parent();
        var nickName = reviseNickname.find('.text').val();
        var username = $.trim(nickName);
        if (username == oldNick) {
            $("#orgNick").html(username);
            nicknameParentNode.show();
            reviseNickname.hide();
            oldNick = username;
            return;
        }
        var div = $('#username_error');
        if (div.length <= 0) {
            var div = $('<div id="username_error"></div>');
            $(this).parent().append(div);
        }

        if (!userCheck(username)) {
            return;
        }
        div.html("<span style='color:#999'>�����С���</span>");
        $.getJSON("../validate/newNickname?nickname=" + escape(username) + "&k=" + getKey() + "&r=" + Math.random(), function (date) {
            if (date.success == 1) {
                $("#orgNick").html(username);
                $("#safeNick").html(date.safeNick);
                nicknameParentNode.show();
                reviseNickname.hide();
                hello();
                oldNick = username;
            }
            if (date.success == 0) {
                div.html(usernamePrompt.error.beUsed.replace("{1}", username));
                return;
            }
            if (date.success == -5) {
                div.html(usernamePrompt.error.bannedWord);
                return;
            }
            if (date.success == -1) {
                div.html("ϵͳ�쳣�����Ժ�����");
                return;
            }
            if (date.success == -4) {
                window.location.href = "http://reg.jd.com/reg/expire";
                return;
            }
        })
    });

    function badFormat(str) {
        return new RegExp("^[A-Za-z0-9_\\-\\u4e00-\\u9fa5]+$").test(str);
    }

    // �û�����֤
    function userCheck(username) {
        var div = $('#username_error');
        var reg = /^[A-Za-z0-9_\\-\\u4e00-\\u9fa5]+$/; //�û���
        var fullNumber = /^[0-9]+$/ //����
        div.removeClass('focus').addClass('error');
        if (username == "") {
            div.html(usernamePrompt.isNull);
            return false;
        }
        var len = betweenLength(username.replace(/[^\x00-\xff]/g, "**"), 4, 20);
        if (!len) {
            div.html(usernamePrompt.error.badLength);
            return false;
        }
        else if (badFormat(username) == false) {
            div.html(usernamePrompt.error.badFormat);
            return false;
        }
        else if (fullNumber.test(username)) {
            div.html(usernamePrompt.error.fullNumberName);
            return false;
        }
        return true;
    }

    // max and min length
    function betweenLength(str, _min, _max) {
        return (str.length >= _min && str.length <= _max);
    }

    $('#emailStr').focus(function () {

        $("#emailStr").removeClass().addClass("text focus-color");
        $("#email_error").html("");
        $("#email_focus").html("�����֤���������ø������¼�������һ����롣");
    });
    $('#emailStr').blur(function () {
        $("#email_focus").html("");
        var content = $("#emailStr").val();
        if (content == "�����������õĵ�������") {
            $("#emailStr").removeClass().addClass("text");
        }
    });
    $('#sendEmail').click(function () {
        sendEmail();
    });
    function strTrim(str) {
        return str.replace(/(^\s*)|(\s*$)/g, "");
    }

    function mobileCodeError(content) {
        $("#smsFocusMessage").removeClass().addClass("sms-tips mobileError").html(content);
        $("#smsFocusDiv").removeClass().addClass("item");
    }

    $('#mobileCode').focus(function () {
        $("#smsErrorDiv").removeClass().addClass("item hide");
        $("#smsErrorMessage").html("");
    });
    // �ֻ���֤
    $('#moblie').bind('focus', function () {
        $("#smsErrorDiv").removeClass().addClass("item hide");
        $("#smsErrorMessage").text("");
        $("#smsFocusDiv").removeClass().addClass("item");
        $("#smsFocusMessage").removeClass().addClass("sms-tips mobileFocus").text("�����֤���������ø��ֻ��ŵ�¼�������һ����롣");
    });

    $('#moblie').bind('blur', function () {
        $("#smsFocusDiv").removeClass().addClass("item hide");
        $("#smsFocusMessage").text("");
    });
    $('#send-sms').click(function () {
        var mobile = $('#moblie').val();
        if (mobile == "") {
            mobileCodeError("�������ֻ���");
            return;
        }
        mobile = strTrim(mobile);
        var isMobile = new RegExp("^0?(13|15|17|18|14)[0-9]{9}$").test(mobile);
        if (!isMobile || mobile.length > 11) {
            mobileCodeError("�ֻ������ʽ������������ȷ���ֻ��š�");
            return;
        }
        var self = $(this);
        var data = 'mobile=' + mobile + "&k=" + $("#k").val() + '&r=' + Math.random();
        $.ajax({
            type: "POST",
            url: "../notify/regValidateCode",
            data: data,
            success: function (result) {
                if (result) {
                    var obj = eval(result);
                    if (obj.rs == 1 || obj.remain) {
                        $("#smsErrorMessage").text("");
                        $("#smsFocusDiv").removeClass().addClass("item hide");
                        $("#smsErrorDiv").removeClass().addClass("item hide");
                        if (obj.remain) {
                            $("#successMes").empty().html(obj.remain);
                        } else {
                            $("#successMes").empty().html("��֤���ѷ��ͣ�����ն��š�");
                        }
                        $('#sms-box').show();
                        $('#validateMobileDiv').removeClass().addClass("sms-btn");
                        $("#mobileCode").empty();
                        $('#moblie').attr("disabled", "disabled");
                        $('#send-sms').attr("disabled", "disabled");
                        var i = 120;
                        self.removeClass().addClass('reg-btn1').val(i + '������»�ȡ');
                        var timer = setInterval(function () {
                            i--;
                            self.val(i + '������»�ȡ');
                            if (i <= 0) {
                                clearInterval(timer);
                                self.addClass('reg-btn2').val('��ȡ������֤��');
                                $("#successMes").empty();
                                $('#moblie').attr("disabled", "");
                                $('#send-sms').attr("disabled", "");

                            }
                        }, 1000);
                    }
                    if (obj.rs == -1) {
                        mobileCodeError("�ֻ������ʽ������������ȷ���ֻ��š�");
                    }
                    if (obj.rs == -5) {
                        window.location.href = "http://reg.jd.com/reg/expire";
                        //mobileCodeError("������ʧЧ��������ǰ��<a href='http://safe.jd.com/user/paymentpassword/safetyCenter.action'>��ȫ����</a>������֤��");
                    }
                    if (obj.rs == -7) {
                        mobileCodeError("������֤���ֻ����뵽<a href='http://safe.jd.com/User/paymentpassword/safetyCenter.action' class='emreg-nickname'>�˻���ȫ</a>��鿴��");
                    }
                    if (obj.info) {
                        mobileCodeError(obj.info);
                    }
                    if (obj.rs == -2) {
                        mobileCodeError("���緱æ�����Ժ����»�ȡ��֤��");
                    }
                }
            }
        });
    });

    function clientError(content) {
        $("#smsErrorMessage").html(content);
        $("#smsErrorDiv").removeClass().addClass("item");
        $("#smsErrorDiv").show();
    }

    var flg = false;
    $('#toValidate').click(function () {
        var mobile = $('#moblie').val();
        mobile = $.trim(mobile);
        if (mobile == "") {
            clientError("�������ֻ���")
            return false;
        }
        var mobileCode = $('#mobileCode').val();
        mobileCode = $.trim(mobileCode);
        if (mobileCode == "") {
            clientError("��������֤��")
            return false;
        }
        var k = $("#k").val();
        var data = 'mobile=' + mobile + "&mobileCode=" + mobileCode + "&k=" + k + '&r=' + Math.random();
        $.getJSON("../reg/validateMobile?" + data, function (result) {
                if (result.success == 1) {
                    window.location.href = "http://reg.jd.com/reg/best?ret=" + result.ret;
                    return;
                }
                if (result.success == -1) {
                    window.location.href = "http://www.jd.com"
                    return;
                }
                if (result.success == -2) {
                    clientError("��֤�벻��ȷ���ѹ���");
                    return;
                }
                if (result.success == -3) {
                    clientError("�ֻ���ռ��");
                    return;
                }
                if (result.success == -4) {
                    clientError("ϵͳ�쳣�����Ժ�����");
                    return;
                }
                if (result.success == -5) {
                    clientError("������֤���ֻ����뵽<a href='http://safe.jd.com/User/paymentpassword/safetyCenter.action' class='emreg-nickname'>�˻���ȫ</a>��鿴��");
                    return;
                }
                if (result.success == -7) {
                    window.location.href = "http://reg.jd.com/reg/expire";
                    return;
                }
            }
        );
    });
})();

//����
function sleep(numberMillis) {
    var now = new Date();
    var exitTime = now.getTime() + numberMillis;
    while (true) {
        now = new Date();
        if (now.getTime() > exitTime)    return;
    }
}

//���·����ʼ�
function reSendEmail(email, key) {
    $('#reSendEmailSuccess').hide();
    sleep(500);
    $('#reSendEmailSuccess').removeClass().empty();
    email = $.trim(email);
    if (email == "" || (isEmail(email) == false)) {
        $("#reSendEmailSuccess").removeClass().addClass('check-email-error');
        $("#reSendEmailSuccess").html("��������Ч�������ַ");
        return;
    }
    var unbind = $("#state").val();
    $.getJSON("../notifyuser/email?email=" + (email) + "&k=" + key + "&state=" + unbind+ "&r=" + Math.random(), function (result) {
        if (result.success == 1) {
            $('#reSendEmailSuccess').removeClass().empty().html('��֤�ʼ������·���');
            $('#reSendEmailSuccess').show();
            initEmailLoginUrl(email);
        }
        if (result.success == 0) {
            $('#reSendEmailSuccess').removeClass().addClass('error').empty().html('�������ѱ�ʹ�ã��������������');
            $('#reSendEmailSuccess').show();
        }
        if (result.success == -1) {
            $('#reSendEmailSuccess').removeClass().addClass('error').empty().html('ϵͳ�쳣�����Ժ����� ��');
            $('#reSendEmailSuccess').show();
        }
        if (result.success == -2) {
            $('#reSendEmailSuccess').removeClass().addClass('error').empty().html('�����뷢����֤�ʼ��Ĵ������ޣ�����24Сʱ�����ԣ�');
            $('#reSendEmailSuccess').show();
        }

        if (result.success == -3) {
            window.location.href = "http://reg.jd.com/reg/expire";
            return;
        }
        if (result.success == -4) {
            $('#reSendEmailSuccess').removeClass().addClass('error').empty().html('��������ע�������');
            $('#reSendEmailSuccess').show();
            return;
        }

        if (result.success == -5) {
            $('#reSendEmailSuccess').removeClass().addClass('error').empty().html('��������Ч�������ַ');
            $('#reSendEmailSuccess').show();
            return;
        }
        $('#reSendEmailSuccess').show();
        //setTimeout(hideEmailSendResult, 5000);
    });
}