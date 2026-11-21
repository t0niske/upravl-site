/** Статьи сайта: список на /stati/ и блок «Статьи» внизу посадочных и статей. */
export type Article = { href: string; title: string; short: string; text: string; date: string; updated: string };

export const articles: Article[] = [
  { href: '/stati/mify-o-bankrotstve-grazhdan/', title: '10 мифов о банкротстве граждан — и что на самом деле', short: '10 мифов о банкротстве', text: 'Спишут ли все долги, заберут ли зарплату и квартиру, дадут ли кредит после процедуры.', date: '2020-01-23', updated: '2026-11-13' },
  { href: '/stati/kto-takoj-arbitrazhnyj-upravlyayushchij/', title: 'Кто такой арбитражный управляющий и что он делает в банкротстве', short: 'Кто такой арбитражный управляющий', text: 'Требования к управляющему, виды, СРО, кто выбирает и сколько ему платят.', date: '2020-02-25', updated: '2026-11-13' },
  { href: '/bankrotstvo-ur-lica/subsidiarnaya-otvetstvennost/', title: 'Субсидиарная ответственность при банкротстве', short: 'Субсидиарная ответственность', text: 'Кого привлекают, по каким основаниям и как строится защита.', date: '2020-02-25', updated: '2026-11-13' },
];

export const fmtDate = (d: string) => new Date(d).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
