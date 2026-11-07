# -*- coding: utf-8 -*-
"""
Sanal Okul - ER diyagramı üretici.

Kullanım:
    python docs/er_uret.py

Çıktı: docs/ER.svg
PNG'ye çevirmek için (Windows, Edge yüklüyse):
    msedge --headless --window-size=1850,1400 --screenshot=docs/ER.png docs/ER.svg
"""

from __future__ import annotations

import os
from html import escape
from dataclasses import dataclass, field

# ---------------------------------------------------------------------
# Tablo tanımları
# ---------------------------------------------------------------------
# (kolon adı, veri tipi, işaret)  işaret: "", "PK", "FK", "UQ", "PK,FK"
TABLOLAR: dict[str, dict] = {
    "roller": {
        "grup": "kimlik",
        "kolonlar": [
            ("id", "TINYINT", "PK"),
            ("ad", "VARCHAR(20)", "UQ"),
        ],
    },
    "bolumler": {
        "grup": "kimlik",
        "kolonlar": [
            ("id", "TINYINT", "PK"),
            ("kod", "VARCHAR(12)", "UQ"),
            ("ad", "VARCHAR(120)", ""),
        ],
    },
    "donemler": {
        "grup": "kimlik",
        "kolonlar": [
            ("id", "TINYINT", "PK"),
            ("ad", "VARCHAR(40)", "UQ"),
            ("baslangic", "DATE", ""),
            ("bitis", "DATE", ""),
            ("aktif", "TINYINT(1)", ""),
        ],
    },
    "kullanicilar": {
        "grup": "kimlik",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("rol_id", "TINYINT", "FK"),
            ("numara", "VARCHAR(20)", "UQ"),
            ("ad", "VARCHAR(60)", ""),
            ("soyad", "VARCHAR(60)", ""),
            ("eposta", "VARCHAR(120)", "UQ"),
            ("sifre_hash", "VARCHAR(255)", ""),
            ("telefon", "VARCHAR(20)", ""),
            ("tema_tercihi", "ENUM", ""),
            ("aktif", "TINYINT(1)", ""),
            ("son_giris", "DATETIME", ""),
            ("olusturma_zamani", "TIMESTAMP", ""),
        ],
    },
    "ogrenci_profilleri": {
        "grup": "kimlik",
        "kolonlar": [
            ("kullanici_id", "INT", "PK,FK"),
            ("bolum_id", "TINYINT", "FK"),
            ("sinif", "TINYINT", ""),
            ("giris_yili", "SMALLINT", ""),
        ],
    },
    "ogretim_elemani_profilleri": {
        "grup": "kimlik",
        "kolonlar": [
            ("kullanici_id", "INT", "PK,FK"),
            ("bolum_id", "TINYINT", "FK"),
            ("unvan", "VARCHAR(60)", ""),
        ],
    },
    "dersler": {
        "grup": "akademik",
        "kolonlar": [
            ("id", "SMALLINT", "PK"),
            ("bolum_id", "TINYINT", "FK"),
            ("kod", "VARCHAR(12)", "UQ"),
            ("ad", "VARCHAR(140)", ""),
            ("kredi", "TINYINT", ""),
            ("akts", "TINYINT", ""),
            ("zorunlu_mu", "TINYINT(1)", ""),
            ("sinif", "TINYINT", ""),
        ],
    },
    "ders_acilislari": {
        "grup": "akademik",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("ders_id", "SMALLINT", "FK"),
            ("donem_id", "TINYINT", "FK"),
            ("ogretim_elemani_id", "INT", "FK"),
            ("sube", "VARCHAR(5)", ""),
            ("kontenjan", "SMALLINT", ""),
        ],
    },
    "ders_kayitlari": {
        "grup": "akademik",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("ogrenci_id", "INT", "FK"),
            ("ders_acilis_id", "INT", "FK"),
            ("kayit_zamani", "TIMESTAMP", ""),
            ("durum", "ENUM", ""),
        ],
    },
    "notlar": {
        "grup": "akademik",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("ders_kaydi_id", "INT", "FK,UQ"),
            ("vize", "DECIMAL(5,2)", ""),
            ("final", "DECIMAL(5,2)", ""),
            ("ortalama", "DECIMAL(5,2)", "trigger"),
            ("harf_notu", "VARCHAR(2)", "trigger"),
            ("guncelleme_zamani", "TIMESTAMP", ""),
        ],
    },
    "ders_programi": {
        "grup": "icerik",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("ders_acilis_id", "INT", "FK"),
            ("gun", "TINYINT", ""),
            ("baslangic_saati", "TIME", ""),
            ("bitis_saati", "TIME", ""),
            ("derslik", "VARCHAR(40)", ""),
        ],
    },
    "ders_icerikleri": {
        "grup": "icerik",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("ders_acilis_id", "INT", "FK"),
            ("hafta", "TINYINT", ""),
            ("baslik", "VARCHAR(160)", ""),
            ("aciklama", "TEXT", ""),
            ("dosya_yolu", "VARCHAR(255)", ""),
        ],
    },
    "duyurular": {
        "grup": "icerik",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("ders_acilis_id", "INT", "FK"),
            ("yazar_id", "INT", "FK"),
            ("baslik", "VARCHAR(160)", ""),
            ("icerik", "TEXT", ""),
            ("onemli", "TINYINT(1)", ""),
            ("yayin_tarihi", "DATETIME", ""),
            ("bitis_tarihi", "DATETIME", ""),
        ],
    },
    "odevler": {
        "grup": "icerik",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("ders_acilis_id", "INT", "FK"),
            ("baslik", "VARCHAR(160)", ""),
            ("aciklama", "TEXT", ""),
            ("son_teslim", "DATETIME", ""),
            ("maks_puan", "SMALLINT", ""),
            ("dosya_yolu", "VARCHAR(255)", ""),
        ],
    },
    "teslimler": {
        "grup": "icerik",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("odev_id", "INT", "FK"),
            ("ogrenci_id", "INT", "FK"),
            ("dosya_yolu", "VARCHAR(255)", ""),
            ("aciklama", "TEXT", ""),
            ("teslim_zamani", "DATETIME", ""),
            ("puan", "SMALLINT", ""),
            ("geri_bildirim", "TEXT", ""),
        ],
    },
    "islem_loglari": {
        "grup": "denetim",
        "kolonlar": [
            ("id", "BIGINT", "PK"),
            ("kullanici_id", "INT", "FK"),
            ("islem", "VARCHAR(255)", ""),
            ("tablo_adi", "VARCHAR(60)", ""),
            ("kayit_id", "INT", ""),
            ("ip", "VARCHAR(45)", ""),
            ("zaman", "TIMESTAMP", ""),
        ],
    },
    "giris_denemeleri": {
        "grup": "denetim",
        "kolonlar": [
            ("id", "BIGINT", "PK"),
            ("eposta", "VARCHAR(120)", ""),
            ("ip", "VARCHAR(45)", ""),
            ("basarili", "TINYINT(1)", ""),
            ("zaman", "TIMESTAMP", ""),
        ],
    },
    "yemek_menuleri": {
        "grup": "dis",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("tarih", "DATE", "UQ"),
            ("corba", "VARCHAR(120)", ""),
            ("ana_yemek", "VARCHAR(120)", ""),
            ("yardimci_yemek", "VARCHAR(120)", ""),
            ("tatli_salata", "VARCHAR(120)", ""),
            ("toplam_kcal", "SMALLINT", ""),
            ("kaynak_url", "VARCHAR(500)", ""),
        ],
    },
    "dis_haberler": {
        "grup": "dis",
        "kolonlar": [
            ("id", "INT", "PK"),
            ("kaynak", "VARCHAR(60)", ""),
            ("baslik", "VARCHAR(255)", ""),
            ("ozet", "TEXT", ""),
            ("url", "VARCHAR(500)", "UQ"),
            ("gorsel_url", "VARCHAR(500)", ""),
            ("yayin_tarihi", "DATETIME", ""),
        ],
    },
    "kaynak_senkron_durumu": {
        "grup": "dis",
        "kolonlar": [
            ("kaynak", "VARCHAR(60)", "PK"),
            ("son_deneme", "DATETIME", ""),
            ("son_basari", "DATETIME", ""),
            ("durum", "ENUM", ""),
            ("hata_mesaji", "TEXT", ""),
        ],
    },
}

# Sütun düzeni: her sütun bir tablo listesi
SUTUNLAR = [
    ["roller", "bolumler", "donemler"],
    ["kullanicilar", "ogrenci_profilleri", "ogretim_elemani_profilleri"],
    ["dersler", "ders_acilislari", "ders_kayitlari", "notlar"],
    ["ders_programi", "ders_icerikleri", "duyurular", "odevler"],
    ["teslimler", "islem_loglari", "giris_denemeleri", "yemek_menuleri", "dis_haberler", "kaynak_senkron_durumu"],
]

ILISKILER = [
    ("roller", "id", "kullanicilar", "rol_id"),
    ("bolumler", "id", "ogrenci_profilleri", "bolum_id"),
    ("bolumler", "id", "ogretim_elemani_profilleri", "bolum_id"),
    ("bolumler", "id", "dersler", "bolum_id"),
    ("kullanicilar", "id", "ogrenci_profilleri", "kullanici_id"),
    ("kullanicilar", "id", "ogretim_elemani_profilleri", "kullanici_id"),
    ("kullanicilar", "id", "ders_acilislari", "ogretim_elemani_id"),
    ("kullanicilar", "id", "ders_kayitlari", "ogrenci_id"),
    ("kullanicilar", "id", "duyurular", "yazar_id"),
    ("kullanicilar", "id", "teslimler", "ogrenci_id"),
    ("kullanicilar", "id", "islem_loglari", "kullanici_id"),
    ("donemler", "id", "ders_acilislari", "donem_id"),
    ("dersler", "id", "ders_acilislari", "ders_id"),
    ("ders_acilislari", "id", "ders_kayitlari", "ders_acilis_id"),
    ("ders_acilislari", "id", "ders_programi", "ders_acilis_id"),
    ("ders_acilislari", "id", "ders_icerikleri", "ders_acilis_id"),
    ("ders_acilislari", "id", "duyurular", "ders_acilis_id"),
    ("ders_acilislari", "id", "odevler", "ders_acilis_id"),
    ("ders_kayitlari", "id", "notlar", "ders_kaydi_id"),
    ("odevler", "id", "teslimler", "odev_id"),
]

GRUPLAR = {
    "kimlik":   ("#2563eb", "Kimlik ve Organizasyon"),
    "akademik": ("#059669", "Akademik Yapı"),
    "icerik":   ("#d97706", "Ders İçeriği ve Ödevler"),
    "denetim":  ("#7c3aed", "Denetim"),
    "dis":      ("#0891b2", "Dış İçerik"),
}

# ---------------------------------------------------------------------
# Yerleşim
# ---------------------------------------------------------------------
X_BASLANGIC = 40
SUTUN_GENISLIK = 330
SUTUN_ARALIK = 30
SATIR_YUKSEKLIK = 22
BASLIK_YUKSEKLIK = 34
KUTU_UST_BOSLUK = 10
TABLO_ARALIK = 26
UST_Y = 130

KONUM: dict[str, dict] = {}


def yerlesim() -> tuple[int, int]:
    genislik = X_BASLANGIC * 2 + len(SUTUNLAR) * SUTUN_GENISLIK + (len(SUTUNLAR) - 1) * SUTUN_ARALIK

    en_alt = 0
    for sutun_no, tablolar in enumerate(SUTUNLAR):
        x = X_BASLANGIC + sutun_no * (SUTUN_GENISLIK + SUTUN_ARALIK)
        y = UST_Y
        for ad in tablolar:
            kolon_sayisi = len(TABLOLAR[ad]["kolonlar"])
            yukseklik = BASLIK_YUKSEKLIK + KUTU_UST_BOSLUK + kolon_sayisi * SATIR_YUKSEKLIK
            KONUM[ad] = {"x": x, "y": y, "w": SUTUN_GENISLIK, "h": yukseklik}
            y += yukseklik + TABLO_ARALIK
        en_alt = max(en_alt, y)

    return genislik, en_alt + 130


def nokta(tablo: str, taraf: str) -> tuple[float, float]:
    k = KONUM[tablo]
    if taraf == "sol":
        return k["x"], k["y"] + k["h"] / 2
    if taraf == "sag":
        return k["x"] + k["w"], k["y"] + k["h"] / 2
    if taraf == "ust":
        return k["x"] + k["w"] / 2, k["y"]
    return k["x"] + k["w"] / 2, k["y"] + k["h"]


def egri(x1: float, y1: float, x2: float, y2: float) -> str:
    dx = max(40.0, abs(x2 - x1) * 0.45)
    return (
        f"M {x1:.1f} {y1:.1f} "
        f"C {x1 + dx:.1f} {y1:.1f}, {x2 - dx:.1f} {y2:.1f}, {x2:.1f} {y2:.1f}"
    )


def svg_uret() -> str:
    genislik, yukseklik = yerlesim()

    parcalar: list[str] = []
    parcalar.append('<?xml version="1.0" encoding="UTF-8"?>')
    parcalar.append(
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{genislik}" height="{yukseklik}" '
        f'viewBox="0 0 {genislik} {yukseklik}" font-family="Segoe UI, Arial, sans-serif">'
    )
    parcalar.append('<defs>')
    parcalar.append(
        '<marker id="ok" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" '
        'orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill="#94a3b8"/></marker>'
    )
    parcalar.append(
        '<filter id="golge" x="-20%" y="-20%" width="140%" height="140%">'
        '<feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#0f172a" flood-opacity="0.10"/>'
        '</filter>'
    )
    parcalar.append('</defs>')

    # Arka plan
    parcalar.append(f'<rect width="{genislik}" height="{yukseklik}" fill="#ffffff"/>')

    # Başlık
    parcalar.append(
        '<text x="40" y="52" font-size="26" font-weight="700" fill="#0f172a">'
        'Nallıhan MYO Sanal Okul — Entity-Relationship Diyagramı</text>'
    )
    parcalar.append(
        '<text x="40" y="82" font-size="14" fill="#64748b">'
        'Ankara Üniversitesi Nallıhan MYO · Bilgisayar Programcılığı · Veritabanı: sanalokul (utf8mb4)</text>'
    )

    # İlişki çizgileri (kutuların arkasında kalır)
    parcalar.append('<g fill="none" stroke="#64748b" stroke-width="1.5" opacity="0.9">')
    for kaynak, kaynak_kolon, hedef, hedef_kolon in ILISKILER:
        ks = KONUM[kaynak]
        hs = KONUM[hedef]

        if hs["x"] > ks["x"] + ks["w"] - 1:
            x1, y1 = nokta(kaynak, "sag")
            x2, y2 = nokta(hedef, "sol")
        elif hs["x"] + hs["w"] < ks["x"] + 1:
            x1, y1 = nokta(kaynak, "sol")
            x2, y2 = nokta(hedef, "sag")
        elif hs["y"] > ks["y"]:
            x1, y1 = nokta(kaynak, "alt")
            x2, y2 = nokta(hedef, "ust")
        else:
            x1, y1 = nokta(kaynak, "ust")
            x2, y2 = nokta(hedef, "alt")

        parcalar.append(f'<path d="{egri(x1, y1, x2, y2)}" marker-end="url(#ok)"/>')
    parcalar.append('</g>')

    # Tablolar
    for ad, tablo in TABLOLAR.items():
        k = KONUM[ad]
        renk, _ = GRUPLAR[tablo["grup"]]
        x, y, w, h = k["x"], k["y"], k["w"], k["h"]

        parcalar.append(
            f'<g filter="url(#golge)">'
            f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="9" fill="#ffffff" '
            f'stroke="#cbd5e1" stroke-width="1"/>'
            f'</g>'
        )
        # Başlık şeridi
        parcalar.append(
            f'<path d="M {x} {y + BASLIK_YUKSEKLIK} V {y + 9} Q {x} {y} {x + 9} {y} '
            f'H {x + w - 9} Q {x + w} {y} {x + w} {y + 9} V {y + BASLIK_YUKSEKLIK} Z" fill="{renk}"/>'
        )
        parcalar.append(
            f'<text x="{x + 14}" y="{y + 23}" font-size="14.5" font-weight="700" fill="#ffffff">'
            f'{escape(ad)}</text>'
        )

        for i, (kolon, tip, isaret) in enumerate(tablo["kolonlar"]):
            satir_y = y + BASLIK_YUKSEKLIK + KUTU_UST_BOSLUK + i * SATIR_YUKSEKLIK

            if i % 2 == 1:
                parcalar.append(
                    f'<rect x="{x + 1}" y="{satir_y}" width="{w - 2}" height="{SATIR_YUKSEKLIK}" '
                    f'fill="#f8fafc"/>'
                )

            badge_x = x + 12
            if "PK" in isaret:
                parcalar.append(
                    f'<rect x="{badge_x}" y="{satir_y + 4}" width="22" height="14" rx="3" fill="#f59e0b"/>'
                    f'<text x="{badge_x + 11}" y="{satir_y + 14.6}" font-size="8.5" font-weight="700" '
                    f'fill="#ffffff" text-anchor="middle">PK</text>'
                )
                badge_x += 26
            if "FK" in isaret:
                parcalar.append(
                    f'<rect x="{badge_x}" y="{satir_y + 4}" width="22" height="14" rx="3" fill="#0ea5e9"/>'
                    f'<text x="{badge_x + 11}" y="{satir_y + 14.6}" font-size="8.5" font-weight="700" '
                    f'fill="#ffffff" text-anchor="middle">FK</text>'
                )
                badge_x += 26

            parcalar.append(
                f'<text x="{badge_x}" y="{satir_y + 15}" font-size="12.2" fill="#1e293b">'
                f'{escape(kolon)}</text>'
            )

            sag_etiket = tip
            if "UQ" in isaret:
                sag_etiket += "  · UQ"
            if isaret == "trigger":
                sag_etiket = "otomatik"

            parcalar.append(
                f'<text x="{x + w - 12}" y="{satir_y + 15}" font-size="10.3" fill="#94a3b8" '
                f'text-anchor="end">{escape(sag_etiket)}</text>'
            )

    # Açıklama (legend)
    legend_y = yukseklik - 78
    parcalar.append(
        f'<rect x="40" y="{legend_y - 22}" width="{genislik - 80}" height="66" rx="10" '
        f'fill="#f8fafc" stroke="#e2e8f0"/>'
    )
    parcalar.append(
        f'<text x="60" y="{legend_y - 2}" font-size="12.5" fill="#334155" font-weight="600">'
        f'Gösterim:</text>'
    )

    legend_items = [
        ("rozet", "#f59e0b", "PK", "Birincil anahtar"),
        ("rozet", "#0ea5e9", "FK", "Yabancı anahtar"),
        ("ok", "#64748b", "→", "1 — N ilişki yönü"),
        ("nokta", "#059669", "", "Trigger ile hesaplanır: ortalama, harf notu"),
    ]
    lx = 148
    for tip, renk, kisa, uzun in legend_items:
        if tip == "rozet":
            parcalar.append(
                f'<rect x="{lx}" y="{legend_y - 13}" width="24" height="15" rx="3" fill="{renk}"/>'
                f'<text x="{lx + 12}" y="{legend_y - 2}" font-size="9" font-weight="700" fill="#ffffff" '
                f'text-anchor="middle">{escape(kisa)}</text>'
            )
            lx += 32
        elif tip == "ok":
            parcalar.append(
                f'<path d="M {lx} {legend_y - 6} L {lx + 20} {legend_y - 6}" stroke="{renk}" '
                f'stroke-width="1.6" fill="none"/>'
                f'<path d="M {lx + 15} {legend_y - 10} L {lx + 21} {legend_y - 6} L {lx + 15} {legend_y - 2}" '
                f'fill="{renk}"/>'
            )
            lx += 28
        else:
            parcalar.append(
                f'<circle cx="{lx + 7}" cy="{legend_y - 6}" r="5" fill="{renk}"/>'
            )
            lx += 18

        parcalar.append(f'<text x="{lx}" y="{legend_y - 2}" font-size="11.5" fill="#475569">{escape(uzun)}</text>')
        lx += 200

    parcalar.append(
        f'<text x="60" y="{legend_y + 24}" font-size="11.5" fill="#64748b">'
        f'Toplam 20 tablo · 20 yabancı anahtar ilişkisi · InnoDB · utf8mb4_turkish_ci · '
        f'oluşturulma: {escape(__import__("datetime").date.today().strftime("%d.%m.%Y"))}</text>'
    )

    parcalar.append('</svg>')
    return "\n".join(parcalar)


if __name__ == "__main__":
    cikti = os.path.join(os.path.dirname(os.path.abspath(__file__)), "ER.svg")
    with open(cikti, "w", encoding="utf-8") as f:
        f.write(svg_uret())
    print(f"Yazıldı: {cikti}")
