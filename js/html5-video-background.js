(function ($) {
  'use strict';

  $(function () {
    var video = $('#vidbg_fullscreen')[0],
      videoControls = $('#vidbg_controls'),
      playPause = $('#vidbg_play_pause'),
      mute = $('#vidbg_mute'),
      close = $('#vidbg_close'),
      playClicked = false,
      isHidden = false;

    playPause.on('click', function () {
      if (!video.paused) {
        playPause.toggleClass('icon-pause');
        playPause.toggleClass('icon-play');
        video.pause();
      } else {
        playPause.toggleClass('icon-pause');
        playPause.toggleClass('icon-play');
        video.play();
      }
    });

    mute.on('click', function () {
      if (video.muted) {
        mute.toggleClass('icon-volume-off');
        mute.toggleClass('icon-volume-up');
        video.muted = false;
      } else {
        mute.toggleClass('icon-volume-up');
        mute.toggleClass('icon-volume-off');
        video.muted = true;
      }
    });

    $(video).on('ended', function () {
      playPause.toggleClass('icon-pause');
      playPause.toggleClass('icon-play');
    });

    $(document).mouseleave(function (e) {
      var from = e.relatedTarget || e.toElement;
      if ((!from || from.nodeName === 'HTML') && !isHidden && playClicked) {
        videoControls.fadeOut(400, function () {
          isHidden = true;
        });
      }
    });

    $(document).mouseenter(function (e) {
      var from = e.relatedTarget || e.toElement;
      if ((!from || from.nodeName !== 'HTML') && isHidden && playClicked) {
        videoControls.fadeIn(400, function () {
          isHidden = false;
        });
      }
    });

    $(close).on('click', function () {
      playClicked = false;
      videoControls.hide();
      close.hide();
      video.pause();
      video.currentTime = 0.1;
      video.play();
      video.muted = true;
    });
  });
})(jQuery);
