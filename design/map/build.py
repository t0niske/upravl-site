import json, sys, math
src, out = sys.argv[1], sys.argv[2]
d = json.load(open(src, encoding='utf-8'))
ru = [f for f in d['features'] if f['properties'].get('adm0_a3') == 'RUS']
HL = {'Tomsk', 'Kemerovo', 'Novosibirsk', 'Altay', 'Krasnodar'}
# Albers equal-area, Russia
lon0, lat0, p1, p2 = math.radians(100), math.radians(60), math.radians(50), math.radians(70)
n = (math.sin(p1) + math.sin(p2)) / 2
C = math.cos(p1) ** 2 + 2 * n * math.sin(p1)
rho0 = math.sqrt(C - 2 * n * math.sin(lat0)) / n
def proj(lon, lat):
    if lon < 0: lon += 360
    l, p = math.radians(lon), math.radians(lat)
    rho = math.sqrt(C - 2 * n * math.sin(p)) / n
    t = n * (l - lon0)
    return rho * math.sin(t), -(rho0 - rho * math.cos(t))
def dp(pts, eps):
    if len(pts) < 3: return pts
    a, b = pts[0], pts[-1]
    dx, dy = b[0]-a[0], b[1]-a[1]; L = math.hypot(dx, dy) or 1e-12
    imax, dmax = 0, 0
    for i in range(1, len(pts)-1):
        dd = abs(dy*(pts[i][0]-a[0]) - dx*(pts[i][1]-a[1])) / L
        if dd > dmax: imax, dmax = i, dd
    if dmax > eps:
        return dp(pts[:imax+1], eps)[:-1] + dp(pts[imax:], eps)
    return [a, b]
def area(r):
    return abs(sum(r[i][0]*r[i-1][1]-r[i-1][0]*r[i][1] for i in range(len(r)))) / 2
polys = []
for f in ru:
    g = f['geometry']; cs = g['coordinates'] if g['type'] == 'MultiPolygon' else [g['coordinates']]
    rings = []
    for poly in cs:
        ring = [proj(*pt[:2]) for pt in poly[0]]
        rings.append(ring)
    polys.append((f['properties']['name'], rings))
xs = [p[0] for _, rs in polys for r in rs for p in r]; ys = [p[1] for _, rs in polys for r in rs for p in r]
minx, maxx, miny, maxy = min(xs), max(xs), min(ys), max(ys)
W = 1000.0; s = W / (maxx - minx); H = (maxy - miny) * s
def tx(p): return ((p[0]-minx)*s, (p[1]-miny)*s)
paths = []
for name, rings in polys:
    parts = []
    for r in rings:
        r = [tx(p) for p in r]
        if area(r) < 1.5: continue
        k = len(r) // 2
        r = dp(r[:k+1], 0.45)[:-1] + dp(r[k:-1] + [r[0]], 0.45)[:-1]
        if len(r) < 4: continue
        parts.append('M' + 'L'.join(f'{x:.1f},{y:.1f}' for x, y in r) + 'Z')
    if parts: paths.append((name, ''.join(parts)))
cities = {'tomsk': (84.95, 56.49), 'kemerovo': (86.09, 55.35), 'novosibirsk': (82.92, 55.03), 'barnaul': (83.78, 53.35), 'krasnodar': (38.98, 45.04)}
cxy = {k: tx(proj(*v)) for k, v in cities.items()}
json.dump({'w': W, 'h': round(H, 1), 'regions': [{'name': nm, 'hl': nm in HL, 'd': dd} for nm, dd in paths], 'cities': {k: [round(x, 1), round(y, 1)] for k, (x, y) in cxy.items()}}, open(out, 'w', encoding='utf-8'), ensure_ascii=False)
print(W, H, len(paths), sum(len(dd) for _, dd in paths))
