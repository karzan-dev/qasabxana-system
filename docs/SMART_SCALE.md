# بەستنەوەی ترازۆی چاپکەری وەسڵ (Rongta RLS1000 / RLS1100)

ئەم ڕێنماییە بۆ ئەو ترازۆیەیە کە لە وێنەکەدا دیارە: **Rongta RLS1000/RLS1100 Label Printing Scale**. سیستەمەکە دوو ڕێگەی هەیە بۆ وەرگرتنی کێش:

1. **Web Serial** — کێش بە ڕاستەوخۆ لە ترازۆکەوە دەخوێنرێتەوە ئەگەر ترازۆکە داتای کێش بە شێوەی سریال دەنێرێت.
2. **دەستی (Manual)** — کێشەکە لەسەر ترازۆکە دەخوێنرێتەوە و لە POS دەنووسرێت. ئەمە هەمیشە بەردەستە.

## ١) ئامادەکاری

- ترازۆکە بە کابلەکەیەوە بە کۆمپیوتەرەکە ببەستە. ئەگەر ترازۆکە تەنها RS232 هەیە، پێویستت بە ئاداپتەری **USB → RS232** هەیە و درایڤەرەکەی دابمەزرێنە.
- لە **Settings → Scale settings**دا:
  - `Enable scale` = **Yes**
  - `Scale mode` = **Web Serial** (یان Manual ئەگەر ناتەوێت بە ڕاستەوخۆ بیخوێنیتەوە)
  - `Baud rate` = ئەو بەهایەی لە مەنیووی ترازۆکەدا هەیە. ئەگەر نازانیت، بە **9600** دەست پێ بکە و تاقی بکەرەوە.
  - `Scale port` تەنها بۆ یادگارییە؛ وێبگەڕ خۆی پۆرتی دروستت پێشان دەدات کاتێک دەست بە بەستن دەکەیت.
- دوای پاشەکەوتکردن، بچۆرە **Point of Sale** و دوگمەی **Connect scale** دەدۆڕێتەوە.

> **گرنگ:** Web Serial تەنها لە Chrome یان Edge و لەسەر `https://` یان `localhost` کار دەکات. ئەگەر بە Firefox یان بە ئادرەسێکی نائەمن بەکاری دەهێنیت، کێش بە دەست بنووسە.

## ٢) بەستنەوە لە POS

1. کاڵایەکی کێشکراو (بۆ نموونە «گۆشتی گا - سەرڕان») زیاد بکەرە سەبەتەکە.
2. لە سەبەتەکەدا دوگمەی **Use scale weight** بۆ ئەو کاڵایە هەڵبژێرە.
3. لە سەرەوەی POS دوگمەی **Connect scale** دابگرە و پۆرتی ترازۆکە هەڵبژێرە.
4. کالاکە دابنێرە سەر ترازۆکە. کاتێک خوێندنەوەکە جێگیر بوو، کێشەکە بە ئۆتۆماتیکی بۆ کاڵا هەڵبژێردراوەکە دادەنرێت.
5. نرخ بە شێوەی `کێش × نرخی هەر کیلۆیەک` حساب دەکرێت و لە پسوڵەکەدا کێشی پاک و سەرچاوەی کێش تۆمار دەکرێن.

ئەگەر ترازۆکە داتای کێش بە سریال نانێرێت (هەندێک مۆدەل تەنها وەک چاپکەری وەسڵ دەردەکەوێت)، ڕێگەی **Manual** بەکاربهێنە: کێشەکە لەسەر ترازۆکە ببینە و لە خانەی **Weight (kg)** بنووسە.

## ٣) بارکۆد / PLU

هەر کاڵایەک لەم سیستەمەدا دوو ناسنامەی هەیە:

- **Barcode** — ئەو بارکۆدەی لەچاپ دەدرێت.
- **Scale product code (PLU)** — بۆ نموونە `PLU001` تا `PLU020`.

کاتێک بارکۆدێک لە POS سکان دەکرێت، سیستەمەکە یەکەم جار بە `barcode` و دواتر بە `scale_product_code` دەیگەڕێت. بۆیە ئەگەر ترازۆکە PLU لەسەر وەسڵەکە چاپ دەکات، کۆدی PLUی کاڵاکە لە **Products → Edit → Scale product code**دا بە هەمان کۆدی ناو ترازۆکە دابنێ.

تێبینی: ئەگەر بارکۆدی چاپکراو کێش یان نرخی تێدا شاردبێتەوە (فۆرماتی تایبەتی Rongta)، ئەم وەشانە کێشەکەی لە بارکۆدەکەوە ناخوێنێتەوە. کێشەکە بە Web Serial یان بە دەست وەربگرە.

## ٤) فۆرماتی داتای کێش

خوێنەری سریال ئەم شێوازانە دەناسێتەوە:

- `ST,GS,+  0.500 kg`
- `0250 g`
- `0.750` (بە کیلۆگرام هەژمار دەکرێت)

ئەگەر ترازۆکە فۆرماتێکی تری هەیە، لە مەنیوی ترازۆکەدا داتای کێش بە یەکێک لەم شێوازانە دەربهێنە یان ڕێگەی Manual بەکاربهێنە.

## ٥) تاقیکردنەوە

1. کاڵایەکی کێشکراو زیاد بکە و کێشێکی ناسراو دابنێ (بۆ نموونە `0.500 kg`).
2. دڵنیابە کە کێشەکە لە سەبەتەکەدا بە `0.500 kg` پیشان دەدرێت.
3. فرۆشتنەکە تەواو بکە و لە پسوڵەکەدا کێش و سەرچاوەی کێش ببینە.
4. ئەگەر کێشەکە نەهات، baud rate بگۆڕە و دووبارە تاقی بکەرەوە، یان کێش بە دەست بنووسە.

---

# Smart label scale connection (Rongta RLS1000 / RLS1100)

The system supports two ways of getting weight:

1. **Web Serial** — reads weight directly from the scale when it outputs serial data.
2. **Manual** — type the weight shown on the scale into POS. Always available.

## Setup

1. Connect the scale via USB, or via a USB-to-RS232 adapter if the scale only has RS232.
2. Go to **Settings → Scale settings** and set **Enable scale = Yes**, **Scale mode = Web Serial**, and the scale's **Baud rate** (try `9600` if unknown). The `Scale port` field is only a note; the browser shows the real port picker.
3. Save, then open **Point of Sale** and click **Connect scale**.

Web Serial requires Chrome/Edge on `https://` or `localhost`. Otherwise use manual weight.

## Daily use

Add a weighted product, tap **Use scale weight** for that cart line, place the item on the scale, and wait for two identical readings. The price is calculated as `weight × price per kg`, and the receipt records net weight and source.

If the scale only acts as a label printer and does not stream weight over serial, use **Manual** entry.

## Barcodes / PLU

The scanner looks up `barcode` first, then `scale_product_code` (for example `PLU001`). Set the product's **Scale product code** to the PLU configured in the scale. Vendor-specific barcodes with embedded weight/price are not decoded in this version; use Web Serial or manual weight for the quantity.

Accepted serial frames include `ST,GS,+  0.500 kg`, `0250 g`, and plain kilograms such as `0.750`.
