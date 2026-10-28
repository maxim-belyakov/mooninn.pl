<?php
/**
 * Applies the placeholder brand (logo, favicon, 3 home slides) to the shop.
 * Run from public_html: php ~/apply.php ~/brand-placeholder
 */
if (PHP_SAPI !== 'cli') { exit(1); }
// not $dir: config.inc.php overwrites a global with that name
$srcDir = rtrim($argv[1] ?? '', '/');
require getcwd() . '/config/config.inc.php';
$ctx = Context::getContext();
$ctx->shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
Shop::setContext(Shop::CONTEXT_SHOP, $ctx->shop->id);
$db = Db::getInstance();
$langs = [];
foreach (Language::getLanguages(false) as $l) { $langs[$l['iso_code']] = (int) $l['id_lang']; }

// Logo and favicon
foreach ([["$srcDir/mooninn-logo.png", _PS_IMG_DIR_ . 'mooninn-logo.png'], ["$srcDir/favicon.ico", _PS_IMG_DIR_ . 'favicon.ico']] as [$from, $to]) {
    if (!copy($from, $to)) { fwrite(STDERR, "copy failed: $from\n"); exit(1); }
    chmod($to, 0644);
}
Configuration::updateValue('PS_LOGO', 'mooninn-logo.png');
Configuration::updateValue('PS_FAVICON', 'favicon.ico');
echo "logo + favicon: ok\n";

// Slides: reuse the 3 existing slide rows, replace image and texts per language
$slides = [
    ['img' => 'slide-1.jpg', 'url' => 'index.php?id_product=20&controller=product',
        'pl' => ['Księżycowy Zajazd', 'Przedsprzedaż pierwszego tomu już trwa'],
        'en' => ['The Moon Inn', 'Volume one is open for preorder'],
        'ru' => ['Лунный постоялый двор', 'Открыт предзаказ первого тома']],
    ['img' => 'slide-2.jpg', 'url' => 'index.php?id_product=22&controller=product',
        'pl' => ['Ogród Lisów', 'Nowa seria w naszym katalogu'],
        'en' => ['The Fox Garden', 'A new series in our catalogue'],
        'ru' => ['Лисий сад', 'Новая серия в нашем каталоге']],
    ['img' => 'slide-3.jpg', 'url' => 'index.php?id_category=10&controller=category',
        'pl' => ['Manhwa po polsku', 'Koreańskie komiksy w starannych polskich wydaniach'],
        'en' => ['Manhwa in Polish', 'Korean comics in careful Polish editions'],
        'ru' => ['Манхва на польском', 'Корейские комиксы в аккуратных польских изданиях']],
];
$ids = array_column($db->executeS('SELECT id_homeslider_slides FROM ' . _DB_PREFIX_ . 'homeslider_slides ORDER BY position'), 'id_homeslider_slides');
$imgDir = _PS_MODULE_DIR_ . 'ps_imageslider/images/';
foreach ($slides as $i => $s) {
    if (!copy("$srcDir/{$s['img']}", $imgDir . $s['img'])) { fwrite(STDERR, "copy failed: {$s['img']}\n"); exit(1); }
    chmod($imgDir . $s['img'], 0644);
    $id = (int) $ids[$i];
    foreach ($langs as $iso => $idLang) {
        [$title, $desc] = $s[$iso] ?? $s['pl'];
        $db->update('homeslider_slides_lang', [
            'title' => pSQL($title),
            'legend' => pSQL($title),
            'description' => pSQL('<p>' . $desc . '</p>', true),
            'url' => pSQL($s['url']),
            'image' => pSQL($s['img']),
        ], 'id_homeslider_slides = ' . $id . ' AND id_lang = ' . (int) $idLang);
    }
    echo "slide $id: {$s['img']}\n";
}
// Home banner: one text-free image for all languages; the text is the
// per-language description ("Title | Subtitle"), drawn by the theme template
$bannerDir = _PS_MODULE_DIR_ . 'ps_banner/img/';
if (!copy("$srcDir/banner-bg.jpg", $bannerDir . 'banner-bg.jpg')) { fwrite(STDERR, "copy failed: banner-bg.jpg\n"); exit(1); }
chmod($bannerDir . 'banner-bg.jpg', 0644);
$img = $link = $desc = [];
$bannerText = [
    'pl' => 'Przedsprzedaż już trwa | Zamów teraz, wyślemy pierwsze tomy w kwietniu 2027',
    'en' => 'Preorders are open | Order now, first volumes ship in April 2027',
    'ru' => 'Предзаказ открыт | Закажите сейчас, первые тома отправим в апреле 2027',
];
foreach ($langs as $iso => $idLang) {
    $img[$idLang] = 'banner-bg.jpg';
    $link[$idLang] = Context::getContext()->link->getPageLink('new-products', true, $idLang);
    $desc[$idLang] = $bannerText[$iso] ?? $bannerText['pl'];
}
Configuration::updateValue('BANNER_IMG', $img);
Configuration::updateValue('BANNER_LINK', $link);
Configuration::updateValue('BANNER_DESC', $desc);
echo "banner: " . json_encode($img) . "\n";

Tools::clearSmartyCache();
echo "done\n";
