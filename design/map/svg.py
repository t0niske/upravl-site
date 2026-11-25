import json, sys
m = json.load(open(sys.argv[1], encoding='utf-8')); out = sys.argv[2]
W, H = m['w'], m['h']
c = m['cities']; t = c['tomsk']
reg = ''.join(f'<path d="{r["d"]}" class="{"h" if r["hl"] else "r"}"/>' for r in m['regions'])
lines = ''.join(f'<path d="M{t[0]},{t[1]} Q{(t[0]+v[0])/2},{min(t[1],v[1])-60 if k=="krasnodar" else (t[1]+v[1])/2} {v[0]},{v[1]}" class="l"/>' for k, v in c.items() if k != 'tomsk')
dots = ''.join(f'<circle cx="{v[0]}" cy="{v[1]}" r="{6 if k=="tomsk" else 4.5}" class="d"/>' for k, v in c.items())
svg = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W:.0f} {H:.0f}" role="img" aria-label="Карта России: регионы работы — Томская, Кемеровская, Новосибирская области, Алтайский и Краснодарский края">
<style>.r{{fill:#d5dbe3;stroke:#fff;stroke-width:.7;stroke-linejoin:round}}.h{{fill:#034b8e;stroke:#fff;stroke-width:.7;stroke-linejoin:round}}.l{{fill:none;stroke:#034b8e;stroke-width:1.8;stroke-dasharray:5 4}}.d{{fill:#034b8e;stroke:#fff;stroke-width:2}}</style>
<g>{reg}</g></svg>'''
open(out, 'w', encoding='utf-8').write(svg)
print(len(svg))
