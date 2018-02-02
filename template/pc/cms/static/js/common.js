$(function () {
  $(".has-sub").mouseover(function () {
    $(this).find(".sub-nav").css({
      "display": "block",
      "height": "168px",
      "opacity": "1",
      "overflow": "inherit"
    });
  });
  
  $(".has-sub").mouseout(function () {
    $(this).find(".sub-nav").css({
      "height": "0",
      "opacity": "0",
      "overflow": "hidden"
    });
  })
  
  $(".has-subs").mouseover(function () {
    $(this).find(".sub-navs").css({
      "display": "block",
      "height": "168px",
      "opacity": "1",
      "overflow": "hidden"
    });
  });
  
  $(".has-subs").mouseout(function () {
    $(this).find(".sub-navs").css({
      "height": "0",
      "opacity": "0",
      "overflow": "hidden"
    });
  })
  
  // go top
  $('#topBtn').click(function () {
    $('html,body').animate({
      scrollTop: 0
    }, 1000);
  });


//  产品介绍
//   获取li 宽度
  window.onresize = function () {
    var liW = $('.intro_pd li').width();
    $(".intro_pd li").css('height', liW)
    var h = $(".intro_pd li").css('height', liW)
  }
  window.onresize()
})

























