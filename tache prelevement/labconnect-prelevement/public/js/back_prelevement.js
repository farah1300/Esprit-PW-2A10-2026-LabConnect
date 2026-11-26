document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("formRefus");
    if (!form) return;

    form.addEventListener("submit", function (e) {
        var motif = document.getElementById("motif_refus").value.trim();
        var valide = motif.length >= 3 && motif.length <= 255;

        document.getElementById("err_motif_refus").textContent = valide
            ? ""
            : "Le motif du refus doit contenir entre 3 et 255 caractères.";

        if (!valide) e.preventDefault();
    });
});