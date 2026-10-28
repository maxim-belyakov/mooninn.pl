<?php
/**
 * Mooninn demo catalog seeder (run on the server from public_html):
 *   php ~/seed.php ~/demo-covers
 *
 * 1. Polish/EN/RU maintenance text.
 * 2. Preorder: orders allowed when out of stock, "Przedsprzedaż" label.
 * 3. Removes the PrestaShop clothing demo catalog (products, categories,
 *    features, attribute groups, brands, suppliers).
 * 4. Adds 2 categories and 5 fictional manga/manhwa demo books with covers.
 * Safe to re-run: step 3 removes everything except Root/Home first.
 */
if (PHP_SAPI !== 'cli') { exit(1); }
$coversDir = rtrim($argv[1] ?? '', '/');
if (!is_dir($coversDir)) { fwrite(STDERR, "covers dir missing\n"); exit(1); }

require getcwd() . '/config/config.inc.php';
$ctx = Context::getContext();
$ctx->shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
Shop::setContext(Shop::CONTEXT_SHOP, $ctx->shop->id);

$langs = [];
foreach (Language::getLanguages(false) as $l) { $langs[$l['iso_code']] = (int) $l['id_lang']; }
/** Build a multilang field from ['pl'=>..,'en'=>..,'ru'=>..]; other languages fall back to pl. */
function ml(array $t, array $langs): array {
    $out = [];
    foreach ($langs as $iso => $id) { $out[$id] = $t[$iso] ?? $t['pl']; }
    return $out;
}
function slugs(array $names): array {
    $out = [];
    foreach ($names as $id => $n) { $out[$id] = Tools::str2url($n); }
    return $out;
}

// 1. Maintenance text
Configuration::updateValue('PS_MAINTENANCE_TEXT', ml([
    'pl' => '<p>Mooninn - wydawnictwo komiksów. Strona jest w przygotowaniu, wkrótce ruszy przedsprzedaż.</p>',
    'en' => '<p>Mooninn - comics publisher. The site is being prepared, preorders open soon.</p>',
    'ru' => '<p>Mooninn - издательство комиксов. Сайт готовится, скоро откроется предзаказ.</p>',
], $langs), true);
echo "maintenance text: ok\n";

// 2. Preorder settings
Configuration::updateValue('PS_ORDER_OUT_OF_STOCK', 1);
Configuration::updateValue('PS_LABEL_OOS_PRODUCTS_BOA', ml([
    'pl' => 'Przedsprzedaż', 'en' => 'Preorder', 'ru' => 'Предзаказ',
], $langs));
echo "preorder settings: ok\n";

// 3. Remove demo catalog
$db = Db::getInstance();
foreach ($db->executeS('SELECT id_product FROM ' . _DB_PREFIX_ . 'product') as $r) {
    (new Product((int) $r['id_product']))->delete();
}
$home = (int) Configuration::get('PS_HOME_CATEGORY');
$root = (int) Configuration::get('PS_ROOT_CATEGORY');
foreach ($db->executeS('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_parent = ' . $home) as $r) {
    $c = new Category((int) $r['id_category']);
    if (Validate::isLoadedObject($c)) { $c->delete(); }
}
foreach ($db->executeS('SELECT id_feature FROM ' . _DB_PREFIX_ . 'feature') as $r) { (new Feature((int) $r['id_feature']))->delete(); }
foreach ($db->executeS('SELECT id_attribute_group FROM ' . _DB_PREFIX_ . 'attribute_group') as $r) { (new AttributeGroup((int) $r['id_attribute_group']))->delete(); }
foreach ($db->executeS('SELECT id_manufacturer FROM ' . _DB_PREFIX_ . 'manufacturer') as $r) { (new Manufacturer((int) $r['id_manufacturer']))->delete(); }
foreach ($db->executeS('SELECT id_supplier FROM ' . _DB_PREFIX_ . 'supplier') as $r) { (new Supplier((int) $r['id_supplier']))->delete(); }
echo 'removed demo; products left: ' . (int) $db->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product') . "\n";

// 4a. Categories
$cats = [];
foreach ([
    'manhwa' => ['pl' => 'Manhwa', 'en' => 'Manhwa', 'ru' => 'Манхва',
        'd' => ['pl' => 'Koreańskie komiksy w polskim wydaniu.', 'en' => 'Korean comics in Polish editions.', 'ru' => 'Корейские комиксы в польском издании.']],
    'manga' => ['pl' => 'Manga', 'en' => 'Manga', 'ru' => 'Манга',
        'd' => ['pl' => 'Japońskie komiksy i nasze własne serie.', 'en' => 'Japanese comics and our own series.', 'ru' => 'Японские комиксы и наши собственные серии.']],
] as $key => $t) {
    $c = new Category();
    $c->id_parent = $home;
    $c->active = 1;
    $c->name = ml($t, $langs);
    $c->link_rewrite = slugs($c->name);
    $c->description = ml($t['d'], $langs);
    $c->add();
    $cats[$key] = (int) $c->id;
}
echo "categories: " . json_encode($cats) . "\n";

// 4b. Features
function feature(array $name, array $langs): int {
    $f = new Feature(); $f->name = ml($name, $langs); $f->add(); return (int) $f->id;
}
function fvalue(int $idFeature, array $value, array $langs): int {
    $v = new FeatureValue(); $v->id_feature = $idFeature; $v->custom = 0; $v->value = ml($value, $langs); $v->add(); return (int) $v->id;
}
$fAuthor = feature(['pl' => 'Autor', 'en' => 'Author', 'ru' => 'Автор'], $langs);
$fPages = feature(['pl' => 'Liczba stron', 'en' => 'Pages', 'ru' => 'Страниц'], $langs);
$fBinding = feature(['pl' => 'Oprawa', 'en' => 'Binding', 'ru' => 'Переплёт'], $langs);
$fAge = feature(['pl' => 'Wiek czytelnika', 'en' => 'Reader age', 'ru' => 'Возраст'], $langs);
$soft = fvalue($fBinding, ['pl' => 'Miękka ze skrzydełkami', 'en' => 'Paperback with flaps', 'ru' => 'Мягкая с клапанами'], $langs);
$hard = fvalue($fBinding, ['pl' => 'Twarda', 'en' => 'Hardcover', 'ru' => 'Твёрдая'], $langs);
$age13 = fvalue($fAge, ['pl' => '13+', 'en' => '13+'], $langs);
$age16 = fvalue($fAge, ['pl' => '16+', 'en' => '16+'], $langs);
$authors = [];
$pagesVals = [];

// 4c. Books (all fictional, marked as demo)
$later = ['pl' => 'Przedsprzedaż, wysyłka planowana na kwiecień 2027', 'en' => 'Preorder, shipping planned for April 2027', 'ru' => 'Предзаказ, отправка планируется в апреле 2027'];
$demoNote = ['pl' => '<p><em>Produkt demonstracyjny. Tytuł i okładka są przykładowe.</em></p>', 'en' => '<p><em>Demo product. The title and cover are placeholders.</em></p>', 'ru' => '<p><em>Демо-товар. Название и обложка условные.</em></p>'];
$books = [
    ['ref' => 'DEMO-01', 'cat' => 'manhwa', 'price' => 39.99, 'pages' => 224, 'binding' => $soft, 'age' => $age13, 'author' => 'Seo Ha-eun',
        'name' => ['pl' => 'Księżycowy Zajazd, tom 1', 'en' => 'The Moon Inn, vol. 1', 'ru' => 'Лунный постоялый двор, том 1'],
        'short' => ['pl' => 'Zajazd, który otwiera się tylko przy pełni. Młoda kucharka trafia tam przypadkiem i zostaje na dłużej.', 'en' => 'An inn that opens only at full moon. A young cook ends up there by chance and stays longer than planned.', 'ru' => 'Постоялый двор, который открывается только в полнолуние. Юная повариха попадает туда случайно и остаётся дольше, чем собиралась.']],
    ['ref' => 'DEMO-02', 'cat' => 'manhwa', 'price' => 39.99, 'pages' => 216, 'binding' => $soft, 'age' => $age13, 'author' => 'Seo Ha-eun',
        'name' => ['pl' => 'Księżycowy Zajazd, tom 2', 'en' => 'The Moon Inn, vol. 2', 'ru' => 'Лунный постоялый двор, том 2'],
        'short' => ['pl' => 'Goście zajazdu przynoszą coraz dziwniejsze zamówienia, a księżyc nie chce zajść.', 'en' => 'The guests bring stranger and stranger orders, and the moon refuses to set.', 'ru' => 'Гости заказывают всё более странные блюда, а луна отказывается заходить.']],
    ['ref' => 'DEMO-03', 'cat' => 'manga', 'price' => 44.99, 'pages' => 192, 'binding' => $soft, 'age' => $age13, 'author' => 'Mira Kowal',
        'name' => ['pl' => 'Ogród Lisów, tom 1', 'en' => 'The Fox Garden, vol. 1', 'ru' => 'Лисий сад, том 1'],
        'short' => ['pl' => 'Opowieść o dziewczynie, która dziedziczy zaniedbany ogród i jego rudych mieszkańców.', 'en' => 'A girl inherits a neglected garden and its red-furred residents.', 'ru' => 'Девушка получает в наследство заброшенный сад и его рыжих обитателей.']],
    ['ref' => 'DEMO-04', 'cat' => 'manhwa', 'price' => 49.99, 'pages' => 288, 'binding' => $hard, 'age' => $age13, 'author' => 'Yun Ji-ho',
        'name' => ['pl' => 'Herbaciarnia o Północy, tom 1', 'en' => 'The Midnight Teahouse, vol. 1', 'ru' => 'Чайная в полночь, том 1'],
        'short' => ['pl' => 'Herbaciarnia czynna od północy do świtu. Każda filiżanka to jedna historia klienta.', 'en' => 'A teahouse open from midnight till dawn. Every cup is one customer\'s story.', 'ru' => 'Чайная работает с полуночи до рассвета. Каждая чашка - история одного гостя.']],
    ['ref' => 'DEMO-05', 'cat' => 'manga', 'price' => 42.99, 'pages' => 208, 'binding' => $soft, 'age' => $age16, 'author' => 'Anna Lis',
        'name' => ['pl' => 'Cień nad Wisłą, tom 1', 'en' => 'Shadow over the Vistula, vol. 1', 'ru' => 'Тень над Вислой, том 1'],
        'short' => ['pl' => 'Mroczna historia z Warszawy, w której rzeka pamięta więcej niż ludzie.', 'en' => 'A dark Warsaw story in which the river remembers more than people do.', 'ru' => 'Мрачная варшавская история, в которой река помнит больше, чем люди.']],
];

$types = ImageType::getImagesTypes('products');
foreach ($books as $i => $b) {
    $p = new Product();
    $p->name = ml($b['name'], $langs);
    $p->link_rewrite = slugs($p->name);
    $p->description_short = ml(array_map(fn ($s) => "<p>$s</p>", $b['short']), $langs);
    $desc = [];
    foreach (['pl', 'en', 'ru'] as $iso) { $desc[$iso] = '<p>' . $b['short'][$iso] . '</p>' . $demoNote[$iso]; }
    $p->description = ml($desc, $langs);
    $p->available_later = ml($later, $langs);
    $p->reference = $b['ref'];
    $p->price = $b['price'];
    $p->id_tax_rules_group = 0;
    $p->id_category_default = $cats[$b['cat']];
    $p->active = 1;
    $p->available_for_order = 1;
    $p->show_price = 1;
    $p->visibility = 'both';
    $p->condition = 'new';
    $p->out_of_stock = 1; // allow orders when out of stock = preorder
    $p->weight = 0.35;
    $p->add();
    $p->addToCategories([$home, $cats[$b['cat']]]);
    StockAvailable::setQuantity((int) $p->id, 0, 0);
    StockAvailable::setProductOutOfStock((int) $p->id, 1);

    if (!isset($authors[$b['author']])) { $authors[$b['author']] = fvalue($fAuthor, ['pl' => $b['author']], $langs); }
    if (!isset($pagesVals[$b['pages']])) { $pagesVals[$b['pages']] = fvalue($fPages, ['pl' => (string) $b['pages']], $langs); }
    Product::addFeatureProductImport((int) $p->id, $fAuthor, $authors[$b['author']]);
    Product::addFeatureProductImport((int) $p->id, $fPages, $pagesVals[$b['pages']]);
    Product::addFeatureProductImport((int) $p->id, $fBinding, $b['binding']);
    Product::addFeatureProductImport((int) $p->id, $fAge, $b['age']);

    $src = sprintf('%s/demo-%02d.png', $coversDir, $i + 1);
    $img = new Image();
    $img->id_product = (int) $p->id;
    $img->position = 1;
    $img->cover = 1;
    $img->legend = $p->name;
    $img->add();
    $base = $img->getPathForCreation();
    ImageManager::resize($src, $base . '.jpg');
    foreach ($types as $t) {
        ImageManager::resize($src, $base . '-' . $t['name'] . '.jpg', (int) $t['width'], (int) $t['height']);
    }
    echo "book {$b['ref']} -> product {$p->id}\n";
}
Tools::clearSf2Cache();
Tools::clearSmartyCache();
echo "done\n";
