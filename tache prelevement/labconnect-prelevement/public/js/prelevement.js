document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("formPrelevement");
    if (!form) return;

    // Verifie que la date existe vraiment et qu'elle est strictement dans le futur
    function dateFuture(texte) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(texte);
        if (!m) return { format: false, futur: false };

        var annee = parseInt(m[1], 10);
        var mois = parseInt(m[2], 10) - 1;
        var jour = parseInt(m[3], 10);
        var d = new Date(annee, mois, jour);

        // Rejette les dates impossibles (ex: 2026-02-31)
        if (d.getFullYear() !== annee || d.getMonth() !== mois || d.getDate() !== jour) {
            return { format: false, futur: false };
        }

        var aujourdhui = new Date();
        aujourdhui.setHours(0, 0, 0, 0);
        return { format: true, futur: d > aujourdhui };
    }

    form.addEventListener("submit", function (e) {
        var ok = true;

        function verifier(champ, valide, message) {
            document.getElementById("err_" + champ).textContent = valide ? "" : message;
            if (!valide) ok = false;
        }

        var adresse = document.getElementById("id_adresse").value;
        var date = document.getElementById("date_prelevement").value.trim();
        var heure = document.getElementById("heure_prelevement").value.trim();
        var motif = document.getElementById("motif").value.trim();

        verifier("id_adresse", adresse !== "", "Veuillez choisir une adresse.");

        var r = dateFuture(date);
        if (!r.format) {
            verifier("date_prelevement", false, "La date doit être valide, au format AAAA-MM-JJ.");
        } else {
            verifier("date_prelevement", r.futur, "La date doit être dans le futur.");
        }

        verifier("heure_prelevement", /^([01][0-9]|2[0-3]):[0-5][0-9]$/.test(heure),
                 "L'heure doit être au format HH:MM (ex: 08:30).");
        verifier("motif", motif.length >= 3 && motif.length <= 150,
                 "Le motif doit contenir entre 3 et 150 caractères.");

        if (!ok) e.preventDefault();
    });
});