$(function () {
    "use strict";
    function t() {
        $("#mySwiper .animate").removeAttr("style")
    }

    function e(t) {
        t.find(".animate:first").velocity({translateY: 0}, 0).velocity({translateY: 0, opacity: 1}, 800, function () {
            $(this).delay(1500).velocity({scale: 1}, 0).velocity({scale: 5, opacity: 0}, 800, function () {
                o.slideNext()
            })
        })
    }

    function i() {
        $(".img21,.img22,.img23").velocity({scale: 0}, 0).velocity({scale: 1, opacity: 1}, 800, function () {
            $(".txt22").velocity({translateY: 30}, 0).velocity({translateY: 0, opacity: 1}, 800, function () {
                $(".txt21").velocity({translateY: 30}, 0).velocity({translateY: 0, opacity: 1}, 800, function () {
                    $(".txt23").velocity({translateY: 30}, 0).velocity({translateY: 0, opacity: 1}, 800, function () {
                        $(".txt24").velocity({translateY: 30}, 0).velocity({translateY: 0, opacity: 1}, 800)
                    })
                })
            })
        })
    }

    function n() {
        $(".music").addClass("play"), document.getElementById("mp3").play(), c = setInterval(function () {
            $(".musics").append("<span></span>"), $(".musics span:last").velocity({
                rotate: 180 * Math.random(),
                scale: 1 * Math.random() + .8,
                x: 0,
                y: 0
            }, 0).velocity({right: -40, bottom: -40, opacity: 0}, 2e3 * Math.random() + 500, function () {
                $(this).remove()
            })
        }, 600)
    }

    var a = 0, o = new Swiper("#mySwiper", {
        loop: !1,
        noSwiping: !0,
        allowSwipeToNext: !0,
        allowSwipeToPrev: !0,
        queueEndCallbacks: !0,
        onSlideChangeEnd: function () {
            if (a != o.activeIndex) {
                t(), a = o.activeIndex;
                var n = $("#mySwiper .swiper-wrapper>.swiper-slide").eq(o.activeIndex);
                switch (window.location.hash = "", o.activeIndex) {
                    case 0:
                        e(n);
                        break;
                    case 1:
                        window.location.hash = o.activeIndex, i(n)
                }
            }
        }
    }), s = document.documentElement.clientWidth;
    window.onload = function () {
        for (var t = $("body").find("img"), i = t.length, n = 0, o = 0, c = $(".percent"), l = 0; i > l; l++)t.eq(l).attr("src", t.eq(l).attr("loadsrc")).load(function () {
            $(this).removeAttr("loadsrc"), n++, o = parseInt(100 * n / i), c.html(o + "%"), 100 == o && setTimeout(function () {
                $(".loading").velocity({translateX: -s}, 800, function () {
                    setTimeout($(this).remove(), 1500), e($("#mySwiper>.swiper-wrapper>.swiper-slide").eq(a))
                })
            }, 800)
        })
    }, $(".fingerprint").velocity({translateY: -50}, 0).velocity({translateY: 0}, 800, function () {
        $(this).find(".scanning").addClass("scanning-animate"), $(".enter .animate").velocity({translateY: 50}, 0).velocity({
            translateY: 0,
            opacity: 1
        }, 800)
    }), $(document).on("click", ".fingerprint", function () {
        $(".enter").velocity({scale: 3, opacity: 0}, 800, function () {
            $(this).hide(), e($("#mySwiper>.swiper-wrapper>.swiper-slide").eq(a))
        })
    }), $(document).on("press", ".fingerprint", function () {
        $(".enter").velocity({scale: 3, opacity: 0}, 800, function () {
            $(this).hide(), e($("#mySwiper>.swiper-wrapper>.swiper-slide").eq(a)), $(".page").show()
        })
    }), n();
    var c;
    $(".music").on("click", function () {
        $(this).hasClass("play") ? ($(this).removeClass("play"), $("#music>span").addClass("zshow").html("关闭"), setTimeout(function () {
            $("#music>span").removeClass("zshow")
        }, 1e3), document.getElementById("mp3").pause(), clearInterval(c)) : ($("#music>span").addClass("zshow").html("开启"), setTimeout(function () {
            $("#music>span").removeClass("zshow")
        }, 1e3), n())
    })
});