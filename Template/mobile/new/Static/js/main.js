function timeClock(e) {
    function t() {
        $(".btn-yzm").text("重新发送(" + n + ")"), n--, 0 > n && ($(".btn-yzm").removeClass("disabled"), n = 59, $(".btn-yzm").text("获取验证码"), clearInterval(a))
    }

    var i = $(e);
    if (i.hasClass("disabled"))return !1;
    i.addClass("disabled");
    var n = 59, a = setInterval(t, 1e3);
    return !1
}
TouchSlide({
    slideCell: "#banner",
    titCell: "#hd ul",
    mainCell: "#bd ul",
    effect: "leftLoop",
    autoPlay: !0,
    autoPage: !0,
    switchLoad: "_src",
    interTime:3500,
    pageStateCell:"#pageState"
}),TouchSlide({
    slideCell: "#banner44",
    titCell: "#hd2 ul",
    mainCell: "#bd2",
    effect: "leftLoop",
    autoPlay: !0,
    autoPage: !0,
    switchLoad: "_src",
    interTime:3500,
    pageStateCell:".ddd"
})
    //TouchSlide({
    //slideCell: "#tabBox1", endFun: function (e) {
    //    var t = document.getElementById("tabBox1-bd");
    //    e > 0 && (t.parentNode.style.transition = "200ms")
    //}
//}),

$(".menu").click(function () {
    return $(".alert-menu").slideToggle(300), !1
}), $(".alert-menu2").height($(window).height() - $("header").height() - $(".bnav").height() + 3), $(".alert-menu2 h3").click(function () {
    $(this).toggleClass("on"), $(this).next(".con").slideToggle(300)
}), $("header .menu2").click(function () {
    return $(".overly-menu2").toggle(), $(".alert-menu2").slideToggle(300), !1
}),$("body").delegate(".checkbox,.checkbox2", "click", function () {
    $(this).toggleClass("on")
}), $("body").delegate(".radio", "click", function () {
    $(this).siblings().removeClass("on"), $(this).addClass("on")
}), $(".page2 .btn-switch").click(function () {
    return $(this).toggleClass("on"), $(this).parent().next(".con").slideToggle(300), !1
}), $(".btn-info").click(function () {
    return $(".alert-info").fadeIn(300), !1
}), $(".alert-info").click(function () {
    $(this).fadeOut("300")
}), $(".modify-nickname .clear").click(function () {
    return $(this).siblings("input").val(""), !1
}), $(".my-service .more").click(function () {
    return $(this).hasClass("on") ? ($(this).removeClass("on"), $(this).parent().next().slideUp(300)) : ($(this).addClass("on"), $(this).parent().next().slideDown(300)), !1
}), $(".my-bespoke .close").click(function () {
    return $(this).parent().slideUp(300), !1
}), $(".btn-yzm").click(function () {
    return timeClock(".btn-yzm"), !1
}), $(".product-filter .btn-sort").click(function () {
    return $(".alert-sort").slideToggle(300), !1
}), $(".special-box").height($(window).height() - $(".alert-special .title").height() - $(".alert-special .operate").height() + "px"), $(".luozuan .part2 img,.luozuan .part3 span,.luozuan .part6 span").click(function () {
    $(this).siblings().removeClass("on"), $(this).addClass("on")
}), $(".jietuo .part2 img,.jietuo .part3 span").click(function () {
    $(this).siblings().removeClass("on"), $(this).addClass("on")
}), $(".special-box h2").click(function () {
    $(this).toggleClass("on"), $(this).next("div").slideToggle(300)
}), $(".btn-special").click(function () {
    layer.msg('此功能暂未开放');return
    return $(".alert-special,.overly-special").fadeIn(300), !1
}), $(".overly-special").click(function () {
    $(".alert-special,.overly-special").fadeOut(300)
}), $(".alert-size .li1 em").click(function () {
    $(this).siblings().removeClass("on"), $(this).addClass("on")
}),$(".btn-size").click(function () {
    return $(".alert-size").fadeIn(300), !1
}), $(".alert-size .close").click(function () {
    return $(".alert-size").fadeOut("300"), !1
}), $(".product-details-filter a").click(function () {
    $(this).siblings().removeClass("on"), $(this).addClass("on");
    var e = ".product-details-" + (parseInt($(this).index()) + 1);
    return $(".product-details-1,.product-details-2,.product-details-3").fadeOut("0"), $(e).fadeIn(300), !1
}),
//    $(".btn-discuss,.discuss-more").click(function () {
//    return $(".product-details-filter a").removeClass("on"), $(".product-details-filter a").eq(2).addClass("on"), $(".product-details-1,.product-details-2").fadeOut("0"), $(".product-details-3").fadeIn(300), !1
//}),
 $(".page33-product .percent span").width(function () {
    return $(this).parent().next(".btm").children("i").text()
}),$(".login2").height($(window).height()), $(".page37 .hd a").click(function () {
    return $(this).hasClass("on") ? void 0 : ($(this).siblings().removeClass("on"), $(this).addClass("on"), $(".page37 .bd>div").fadeOut("300", function () {
        $(".page37 .bd>div").removeClass("show")
    }), $(".page37 .bd>div").eq($(this).index()).fadeIn(300), !1)
});

var discuss = new Array("1分", "2分", "3分", "4分", "5分"), isClick = -1;
$(".star i").click(function () {
    $(this).siblings("i").removeClass("on"), $(this).prevAll("i").addClass("on"), $(this).addClass("on"), $(this).siblings("em").text(discuss[$(this).index() - 1]), isClick = 1
}), $(".classification-left").height($(window).height() - $("header").height() - $(".bnav").height()), $(".classification-right").height($(window).height() - $("header").height() - $(".bnav").height()),
    $(".classification-left li").click(function () {
        $(this).siblings().removeClass("on"), $(this).addClass("on"), $(".classification-right>div").removeClass("on");
        var e = $(this).index();
        return $(".classification-right .classification-box").eq(e).addClass("on"), !1
    }),
     $(".page44-hd a").click(function () {
    if (!$(this).hasClass("on")) {
        var e = $(this).index();
        $(this).siblings().removeClass("on"), $(this).addClass("on"), $(".page44-bd>div").fadeOut("0"), 0 == e ? $(".page44-bd .con1").fadeIn(300, function () {
            TouchSlide({
                slideCell: "#banner44",
                titCell: ".hd ul",
                mainCell: ".bd ul",
                effect: "leftLoop",
                autoPlay: !0,
                autoPage: !0,
                switchLoad: "_src"
            })
        }) : $(".page44-bd .con2").fadeIn(300)
    }
    return !1
}), $(".fixed56 .btn-top").click(function (e) {
    return e.preventDefault(), $("html, body").animate({scrollTop: 0}, 1e3), !1
}), $(".fixed56 .btn-made").click(function () {
    return $(".alert-made").fadeIn(300), !1
});
//    $("body").delegate(".radio63", "click", function () {
//    $(this).parent().siblings().children(".radio63").removeClass("on"), $(this).addClass("on")
//});
var currYear = (new Date).getFullYear(), opt = {};
opt.date = {preset: "date"}, opt.datetime = {preset: "datetime"}, opt.time = {preset: "time"}, opt.default = {
    theme: "android-ics light",
    display: "modal",
    mode: "scroller",
    dateFormat: "yyyy-mm-dd",
    lang: "zh",
    showNow: !0,
    nowText: "今天",
    startYear: currYear - 1,
    endYear: currYear + 1
};
var optDateTime = $.extend(opt.datetime, opt["default"]), optTime = $.extend(opt.time, opt["default"]);
//$("#appDateTime").mobiscroll(optDateTime).datetime(optDateTime);