document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("formAdresse");
    if (!form) return;

    form.addEventListener("submit", function (e) {
        var ok = true;

        function verifier(champ, valide, message) {
            document.getElementById("err_" + champ).textContent = valide ? "" : message;
            if (!valide) ok = false;
        }

        var rue = document.getElementById("rue").value.trim();
        var ville = document.getElementById("ville").value.trim();
        var cp = document.getElementById("code_postal").value.trim();
        var gouv = document.getElementById("gouvernorat").value;
        var comp = document.getElementById("complement").value.trim();

        verifier("rue", rue.length >= 5, "La rue doit contenir au moins 5 caractères.");
        verifier("ville", /^[A-Za-zÀ-ÿ\s'\-]{2,80}$/.test(ville), "La ville doit contenir seulement des lettres (2 à 80).");
        verifier("code_postal", /^[0-9]{4}$/.test(cp), "Le code postal doit contenir exactement 4 chiffres.");
        verifier("gouvernorat", gouv !== "", "Veuillez choisir un gouvernorat.");
        verifier("complement", comp.length <= 150, "Le complément ne doit pas dépasser 150 caractères.");

        if (!ok) e.preventDefault();
    });
});