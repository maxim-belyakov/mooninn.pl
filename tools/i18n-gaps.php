<?php
/**
 * Second i18n pass after the RU/EN audit (tools/audit): theme strings the
 * packs miss, honest placeholder bodies for CMS pages instead of the
 * PrestaShop demo text, the data privacy note, demo stores off.
 * Run on the server: php i18n-gaps.php. Re-runnable.
 */
if (PHP_SAPI !== 'cli') { exit(1); }
require '/home/moonmrys/public_html/config/config.inc.php';
$db = Db::getInstance();
$L = [];
foreach (Language::getLanguages(false) as $l) { $L[$l['iso_code']] = (int) $l['id_lang']; }

// 1. Interface strings: [domain without dots, key, ru]
$strings = [
    ['ShopThemeCatalog', 'No products available at the moment', 'Пока здесь нет товаров'],
    ['ShopThemeCatalog', 'Stay tuned! More products will be shown here as they are added.', 'Загляните позже: новые книги появятся здесь, как только мы их добавим.'],
    ['ShopThemeCatalog', 'Back to Home page', 'На главную'],
    ['ModulesFacetedsearchShop', 'Selections', 'Подборки'],
];
foreach ($strings as [$domain, $key, $ru]) {
    $db->delete('translation', "id_lang = {$L['ru']} AND `key` = '" . pSQL($key) . "' AND domain = '{$domain}'");
    foreach ([null, 'mooninn'] as $theme) {
        $db->insert('translation', ['id_lang' => $L['ru'], 'key' => pSQL($key), 'translation' => pSQL($ru), 'domain' => $domain, 'theme' => $theme], true);
    }
}
echo "strings: ", count($strings), "\n";

// 2. CMS bodies: id_cms => [pl, en, ru]
$cms = [
    1 => ['Informacje o dostawie, kosztach wysyłki i zwrotach pojawią się tutaj przed startem sprzedaży.',
          'Delivery options, shipping costs and returns will be described here before sales start.',
          'Здесь появится информация о доставке, её стоимости и возвратах до начала продаж.'],
    2 => ['Dane wydawnictwa i informacje prawne pojawią się tutaj wkrótce.',
          'Publisher details and legal information will appear here soon.',
          'Реквизиты издательства и правовая информация скоро появятся здесь.'],
    3 => ['Regulamin sklepu jest w przygotowaniu i zostanie opublikowany przed startem sprzedaży.',
          'The store terms and conditions are being prepared and will be published before sales start.',
          'Правила магазина готовятся и будут опубликованы до начала продаж.'],
    4 => ['Mooninn to wydawnictwo komiksów: mangi i manhwy. Więcej o nas wkrótce.',
          'Mooninn is a comics publisher: manga and manhwa. More about us soon.',
          'Mooninn - издательство комиксов: манга и манхва. Скоро расскажем о себе подробнее.'],
    5 => ['Informacje o metodach płatności pojawią się tutaj przed startem sprzedaży.',
          'Payment methods will be described here before sales start.',
          'Здесь появится информация о способах оплаты до начала продаж.'],
];
foreach ($cms as $id => $texts) {
    foreach (['pl', 'en', 'ru'] as $i => $iso) {
        $db->update('cms_lang', ['content' => pSQL('<p>' . $texts[$i] . '</p>', true)], "id_cms = {$id} AND id_lang = {$L[$iso]}");
    }
}
echo "cms: ", count($cms), "\n";

// 3. Data privacy note on registration (ps_dataprivacy)
Configuration::updateValue('CUSTPRIV_MSG_AUTH', [
    $L['pl'] => 'Podane dane osobowe wykorzystujemy, aby odpowiadać na pytania, realizować zamówienia i dawać dostęp do konta. W każdej chwili możesz je poprawić lub usunąć w zakładce „Moje konto”.',
    $L['en'] => 'We use the personal data you provide to answer questions, process orders and give access to your account. You can change or delete it at any time on the "My account" page.',
    $L['ru'] => 'Ваши персональные данные мы используем, чтобы отвечать на вопросы, выполнять заказы и давать доступ к аккаунту. Изменить или удалить их можно в любой момент на странице «Мой аккаунт».',
], true);

// 4. PrestaShop demo stores in Miami
$db->update('store', ['active' => 0]);
echo "stores off\n";

Tools::clearSmartyCache();
Tools::clearSf2Cache();
echo "done\n";
