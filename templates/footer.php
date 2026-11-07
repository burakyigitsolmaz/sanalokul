<?php
/**
 * Ana içeriği kapatır, alt bilgiyi ve betikleri basar.
 */

declare(strict_types=1);
?>
    </div>
</main>

<footer class="alt-bilgi">
    <div class="kapsayici alt-ic">
        <p>
            Bu uygulama Ankara Üniversitesi Nallıhan Meslek Yüksekokulu öğrencileri tarafından
            <strong>ders projesi</strong> olarak geliştirilmiştir.
            <strong>Resmî bir uygulama değildir</strong>; okulun resmî bilgi sistemlerinin yerini almaz.
            İçindeki e-posta, telefon ve şifreler demo amaçlıdır.
        </p>
        <div class="alt-linkler">
            <a href="<?= e(url('')) ?>">Ana Sayfa</a>
            <a href="<?= e(url('giris.php')) ?>">Giriş</a>
            <span><?= e(date('Y')) ?> · v<?= e(config('uygulama.surum')) ?></span>
        </div>
    </div>
</footer>

<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
