<?php
/**
 * Mooninn editions: a book has one product with a combination per language
 * edition (attribute group "Wydanie"). This module
 *  - shows the cover of the edition in the site language in product lists,
 *  - passes the language -> attribute map to the theme JS (editions.js),
 *    which selects that edition on the product page.
 * If a book has no edition in the site language, nothing changes and the
 * default (Polish) edition is shown.
 *
 * Map is stored in MOONINN_EDITIONS as JSON: {"group":5,"values":{"pl":26,"ru":27,"en":28}}.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class MooninnEditions extends Module
{
    public function __construct()
    {
        $this->name = 'mooninneditions';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Mooninn';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = 'Mooninn: language editions';
        $this->description = 'Shows the edition matching the site language in product lists and on product pages.';
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionPresentProductListing')
            && $this->registerHook('displayHeader');
    }

    private function editions(): array
    {
        $map = json_decode((string) Configuration::get('MOONINN_EDITIONS'), true);

        return is_array($map) && isset($map['values']) ? $map : ['group' => 0, 'values' => []];
    }

    public function hookDisplayHeader()
    {
        Media::addJsDef(['mooninnEditions' => $this->editions()]);
    }

    public function hookActionPresentProductListing(array $params)
    {
        $presented = &$params['presentedProduct'];
        // cart lines carry the edition the customer chose: never touch them
        if (isset($presented['cart_quantity'])) {
            return;
        }
        $iso = $this->context->language->iso_code;
        $map = $this->editions();
        $idAttribute = (int) ($map['values'][$iso] ?? 0);
        if (!$idAttribute) {
            return;
        }
        $idProduct = (int) $presented['id_product'];
        $idImage = (int) Db::getInstance()->getValue(
            'SELECT pai.id_image
             FROM ' . _DB_PREFIX_ . 'product_attribute pa
             JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
             JOIN ' . _DB_PREFIX_ . 'product_attribute_image pai ON pai.id_product_attribute = pa.id_product_attribute
             WHERE pa.id_product = ' . $idProduct . ' AND pac.id_attribute = ' . $idAttribute
        );
        if (!$idImage) {
            return;
        }
        // listings only carry the default combination's images, so build the edition image
        $retriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever($this->context->link);
        $image = $retriever->getImage(new Product($idProduct, false, $this->context->language->id), $idImage);
        if (empty($image)) {
            return;
        }
        $image['cover'] = 1;
        $image['id_image'] = $idImage;
        $presented['cover'] = $image;
        $presented['default_image'] = $image;
    }
}
