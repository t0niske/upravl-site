/**
 * ЕДИНЫЙ ИСТОЧНИК ФАКТОВ САЙТА.
 * Контакты, цены, дела, правовые цифры берутся только отсюда, чтобы на всех страницах было одинаково.
 * Откуда каждый факт и что надо проверить: seo/BRANDCORE.md
 */

export const SITE_URL = 'https://upravl.ru';

/** Дата последней содержательной проверки контента (видна на страницах и уходит в dateModified). */
export const CONTENT_UPDATED = '2026-11-13';

/**
 * true — когда Ю. А. Антонец вычитал тексты. Тогда на страницах появится
 * «Проверено: Антонец Ю. А., арбитражный управляющий». До этого подпись — «Редакция сайта».
 */
export const CONTENT_REVIEWED_BY_EXPERT = false;

export const person = {
  name: 'Антонец Юрий Анатольевич',
  short: 'Ю. А. Антонец',
  givenName: 'Юрий',
  familyName: 'Антонец',
  job: 'Арбитражный управляющий',
  sinceYear: 1999,
  citizensSinceYear: 2016,
  /** ◐ со старого сайта (2022), подтвердить */
  proceduresText: 'более 200 процедур банкротства организаций и граждан',
  // ✖ заполнить до запуска: без этих данных не показываются (блоки скрываются)
  sro: '', // напр. 'Ассоциация «СРО АУ …»'
  registryNumber: '', // рег. номер в сводном реестре АУ
  inn: '',
  fedresursUrl: '', // карточка на fedresurs.ru
};

export const contacts = {
  city: 'Томск',
  region: 'Томская область',
  postalCode: '634061',
  street: 'ул. Тверская, д. 14, оф. 1',
  addressFull: '634061, г. Томск, ул. Тверская, д. 14, оф. 1',
  phone: '+7 (3822) 30-02-15',
  phoneHref: 'tel:+73822300215',
  phoneE164: '+73822300215',
  email: 'antonece@mail.ru',
  /** ◐ график из макета дизайнера (17.11.2026), подтвердить у Антонца; после подтверждения заполнить hoursSchema */
  hours: 'Пн–Пт с 9:00 до 18:00, по предварительной записи',
  hoursDays: 'Пн–Пт с 9:00 до 18:00',
  hoursNote: 'по предварительной записи',
  hoursSchema: '', // напр. 'Mo-Fr 09:00-18:00'
  // мессенджеры: пустая строка = кнопка не выводится
  telegram: '',
  whatsapp: '',
  max: '',
  yandexMapsUrl: 'https://yandex.ru/maps/?text=%D0%A2%D0%BE%D0%BC%D1%81%D0%BA%2C%20%D1%83%D0%BB.%20%D0%A2%D0%B2%D0%B5%D1%80%D1%81%D0%BA%D0%B0%D1%8F%2C%2014',
  geo: { lat: 56.4617, lon: 84.9744 }, // ✖ приблизительно, уточнить по карте
};

/** ◐ цены со старого сайта 2020–2022 — подтвердить перед запуском */
export const pricing = {
  citizenTurnkey: 95000,
  citizenInstallmentFrom: 5000,
  consultation: 'бесплатно',
};

/** Обязательные расходы по закону (не гонорар). Сверено 13.11.2026, см. BRANDCORE. */
export const law = {
  fuDeposit: 25000, // депозит на вознаграждение финансового управляющего
  fuPercent: 7, // % от сумм, вырученных от реализации имущества и погашения требований
  citizenDutyThreshold: 500000, // долг, при котором гражданин ОБЯЗАН подать заявление
  companyThreshold: 2000000, // порог для дела о банкротстве юрлица (107-ФЗ от 29.05.2024)
  mfcMin: 25000,
  mfcMax: 1000000,
  mfcDurationMonths: 6,
  restructuringYears: 3,
  realizationMonths: 6,
  challengePeriodYears: 3, // глубина оспаривания сделок
};

const fmt = new Intl.NumberFormat('ru-RU');
export const rub = (n: number) => `${fmt.format(n)} ₽`;

export type CaseItem = {
  number: string;
  amount: number;
  finished: string; // ISO или год
  finishedLabel: string;
  debtor: string;
};

/** Завершённые дела. Проверяются по номеру в картотеке kad.arbitr.ru. */
export const cases: CaseItem[] = [
  { number: 'А67-3303/2021', amount: 3326575, finished: '2022-02-15', finishedLabel: '15 февраля 2022', debtor: 'гражданин' },
  { number: 'А67-8326/2021', amount: 2791442, finished: '2022-05-06', finishedLabel: '6 мая 2022', debtor: 'гражданин' },
  { number: 'А67-9850/2020', amount: 14204473, finished: '2021-12-02', finishedLabel: '2 декабря 2021', debtor: 'гражданин' },
  { number: 'А67-5022/2020', amount: 2613849, finished: '2021-08-27', finishedLabel: '27 августа 2021', debtor: 'гражданин' },
  { number: 'А67-2702/2017', amount: 14955006, finished: '2019', finishedLabel: '2019', debtor: 'гражданин' },
  { number: 'А67-5442/2015', amount: 46289387, finished: '2019', finishedLabel: '2019', debtor: 'гражданин (ИП)' },
  { number: 'А67-1160/2016', amount: 116540735, finished: '2018', finishedLabel: '2018', debtor: 'гражданин' },
  { number: 'А67-2085/2016', amount: 54119373, finished: '2017', finishedLabel: '2017', debtor: 'гражданин' },
];

export const KAD_URL = 'https://kad.arbitr.ru/';

export type NavItem = { href: string; label: string };

export const services: (NavItem & { lead: string })[] = [
  {
    href: '/bankrotstvo-fizicheskih-lic/',
    label: 'Банкротство граждан',
    lead: 'Списание долгов через Арбитражный суд Томской области. Под ключ — 95 000 ₽, рассрочка.',
  },
  {
    href: '/bankrotstvo-fizicheskih-lic/cherez-mfc/',
    label: 'Банкротство через МФЦ',
    lead: `Бесплатно, при долге от 25 000 до 1 000 000 ₽ и оконченном исполнительном производстве.`,
  },
  {
    href: '/bankrotstvo-ip/',
    label: 'Банкротство ИП',
    lead: 'Списание долгов предпринимателя, включая налоги и долги по бизнесу.',
  },
  {
    href: '/bankrotstvo-ur-lica/',
    label: 'Банкротство организаций',
    lead: 'Ликвидация ООО через банкротство с контролем рисков для директора и участников.',
  },
  {
    href: '/bankrotstvo-ur-lica/subsidiarnaya-otvetstvennost/',
    label: 'Защита от субсидиарной ответственности',
    lead: 'Для директоров, учредителей и главбухов, которых хотят сделать ответчиками по долгам компании.',
  },
  {
    href: '/bankrotstvo-ur-lica/kreditoram/',
    label: 'Сопровождение кредиторов',
    lead: 'Включение в реестр, оспаривание сделок должника, контроль управляющего.',
  },
];

/**
 * Цифры первого экрана (из макета дизайнера, 16.11.2026).
 * ◐ «320+ млн ₽» подтвердить у Антонца: сумма 8 дел из `cases` — 254,8 млн ₽,
 * итог со старого сайта — 159 млн ₽ (см. BRANDCORE, «противоречие»).
 */
export const heroStats = {
  years: '25+ лет',
  yearsLabel: 'в банкротстве',
  written: '320+ млн ₽',
  writtenLabel: 'списанных долгов',
};

/** Меню новой шапки (макет главной, 16.11.2026). Пункты без страниц ведут на ближайшую существующую. */
export type NavGroup = NavItem & { children?: NavItem[] };
export const headerNav: NavGroup[] = [
  {
    href: '/bankrotstvo-fizicheskih-lic/',
    label: 'Услуги',
    children: [
      ...services.map(({ href, label }) => ({ href, label })),
      { href: '/yuridicheskie-uslugi/', label: 'Юридические услуги' },
    ],
  },
  { href: '/yuridicheskie-uslugi/', label: 'Цены' },
  {
    href: '/stati/',
    label: 'Все о банкротстве',
    children: [
      { href: '/stati/kto-takoj-arbitrazhnyj-upravlyayushchij/', label: 'Кто такой арбитражный управляющий' },
      { href: '/stati/mify-o-bankrotstve-grazhdan/', label: 'Мифы о банкротстве граждан' },
      { href: '/stati/', label: 'Все статьи' },
    ],
  },
  {
    href: '/ob-upravlyayushchem/',
    label: 'Обо мне',
    children: [
      { href: '/ob-upravlyayushchem/', label: 'Опыт и документы' },
      { href: '/dela/', label: 'Завершённые дела' },
    ],
  },
  { href: '/#otzyvy', label: 'Отзывы' },
  { href: '/kontakty/', label: 'Контакты' },
];

export const mainNav: NavItem[] = [
  { href: '/bankrotstvo-fizicheskih-lic/', label: 'Гражданам' },
  { href: '/bankrotstvo-ur-lica/', label: 'Бизнесу' },
  { href: '/dela/', label: 'Дела' },
  { href: '/yuridicheskie-uslugi/', label: 'Цены' },
  { href: '/ob-upravlyayushchem/', label: 'Об управляющем' },
  { href: '/kontakty/', label: 'Контакты' },
];
