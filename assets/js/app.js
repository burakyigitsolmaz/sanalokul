/* =====================================================================
   Nallıhan MYO Sanal Okul - Arayüz betiği
   İki temalı geçiş (koyu/açık), mobil çekmece menü, flash mesaj.
   ===================================================================== */
(function () {
    'use strict';

    var TEMA_ANAHTARI = 'sanalokul-tema';
    var kok = document.documentElement;

    // ------------------------------------------------------------------
    // Tema (yalnızca iki mod: koyu ve acik)
    // ------------------------------------------------------------------
    function secili() {
        var tema = localStorage.getItem(TEMA_ANAHTARI)
            || kok.getAttribute('data-tema-secim')
            || 'koyu';

        return tema === 'acik' ? 'acik' : 'koyu';
    }

    function uygula(tema) {
        var gercek = tema === 'acik' ? 'acik' : 'koyu';
        var etiket = gercek === 'acik' ? 'Açık' : 'Koyu';

        kok.setAttribute('data-tema', gercek);
        kok.setAttribute('data-tema-secim', gercek);

        var dugme = document.getElementById('temaDugme');
        if (dugme) {
            dugme.setAttribute('title', etiket + ' tema · değiştirmek için tıkla');
            dugme.setAttribute('aria-label', etiket + ' tema · değiştirmek için tıkla');
            dugme.setAttribute('aria-pressed', gercek === 'acik' ? 'true' : 'false');
        }

        document.querySelectorAll('[data-tema-etiket]').forEach(function (etiketAlani) {
            etiketAlani.textContent = etiket;
        });
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

    function temaDegistir() {
        kaydet(secili() === 'koyu' ? 'acik' : 'koyu');
    }

    uygula(secili());

    var temaDugme = document.getElementById('temaDugme');
    if (temaDugme) {
        temaDugme.addEventListener('click', temaDegistir);
    }

    document.querySelectorAll('[data-tema-degistir]').forEach(function (dugme) {
        dugme.addEventListener('click', temaDegistir);
    });

    // ------------------------------------------------------------------
    // Giriş ekranı: demo hesap kartları (tıklayınca alanları doldurur)
    // ------------------------------------------------------------------
    var DEMO_SIFRE = 'Sanal1234!';

    document.querySelectorAll('[data-demo-eposta]').forEach(function (dugme) {
        dugme.addEventListener('click', function () {
            var eposta = document.getElementById('eposta');
            var sifre = document.getElementById('sifre');
            var gonder = document.querySelector('form[data-tek-gonderim] button[type="submit"]');

            if (eposta) {
                eposta.value = dugme.getAttribute('data-demo-eposta') || '';
                eposta.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (sifre) {
                sifre.value = DEMO_SIFRE;
                sifre.dispatchEvent(new Event('input', { bubbles: true }));
            }

            document.querySelectorAll('.giris-demo-oge').forEach(function (oge) {
                oge.classList.toggle('secili', oge === dugme);
            });

            if (gonder) {
                gonder.focus();
            }
        });
    });

    // ------------------------------------------------------------------
    // Mobil çekmece menü
    // ------------------------------------------------------------------
    var menuDugme = document.getElementById('menuDugme');
    var cekmece = document.getElementById('cekmece');
    var katman = document.getElementById('cekmeceKatman');
    var kapatDugme = document.getElementById('cekmeceKapat');

    function cekmeceAc() {
        if (!cekmece || !katman || !menuDugme) {
            return;
        }

        cekmece.classList.add('acik');
        katman.classList.add('acik');
        document.body.classList.add('cekmece-acik');
        menuDugme.setAttribute('aria-expanded', 'true');
        cekmece.setAttribute('aria-hidden', 'false');
        katman.setAttribute('aria-hidden', 'false');

        if (kapatDugme) {
            kapatDugme.focus();
        }
    }

    function cekmeceKapat() {
        if (!cekmece || !katman || !menuDugme) {
            return;
        }

        cekmece.classList.remove('acik');
        katman.classList.remove('acik');
        document.body.classList.remove('cekmece-acik');
        menuDugme.setAttribute('aria-expanded', 'false');
        cekmece.setAttribute('aria-hidden', 'true');
        katman.setAttribute('aria-hidden', 'true');
        menuDugme.focus();
    }

    if (menuDugme && cekmece && katman) {
        menuDugme.addEventListener('click', function () {
            if (cekmece.classList.contains('acik')) {
                cekmeceKapat();
            } else {
                cekmeceAc();
            }
        });

        katman.addEventListener('click', cekmeceKapat);

        if (kapatDugme) {
            kapatDugme.addEventListener('click', cekmeceKapat);
        }

        // Menüden bir bağlantıya tıklanınca çekmeceyi kapat.
        cekmece.querySelectorAll('a').forEach(function (baglanti) {
            baglanti.addEventListener('click', function () {
                cekmece.classList.remove('acik');
                katman.classList.remove('acik');
                document.body.classList.remove('cekmece-acik');
            });
        });

        document.addEventListener('keydown', function (olay) {
            if (olay.key === 'Escape' && cekmece.classList.contains('acik')) {
                cekmeceKapat();
            }
        });

        // Ekran büyütülürse açık kalan çekmeceyi kapat.
        var genislik = window.matchMedia('(min-width: 961px)');
        var genislikDinle = function (olay) {
            if (olay.matches && cekmece.classList.contains('acik')) {
                cekmeceKapat();
            }
        };

        if (genislik.addEventListener) {
            genislik.addEventListener('change', genislikDinle);
        } else if (genislik.addListener) {
            genislik.addListener(genislikDinle);
        }
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
    // Onay isteyen formlar (inline onsubmit yerine)
    // ------------------------------------------------------------------
    document.querySelectorAll('form[data-onay]').forEach(function (form) {
        form.addEventListener('submit', function (olay) {
            var mesaj = form.getAttribute('data-onay') || 'Devam edilsin mi?';

            if (!window.confirm(mesaj)) {
                olay.preventDefault();
            }
        });
    });

    // ------------------------------------------------------------------
    // Yazdır düğmeleri (inline onclick yerine)
    // ------------------------------------------------------------------
    document.querySelectorAll('[data-yazdir]').forEach(function (dugme) {
        dugme.addEventListener('click', function () {
            window.print();
        });
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
    // ------------------------------------------------------------------
    // Panel menüsü (mobilde açılır)
    // ------------------------------------------------------------------
    document.querySelectorAll('[data-panel-menu]').forEach(function (dugme) {
        dugme.addEventListener('click', function () {
            var menu = document.getElementById('panelMenu');
            if (!menu) {
                return;
            }

            var acik = menu.classList.toggle('acik');
            dugme.setAttribute('aria-expanded', acik ? 'true' : 'false');
        });
    });

    // ------------------------------------------------------------------
    // ER diyagramını sayfa içinde büyüt
    // ------------------------------------------------------------------
    var erPencere = document.getElementById('erBuyut');

    if (erPencere && typeof erPencere.showModal === 'function') {
        document.querySelectorAll('[data-er-buyut]').forEach(function (baglanti) {
            baglanti.addEventListener('click', function (olay) {
                olay.preventDefault();
                erPencere.showModal();
                document.body.classList.add('er-buyut-acik');
            });
        });

        document.querySelectorAll('[data-er-kapat]').forEach(function (dugme) {
            dugme.addEventListener('click', function () {
                erPencere.close();
            });
        });

        erPencere.addEventListener('click', function (olay) {
            if (olay.target === erPencere) {
                erPencere.close();
            }
        });

        erPencere.addEventListener('close', function () {
            document.body.classList.remove('er-buyut-acik');
        });
    }
})();
