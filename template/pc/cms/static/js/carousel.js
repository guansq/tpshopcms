
var imgNameArr = [
  '../image//1.jpg',
  '../image//2.jpg',
  '../image/3.jpg',
  '../image/4.jpg',
  '../image/5.jpg'
]
var selectionW = $('.selection').width()
var selectionH = $('.selection').height()
var width = selectionW*0.55;
var height = selectionH;
// alert(height)
var time = 3000;
var $element = $('.selection .r');

//1、 添加轮播图图片
function addImg() {
  var html = '';
  for (var i = 0; i < imgNameArr.length; i++) {
    html += '<img src="' + imgNameArr[i] + '">';
  }
  $element.html(html);
}


// 2、设置轮播图 img的样式
function adjustImgStyle() {
  $element.css({
    position: 'relative',
    width: width,
    height: height,
    overflow: 'hidden',
    display:'block'
  })
  // alert(height*0.6)
  $element.children('img').css({
    position: 'absolute',
    top: '0',
    left: '0',
    width: width * 0.85,
    height: height*0.6
  }).css('left', function (index) {
    // index:当前标签的索引
    return index * width;
  });
}


// 3、实现图片切换
var interval = null;

function changeImg() {
  interval = setInterval(function () {
    $element.children('img').css('left', '-=' + width);
    // 找到正在显示的图片的索引，修改对应的圆点颜色
    // 正在显示的图片：left=0
    // each：遍历jquery对象中每一个元素，类似for循环
    $element.children('img').each(function () {
      var left = $(this).position().left;
      if (left == 0) {
        // 正在显示的图片的索引
        var index = $(this).index();
        $element.children('.dot').children('li')
        .eq(index).css({
          'color': '#F7750B',
          'marginLeft': 40
        })
        .siblings().css({
          'color': 'lightgray',
          'marginLeft': 0
        });
        
        $element.children('.listimg').children('li')
        .eq(index).css({
          'border': '1px solid #F7750B',
        })
        .siblings().css({
          'border': 'none',
        });
      }
    })
    // 边界判断
    var left = $element.children('img').eq(0).position().left;
    if (left < (width * (imgNameArr.length - 1) * (-1))) {
      resetImgAndDot();
    }
  }, time)
}

//6、 重置图片、圆点样式为初始状态
function resetImgAndDot() {
  // 重置图片的位置
  $element.children('img')
  .css('left', function (index) {
    // index:当前标签的索引
    return index * width;
  })
  //圆点重置
  $element.children('.dot').children('li')
  .eq(0).css('color', '#F7750B')
  .siblings().css('color', 'lightgray');
  
  //图片列表重置
  $element.children('.listimg').children('li')
  .eq(0).css('border', '1px solid #F7750B')
  .siblings().css('border', 'none');
}

// 4、添加圆点
function addDot() {
  $element.append('<ul class="dot"></ul>');
  var html = '';
  for (var i = 0; i < imgNameArr.length; i++) {
    html += '<li>' + (i + 1) + '&emsp;&emsp;好地方第三方但是' + '</li>';
  }
  $element.children('.dot').html(html);
}

// 4.1、添加分页器列表
function addImgList() {
  $element.append('<ul class="listimg"></ul>');
  var html = '';
  for (var i = 0; i < imgNameArr.length; i++) {
    html += '<li>' + '<img src="' + imgNameArr[i] + '">' + '</li>';
  }
  $element.children('.listimg').html(html);
}

// 5、设置圆点样式
function adjustDotStyle() {
  $element.children('.dot').children('li').css({
    listStyle: 'none',
    color: '#F7750B',
    marginLeft: '0px',
    marginBottom: '10px',
    fontSize: '16px'
  }).eq(0).css('color', 'rgb(145, 145, 148)');
  // var pWidth = $element.width();
  // var pHeight = $element.height();
  // var sWidth = $element.children('.dot').width();
  // var sHeight = $element.children('.dot').height();
  $element.children('.dot').css({
    position: 'absolute',
    width: width,
    top: height*0.6 + 20,
    paddingLeft: 0
  })
}

// 5.1、添加分页器列表样式
function adjustListStyle() {
  $element.children('.listimg').children('li').css({
    listStyle: 'none',
    border: '#f7750b',
    marginLeft: '0px',
    marginBottom: '1px',
    padding:0,
    height:(1/imgNameArr.length)*(width*0.6 - 40)
  }).eq(0).css('border', 'none');
  $element.children('.listimg').css({
    position: 'absolute',
    width: width * 0.14,
    top: 0,
    right: 0,
    paddingLeft: 0,
    margin:0
  })
  $element.children('.listimg').children('li').children('img').css({
    width: '100%',
    height:'100%'
  })
}

// 7、鼠标点击圆点上切换图片
function changeDot() {
  $element.children('.dot').children('li')
  .click(function () {
    $(this).css({
      'color': '#F7750B',
      'marginLeft': 40
    })
    .siblings().css({
      'color': 'lightgray',
      'marginLeft': 0
    });
    // 圆点的索引
    var index = $(this).index();
    $element.children('img').css('left', function (i) {
      //i:图片的索引
      return (i - index) * width;
    });
    
    $element.children('.listimg').children('li')
    .eq(index).css({
      'border': '1px solid #F7750B',
    })
    .siblings().css({
      'border': 'none',
    });
    // 清除定时器
    clearInterval(interval);
    // 重建定时器
    changeImg();
  })
}

// 7.1、鼠标点击图片列表上切换轮播图片
function changeImgList() {
  $element.children('.listimg').children('li')
  .click(function () {
    $(this).css({
      'border': '1px solid #f7750b',
    })
    .siblings().css({
      'border': 'none',
// 'marginLeft': 0
    });
// 圆点的索引
    var index = $(this).index();
    $element.children('img').css('left', function (i) {
//i:图片的索引
      return (i - index) * width;
    });
    
    $element.children('.dot').children('li')
    .eq(index).css({
      'color': '#F7750B',
      'marginLeft': 40
    })
    .siblings().css({
      'color': 'lightgray',
      'marginLeft': 0
    });

// 清除定时器
    clearInterval(interval);
// 重建定时器
    changeImg();
  })
}

//8、 轮播图
function carousel() {
  addImg();
  adjustImgStyle();
  changeImg();
  addDot();
  adjustDotStyle();
  changeDot();
  addImgList();
  changeImgList();
  adjustListStyle();
}

carousel();
