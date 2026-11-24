# تەرازووی چاپکەری لیبڵ لە Qasabxana

## یەکەم ڕۆژ لەگەڵ تەرازوو

1. **لیبڵ ئامادە بکە.** لە مێنیوی Rongta، EAN‑13 هەڵبژێرە و لیبڵێک چاپ بکە. بارکۆدخوێنی کیبۆرد یان کامێرای POS بەکاری دەهێنێت. خوێندنەوە بە کابلی تەرازوو پێویست نییە بۆ لیبڵ.
2. **کاڵا ئامادە بکە.** لە کاڵاکان، کاڵای بە کێش و نرخی هەر کیلۆ دابنێ. هەمان PLU و هەمان نرخ لە تەرازوو دابنێ. CSV ـی تەرازوو بۆ گواستنەوەی دەستی بۆ بەرنامەی Rongta بەکاربهێنە.
3. **فۆرمات فێر بکە.** لە Settings → فێربوون لە لیبڵ، کۆد، PLU و کێشی چاپکراوی دوو لیبڵ بە کێشی جیاواز و نائەستوور بنووسە. ئەگەر دەکرێت PLU ـەکان جیاواز بن. تەنها ئەگەر یەک فۆرمات دەرکەوت، پشتڕاستی بکەوە. ئەگەر چەند فۆرمات ماون، نموونەی زیاتر زیاد بکە یان خانەکان بە پێی ڕێکخستنی خودی تەرازوو پڕ بکە.
4. **کێش بپشکنە.** 0.500 kg و 1.250 kg و کاڵای سێیەم تاقی بکە. بەپێی مۆدێل و مەودای تەرازوو، هەنگاوەکانی 5 g و 10 g بپشکنە. نرخی POS و تەرازوو یەکسان بکە.
5. **چالاکی بکە.** لە Settings خوێندنەوەی لیبڵ چالاک و پاشەکەوت بکە. لە POS کۆدەکە سکان بکە. بۆ بارکۆدخوێنی کیبۆرد Enter بەکاربهێنە. دووبارە سکانکردنی هەمان لیبڵ پشتڕاست بکەوە ئەگەر پاکەتێکی ترە.
6. **فرۆشتن و گەڕاندنەوە تاقی بکە.** کێش، نرخ و وەسڵ بپشکنە. یەک فرۆشتنی تاقیکردنەوە بگەڕێنەوە و دڵنیابە بڕی کۆگا گەڕاوەتەوە. تەنها دوای پشکنینی ڕاستەقینە چێکلیست نیشانە بکە.
7. **ئەگەر کێشە هەبوو، بە دەست بنووسە.** کاڵاکە لە POS هەڵبژێرە و کێش بە کیلۆ بنووسە. ئەگەر ئینتەرنێت بڕا، کێش دەتوانێت لە سەبەتە بمێنێتەوە. پاشەکەوتکردنی فرۆشتن پێویستی بە سێرڤەر هەیە.
8. **سریال تەنها ئەگەر تەرازوو کێش دەنێرێت.** RS232 بە کابلی گونجاو یان ئاداپتەری USB‑RS232ـی کۆمپیوتەر ببەستە. لە Chrome/Edge لە HTTPS یان localhost پۆرتەکە هەڵبژێرە. Baud، data bits، parity و stop bits لەگەڵ ئامێرەکە یەک بگرن. فریم و ئەنجام لە بەشی پشکنینی سریال دەردەکەون.

## فۆرمات و سنوورەکان

فۆرماتی بنەڕەت **گریمانەی تاقیکردنەوەیە**. Rongta پشتڕاستی نەکردووەتەوە. EAN‑13، پێشگری 20، PLU ـی ٥ ژمارە، بەهای ٥ ژمارە، ٣ خانەی دەهەمی کیلۆ و یەک ژمارەی پشکنین. بۆ PLU 1 و 0.525 kg کۆدە دروستەکە `2000001005255` ـە. هەموو خانەکانی فۆرمات دەستکاری دەکرێن. فۆرماتی نرخی ٥ ژمارە و سفر خانەی دەهەمی زۆرترین 99,999 دینار دەگرێت. فۆرماتی کێش باشترە. Code128، GS128 و کۆدی ١٨ ژمارە لەم ڕێڕەوەیە پشتگیری ناکرێن.

RS232 و Ethernet لە زانیارییەکانی ئامێردا هەن و Wi‑Fi هەڵبژاردەییە. USB ـی خودی تەرازوو، شێوەی لیبڵ و ناردنی فریمەکانی سریال پشتڕاست نەکراون. هیچ بارکردنی PLU بە TCP لە سیستەم نییە. خوێندنەوەی لیبڵ بە بنەڕەت ناچالاکە و **NOT VERIFIED ON HARDWARE** تا تەواوبوونی چێکلیست دەردەکەوێت.

## English

1. Print EAN-13 on the scale. Use a keyboard barcode scanner with Enter, or the POS camera scanner. A scale cable is optional for label scanning.
2. Configure each active weighted product with a unique numeric PLU and the same per-kg price on the scale and in POS. The UTF-8 scale CSV is for manual entry into the Rongta PC software. PLU001, 001 and 00001 are equivalent.
3. In **Settings → Learn from label**, enter the scan, the PLU and printed weight or price. Use two different non-round weights and different PLUs if possible. Save only one matching layout. A zero at the prefix boundary can leave multiple valid layouts even with two samples. Use additional samples or manually enter the layout you can confirm on the scale. If nothing matches, copy the raw digits for troubleshooting. Keep label scanning off until ready.
4. Confirm 0.500 kg, 1.250 kg and a third product, plus the 5 g and 10 g intervals where the scale mode supports them. Compare the printed price with the POS price. The POS calculates the line total from the current POS price per kg and uses the label's net weight without subtracting tare again.
5. Enable label reading explicitly. At checkout, confirm identical rescans only for separate packs. Check a test receipt and reverse the test sale to confirm that kg or gram stock returns to its starting value. Record completion in the admin checklist after physical testing.
6. If labels or the cable fail, select the product and enter kg manually. A locally retained cart can keep the weight during a network interruption. Submitting the sale requires the server.
7. For optional live weight, confirm whether the scale actually streams serial data. Use RS232 wiring or a compatible computer-side USB-to-RS232 adapter. Web Serial requires Chrome/Edge over HTTPS or localhost. Match baud rate, data bits, parity and stop bits to the device. The defaults are 9600, 8, none and 1. Unitless frames default to kg and can be configured as grams. Diagnostics display raw frames. Return the scale to zero before the next reading. Select the cart line when several weighted products are present.

The default EAN-13 layout, prefix 20 + five PLU digits + five value digits + one check digit, is a simulated assumption. Three decimals mean kilograms in weight mode. PLU 1 at 0.525 kg encodes as `2000001005255`. With five price digits and zero decimals the ceiling is 99,999 IQD. Prefer weight labels. Select EAN-13 on the scale if it currently prints Code128, GS128 or an 18-digit label.

The documented hardware interfaces include RS232, RJ45 Ethernet and optional Wi-Fi. The label layout and live serial framing remain unverified. The application sends no TCP commands to the scale. The label and serial simulators make no sale, cart or stock changes. Hardware verification stays pending until an administrator completes the checklist.

## Hostinger release order

1. After approval, back up the current database and release. Prepare the new Laravel release outside the web root. Attach the existing private `.env` and preserve the shared files under `storage/app/public`.
2. Install locked Composer dependencies and run `npm ci` and `npm run build` in the staged release or the approved build environment. Keep the resulting `public/build/manifest.json` and its matching assets together.
3. Check `php artisan migrate:status` and confirm the only expected pending migration for this feature is `2026_11_24_000002_add_scale_label_settings`. Apply that migration with `php artisan migrate --force --path=database/migrations/2026_11_24_000002_add_scale_label_settings.php`. It inserts missing settings and leaves existing product, price and PLU records intact.
4. Publish only the release's `public` files to the configured document root. Keep private Laravel files outside it. Switch after assets and the public storage link are ready, and verify login, rendered assets and a read-only POS request. Keep the previous release available for rollback. On this site, hPanel's previous Redeploy destination overwrote the web-root layout, so use the staged release procedure.
