// POST PAGE CAROUSEL
    $(document).ready(function(){
        $('.postImagesCarousel').slick({
          lazyLoad: 'ondemand',
          slidesToShow: 1,
          slidesToScroll: 1,
          adaptiveHeight: true,
          dots: true,
          infinite: true,
          speed: 500,
          fade: true,
          arrows: true,
          cssEase: 'linear'
        }).on('lazyLoaded', function(event, slick, image, imageSource) {
          slick.resize();
        });
    });

// LIKE THE POST
    $('.likePost').on('click', function(event) {
        let path = $(this).data('path');
        $.ajax({
            type: 'POST',
            url: path,
            success: function(data) {
                $('#likePostSpan').html('<i class="fas fa-heart fa-lg" style="color:red;"></i> '+data);
            }
        });
    });

// TOGGLE POST AS FAVORITE
    $('.favoritePost').on('click', function(event) {
        let path = $(this).data('path');
        $.ajax({
            type: 'POST',
            url: path,
            success: function(data) {
                $('#favoritePost').toggleClass('fas far');;
            }
        });
    });


// FOR YOUTUBE VIDEOS DISPLAY
  function labnolIframe(div) {
    var iframe = document.createElement('iframe');
    iframe.setAttribute(
      'src',
      'https://www.youtube.com/embed/' + div.dataset.id + '?autoplay=1&rel=0'
    );
    iframe.setAttribute('frameborder', '0');
    iframe.setAttribute('allowfullscreen', '1');
    iframe.setAttribute(
      'allow',
      'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture'
    );
    div.parentNode.replaceChild(iframe, div);
  }

// FOR YOUTUBE VIDEOS DISPLAY
  function initYouTubeVideos() {
    var playerElements = document.getElementsByClassName('youtube-player');
    for (var n = 0; n < playerElements.length; n++) {
      var videoId = playerElements[n].dataset.id;
      var hasCookieConsent = playerElements[n].dataset.cookie;
      var div = document.createElement('div');
      div.setAttribute('data-id', videoId);
      var thumbNode = document.createElement('img');
      thumbNode.src = '//i.ytimg.com/vi/ID/hqdefault.jpg'.replace(
        'ID',
        videoId
      );
      div.appendChild(thumbNode);
      var playButton = document.createElement('div');
      playButton.setAttribute('class', 'play');
      div.appendChild(playButton);
      div.onclick = function () {
          if (hasCookieConsent == 1) {
           labnolIframe(this);
          }
      };
      playerElements[n].appendChild(div);
    }
  }

// FOR YOUTUBE VIDEOS DISPLAY
  document.addEventListener('DOMContentLoaded', initYouTubeVideos);
