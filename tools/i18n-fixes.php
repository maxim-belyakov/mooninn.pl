<?php
/**
 * Fills storefront strings that the PL/RU language packs leave in English
 * (new Hummingbird wordings) and translates demo content: CMS pages,
 * reassurance blocks, custom text, comment criterion, contacts.
 * Run from public_html: php ~/i18n-fixes.php
 */
if (PHP_SAPI !== 'cli') { exit(1); }
require getcwd() . '/config/config.inc.php';
$ctx = Context::getContext();
$ctx->shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
Shop::setContext(Shop::CONTEXT_SHOP, $ctx->shop->id);
$db = Db::getInstance();
$P = _DB_PREFIX_;
$L = [];
foreach (Language::getLanguages(false) as $l) { $L[$l['iso_code']] = (int) $l['id_lang']; }

// 1. Interface strings: [domain, key, pl, ru]
$strings = [
    ['Shop.Theme.Catalog', 'All featured products', 'Wszystkie polecane', 'Все рекомендуемые'],
    ['Shop.Theme.Catalog', 'Latest arrivals', 'Nowości', 'Новинки'],
    ['Shop.Theme.Catalog', 'Search products...', 'Szukaj komiksów...', 'Поиск комиксов...'],
    ['Shop.Theme.Catalog', 'Back to home', 'Wróć na stronę główną', 'На главную'],
    ['Shop.Theme.Catalog', 'Check the spelling of your search term.', 'Sprawdź pisownię wyszukiwanej frazy.', 'Проверьте написание запроса.'],
    ['Shop.Theme.Catalog', 'Try a more general or different keyword.', 'Spróbuj ogólniejszej lub innej frazy.', 'Попробуйте более общий или другой запрос.'],
    ['Shop.Theme.Catalog', "We couldn't find any matching products.", 'Nie znaleźliśmy pasujących produktów.', 'Подходящих товаров не найдено.'],
    ['Shop.Theme.Catalog', 'Price: %price%.', 'Cena: %price%.', 'Цена: %price%.'],
    ['Shop.Theme.Catalog', 'Price: ', 'Cena: ', 'Цена: '],
    ['Shop.Theme.Catalog', 'Clear search', 'Wyczyść wyszukiwanie', 'Очистить поиск'],
    ['Shop.Theme.Catalog', 'Slide to product image %number%', 'Zdjęcie produktu %number%', 'Изображение товара %number%'],
    ['Shop.Theme.Global', 'Product availability:', 'Dostępność:', 'Наличие:'],
    ['Shop.Theme.Global', 'results', 'wyników', 'результатов'],
    ['Shop.Theme.Global', 'Skip to main content', 'Przejdź do treści', 'Перейти к содержимому'],
    ['Shop.Theme.Global', 'Toggle %s links', 'Rozwiń: %s', 'Развернуть: %s'],
    ['Shop.Theme.Global', 'Toggle store information', 'Rozwiń informacje o sklepie', 'Развернуть информацию о магазине'],
    ['Shop.Theme.Global', 'Change language', 'Zmień język', 'Сменить язык'],
    ['Shop.Theme.Global', 'Change sort order', 'Zmień sortowanie', 'Изменить сортировку'],
    ['Shop.Theme.Global', 'Close search', 'Zamknij wyszukiwanie', 'Закрыть поиск'],
    ['Shop.Theme.Global', 'Go to slide %slide_index%', 'Slajd %slide_index%', 'Слайд %slide_index%'],
    ['Shop.Theme.Global', 'Open zoomed product image gallery', 'Powiększ zdjęcia produktu', 'Увеличить изображения товара'],
    ['Shop.Theme.Global', 'Product filters', 'Filtry', 'Фильтры'],
    ['Shop.Theme.Global', 'Send us an email to: %email%', 'Napisz do nas: %email%', 'Напишите нам: %email%'],
    ['Shop.Theme.Global', 'Sort products by: %sort_order%', 'Sortuj: %sort_order%', 'Сортировка: %sort_order%'],
    ['Shop.Theme.Customeraccount', 'Toggle your account links', 'Rozwiń linki konta', 'Развернуть ссылки аккаунта'],
    ['Shop.Theme.Actions', 'Send your message', 'Wyślij wiadomość', 'Отправить сообщение'],
    ['Shop.Theme.Actions', 'Share on %social_share_link_label%', 'Udostępnij: %social_share_link_label%', 'Поделиться: %social_share_link_label%'],
    ['Shop.Theme.Actions', 'Show password', 'Pokaż hasło', 'Показать пароль'],
    ['Shop.Theme.Menu', 'Main navigation', 'Menu główne', 'Главное меню'],
    ['Shop.Theme.Menu', 'Open mobile menu', 'Otwórz menu', 'Открыть меню'],
    ['Modules.Productcomments.Shop', 'Rated', 'Ocena', 'Оценка'],
    ['Modules.Productcomments.Shop', 'Rating for ', 'Ocena dla ', 'Оценка для '],
    ['Modules.Productcomments.Shop', 'out of 5 stars based on ', 'na 5 gwiazdek, na podstawie ', 'из 5 звёзд, на основе '],
    ['Modules.Productcomments.Shop', 'review(s)', 'opinii', 'отзывов'],
    ['Modules.Productcomments.Shop', 'Title of your review', 'Tytuł opinii', 'Заголовок отзыва'],
    ['Modules.Productcomments.Shop', 'Send review', 'Wyślij opinię', 'Отправить отзыв'],
    ['Modules.Productcomments.Shop', '%s star out of 5', '%s gwiazdka na 5', '%s звезда из 5'],
    ['Modules.Productcomments.Shop', '%s stars out of 5', '%s gwiazdek na 5', '%s звёзд из 5'],
    ['Modules.Emailsubscription.Shop', 'Subscribe to our newsletter', 'Zapisz się do newslettera', 'Подпишитесь на рассылку'],
    ['Shop.Theme.Catalog', '%s other product in the same category', '%s inny produkt w tej kategorii', 'Ещё %s товар в этой категории'],
    ['Shop.Theme.Catalog', '%s other products in the same category', 'Inne produkty w tej kategorii: %s', 'Другие товары в этой категории: %s'],
    ['Shop.Theme.Catalog', 'Products pagination', 'Strony produktów', 'Страницы товаров'],
    ['Shop.Theme.Catalog', 'No search results for "%search_term%"', 'Brak wyników dla „%search_term%”', 'Нет результатов по запросу «%search_term%»'],
    ['Shop.Theme.Catalog', 'Reference, A to Z', 'Indeks, od A do Z', 'Артикул, от А до Я'],
    ['Shop.Theme.Catalog', 'Reference, Z to A', 'Indeks, od Z do A', 'Артикул, от Я до А'],
    ['Shop.Theme.Global', 'Carousel container', 'Karuzela', 'Карусель'],
    ['Shop.Theme.Global', 'Show search bar', 'Pokaż wyszukiwarkę', 'Показать поиск'],
    ['Shop.Theme.Global', 'Close', 'Zamknij', 'Закрыть'],
    ['Shop.Theme.Actions', 'Close', 'Zamknij', 'Закрыть'],
    ['Shop.Theme.Menu', 'Go back to main menu', 'Wróć do menu głównego', 'Назад в главное меню'],
    ['Shop.Theme.Customeraccount', 'Order tracking', 'Śledzenie zamówienia', 'Отслеживание заказа'],
    ['Modules.Productcomments.Shop', 'Please choose a rating for your review.', 'Wybierz ocenę.', 'Выберите оценку.'],
];
$n = 0;
foreach ($strings as [$domain, $key, $pl, $ru]) {
    // ps_translation.domain must match catalogue domains, which have no dots (ShopThemeCatalog)
    $domain = str_replace('.', '', $domain);
    foreach (['pl' => $pl, 'ru' => $ru] as $iso => $tr) {
        if (!isset($L[$iso])) { continue; }
        $db->delete('translation', "id_lang = {$L[$iso]} AND `key` = '" . pSQL($key) . "' AND domain = '" . pSQL($domain) . "'");
        foreach ([null, 'mooninn'] as $theme) {
            $db->insert('translation', ['id_lang' => $L[$iso], 'key' => pSQL($key), 'translation' => pSQL($tr), 'domain' => pSQL($domain), 'theme' => $theme], true);
            $n++;
        }
    }
}
echo "interface strings: $n rows\n";

// 2. CMS pages (titles, meta, slugs); bodies stay placeholders until real texts arrive
$cms = [
    'About us' => ['O nas', 'О нас'],
    'Delivery' => ['Dostawa', 'Доставка'],
    'Legal Notice' => ['Nota prawna', 'Правовая информация'],
    'Terms and conditions of use' => ['Regulamin', 'Условия использования'],
    'Secure payment' => ['Bezpieczne płatności', 'Безопасная оплата'],
];
$en = $L['en'];
foreach ($db->executeS("SELECT id_cms, meta_title FROM {$P}cms_lang WHERE id_lang = $en") as $r) {
    if (!isset($cms[$r['meta_title']])) { continue; }
    [$pl, $ru] = $cms[$r['meta_title']];
    foreach (['pl' => $pl, 'ru' => $ru] as $iso => $t) {
        $db->update('cms_lang', ['meta_title' => pSQL($t), 'head_seo_title' => pSQL($t), 'meta_description' => '', 'link_rewrite' => pSQL(Tools::str2url($t))],
            "id_cms = {$r['id_cms']} AND id_lang = {$L[$iso]}");
    }
}
echo "cms: ok\n";

// 3. Reassurance blocks (top bar + product page)
$reassurance = [
    ['Bezpieczne płatności', 'Płatność przelewem, wkrótce także BLIK', 'Безопасная оплата', 'Оплата переводом, скоро и BLIK', 'Secure payment', 'Bank transfer, BLIK coming soon'],
    ['Przedsprzedaż', 'Zamów teraz, wyślemy po premierze', 'Предзаказ', 'Закажите сейчас, отправим после выхода', 'Preorder', 'Order now, we ship after release'],
    ['Wysyłka w Polsce i za granicę', 'Paczkomaty InPost, kurier, Poczta Polska', 'Доставка по Польше и за границу', 'Пачкоматы InPost, курьер, Poczta Polska', 'Shipping in Poland and abroad', 'InPost lockers, courier, Poczta Polska'],
];
$ids = array_column($db->executeS("SELECT id_psreassurance FROM {$P}psreassurance ORDER BY position"), 'id_psreassurance');
foreach ($ids as $i => $id) {
    if (!isset($reassurance[$i])) { break; }
    $r = $reassurance[$i];
    foreach (['pl' => [$r[0], $r[1]], 'ru' => [$r[2], $r[3]], 'en' => [$r[4], $r[5]]] as $iso => [$t, $d]) {
        $db->update('psreassurance_lang', ['title' => pSQL($t), 'description' => pSQL($d)], "id_psreassurance = $id AND id_lang = {$L[$iso]}");
    }
}
echo 'reassurance: ' . count($ids) . " blocks\n";

// 4. Custom text block
$custom = [
    'pl' => '<h2>Mooninn</h2><p>Małe wydawnictwo komiksów: manhwa i manga w starannych polskich wydaniach. Strona jest w przygotowaniu, wszystkie tytuły są przykładowe.</p>',
    'ru' => '<h2>Mooninn</h2><p>Небольшое издательство комиксов: манхва и манга в аккуратных польских изданиях. Сайт готовится, все названия условные.</p>',
    'en' => '<h2>Mooninn</h2><p>A small comics publisher: manhwa and manga in careful Polish editions. The site is in progress, all titles are placeholders.</p>',
];
foreach ($custom as $iso => $html) { $db->update('info_lang', ['text' => pSQL($html, true)], "id_lang = {$L[$iso]}"); }
echo "custom text: ok\n";

// 5. Comment criterion, contacts, currency
foreach (['pl' => 'Jakość', 'ru' => 'Качество'] as $iso => $t) {
    $db->update('product_comment_criterion_lang', ['name' => pSQL($t)], "id_lang = {$L[$iso]} AND name = 'Quality'");
}
foreach ($db->executeS("SELECT id_contact, name FROM {$P}contact_lang WHERE id_lang = $en") as $c) {
    $map = ['Webmaster' => ['Sprawy techniczne', 'Технические вопросы'], 'Customer service' => ['Obsługa klienta', 'Служба поддержки']];
    if (!isset($map[$c['name']])) { continue; }
    $db->update('contact_lang', ['name' => pSQL($map[$c['name']][0])], "id_contact = {$c['id_contact']} AND id_lang = {$L['pl']}");
    $db->update('contact_lang', ['name' => pSQL($map[$c['name']][1])], "id_contact = {$c['id_contact']} AND id_lang = {$L['ru']}");
}
$usd = Currency::getIdByIsoCode('USD');
if ($usd) { $c = new Currency($usd); $c->active = 0; $c->update(); }
echo "criterion, contacts, USD off: ok\n";

// 6. Russian page titles (meta), copied from Polish when Russian was added
$metaRu = [
    'pagenotfound' => 'Страница не найдена', 'best-sales' => 'Хиты продаж', 'contact' => 'Контакты', 'index' => 'Главная',
    'manufacturer' => 'Бренды', 'new-products' => 'Новинки', 'password' => 'Забыли пароль', 'prices-drop' => 'Скидки',
    'sitemap' => 'Карта сайта', 'supplier' => 'Поставщики', 'address' => 'Адрес', 'addresses' => 'Адреса',
    'authentication' => 'Вход', 'registration' => 'Регистрация', 'cart' => 'Корзина', 'discount' => 'Скидки',
    'history' => 'История заказов', 'identity' => 'Личные данные', 'my-account' => 'Мой аккаунт',
    'order-follow' => 'Возвраты', 'order-slip' => 'Кредитные квитанции', 'order' => 'Заказ', 'search' => 'Поиск',
    'stores' => 'Магазины', 'guest-tracking' => 'Отслеживание заказа', 'order-confirmation' => 'Подтверждение заказа',
];
foreach ($metaRu as $page => $title) {
    $id = (int) $db->getValue("SELECT id_meta FROM {$P}meta WHERE page = '" . pSQL($page) . "'");
    if ($id) { $db->update('meta_lang', ['title' => pSQL($title)], "id_meta = $id AND id_lang = {$L['ru']}"); }
}
$db->update('meta_lang', ['description' => pSQL('Потерялись? Найдите то, что ищете!')], "id_lang = {$L['ru']} AND description LIKE 'Zagubi%'");
foreach (['best-sales' => 'Наши самые популярные книги', 'contact' => 'Воспользуйтесь формой обратной связи', 'new-products' => 'Наши новинки', 'prices-drop' => 'Книги со скидкой'] as $page => $d) {
    $id = (int) $db->getValue("SELECT id_meta FROM {$P}meta WHERE page = '" . pSQL($page) . "'");
    if ($id) { $db->update('meta_lang', ['description' => pSQL($d)], "id_meta = $id AND id_lang = {$L['ru']}"); }
}
foreach (['Pan' => 'Г-н', 'Pani' => 'Г-жа'] as $pl => $ru) { $db->update('gender_lang', ['name' => pSQL($ru)], "id_lang = {$L['ru']} AND name = '" . pSQL($pl) . "'"); }
// footer link blocks
foreach (['Produkty' => 'Товары', 'Nasza firma' => 'О компании'] as $pl => $ru) {
    $db->update('link_block_lang', ['name' => pSQL($ru)], "id_lang = {$L['ru']} AND name = '" . pSQL($pl) . "'");
}
// newsletter + GDPR consent texts
Configuration::updateValue('NW_CONDITIONS', [$L['ru'] => 'Вы можете отписаться в любой момент. Контакты указаны в правовой информации.'] + (array) Configuration::getConfigInMultipleLangs('NW_CONDITIONS'), true);
if ($db->getValue("SHOW TABLES LIKE '{$P}psgdpr_consent_lang'")) {
    foreach ($db->executeS("SHOW COLUMNS FROM {$P}psgdpr_consent_lang") as $col) {
        if (in_array($col['Field'], ['id_gdpr_consent', 'id_lang', 'id_shop', 'id_module', 'date_add', 'date_upd'], true) || stripos($col['Type'], 'text') === false) { continue; }
        $f = bqSQL($col['Field']);
        $db->execute("UPDATE {$P}psgdpr_consent_lang SET `$f` = '" . pSQL('Zapoznałem się i akceptuję regulamin oraz politykę prywatności') . "' WHERE id_lang = {$L['pl']} AND `$f` LIKE 'I agree%'");
        $db->execute("UPDATE {$P}psgdpr_consent_lang SET `$f` = '" . pSQL('Я принимаю условия использования и политику конфиденциальности') . "' WHERE id_lang = {$L['ru']} AND `$f` LIKE 'I agree%'");
    }
}
foreach (['pl', 'ru'] as $iso) {
    $t = $iso === 'pl' ? 'Zapoznałem się i akceptuję regulamin oraz politykę prywatności' : 'Я принимаю условия использования и политику конфиденциальности';
    foreach ($db->executeS("SELECT name FROM {$P}configuration WHERE name IN ('PSGDPR_CREATION_FORM', 'PSGDPR_CUSTOMER_FORM')") as $c) {
        $v = Configuration::get($c['name'], $L[$iso]);
        if (is_string($v) && stripos($v, 'I agree') !== false) { Configuration::updateValue($c['name'], [$L[$iso] => $t] + (array) Configuration::getConfigInMultipleLangs($c['name']), true); }
    }
}
foreach ($db->executeS("SELECT id_contact FROM {$P}contact") as $c) {
    $db->update('contact_lang', ['description' => pSQL('Воспользуйтесь формой обратной связи')], "id_contact = {$c['id_contact']} AND id_lang = {$L['ru']} AND description LIKE 'Skorzystaj%'");
}
echo "russian content: ok\n";

Tools::clearSf2Cache();
Tools::clearSmartyCache();
echo "done\n";
