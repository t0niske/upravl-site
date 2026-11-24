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
  /** подтверждено Антонцем 18.11.2026 */
  proceduresText: 'более 250 процедур банкротства организаций и граждан',
  // ✖ заполнить до запуска: без этих данных не показываются (блоки скрываются)
  sro: 'СМОО «Ассоциация антикризисных управляющих»', // по определению по делу А67-9775/2024, актуальность подтверждена 18.11.2026
  registryNumber: '207', // рег. номер в сводном реестре АУ (прислал Антонец 18.11.2026)
  inn: '702100629211',
  // Ссылка от Антонца (old.bankrot.fedresurs.ru/ArbitrManagerCard.aspx?ID=7d9f2349-6b3f-457d-86c5-43c398be2e0a) не открывается — 18.11.2026 убрана, ждём рабочую с fedresurs.ru
  fedresursUrl: '', // карточка АУ в ЕФРСБ
};

export const contacts = {
  city: 'Томск',
  region: 'Томская область',
  postalCode: '634061',
  street: 'ул. Тверская, д. 14, оф. 1',
  addressFull: '634061, г. Томск, ул. Тверская, д. 14, оф. 1',
  // ✔ актуальные номера подтверждены дизайнером 17.11.2026
  phone: '+7 (3822) 30-02-15',
  phoneHref: 'tel:+73822300215',
  phoneE164: '+73822300215',
  phone2: '+7 (913) 889-35-36',
  phone2Href: 'tel:+79138893536',
  phone2E164: '+79138893536',
  // единственная почта для сайта — подтвердила дизайнер 18.11.2026 (antonece@mail.ru со старого сайта не используем)
  email: 'arbitomsk@gmail.com',
  email2: '',
  /** ◐ график из макета дизайнера (17.11.2026), подтвердить у Антонца; после подтверждения заполнить hoursSchema */
  hours: 'Пн–Пт с 9:00 до 18:00, по предварительной записи',
  hoursDays: 'Пн–Пт с 9:00 до 18:00',
  hoursNote: 'по предварительной записи',
  hoursSchema: '', // напр. 'Mo-Fr 09:00-18:00'
  // мессенджеры: пустая строка = кнопка не выводится
  telegram: '',
  whatsapp: '',
  max: '',
  yandexMapsUrl: 'https://yandex.ru/maps/?pt=84.971674,56.483464&z=17&l=map',
  geo: { lat: 56.483464, lon: 84.971674 }, // координаты карточки офиса в 2ГИС (18.11.2026)
};

/** ◐ цены со старого сайта 2020–2022 — подтвердить перед запуском */
export const pricing = {
  /** минимальная цена юридического сопровождения банкротства гражданина — «от»; сообщил Антонец 18.11.2026 */
  citizenTurnkey: 125000,
  citizenInstallmentFrom: 10000,
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

/**
 * Номер дела на сайте показываем замазанным (звёздочки вместо цифр перед годом). По полному номеру в картотеке
 * видно ФИО должника, а рядом наши истории с деталями — не раскрываем клиентов без их согласия (решение 18.11.2026).
 */
export const maskCase = (n: string) => n.replace(/^([А-ЯA-Z]\d{2})\s*[-–]\s*\d+\//, '$1-****/');
/** Суд по коду номера дела */
export const courtOf = (n: string) => (n.startsWith('А75') ? 'Арбитражный суд ХМАО — Югры' : 'Арбитражный суд Томской области');

export type NavItem = { href: string; label: string };

export const services: (NavItem & { lead: string })[] = [
  {
    href: '/bankrotstvo-fizicheskih-lic/',
    label: 'Банкротство физических лиц',
    lead: 'Списание долгов через Арбитражный суд Томской области. Под ключ — от 125 000 ₽, рассрочка.',
  },
  {
    href: '/bankrotstvo-cherez-mfc/',
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
 * «320+ млн ₽» подтверждено Антонцем через дизайнера 18.11.2026 (сумма 8 дел из `cases` — 254,8 млн ₽,
 * итог старого сайта 159 млн ₽ — на 2022 год).
 */
export const heroStats = {
  years: '25+ лет',
  yearsLabel: 'в банкротстве',
  written: '320+ млн ₽',
  writtenLabel: 'списанных долгов',
};

/** Меню новой шапки (макет главной, 16–17.11.2026). icon — ключ иконки в HomeHero.astro. */
export type MenuLink = NavItem & { icon?: string };
export type MenuColumn = { title: string; items: MenuLink[] };
/** href: '' — страницы ещё нет: пункт виден в меню, но без ссылки */
export type NavGroup = NavItem & { children?: MenuLink[]; columns?: MenuColumn[]; promo?: boolean };

/** Широкая панель «Услуги»: две колонки + карточка квиза. Цены — отдельным пунктом. */
export const servicesMenu: MenuColumn[] = [
  // Состав пунктов — по ответу заказчика (24.11.2026)
  {
    title: 'Гражданам',
    items: [
      { href: '/bankrotstvo-fizicheskih-lic/', label: 'Банкротство физических лиц', icon: 'court' },
      { href: '/bankrotstvo-ip/', label: 'Банкротство ИП', icon: 'store' },
      { href: '/yuridicheskie-uslugi/#konsultacii', label: 'Консультации по вопросам банкротства', icon: 'doc' },
      { href: '/yuridicheskie-uslugi/#zayavlenie-grazhdanina', label: 'Подготовка документов', icon: 'doc' },
      { href: '/yuridicheskie-uslugi/#predstavitelstvo-grazhdanina', label: 'Представление интересов в арбитражных судах', icon: 'court' },
    ],
  },
  {
    title: 'Бизнесу',
    items: [
      { href: '/bankrotstvo-ur-lica/', label: 'Проведение процедур банкротства ООО и АО', icon: 'briefcase' },
      { href: '/bankrotstvo-ur-lica/subsidiarnaya-otvetstvennost/', label: 'Защита КДЛ от субсидиарной ответственности', icon: 'shield' },
      { href: '/bankrotstvo-ur-lica/kreditoram/', label: 'Представление интересов в делах о банкротстве ЮЛ', icon: 'doc' },
    ],
  },
  {
    title: 'Другие услуги',
    items: [
      { href: '/yuridicheskie-uslugi/#arbitrazhnyj-sud', label: 'Юридические услуги в арбитражных судах', icon: 'court' },
    ],
  },
];

/** Широкая панель «Всё о банкротстве» (структура дизайнера, 17.11.2026). Пустой href — страницы пока нет. */
export const knowMenu: MenuColumn[] = [
  {
    title: 'Как это работает',
    items: [
      { href: '/bankrotstvo-fizicheskih-lic/', label: 'Как проходит банкротство через суд' },
      { href: '/bankrotstvo-cherez-mfc/', label: 'Как пройти банкротство через МФЦ' },
      { href: '/stati/kto-takoj-arbitrazhnyj-upravlyayushchij/', label: 'Кто такой арбитражный управляющий' },
      { href: '', label: 'Банкротство или реструктуризация' },
    ],
  },
  {
    title: 'Ваша ситуация',
    items: [
      { href: '', label: 'Банкротство с ипотекой' },
      { href: '', label: 'Банкротство пенсионеров' },
      { href: '', label: 'Банкротство поручителя' },
      { href: '', label: 'Банкротство с автокредитом' },
      { href: '', label: 'Банкротство супругов' },
    ],
  },
  {
    title: 'Перед тем как решиться',
    items: [
      { href: '', label: 'Последствия банкротства' },
      { href: '', label: 'Документы для банкротства' },
      { href: '/stati/mify-o-bankrotstve-grazhdan/', label: 'Мифы о банкротстве' },
      { href: '', label: 'Когда суд не списывает долги' },
      { href: '', label: 'Как не попасть к недобросовестной фирме' },
    ],
  },
];

export const headerNav: NavGroup[] = [
  {
    href: '/bankrotstvo-fizicheskih-lic/',
    label: 'Услуги',
    columns: servicesMenu,
    promo: true,
    children: servicesMenu.flatMap((c) => c.items),
  },
  {
    href: '/yuridicheskie-uslugi/',
    label: 'Цены',
    // страницы «Стоимость», «Калькулятор», «Рассрочка» в плане (карта сайта v2); пока ведут на прайс
    children: [
      { href: '/yuridicheskie-uslugi/', label: 'Стоимость банкротства', icon: 'ruble' },
      { href: '/yuridicheskie-uslugi/', label: 'Калькулятор полной цены', icon: 'calc' },
      { href: '/yuridicheskie-uslugi/', label: 'Рассрочка', icon: 'calendar' },
      { href: '/yuridicheskie-uslugi/', label: 'Юридические услуги и цены', icon: 'list' },
    ],
  },
  {
    href: '/stati/',
    label: 'Все о банкротстве',
    columns: knowMenu,
    children: knowMenu.flatMap((c) => c.items).filter((i) => i.href),
  },
  {
    href: '/ob-upravlyayushchem/',
    label: 'Обо мне',
    children: [
      { href: '/ob-upravlyayushchem/', label: 'Опыт и документы', icon: 'award' },
      { href: '/dela/', label: 'Завершённые дела', icon: 'doc' },
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

/**
 * Свежие завершённые дела для блока кейсов на главной (определения прислал дизайнер, 18.11.2026).
 * Факты сверены с определениями Арбитражного суда Томской области; личные данные не переносим.
 * months — от введения процедуры (или принятия заявления) до определения о завершении, округлено.
 */
export interface RecentCase {
  number: string;
  finished: string; // ISO-дата резолютивной части определения о завершении
  debtor: 'гражданин' | 'гражданка';
  months: number;
  title: string;
  text: string;
  img: 'folder' | 'padlock' | 'stamp' | 'keys' | 'binders' | 'phone';
  /** показывать в ленте на главной (остальные — только на /dela/) */
  featured?: boolean;
}
export const recentCases: RecentCase[] = [
  { number: 'А67-2576/2026', finished: '2026-11-05', debtor: 'гражданка', months: 7, img: 'binders',
    title: '1,3 млн ₽ долгов перед банками и налоговой списали за полгода',
    text: 'Имущества не было, продавать нечего. Суд освободил от долгов перед тремя банками и налоговой' },
  { number: 'А75-18552/2024', finished: '2026-11-05', debtor: 'гражданин', months: 24, img: 'phone', featured: true,
    title: 'Кредиторам вернули 1,2 млн ₽, остаток долга списан',
    text: 'Долги 2,87 млн ₽. Кредиторы получили почти 42 %, от остальных долгов суд освободил' },
  { number: 'А67-1437/2026', finished: '2026-11-02', debtor: 'гражданин', months: 7, img: 'folder', featured: true,
    title: 'Долги 550 тыс. ₽ списали за 7 месяцев',
    text: 'Имущества для продажи не было. Из дохода кредиторам перечислили 36 % долга, от остального суд освободил' },
  { number: 'А67-2627/2026', finished: '2026-10-27', debtor: 'гражданин', months: 6, img: 'padlock', featured: true,
    title: '780 тыс. ₽ долга перед банком списали за полгода',
    text: 'Самозанятый, один кредитор, имущества нет. Продавать было нечего, и суд освободил от долга целиком' },
  { number: 'А67-2738/2025', finished: '2026-09-29', debtor: 'гражданка', months: 17, img: 'stamp', featured: true,
    title: 'Ипотечную квартиру сохранили, долги списали',
    text: 'Шесть банков и МФО, 1,6 млн ₽. По ипотеке — отдельное соглашение с банком, участок продали, от остальных долгов суд освободил' },
  { number: 'А67-8334/2025', finished: '2026-08-13', debtor: 'гражданин', months: 10, img: 'keys', featured: true,
    title: 'Списали 17,3 млн ₽ долгов, жильё сохранили',
    text: 'Долги перед двумя банками, имущества для продажи не нашли. Долю в квартире оставили как единственное жильё, суд освободил от всех долгов' },
  { number: 'А67-340/2025', finished: '2026-05-27', debtor: 'гражданка', months: 15, img: 'keys',
    title: 'Старый автомобиль продали, 1,07 млн ₽ долгов списали',
    text: 'Три банка. Сначала реструктуризация, потом реализация: автомобиль 2001 года продали, кредиторы получили 14 %, остальное суд списал' },
  { number: 'А67-8303/2024', finished: '2026-03-23', debtor: 'гражданин', months: 17, img: 'stamp',
    title: 'Долг перед банком списали после продажи старого трактора',
    text: 'Из дохода и половины выручки от трактора банк получил около трети долга. Вторая половина выручки по закону ушла супругу, остальное суд списал' },
  { number: 'А67-9775/2024', finished: '2025-12-09', debtor: 'гражданин', months: 13, img: 'binders', featured: true,
    title: 'Освободили от 2,7 млн ₽ долгов перед тремя банками',
    text: 'Самозанятый без дохода. Найденное имущество продали в пользу кредиторов, сделок для оспаривания не было. От остальных долгов суд освободил' },
  { number: 'А67-10001/2024', finished: '2025-10-20', debtor: 'гражданин', months: 11, img: 'padlock',
    title: 'Долг 206 тыс. ₽ списали без продажи имущества',
    text: 'Самозанятый, имущества нет. Реструктуризация не подошла, перешли к реализации, и суд освободил от долгов' },
  { number: 'А67-10153/2024', finished: '2025-10-01', debtor: 'гражданка', months: 11, img: 'phone',
    title: 'Реструктуризация не помогла — долг списали',
    text: 'Безработная, имущества нет. Сначала пробовали план реструктуризации, он не сработал. Перешли к реализации, и суд освободил от долгов' },
  { number: 'А67-7358/2024', finished: '2025-08-07', debtor: 'гражданин', months: 12, img: 'folder',
    title: 'Из зарплаты погасили 39 %, остальное списали',
    text: 'Работает по найму, один кредитор — банк, 257 тыс. ₽. За год процедуры часть долга погасили из зарплаты, остаток суд списал' },
  { number: 'А67-8119/2024', finished: '2025-07-07', debtor: 'гражданин', months: 5, img: 'binders',
    title: 'Долги списали за 5 месяцев, доля в квартире осталась',
    text: 'Работает на полставки, имущества нет, кроме доли в единственном жилье. Ни один кредитор не включился в реестр, суд освободил от долгов' },
];
