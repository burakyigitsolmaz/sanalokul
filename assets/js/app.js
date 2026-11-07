/* =====================================================================
   Nallıhan MYO Sanal Okul - Arayüz betiği
   Tema değiştirme, mobil menü, flash mesaj.
   ===================================================================== */
(function () {
    'use strict';

    var TEMA_ANAHTARI = 'sanalokul-tema';
    var kok = document.documentElement;

    // ------------------------------------------------------------------
    // Tema
    // ------------------------------------------------------------------
    function sistemTemasi() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'koyu'
            : 'acik';
    }

    function uygula(tema) {
        var gercek = tema === 'sistem' ? sistemTemasi() : tema;
        kok.setAttribute('data-tema', gercek);
        kok.setAttribute('data-tema-secim', tema);

        var dugme = document.getElementById('temaDugme');
        if (dugme) {
            var etiket = tema === 'sistem' ? 'Sistem teması' : (tema === 'acik' ? 'Açık tema' : 'Koyu tema');
            dugme.setAttribute('title', etiket + ' · değiştirmek için tıkla');
            dugme.setAttribute('aria-label', etiket + ' · değiştirmek için tıkla');
            dugme.textContent = tema === 'sistem' ? '◐' : (tema === 'acik' ? '☀' : '☾');
        }
    }

    function secili() {
        return localStorage.getItem(TEMA_ANAHTARI) || kok.getAttribute('data-tema-secim') || 'sistem';
    }

    function kaydet(tema) {
        try {
            localStorage.setItem(TEMA_ANAHTARI, tema);
        } catch (e) {
            /* gizli mod: sessizce geç */
        }

        uygula(tema);

        // Giriş yapılmışsa tercihi veritabanına da yaz.
        var adres = kok.getAttribute('data-tema-adres');
        if (!adres) {
            return;
        }

        var govde = new URLSearchParams();
        govde.set('tema', tema);

        fetch(adres, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: govde.toString(),
            credentials: 'same-origin'
        }).catch(function () {
            /* çevrimdışıysa yerel tercih geçerli kalır */
        });
    }

    uygula(secili());

    var temaDugme = document.getElementById('temaDugme');
    if (temaDugme) {
        temaDugme.addEventListener('click', function () {
            var sira = ['koyu', 'acik', 'sistem'];
            var simdiki = secili();
            var sonraki = sira[(sira.indexOf(simdiki) + 1) % sira.length];
            kaydet(sonraki);
        });
    }

    // Sistem teması seçiliyse işletim sistemi değişimini takip et.
    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var dinle = function () {
            if (secili() === 'sistem') {
                uygula('sistem');
            }
        };

        if (mq.addEventListener) {
            mq.addEventListener('change', dinle);
        } else if (mq.addListener) {
            mq.addListener(dinle);
        }
    }

    // ------------------------------------------------------------------
    // Mobil menü
    // ------------------------------------------------------------------
    var menuDugme = document.getElementById('menuDugme');
    var ustMenu = document.getElementById('ustMenu');

    if (menuDugme && ustMenu) {
        menuDugme.addEventListener('click', function (olay) {
            olay.stopPropagation();
            var acik = ustMenu.classList.toggle('acik');
            menuDugme.setAttribute('aria-expanded', acik ? 'true' : 'false');
        });

        document.addEventListener('click', function (olay) {
            if (!ustMenu.classList.contains('acik')) {
                return;
            }

            if (!ustMenu.contains(olay.target) && !menuDugme.contains(olay.target)) {
                ustMenu.classList.remove('acik');
                menuDugme.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (olay) {
            if (olay.key === 'Escape' && ustMenu.classList.contains('acik')) {
                ustMenu.classList.remove('acik');
                menuDugme.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ------------------------------------------------------------------
    // Flash mesaj
    // ------------------------------------------------------------------
    document.querySelectorAll('.uyari[data-kapanir]').forEach(function (kutu) {
        var kapat = kutu.querySelector('.uyari-kapat');

        if (kapat) {
            kapat.addEventListener('click', function () {
                kutu.remove();
            });
        }

        window.setTimeout(function () {
            if (!kutu.isConnected) {
                return;
            }

            kutu.style.transition = 'opacity .35s ease, transform .35s ease';
            kutu.style.opacity = '0';
            kutu.style.transform = 'translateY(-4px)';

            window.setTimeout(function () {
                kutu.remove();
            }, 360);
        }, 7000);
    });

    // ------------------------------------------------------------------
    // Form gönderilirken düğmeyi kilitle (çift tıklama koruması)
    // ------------------------------------------------------------------
    document.querySelectorAll('form[data-tek-gonderim]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var dugme = form.querySelector('button[type="submit"]');

            if (dugme && !dugme.disabled) {
                dugme.disabled = true;
                dugme.style.opacity = '0.72';
                dugme.textContent = 'Gönderiliyor…';
            }
        });
    });
})();
