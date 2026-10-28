<?php
/**
 * Demo of language editions on all demo books (DEMO-01..05):
 * attribute group "Wydanie / Edition / Издание" with Polish, Russian and English
 * editions, each a combination with its own cover (demo-0N.png, demo-0N-ru.png,
 * demo-0N-en.png). Polish is the default. Safe to re-run.
 * Prints the attribute ids the theme needs (theme/assets/js/editions.js).
 * Run from public_html: php ~/editions.php ~/demo-covers
 */
if (PHP_SAPI !== 'cli') { exit(1); }
$coversDir = rtrim($argv[1] ?? '', '/');
require getcwd() . '/config/config.inc.php';
$ctx = Context::getContext();
$ctx->shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
Shop::setContext(Shop::CONTEXT_SHOP, $ctx->shop->id);
$db = Db::getInstance();
$P = _DB_PREFIX_;
$L = [];
foreach (Language::getLanguages(false) as $l) { $L[$l['iso_code']] = (int) $l['id_lang']; }
function ml(array $t, array $L): array { $o = []; foreach ($L as $iso => $id) { $o[$id] = $t[$iso] ?? $t['pl']; } return $o; }

// Attribute group (reuse if it already exists)
$groupName = ['pl' => 'Wydanie', 'en' => 'Edition', 'ru' => 'Издание'];
$idGroup = (int) $db->getValue("SELECT id_attribute_group FROM {$P}attribute_group_lang WHERE name = 'Wydanie' AND id_lang = {$L['pl']}");
if (!$idGroup) {
    $g = new AttributeGroup();
    $g->name = ml($groupName, $L);
    $g->public_name = ml($groupName, $L);
    $g->group_type = 'select';
    $g->add();
    $idGroup = (int) $g->id;
}

$editions = [
    'pl' => ['name' => ['pl' => 'Polskie', 'en' => 'Polish', 'ru' => 'Польское'], 'default' => true],
    'ru' => ['name' => ['pl' => 'Rosyjskie', 'en' => 'Russian', 'ru' => 'Русское'], 'default' => false],
    'en' => ['name' => ['pl' => 'Angielskie', 'en' => 'English', 'ru' => 'Английское'], 'default' => false],
];
$map = [];

$types = ImageType::getImagesTypes('products');
foreach (['DEMO-01', 'DEMO-02', 'DEMO-03', 'DEMO-04', 'DEMO-05'] as $n => $ref) {
    $idProduct = (int) $db->getValue("SELECT id_product FROM {$P}product WHERE reference = '" . pSQL($ref) . "'");
    if (!$idProduct) { fwrite(STDERR, "$ref not found\n"); continue; }
    $product = new Product($idProduct);
    $coverBase = sprintf('demo-%02d', $n + 1);
    // Start clean: drop combinations and every image except the Polish cover
    foreach ($product->getAttributeCombinations() as $c) { (new Combination((int) $c['id_product_attribute']))->delete(); }
    $existingCover = Image::getCover($idProduct);
    foreach (Image::getImages($L['pl'], $idProduct) as $im) {
        if ((int) $im['id_image'] !== (int) $existingCover['id_image']) { (new Image((int) $im['id_image']))->delete(); }
    }
    foreach ($editions as $iso => $e) {
        $idAttr = (int) $db->getValue("SELECT a.id_attribute FROM {$P}attribute a JOIN {$P}attribute_lang al ON al.id_attribute = a.id_attribute AND al.id_lang = {$L['pl']} WHERE a.id_attribute_group = $idGroup AND al.name = '" . pSQL($e['name']['pl']) . "'");
        if (!$idAttr) {
            $a = new ProductAttribute();
            $a->id_attribute_group = $idGroup;
            $a->name = ml($e['name'], $L);
            $a->add();
            $idAttr = (int) $a->id;
        }
        // image: the Polish cover already exists on the product, others are added
        if ($iso === 'pl' && $existingCover) {
            $idImage = (int) $existingCover['id_image'];
        } else {
            $img = new Image();
            $img->id_product = $idProduct;
            $img->position = Image::getHighestPosition($idProduct) + 1;
            $img->cover = 0;
            $img->legend = ml(['pl' => 'Okładka: wydanie ' . mb_strtolower($e['name']['pl']), 'en' => $e['name']['en'] . ' edition cover', 'ru' => 'Обложка: ' . mb_strtolower($e['name']['ru']) . ' издание'], $L);
            $img->add();
            $base = $img->getPathForCreation();
            $src = "$coversDir/$coverBase" . ($iso === 'pl' ? '' : "-$iso") . '.png';
            ImageManager::resize($src, $base . '.jpg');
            foreach ($types as $t) { ImageManager::resize($src, $base . '-' . $t['name'] . '.jpg', (int) $t['width'], (int) $t['height']); }
            $idImage = (int) $img->id;
        }
        $c = new Combination();
        $c->id_product = $idProduct;
        $c->price = 0;
        $c->weight = 0;
        $c->minimal_quantity = 1;
        $c->default_on = $e['default'] ? 1 : null;
        $c->reference = $ref . '-' . strtoupper($iso);
        $c->add();
        $c->setAttributes([$idAttr]);
        $c->setImages([$idImage]);
        StockAvailable::setQuantity($idProduct, (int) $c->id, 0);
        StockAvailable::setProductOutOfStock($idProduct, 1, null, (int) $c->id);
        $map[$iso] = $idAttr;
        echo "$ref $iso: attribute $idAttr, combination {$c->id}, image $idImage\n";
    }
    Product::updateDefaultAttribute($idProduct);
}
Tools::clearSmartyCache();
echo 'EDITIONS ' . json_encode(['group' => $idGroup, 'values' => $map]) . "\n";
