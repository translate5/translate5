export default class LanguageResolver {
    // list of supported languages by the editor
    // @link https://ckeditor.com/docs/ckeditor5/latest/getting-started/setup/ui-language.html#list-of-available-professional-translations
    #supportedLocales = [
        "ar",
        "bn",
        "bg",
        "ca",
        "zh",
        "zh-tw",
        "cs",
        "da",
        "nl",
        "en",
        "et",
        "fi",
        "fr",
        "de",
        "el",
        "he",
        "hi",
        "hu",
        "id",
        "it",
        "ja",
        "ko",
        "lv",
        "lt",
        "ms",
        "no",
        "pl",
        "pt-br",
        "pt",
        "ro",
        "ru",
        "sr",
        "sk",
        "es",
        "sv",
        "th",
        "tr",
        "uk",
        "vi",
    ];

    /**
     * @param {string} locale
     * @returns {string}
     */
    resolveLanguage(locale) {
        const normalizedLocale = locale.toLowerCase();
        if (this.#supportedLocales.includes(normalizedLocale)) {
            return normalizedLocale;
        }

        const primarySubtag = normalizedLocale.split('-')[0];
        if (this.#supportedLocales.includes(primarySubtag)) {
            return primarySubtag;
        }

        return 'en';
    }
}