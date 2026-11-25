"""Вставляет снимок квиза на ровный экран телефона. python -I screen.py bg.png screen.png out.png"""
from PIL import Image, ImageDraw, ImageFilter
import sys
bg,scr,out=sys.argv[1:4]
im=Image.open(bg).convert('RGB'); px=im.load(); W,H=im.size
# экран — самая светлая почти-белая область справа: границы по строке/столбцу через центр
cx,cy=None,None
best=0
for x in range(W//2,W,4):
    for y in range(0,H,4):
        r,g,b=px[x,y]
        if r>215 and g>215 and b>215:
            cx,cy=x,y; break
    if cx: break
# расширяем от найденной точки к центру экрана
y0=cy
while y0>0 and min(px[cx+60,y0-1])>185: y0-=1
y1=cy
while y1+1<H and min(px[cx+60,y1+1])>185: y1+=1
my=(y0+y1)//2
xs=[x for x in range(W//2,W) if min(px[x,my])>185]; x0=xs[0]; x1=x0
while x1+1<W and min(px[x1+1,my])>185: x1+=1
sw,sh=x1-x0+1,y1-y0+1
print('screen',x0,y0,x1,y1)
s=Image.open(scr).convert('RGB')
# заполняем по ширине, низ — белый
k=sw/s.width; s=s.resize((sw,int(s.height*k)),Image.LANCZOS)
canvas=Image.new('RGB',(sw,sh),(255,255,255)); canvas.paste(s.crop((0,0,sw,min(sh,s.height))),(0,0))
# лёгкое затемнение под общую экспозицию
canvas=Image.blend(canvas,Image.new('RGB',(sw,sh),(214,220,230)),0.12)
# маска — по самому светлому экрану исходника (повторяет его скругления), края чуть сжаты и смягчены
src=im.crop((x0,y0,x1+1,y1+1)).convert('L')
mask=src.point(lambda v:255 if v>175 else 0).filter(ImageFilter.MinFilter(5)).filter(ImageFilter.GaussianBlur(0.8))
im.paste(canvas,(x0,y0),mask)
im.save(out)
