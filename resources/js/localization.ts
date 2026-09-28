export default class _ {

  static data : Record<string,TranslationEntry> = {
    today: { en: 'today', de: 'heute', it: 'oggi' },
    hour_abbr: { en: 'h', de: 'h', it: 'h' },
    weather_source_label: { en: 'Source', de: 'Quelle', it: 'Fonte' },
    weather_source_dwd: { en: 'German Weather Service (DWD)', de: 'Deutscher Wetterdienst (DWD)', it: 'Servizio Meteorologico Tedesco (DWD)' },
    weather_source_owm: { en: 'OpenWeatherMap', de: 'OpenWeatherMap', it: 'OpenWeatherMap' },
  } as const;

  static _(key : string, lang : string) {
    return this.data[key]?.[lang] ?? key;
  }
}

declare interface TranslationEntry{
  en:string;
  de:string;
  it:string;
}
