/**
 * Неразрывный пробел после коротких предлогов, союзов и частиц,
 * чтобы они не висели в конце строки: «работа с банками» → «работа с&nbsp;банками».
 * Плюс: разряды чисел и знак ₽ / «млн» не отрываются от числа.
 */
const re = /(^|[\s («])(в|во|с|со|и|к|ко|о|об|обо|у|а|но|на|по|за|от|до|из|не|ни|для|при|без|под|над|или|что)[^\S ]+/gi;
const NBSP = ' ';

export const nb = (text: string) => {
  let prev = '';
  let s = text;
  while (prev !== s) {
    prev = s;
    s = s.replace(re, (_, a: string, w: string) => `${a}${w}${NBSP}`);
  }
  // разряды чисел и знак рубля не разрываются: «1 000 000 ₽», «95 000 руб.»
  s = s
    .replace(/(\d)[  ](?=\d{3}(?:\D|$))/g, `$1${NBSP}`)
    .replace(/(\d)\s+(₽|руб\.?|млн|млрд|тыс\.?)/g, `$1${NBSP}$2`)
    .replace(/(млн|млрд|тыс\.?)\s+₽/g, `$1${NBSP}₽`);
  // неразрывный дефис в коротких составных словах: «из-за», «из-под», «кое-как»
  return s.replace(/(^|[^А-Яа-яЁё])([А-Яа-яЁё]{1,3})-([А-Яа-яЁё]{1,4})(?=[^А-Яа-яЁё]|$)/g, (_, a: string, x: string, y: string) => `${a}${x}‑${y}`);
};

/**
 * Мягкие переносы (U+00AD) в длинных словах — для узких колонок на телефоне, чтобы справа
 * не оставалось пустоты. Браузер покажет перенос только там, где он нужен.
 * Упрощённые слоговые правила: V|CV, VC|CCV, «й/ь/ъ» остаются с предыдущей буквой,
 * не меньше 2 букв с каждого края, только слова от 7 букв.
 */
const V = 'аеёиоуыэюяАЕЁИОУЫЭЮЯ';
const X = 'йьъЙЬЪ';
const isV = (c: string) => V.includes(c);
const hyphenWord = (w: string) => {
  if (w.length < 7 || !/^[а-яё]+$/.test(w)) return w; // только строчные: «Госуслуги» и имена не трогаем
  const cuts: number[] = [];
  const vow = [...w].map((c, i) => (isV(c) ? i : -1)).filter((i) => i >= 0);
  for (let k = 0; k < vow.length - 1; k++) {
    const a = vow[k], b = vow[k + 1], n = b - a - 1;
    if (n === 0) continue;
    let cut = n === 1 ? a + 1 : a + 2;
    while (cut < b && X.includes(w[cut])) cut++;
    if (n === 1 && X.includes(w[a + 1])) cut = a + 2;
    if (cut >= 2 && w.length - cut >= 2 && cut <= b) cuts.push(cut);
  }
  let out = '';
  let prev = 0;
  for (const c of cuts) { out += w.slice(prev, c) + '\u00AD'; prev = c; }
  return out + w.slice(prev);
};
export const soft = (text: string) => text.replace(/[А-Яа-яЁё]+/g, hyphenWord);
