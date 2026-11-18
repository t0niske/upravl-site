import { SITE_URL, person, contacts, CONTENT_UPDATED } from './site';

export const ORG_ID = `${SITE_URL}/#org`;
export const PERSON_ID = `${SITE_URL}/ob-upravlyayushchem/#person`;
export const WEBSITE_ID = `${SITE_URL}/#website`;

const sameAs = [person.fedresursUrl].filter(Boolean);

/** Юридическая практика (LegalService) — локальный бизнес для карт и Knowledge Graph */
export function orgSchema() {
  return {
    '@type': ['LegalService', 'ProfessionalService'],
    '@id': ORG_ID,
    name: `Арбитражный управляющий ${person.name}`,
    url: `${SITE_URL}/`,
    image: `${SITE_URL}/og/antonec.jpg`,
    logo: `${SITE_URL}/favicon.svg`,
    telephone: contacts.phoneE164,
    email: contacts.email,
    priceRange: '₽₽',
    address: {
      '@type': 'PostalAddress',
      streetAddress: contacts.street,
      addressLocality: contacts.city,
      addressRegion: contacts.region,
      postalCode: contacts.postalCode,
      addressCountry: 'RU',
    },
    geo: { '@type': 'GeoCoordinates', latitude: contacts.geo.lat, longitude: contacts.geo.lon },
    areaServed: [
      { '@type': 'City', name: 'Томск' },
      { '@type': 'AdministrativeArea', name: 'Томская область' },
    ],
    ...(contacts.hoursSchema ? { openingHours: contacts.hoursSchema } : {}),
    founder: { '@id': PERSON_ID },
    employee: { '@id': PERSON_ID },
    ...(sameAs.length ? { sameAs } : {}),
  };
}

export function personSchema() {
  return {
    '@type': 'Person',
    '@id': PERSON_ID,
    name: person.name,
    givenName: person.givenName,
    familyName: person.familyName,
    jobTitle: person.job,
    ...(person.inn ? { taxID: person.inn } : {}),
    ...(person.sro ? { memberOf: { '@type': 'Organization', name: person.sro } } : {}),
    image: `${SITE_URL}/og/antonec.jpg`,
    url: `${SITE_URL}/ob-upravlyayushchem/`,
    worksFor: { '@id': ORG_ID },
    workLocation: { '@type': 'Place', name: 'Томск' },
    knowsAbout: [
      'Банкротство физических лиц',
      'Банкротство юридических лиц',
      'Субсидиарная ответственность',
      'Федеральный закон «О несостоятельности (банкротстве)» № 127-ФЗ',
    ],
    hasCredential: [
      {
        '@type': 'EducationalOccupationalCredential',
        name: 'Свидетельство о сдаче теоретического экзамена по единой программе подготовки арбитражных управляющих',
        credentialCategory: 'certificate',
        recognizedBy: { '@type': 'GovernmentOrganization', name: 'Министерство юстиции Российской Федерации' },
        dateCreated: '2003-12-22',
      },
    ],
    ...(sameAs.length ? { sameAs } : {}),
  };
}

export function websiteSchema() {
  return {
    '@type': 'WebSite',
    '@id': WEBSITE_ID,
    url: `${SITE_URL}/`,
    name: 'Арбитражный управляющий Антонец Ю. А.',
    inLanguage: 'ru-RU',
    publisher: { '@id': ORG_ID },
  };
}

export type Crumb = { name: string; href: string };

export function breadcrumbSchema(crumbs: Crumb[]) {
  return {
    '@type': 'BreadcrumbList',
    itemListElement: crumbs.map((c, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: c.name,
      item: `${SITE_URL}${c.href}`,
    })),
  };
}

export function serviceSchema(opts: { name: string; url: string; description: string; price?: number; serviceType?: string }) {
  return {
    '@type': 'Service',
    '@id': `${SITE_URL}${opts.url}#service`,
    name: opts.name,
    serviceType: opts.serviceType ?? opts.name,
    description: opts.description,
    url: `${SITE_URL}${opts.url}`,
    provider: { '@id': ORG_ID },
    areaServed: { '@type': 'AdministrativeArea', name: 'Томская область' },
    ...(opts.price
      ? {
          offers: {
            '@type': 'Offer',
            price: opts.price,
            priceCurrency: 'RUB',
            availability: 'https://schema.org/InStock',
          },
        }
      : {}),
  };
}

export function faqSchema(items: { q: string; a: string }[]) {
  return {
    '@type': 'FAQPage',
    mainEntity: items.map((i) => ({
      '@type': 'Question',
      name: i.q,
      acceptedAnswer: { '@type': 'Answer', text: i.a },
    })),
  };
}

export function articleSchema(opts: { title: string; url: string; description: string; published: string; modified?: string }) {
  return {
    '@type': 'Article',
    headline: opts.title,
    description: opts.description,
    url: `${SITE_URL}${opts.url}`,
    mainEntityOfPage: `${SITE_URL}${opts.url}`,
    datePublished: opts.published,
    dateModified: opts.modified ?? CONTENT_UPDATED,
    inLanguage: 'ru-RU',
    author: { '@id': ORG_ID },
    publisher: { '@id': ORG_ID },
    image: `${SITE_URL}/og/antonec.jpg`,
  };
}
