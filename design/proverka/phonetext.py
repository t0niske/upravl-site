"""Вписывает текст квиза на экран телефона (генератор ломает кириллицу). Текст и выбранная кнопка наклонены вместе с телефоном.
python -I phonetext.py hero-bg-quiz.png hero-bg-quiz-fixed.png [crop.png]"""
from PIL import Image, ImageDraw, ImageFont
import math, sys
src,out=sys.argv[1:3]; crop=sys.argv[3] if len(sys.argv)>3 else None
im=Image.open(src).convert('RGB'); px=im.load()
F='C:/Windows/Fonts/'; SLOPE=0.087; ANG=math.degrees(math.atan(SLOPE)); K=4

def colfill(x0,y0,x1,y1):
    for x in range(x0,x1+1):
        a=px[x,y0-2]; b=px[x,y1+2]
        la=sum(a)/3; lb=sum(b)/3
        if abs(la-lb)>60:
            if la<lb and la<120: b=a
            elif lb<la and lb<120: a=b
        for y in range(y0,y1+1):
            t=(y-y0)/max(1,(y1-y0))
            px[x,y]=tuple(int(a[i]*(1-t)+b[i]*t) for i in range(3))

# 1) стереть сломанные надписи (кроме выбранной кнопки — её перерисуем целиком)
for r in [(1062,365,1162,388),(1029,440,1222,478),(1080,515,1172,542),(1100,633,1190,661),(1146,720,1196,743)]:
    colfill(*r)
for x in range(1186,1200):
    for y in range(626,652):
        r_,g_,b_=px[x,y]
        if max(r_,g_,b_)-min(r_,g_,b_)<28 and (r_+g_+b_)/3<215: px[x,y]=(237,238,242)

def paste_rotated(layer, cx, cy):
    rot=layer.rotate(ANG, resample=Image.BICUBIC, expand=True)
    rot=rot.resize((rot.width//K, rot.height//K), Image.LANCZOS)
    im.paste(rot,(int(round(cx-rot.width/2)),int(round(cy-rot.height/2))),rot)

def text_at(t, font_file, size, color, cx=None, x_left=None, line=None, cy=None):
    font=ImageFont.truetype(F+font_file, size*K)
    d0=ImageDraw.Draw(Image.new('RGBA',(1,1)))
    w=d0.textlength(t,font=font); bb=font.getbbox(t)
    pad=6*K; L=Image.new('RGBA',(int(w)+2*pad, (bb[3]-bb[1])+2*pad),(0,0,0,0))
    ImageDraw.Draw(L).text((pad,pad-bb[1]),t,font=font,fill=color)
    if cx is None: cx=x_left+w/K/2
    if cy is None: cy=line[1]-(cx-line[0])*SLOPE
    paste_rotated(L,cx,cy)

# 2) выбранная кнопка: синяя «таблетка» с радио и текстом, повёрнута как телефон
PCX,PCY,PW,PH=1147.5,584.7,198,52
L=Image.new('RGBA',(PW*K,PH*K),(0,0,0,0)); d=ImageDraw.Draw(L)
d.rounded_rectangle((0,0,PW*K-1,PH*K-1),radius=PH*K//2,fill=(2,76,145,255))
rcx=(1073.3-PCX+PW/2)*K; rcy=PH*K/2
for r,c in ((9.5,(255,255,255,255)),(7,(2,76,145,255)),(4.5,(255,255,255,255))):
    d.ellipse((rcx-r*K,rcy-r*K,rcx+r*K,rcy+r*K),fill=c)
font=ImageFont.truetype(F+'seguisb.ttf',12*K); t='от 500 тыс. до 1 млн ₽'; bb=font.getbbox(t)
d.text(((1092-PCX+PW/2)*K, rcy-(bb[3]-bb[1])/2-bb[1]),t,font=font,fill=(255,255,255,255))
paste_rotated(L,PCX,PCY)

# 3) остальные надписи — вдоль наклона экрана
text_at('Вопрос 2 из 6','segoeui.ttf',12,(120,124,132),cx=1112,line=(1112,376.5))
text_at('Какая сумма долга?','segoeuib.ttf',18,(29,30,33),cx=1125,line=(1125,459))
text_at('до 500 тыс. ₽','seguisb.ttf',13,(29,30,33),x_left=1084,line=(1126,528.5))
text_at('больше 1 млн ₽','seguisb.ttf',13,(29,30,33),x_left=1103,line=(1145,647))
text_at('Далее','segoeuib.ttf',14,(255,255,255),cx=1171,line=(1171,731.5))
im.save(out)
if crop: im.crop((1000,340,1290,780)).resize((580,880)).save(crop)
