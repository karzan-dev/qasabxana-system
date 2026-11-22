# Qasabxana | Butcher Shop POS — Complete Package

ئەم فایلە سیستەمەکەیە بە هەموو پێکهاتەکانیەوە (کۆد + داتابەیس + فایلە کۆمپایلکراوەکانی ڕووکار).

## ناوەڕۆک
- سەرچاوەی تەواوی Laravel + React/Inertia
- `vendor/` — پاکەجەکانی PHP (ئامادەیە بۆ ڕانکردن بەبێ composer install)
- `public/build/` — فایلە کۆمپایلکراوەکانی CSS/JS (ئامادەیە بۆ ڕانکردن بەبێ npm build)
- `database/sql/supermarket_froshgay_min.sql` — دامپی تەواوی داتابەیس (schema + داتا)
- `.env` — ڕێکخستنەکانی ژینگەی ئێستا (بەپێی ژینگەی خۆت ڕێکی بکەرەوە)

## چۆن ڕان بکرێت / How to run

### 1. دامەزراندنی ژینگە
WAMP (یان XAMPP) دامەزرێنە بە MariaDB/MySQL و PHP 8.2+.

### 2. دروستکردنی داتابەیس
لە MariaDBـدا داتابەیسی `supermarket_froshgay_min` دروست بکە، پاشان دامپەکەی ناو `database/sql/` وارد بکە:

```
mysql -uroot -p -h 127.0.0.1 -P 3307 supermarket_froshgay_min < database/sql/supermarket_froshgay_min.sql
```

یان لە phpMyAdminـەوە Import بکە.

### 3. ڕێکخستنی .env
ئەم بەهایانە بەپێی ژینگەی خۆت ڕێک بکەرەوە:

```
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=supermarket_froshgay_min
DB_USERNAME=root
DB_PASSWORD=
APP_KEY=base64:...   # ئەگەر گۆڕا، php artisan key:generate بەکاربێنە
```

### 4. ڕانکردنی ئەپەکە
```
php artisan serve --host=127.0.0.1 --port=8000
```
پاشان بچۆرە `http://127.0.0.1:8000`.

### 5. (ئارەزوومەندانە) دووبارە کۆمپایلکردنی ڕووکار
ئەگەر گۆڕانکاریت لە frontendـدا کرد، `npm install` ئەنجام بدە پاشان `npm run build`.
پێویست نییە بۆ ڕانکردن چونکە `public/build/` لە ناو فایلەکەدایە.

## تێبینی
- ئەم فایلە داتای ڕاستی لەخۆدەگرێت (کڕیار، دابینکار، فرۆشتن...). بە وریاییەوە هەڵی بگرە.
- کلیلی SSH / تۆکەنی GitHub لە ناو ئەم پاکەجەدا نییە.
- بۆ پشتگیریی تەرازوو (Smart Scale)، فایلی `docs/SMART_SCALE.md` بخوێنەرەوە.

تاریخی پاکەج: 2026-11-22
