<?php
/**
 * Mooninn clean URLs: links without numeric IDs.
 *
 *   /en/manga                              category
 *   /en/manhwa/the-moon-inn-vol-1.html     product
 *   /en/info/about-us                      CMS page
 *   /en/brand/<name>, /en/supplier/<name>  brands and suppliers
 *
 * The core router needs an {id} in every rule, so this module replaces the
 * rules after they are loaded (actionAfterLoadRoutes) and, before the
 * controller runs (actionDispatcherBefore), finds the ID by the slug.
 * Old URLs with IDs still resolve and the core canonical redirect sends
 * them to the new address with a 301.
 *
 * Slugs must be unique within each kind; all languages use the English slug.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class MooninnUrls extends Module
{
    private const SLUG = '[_a-zA-Z0-9\pL-]+';

    public function __construct()
    {
        $this->name = 'mooninnurls';
        $this->tab = 'seo';
        $this->version = '1.0.0';
        $this->author = 'Mooninn';
        $this->need_instance = 0;
        parent::__construct();
        $this->displayName = 'Mooninn: clean URLs';
        $this->description = 'Category, product and CMS URLs without numeric IDs.';
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionAfterLoadRoutes')
            && $this->registerHook('actionDispatcherBefore');
    }

    /** route name => [rule, kind]; order matters: the catch-all category rule goes last */
    private function rules(): array
    {
        return [
            'cms_category_rule' => ['info/section/{rewrite}', 'cms_category'],
            'cms_rule' => ['info/{rewrite}', 'cms'],
            'mooninn_legacy_cms_category' => ['content/category/{rewrite}', 'cms_category'],
            'mooninn_legacy_cms' => ['content/{rewrite}', 'cms'],
            'manufacturer_rule' => ['brand/{rewrite}', 'manufacturer'],
            'supplier_rule' => ['supplier/{rewrite}', 'supplier'],
            'product_rule' => ['{category:/}{rewrite}.html', 'product'],
            'category_rule' => ['{rewrite}', 'category'],
        ];
    }

    public function hookActionAfterLoadRoutes(array $params)
    {
        /** @var Dispatcher $dispatcher */
        $dispatcher = $params['dispatcher'];
        $idShop = (int) $params['id_shop'];
        $controllers = [
            'cms_category' => 'cms', 'cms' => 'cms', 'manufacturer' => 'manufacturer',
            'supplier' => 'supplier', 'product' => 'product', 'category' => 'category',
        ];
        $base = [
            'cms_category' => 'cms_category_rule', 'cms' => 'cms_rule', 'manufacturer' => 'manufacturer_rule',
            'supplier' => 'supplier_rule', 'product' => 'product_rule', 'category' => 'category_rule',
        ];
        foreach (Language::getIDs(false) as $idLang) {
            foreach ($this->rules() as $name => [$rule, $kind]) {
                $keywords = $dispatcher->default_routes[$base[$kind]]['keywords'];
                foreach ($keywords as $k => $def) {
                    if ($k !== 'rewrite') {
                        unset($keywords[$k]['param']);
                    }
                }
                $keywords['rewrite'] = ['regexp' => self::SLUG, 'param' => 'rewrite'];
                $dispatcher->removeRoute($name, $idLang, $idShop);
                $dispatcher->addRoute($name, $rule, $controllers[$kind], $idLang, $keywords, ['mooninn_kind' => $kind], $idShop);
            }
        }
    }

    public function hookActionDispatcherBefore(array $params)
    {
        $kind = $_GET['mooninn_kind'] ?? null;
        if (!$kind) {
            return;
        }
        $slug = $_GET['rewrite'] ?? '';
        // drop route-only values, otherwise the canonical redirect appends them as a query
        unset($_GET['mooninn_kind'], $_GET['rewrite'], $_GET['category']);
        if (!is_string($slug) || $slug === '' || !in_array($kind, ['cms', 'cms_category', 'product', 'category', 'manufacturer', 'supplier'], true)) {
            return;
        }
        $param = [
            'product' => 'id_product', 'category' => 'id_category', 'cms' => 'id_cms',
            'cms_category' => 'id_cms_category', 'manufacturer' => 'id_manufacturer', 'supplier' => 'id_supplier',
        ][$kind];
        $id = $this->findId($kind, $slug);
        if (!$id && preg_match('/^(\d+)(?:-(\d+))?-/', $slug, $m)) {
            // legacy URL with ID, e.g. 20-43-the-moon-inn-vol-1: let the core redirect it
            $id = (int) $m[1];
            if ($kind === 'product' && !empty($m[2])) {
                $_GET['id_product_attribute'] = (int) $m[2];
            }
        }
        $_GET[$param] = $id ?: 0;
    }

    private function findId(string $kind, string $slug): int
    {
        $db = Db::getInstance();
        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;
        $s = pSQL($slug);
        switch ($kind) {
            case 'product':
                return (int) $db->getValue('SELECT id_product FROM `' . _DB_PREFIX_ . 'product_lang` WHERE link_rewrite = \'' . $s . '\' AND id_shop = ' . $idShop . ' ORDER BY id_lang = ' . $idLang . ' DESC');
            case 'category':
                return (int) $db->getValue('SELECT cl.id_category FROM `' . _DB_PREFIX_ . 'category_lang` cl JOIN `' . _DB_PREFIX_ . 'category` c ON c.id_category = cl.id_category WHERE c.id_parent > 0 AND cl.link_rewrite = \'' . $s . '\' AND cl.id_shop = ' . $idShop . ' ORDER BY cl.id_lang = ' . $idLang . ' DESC');
            case 'cms':
                return (int) $db->getValue('SELECT id_cms FROM `' . _DB_PREFIX_ . 'cms_lang` WHERE link_rewrite = \'' . $s . '\' AND id_shop = ' . $idShop . ' ORDER BY id_lang = ' . $idLang . ' DESC');
            case 'cms_category':
                return (int) $db->getValue('SELECT id_cms_category FROM `' . _DB_PREFIX_ . 'cms_category_lang` WHERE link_rewrite = \'' . $s . '\' AND id_shop = ' . $idShop . ' ORDER BY id_lang = ' . $idLang . ' DESC');
            case 'manufacturer':
            case 'supplier':
                $rows = $db->executeS('SELECT id_' . $kind . ' AS id, name FROM `' . _DB_PREFIX_ . $kind . '`');
                foreach ((array) $rows as $row) {
                    if (Tools::str2url($row['name']) === $slug) {
                        return (int) $row['id'];
                    }
                }
        }

        return 0;
    }
}
