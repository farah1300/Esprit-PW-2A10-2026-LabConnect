/* ============================================================
   LabConnect – Scripts du site patient
   ============================================================ */

(function ($) {
  'use strict';

  /* === Preloader === */
  $(function () {
    $('.preloader').fadeOut(1000);
  });

  /* === Navigation === */
  // Close the mobile menu after a link is clicked.
  $('.navbar-collapse a').on('click', function () {
    $('.navbar-collapse').collapse('hide');
  });

  /* === Smooth scroll (same-page anchors only) === */
  $('a.smoothScroll').on('click', function (event) {
    var href = $(this).attr('href');
    if (!href || href.charAt(0) !== '#') {
      return;
    }

    var $target = $(href);
    if (!$target.length) {
      return;
    }

    event.preventDefault();
    $('html, body').stop().animate({
      scrollTop: $target.offset().top - 49
    }, 1000);
  });

  /* === Hero carousel === */
  $('.owl-carousel').owlCarousel({
    animateOut: 'fadeOut',
    items: 1,
    loop: true,
    autoplay: true,
    autoplayHoverPause: true,
    dots: true,
    nav: false
  });

  /* === Scroll animations === */
  new WOW({ mobile: false }).init();

})(jQuery);


/* ============================================================
   Appointment form – POST /api/appointments
   ============================================================ */

jQuery(function ($) {
  'use strict';

  var $form = $('#appointment-form');
  if (!$form.length) {
    return;
  }

  /* Set the date picker minimum to today (local time). */
  var dateInput = document.getElementById('date');
  if (dateInput) {
    var today = new Date();
    var month = ('0' + (today.getMonth() + 1)).slice(-2);
    var day = ('0' + today.getDate()).slice(-2);
    dateInput.min = today.getFullYear() + '-' + month + '-' + day;
  }

  $form.on('submit', function (event) {
    event.preventDefault();

    var form = this;
    var $button = $('#cf-submit').prop('disabled', true);
    var fields = form.elements;

    $('#form-ok, #form-err').hide();

    var data = {
      name: fields.namedItem('name').value,
      email: fields.namedItem('email').value,
      date: fields.namedItem('date').value,
      labo: fields.namedItem('labo').value,
      exam: fields.namedItem('exam').value,
      phone: fields.namedItem('phone').value,
      domicile: fields.namedItem('domicile').checked,
      message: fields.namedItem('message').value
    };

    $.ajax({
      url: '/api/appointments',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(data)
    }).done(function () {
      $('#form-ok').slideDown();
      form.reset();
    }).fail(function (xhr) {
      var errors = (xhr.responseJSON && xhr.responseJSON.errors) || ['[Erreur serveur, réessayez.]'];
      $('#form-err').text(errors.join(' ')).slideDown();
    }).always(function () {
      $button.prop('disabled', false);
    });
  });
});
