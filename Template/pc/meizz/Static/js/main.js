
jQuery(".banner").slide({
    mainCell: ".bd ul",
    autoPlay: !0
}),
jQuery(".banner2").slide({
    mainCell: ".bd ul",
    autoPlay: !0
}),
//热门商品
jQuery('.home_hot .title p a').click(function(){
    var catId = $(this).attr('data-catid');
    $(this).parent("p").children('a').removeClass('on');
    $(this).addClass('on');
    $(this).parents("section").children('.con').hide();
    $('.cat_'+catId).fadeIn(500);
}),

jQuery(".slideTxtBox").slide(),
$(".section13 .percent span").css("width",
function() {
    return $(this).parent().siblings(".info").children("span").text()
}),
$(".side-menu h3").click(function() {
    $(this).hasClass("on") ? ($(this).removeClass("on"), $(this).siblings("ul").slideUp("300")) : ($(".side-menu h3").removeClass("on"), $(".side-menu ul").slideUp(300), $(this).addClass("on"), $(this).siblings("ul").slideDown("300"))
}),
$(".page17 .btn-detail").click(function() {
    return $(this).parent().parent().parent(".item").addClass("on"),
    $(this).parent().parent().siblings(".item-bd").slideDown("300"),
    !1
}),
$(".page17 .close").click(function() {
    return $(this).parent().parent(".item").removeClass("on"),
    $(this).parent().slideUp("300"),
    !1
}),
$(".page14-rule .btn-rule").click(function() {
    return $(".page14-rule .con").slideToggle(300),
    $(this).toggleClass("on"),
    !1
}),
$(".page14-apply .btn-list").click(function() {
    return $(".page14-list").slideToggle("300"),
    !1
}),
$(".page14-list .btn-sure").click(function() {
    return  $("#order_id").val($(this).parent().siblings(".td8").val()),
    $("#goods_id").val($(this).parent().siblings(".td9").val()),
    $("#spec_key").val($(this).parent().siblings(".td10").val()),
    $(".page14-apply .td2").addClass("tl").text($(this).parent().siblings(".td3").children("p").text()),
    $(".page14-apply .td3").text($(this).parent().siblings(".td4").text()),
    $(".page14-apply .td4").text($(this).parent().siblings(".td2").text()),
    $(".page14-apply .td1").addClass("on").children("span").text($(this).parent().siblings(".td1").text()),
    $(".page14-list").slideUp(300),
    !1
}),
$(".page14-apply .btn-change").click(function() {
    return $(".page14-apply .td2").removeClass("tl").text("-"),
    $(".page14-apply .td3").text("-"),
    $(".page14-apply .td4").text("-"),
    $(".page14-apply .td1").removeClass("on"),
    !1
}),
/*$("body").delegate(".jia-jian .jia", "click",
function() {
    $(this).siblings(".num").val(parseInt($(this).siblings(".num").val()) + 1)
}),
$("body").delegate(".jia-jian .jian", "click",
function() {
    $(this).siblings(".num").val() > 1 && $(this).siblings(".num").val(parseInt($(this).siblings(".num").val()) - 1)
}),*/
$(".page8-rule .btn-more").click(function() {
    return $(this).parent().siblings(".con").slideToggle(300),
    !1
}),
$(".product-filter .operate .btn1").click(function() {
    return $(".product-filter .ul2").slideDown("300"),
    $(this).parent().addClass("on"),
    !1
}),
$(".product-filter .operate .btn2").click(function() {
    return $(".product-filter .ul2").slideUp("300"),
    $(this).parent().removeClass("on"),
    !1
}),
$(".product-filter li i").click(function() {
    $(this).siblings("i").removeClass("on"),
    $(this).addClass("on")
}),
$(".tab37 .btn1").click(function() {
    return $(this).siblings().removeClass("on"),
    $(this).addClass("on"),
    $(".page37-2").fadeOut("0"),
    $(".page37-1").fadeIn(0),
    !1
}),
$(".tab37 .btn2").click(function() {
    return $(this).siblings().removeClass("on"),
    $(this).addClass("on"),
    $(".page37-1").fadeOut("0"),
    $(".page37-2").fadeIn(0),
    !1
}),
$(".product-filter37 li i").click(function() {
    $(this).siblings("i").removeClass("on"),
    $(this).addClass("on")
	var datakey = $(this).attr('data-key');
	var datapkey = $(this).attr('data-pkey');
	ChangeState(datakey,datapkey);
}),
$(".product-filter371 li em").click(function() {
    $(this).siblings("em").removeClass("on"),
    $(this).addClass("on")
	var datakey = $(this).attr('data-key');
	var datapkey = $(this).attr('data-pkey');
	ChangeState(datakey,datapkey);
}),
$(".product-filter371 .operate .btn1").click(function() {
    return $(".product-filter371 .ul2").slideDown("300"),
    $(this).parent().addClass("on"),
    !1
}),
$(".product-filter371 .operate .btn2").click(function() {
    return $(".product-filter371 .ul2").slideUp("300"),
    $(this).parent().removeClass("on"),
    !1
}),
$(".product-detail-right .more-choose i,.product-detail-right .choose1 i ").click(function() {
    $(this).siblings("i").removeClass("on"),
    $(this).addClass("on")
}),
$(".side-classify h3").click(function() {
    $(this).next(".con").slideToggle("300")
}),
$(".product-details-title a").click(function() {
    var indexNum = parseInt($(this).index());
    var t = ".product-details-" + (indexNum + 1);
    $(t).hasClass("on") || ($(this).siblings().removeClass("on"),
    $(this).addClass("on"),
    $(".product-details-1,.product-details-2,.product-details-3,.product-details-4,.product-details-5").fadeOut("0").removeClass("on"));
    if(indexNum == 1){
        $('.product-discuss h3').hide();
        $(t).fadeIn(300).addClass("on");
    }else{
        $('.product-discuss h3').show();
        $(t).fadeIn(300).addClass("on");
    }
    if(indexNum == 2 || indexNum == 3){
        // ajaxComment(1,1);// ajax 加载评价列表
        $('.product-details-2').fadeIn(300);
    }
    if(indexNum == 4){
        $('.product-details-2').children('#ajax_comment_return').hide();
        $('.product-details-2').fadeIn(300);
    }
    return !1
}),
$(".product-detail-right .ul2 em").click(function() {
    $(this).siblings("em").removeClass("on"),
    $(this).addClass("on")
}),

$(".hd-nav .menu").click(function() {
    return $(this).toggleClass("on"),
    $(".menu-alert").slideToggle(300),
    !1
}),
$(".page48 .con em").click(function() {
    $(this).siblings("em").removeClass("on"),
    $(this).addClass("on")
}), $(".page56 .part1 span").click(function () {
    $(this).siblings("span").removeClass("on"), $(this).addClass("on")
}), $("body").delegate(".radio58", "click", function () {
    $(this).parent().siblings().children(".radio58").removeClass("on"), $(this).addClass("on")
});