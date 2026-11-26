</main>
<footer class="pied">LabConnect BackOffice · Responsable des prélèvements · Esprit 2A10 · 2026-2027</footer>
<?php foreach (($scripts ?? []) as $s): ?>
<script src="<?= htmlspecialchars($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>