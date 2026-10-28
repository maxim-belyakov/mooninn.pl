/**
 * Mooninn: pick the book edition that matches the site language.
 *
 * Books with several language editions have a combination per edition
 * (attribute group "Wydanie / Edition / Издание"). On a product page opened
 * without an explicit combination, switch to the edition in the visitor's
 * site language. If the book has no such edition, the default combination
 * (Polish) stays selected.
 *
 * The language -> attribute map comes from the mooninneditions module
 * (window.mooninnEditions, set from the MOONINN_EDITIONS configuration).
 */
(function () {

  function explicitCombination() {
    // /20-41-slug.html or #/27-wydanie-rosyjskie mean the visitor chose an edition already
    return /\/\d+-\d+-[^/]*\.html/.test(location.pathname) || /^#\/\d+/.test(location.hash);
  }

  function pickEdition() {
    var EDITIONS = window.mooninnEditions;
    if (typeof prestashop === 'undefined' || !EDITIONS || !EDITIONS.group || explicitCombination()) { return; }
    var select = document.querySelector('select[data-product-attribute="' + EDITIONS.group + '"]');
    if (!select) { return; }
    var wanted = EDITIONS.values[prestashop.language && prestashop.language.iso_code];
    if (!wanted || String(select.value) === String(wanted)) { return; }
    if (!select.querySelector('option[value="' + wanted + '"]')) { return; }
    select.value = String(wanted);
    select.dispatchEvent(new Event('change', { bubbles: true }));
  }

  if (document.readyState === 'complete') { pickEdition(); } else { window.addEventListener('load', pickEdition); }
})();
