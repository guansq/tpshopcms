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
  $('#topBtn').click(function (event) {
    console.log(this)
    $('html,body').animate({
      scrollTop: 0
    }, 1000);
  });


//  产品介绍
//   获取li 宽度
  window.onresize = function () {
    var liW = $('.intro_pd li').width();
    $(".intro_pd li").css('height', liW)
    var h = $(".intro_pd li").css('height', liW);
    //    图片大小
    var width = $('.bag').width();
    $('.bag').css('height', width);
  }
  window.onresize();
});

// 回到顶部按钮
var winH = $(window).height();

window.onscroll = function () {
  var scrollTop = $(document).scrollTop();
  var DH = $(document.body).height();
  // console.log(scrollTop);
  // console.log(winH);
  if(scrollTop > 300){
     $('.topBtn').addClass('show')
  }else{
    $('.topBtn').removeClass('show')
  }
};




















