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
  if ($('.owl-carousel').length) {
    $('.owl-carousel').owlCarousel({
      animateOut: 'fadeOut',
      items: 1,
      loop: true,
      autoplay: true,
      autoplayHoverPause: true,
      dots: true,
      nav: false
    });
  }

  /* === Scroll animations === */
  new WOW({ mobile: false }).init();

})(jQuery);


/* ============================================================
   Session & Auth UI (front-office)
   ============================================================ */

jQuery(function ($) {
  'use strict';

  var currentUser = null;

  function applyAuthUI(user) {
    currentUser = user || null;

    if (currentUser) {
      $('#nav-auth-guest').hide();
      $('#nav-user-name').text(currentUser.name);
      $('#nav-auth-user').show();
      $('#nav-auth-logout').show();

      if (currentUser.role === 'admin') {
        $('#nav-auth-admin').show();
      } else {
        $('#nav-auth-admin').hide();
      }

      // Appointment form gating on index.html
      if ($('#appointment-form').length) {
        $('#appointment-auth-gate').hide();
        $('#appointment-form').show();
        $('#appointment-user-label').text(currentUser.name + ' (' + currentUser.email + ')');
        $('#appointment-user-bar').show();

        var $name = $('#name');
        var $email = $('#email');
        var $phone = $('#phone');
        if ($name.length && !$name.val()) $name.val(currentUser.name || '');
        if ($email.length && !$email.val()) $email.val(currentUser.email || '');
        if ($phone.length && !$phone.val() && currentUser.phone) $phone.val(currentUser.phone);
      }

      // Session banner on connexion.html
      if ($('#already-logged-box').length) {
        $('#session-user-label').text('Connecté en tant que ' + currentUser.name);
        $('#session-user-email').text(currentUser.email + (currentUser.role === 'admin' ? ' (Administrateur)' : ' (Patient)'));
        if (currentUser.role === 'admin') {
          $('#session-admin-link').show();
        } else {
          $('#session-admin-link').hide();
        }
        $('#already-logged-box').show();
      }
    } else {
      $('#nav-auth-guest').show();
      $('#nav-auth-user').hide();
      $('#nav-auth-admin').hide();
      $('#nav-auth-logout').hide();

      if ($('#appointment-form').length) {
        $('#appointment-form').hide();
        $('#appointment-user-bar').hide();
        $('#appointment-auth-gate').show();
      }

      if ($('#already-logged-box').length) {
        $('#already-logged-box').hide();
      }
    }
  }

  function loadSession() {
    $.getJSON('/api/auth/me').done(function (res) {
      applyAuthUI(res && res.authenticated ? res.user : null);
    }).fail(function () {
      applyAuthUI(null);
    });
  }

  function performLogout(event) {
    if (event) event.preventDefault();
    $.ajax({
      url: '/api/auth/logout',
      method: 'POST'
    }).always(function () {
      applyAuthUI(null);
      if ($('#appointment-form').length) {
        $('#appointment-form')[0].reset();
      }
    });
  }

  $(document).on('click', '#front-logout-btn, #appointment-logout-link, #banner-logout-btn', performLogout);

  loadSession();

  /* ============================================================
     Connexion & Inscription (front/connexion.html)
     ============================================================ */
  var $loginForm = $('#login-form');
  if ($loginForm.length) {
    $loginForm.on('submit', function (event) {
      event.preventDefault();
      $('#login-ok, #login-err').hide();

      var email = $('#login-email').val().trim();
      var password = $('#login-password').val();
      if (!email || !password) {
        $('#login-err').text('Veuillez saisir votre e-mail et votre mot de passe.').slideDown();
        return;
      }

      var $btn = $('#login-submit').prop('disabled', true);
      $.ajax({
        url: '/api/auth/login',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ email: email, password: password })
      }).done(function (res) {
        $('#login-ok').text('Connexion réussie, redirection...').slideDown();
        window.location.href = (res && res.redirect) || '/index.html#appointment';
      }).fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Identifiants incorrects.';
        $('#login-err').text(msg).slideDown();
      }).always(function () {
        $btn.prop('disabled', false);
      });
    });
  }

  var $regForm = $('#register-form');
  if ($regForm.length) {
    $regForm.on('submit', function (event) {
      event.preventDefault();
      $('#reg-ok, #reg-err').hide();

      var name = $('#reg-name').val().trim();
      var email = $('#reg-email').val().trim();
      var phone = $('#reg-phone').val().trim();
      var password = $('#reg-password').val();
      var confirmPassword = $('#reg-confirm').val();

      if (name.length < 2) {
        $('#reg-err').text('Veuillez saisir votre nom complet (2 caractères minimum).').slideDown();
        return;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        $('#reg-err').text('Adresse e-mail invalide.').slideDown();
        return;
      }
      if (password.length < 8 || !/[A-Za-z]/.test(password) || !/\d/.test(password)) {
        $('#reg-err').text('Le mot de passe doit contenir au moins 8 caractères, avec des lettres et des chiffres.').slideDown();
        return;
      }
      if (password !== confirmPassword) {
        $('#reg-err').text('Les mots de passe ne correspondent pas.').slideDown();
        return;
      }

      var $btn = $('#reg-submit').prop('disabled', true);
      $.ajax({
        url: '/api/auth/register',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          name: name,
          email: email,
          phone: phone,
          password: password,
          confirmPassword: confirmPassword
        })
      }).done(function () {
        $regForm[0].reset();
        $('#reg-ok').text('Compte créé avec succès ! Connectez-vous maintenant à gauche avec votre e-mail et votre mot de passe.').slideDown();
        $('#login-email').val(email);
        $('#login-password').val('').focus();
      }).fail(function (xhr) {
        var errors = (xhr.responseJSON && xhr.responseJSON.errors) ||
          [(xhr.responseJSON && xhr.responseJSON.error) || 'Erreur lors de la création du compte.'];
        $('#reg-err').text(errors.join(' ')).slideDown();
      }).always(function () {
        $btn.prop('disabled', false);
      });
    });
  }

  /* ============================================================
     Appointment form – POST /api/appointments
     ============================================================ */
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
      if (currentUser) {
        fields.namedItem('name').value = currentUser.name || '';
        fields.namedItem('email').value = currentUser.email || '';
        if (currentUser.phone) fields.namedItem('phone').value = currentUser.phone;
      }
    }).fail(function (xhr) {
      if (xhr.status === 401) {
        applyAuthUI(null);
        return;
      }
      var errors = (xhr.responseJSON && xhr.responseJSON.errors) ||
        [(xhr.responseJSON && xhr.responseJSON.error) || '[Erreur serveur, réessayez.]'];
      $('#form-err').text(errors.join(' ')).slideDown();
    }).always(function () {
      $button.prop('disabled', false);
    });
  });
});
