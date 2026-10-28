{**
 * Mooninn: the banner image carries no text. The text comes from the
 * module's per-language "description" field (Modules > Banner) and is
 * drawn on top, so one picture serves every language.
 * Format of the description: "Title | Subtitle" (subtitle optional).
 *}
{if isset($banner_img)}
  {assign var=mooninnBannerParts value=' | '|explode:$banner_desc}
  <section class="ps-banner mooninn-banner">
    <div class="container">
      <a class="banner d-block position-relative" href="{$banner_link}">
        <img
          src="{$banner_img}"
          alt=""
          class="img-fluid w-100"
          loading="lazy"
          {if !empty($banner_width)}width="{$banner_width}"{/if}
          {if !empty($banner_height)}height="{$banner_height}"{/if}
        >
        <span class="mooninn-banner__text">
          <span class="mooninn-banner__title">{$mooninnBannerParts[0]|escape:'html':'UTF-8'}</span>
          {if isset($mooninnBannerParts[1])}
            <span class="mooninn-banner__subtitle">{$mooninnBannerParts[1]|escape:'html':'UTF-8'}</span>
          {/if}
        </span>
      </a>
    </div>
  </section>
{/if}
