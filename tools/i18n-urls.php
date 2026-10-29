<?php
/**
 * English slugs in every language (URLs are the same on /pl/, /en/, /ru/),
 * names for the home category (shown in the category tree) and the CMS root,
 * and an English price format. Re-runnable.
 *
 * Run on the server: php tools/i18n-urls.php (from ~/public_html or with the path).
 */
require '/home/moonmrys/public_html/config/config.inc.php';
$db = Db::getInstance();
$p = _DB_PREFIX_;
$en = (int) Language::getIdByIso('en');

// Home category: "Catalog", it is the root of the category tree and of /catalog
$names = ['pl' => 'Katalog', 'en' => 'Catalog', 'ru' => 'Каталог', 'ar' => 'Catalog'];
foreach ($names as $iso => $name) {
    $id = (int) Language::getIdByIso($iso);
    if ($id) {
        $db->update('category_lang', ['name' => pSQL($name), 'meta_title' => pSQL($name)], 'id_category = ' . (int) Configuration::get('PS_HOME_CATEGORY') . ' AND id_lang = ' . $id);
    }
}
$db->update('category_lang', ['link_rewrite' => 'catalog'], 'id_category = ' . (int) Configuration::get('PS_HOME_CATEGORY'));

// CMS root category
foreach (['pl' => 'Strona główna', 'en' => 'Home', 'ru' => 'Главная', 'ar' => 'Home'] as $iso => $name) {
    $id = (int) Language::getIdByIso($iso);
    if ($id) {
        $db->update('cms_category_lang', ['name' => pSQL($name)], 'id_cms_category = 1 AND id_lang = ' . $id);
    }
}

// Copy the English slug to every language
$tables = [
    ['category_lang', 'id_category', 'link_rewrite'],
    ['product_lang', 'id_product', 'link_rewrite'],
    ['cms_lang', 'id_cms', 'link_rewrite'],
    ['cms_category_lang', 'id_cms_category', 'link_rewrite'],
];
foreach ($tables as [$table, $key, $col]) {
    $db->execute("UPDATE `{$p}{$table}` t JOIN `{$p}{$table}` e ON e.`{$key}` = t.`{$key}` AND e.id_shop = t.id_shop AND e.id_lang = {$en}
        SET t.`{$col}` = e.`{$col}` WHERE t.id_lang <> {$en}");
    echo $table, ': ', $db->Affected_Rows(), "\n";
}
$db->execute("UPDATE `{$p}meta_lang` t JOIN `{$p}meta_lang` e ON e.id_meta = t.id_meta AND e.id_shop = t.id_shop AND e.id_lang = {$en}
    SET t.url_rewrite = e.url_rewrite WHERE t.id_lang <> {$en}");
echo 'meta_lang: ', $db->Affected_Rows(), "\n";

// English price as "39.99 zł" instead of "zł39.99"
$db->execute("UPDATE `{$p}currency_lang` SET pattern = '#,##0.00 ¤' WHERE id_lang = {$en}");

// Duplicate slugs would break lookups by slug
foreach ($tables as [$table, $key, $col]) {
    $dups = $db->executeS("SELECT `{$col}` s, COUNT(DISTINCT `{$key}`) n FROM `{$p}{$table}` WHERE id_lang = {$en} GROUP BY `{$col}` HAVING n > 1");
    foreach ((array) $dups as $d) {
        echo "DUPLICATE in {$table}: {$d['s']}\n";
    }
}
Tools::clearSmartyCache();
Tools::clearSf2Cache();
echo "done\n";
