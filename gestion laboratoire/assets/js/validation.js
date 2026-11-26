/* ============================================================
   LabConnect – validation côté client (Module 1)
   ------------------------------------------------------------
   Ce fichier améliore le confort : il affiche immédiatement les
   erreurs en français. Il ne remplace JAMAIS la validation PHP :
   le contrôleur et le Validator restent la seule barrière de
   sécurité (un utilisateur peut désactiver JavaScript).

   Formulaires reconnus grâce à l'attribut data-validate :
     - data-validate="login"  → e-mail + mot de passe
     - data-validate="user"   → nom, prénom, e-mail, téléphone,
                                mot de passe, confirmation, rôle

   data-password-optional="1" sur le formulaire de modification :
   un mot de passe vide conserve l'ancien.
   ============================================================ */
(function () {
  'use strict';

  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  var PHONE_RE = /^[2-9][0-9]{7}$/;
  var LETTER_RE = /[A-Za-zÀ-ÖØ-öø-ÿ]/;
  var DIGIT_RE = /[0-9]/;

  function visualLength(value) {
    // Array.from() compte les caractères Unicode (accents) et non les octets.
    return Array.from(value).length;
  }

  function field(form, name) {
    return form.elements.namedItem(name);
  }

  function groupOf(input) {
    return input.closest ? input.closest('.form-group') : null;
  }

  function errorSlot(input) {
    var group = groupOf(input);
    var slot = group
      ? group.querySelector('[data-error-for="' + input.name + '"]')
      : null;

    if (slot) {
      return slot;
    }

    // Certains formulaires (recherche, changement de rôle) n'utilisent pas
    // de .form-group : le message est alors placé ailleurs dans le formulaire.
    return input.form
      ? input.form.querySelector('[data-error-for="' + input.name + '"]')
      : null;
  }

  function setError(input, message) {
    var group = groupOf(input);
    var slot = errorSlot(input);

    if (group) {
      group.classList.toggle('has-error', message !== '');
    }

    if (slot) {
      slot.textContent = message;
    }

    if (message !== '') {
      input.setAttribute('aria-invalid', 'true');
    } else {
      input.removeAttribute('aria-invalid');
    }
  }

  /**
   * Retourne le message d'erreur du champ, ou '' s'il est valide.
   * Les messages reprennent volontairement ceux du Validator PHP.
   */
  function rulesFor(form, input) {
    var name = input.name;
    var type = form.getAttribute('data-validate');
    var value = input.value;
    var trimmed = value.trim();

    if (name === 'nom' || name === 'prenom') {
      if (trimmed === '') {
        return 'Ce champ est obligatoire.';
      }

      var length = visualLength(trimmed);

      if (length < 2 || length > 60) {
        return 'Ce champ doit contenir entre 2 et 60 caractères.';
      }

      return '';
    }

    if (name === 'email') {
      if (trimmed === '') {
        return "L'adresse e-mail est obligatoire.";
      }

      if (!EMAIL_RE.test(trimmed)) {
        return "L'adresse e-mail est invalide.";
      }

      if (trimmed.length > 190) {
        return "L'adresse e-mail est trop longue (190 caractères maximum).";
      }

      return '';
    }

    if (name === 'telephone') {
      // Facultatif : on ne contrôle que s'il est rempli.
      if (trimmed !== '' && !PHONE_RE.test(trimmed)) {
        return 'Le téléphone doit contenir exactement 8 chiffres et commencer '
          + 'par 2, 3, 4, 5, 6, 7, 8 ou 9.';
      }

      return '';
    }

    if (name === 'q') {
      // Recherche : vide = tous les utilisateurs. La limite de 100
      // caractères est la même que celle vérifiée par le contrôleur PHP.
      if (visualLength(trimmed) > 100) {
        return 'La recherche est limitée à 100 caractères.';
      }

      return '';
    }

    if (name === 'password') {
      if (type === 'login') {
        return value === '' ? 'Le mot de passe est obligatoire.' : '';
      }

      var optional = form.getAttribute('data-password-optional') === '1';

      if (value === '') {
        return optional ? '' : 'Le mot de passe est obligatoire.';
      }

      if (visualLength(value) < 8) {
        return 'Le mot de passe doit contenir au moins 8 caractères.';
      }

      if (!LETTER_RE.test(value)) {
        return 'Le mot de passe doit contenir au moins une lettre.';
      }

      if (!DIGIT_RE.test(value)) {
        return 'Le mot de passe doit contenir au moins un chiffre.';
      }

      return '';
    }

    if (name === 'password_confirm') {
      var password = field(form, 'password');
      var optionalPassword = form.getAttribute('data-password-optional') === '1';

      if (password && password.value === '') {
        if (optionalPassword) {
          return value === '' ? '' : "Saisissez d'abord le mot de passe.";
        }

        return '';
      }

      if (password && value !== password.value) {
        return 'Les deux mots de passe ne correspondent pas.';
      }

      return '';
    }

    if (name === 'role_id') {
      if (input.value === '' || input.value === '0') {
        return 'Le rôle est obligatoire.';
      }

      return '';
    }

    return '';
  }

  function validateField(form, input) {
    var message = rulesFor(form, input);
    setError(input, message);

    return message === '';
  }

  function validateForm(form) {
    var fields = form.querySelectorAll('input, select, textarea');
    var firstInvalid = null;

    for (var i = 0; i < fields.length; i++) {
      var input = fields[i];

      if (!input.name || input.disabled) {
        continue;
      }

      if (input.type === 'hidden' || input.type === 'submit'
          || input.type === 'button' || input.type === 'reset') {
        continue;
      }

      if (!validateField(form, input) && firstInvalid === null) {
        firstInvalid = input;
      }
    }

    if (firstInvalid !== null) {
      firstInvalid.focus();
      return false;
    }

    return true;
  }

  function enhance(form) {
    // Le navigateur ne doit pas afficher ses propres bulles (souvent en
    // anglais) : nos messages français sont plus cohérents avec le serveur.
    form.setAttribute('novalidate', 'novalidate');

    form.addEventListener('submit', function (event) {
      if (!validateForm(form)) {
        event.preventDefault();
      }
    });

    // Validation au blur : l'utilisateur voit l'erreur en quittant le champ.
    form.addEventListener('blur', function (event) {
      var input = event.target;

      if (input && input.name && input.matches
          && input.matches('input, select, textarea')) {
        validateField(form, input);
      }
    }, true);

    // Correction en direct : on efface l'erreur dès que la saisie redevient
    // valide, sans attendre une nouvelle tentative d'envoi.
    form.addEventListener('input', function (event) {
      var input = event.target;
      var group = input ? groupOf(input) : null;

      if (input && input.name && group && group.classList.contains('has-error')) {
        validateField(form, input);
      }

      if (input && input.name === 'password') {
        var confirm = field(form, 'password_confirm');
        var confirmGroup = confirm ? groupOf(confirm) : null;

        if (confirm && confirmGroup
            && (confirm.value !== '' || confirmGroup.classList.contains('has-error'))) {
          validateField(form, confirm);
        }
      }
    });

    form.addEventListener('change', function (event) {
      var input = event.target;

      if (input && input.name && input.matches && input.matches('select')) {
        validateField(form, input);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form[data-validate]');

    for (var i = 0; i < forms.length; i++) {
      enhance(forms[i]);
    }

    // Bouton « œil » facultatif : affiche/masque un mot de passe.
    var toggles = document.querySelectorAll('[data-toggle-password]');

    for (var j = 0; j < toggles.length; j++) {
      toggles[j].addEventListener('click', function () {
        var target = document.getElementById(this.getAttribute('data-toggle-password'));

        if (!target) {
          return;
        }

        var show = target.type === 'password';
        target.type = show ? 'text' : 'password';
        this.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');

        var icon = this.querySelector('.fa');

        if (icon) {
          icon.classList.toggle('fa-eye', !show);
          icon.classList.toggle('fa-eye-slash', show);
        }
      });
    }
  });
})();
