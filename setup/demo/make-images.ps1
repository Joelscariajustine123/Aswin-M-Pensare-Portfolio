# Renders demo images for the Aswin portfolio from demo.json (HTML designs -> PNG via headless Edge).
# Usage: powershell -File make-images.ps1
$ErrorActionPreference = 'Continue'
$Here = Split-Path -Parent $MyInvocation.MyCommand.Path
$Out  = Join-Path $Here 'img'
$Tmp  = Join-Path $Here 'html'
New-Item -ItemType Directory -Force $Out, $Tmp | Out-Null
$Edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
$Prof = Join-Path $env:TEMP 'aswin-demo-edge'
$data = Get-Content (Join-Path $Here 'demo.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$brands = @{}; foreach ($b in $data.brands) { $brands[$b.slug] = $b }

function Enc([string]$s) { [System.Net.WebUtility]::HtmlEncode($s) }

function Symbol($name, $color, $size) {
  $p = switch ($name) {
    'wave'   { '<path d="M4 34c8-10 16-10 24 0s16 10 24 0 16-10 24 0" fill="none" stroke="CLR" stroke-width="7" stroke-linecap="round"/><path d="M4 52c8-10 16-10 24 0s16 10 24 0 16-10 24 0" fill="none" stroke="CLR" stroke-width="7" stroke-linecap="round" opacity=".55"/>' }
    'bars'   { '<rect x="8" y="44" width="14" height="28" rx="4" fill="CLR"/><rect x="31" y="28" width="14" height="44" rx="4" fill="CLR"/><rect x="54" y="10" width="14" height="62" rx="4" fill="CLR"/>' }
    'drop'   { '<path d="M40 6C40 6 14 36 14 52a26 26 0 0 0 52 0C66 36 40 6 40 6z" fill="CLR"/>' }
    'spark'  { '<path d="M40 4l8 26 26 10-26 10-8 26-8-26L6 40l26-10z" fill="CLR"/>' }
    'roof'   { '<path d="M8 40 40 10l32 30" fill="none" stroke="CLR" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/><rect x="20" y="40" width="40" height="30" rx="4" fill="CLR"/>' }
    default  { '<circle cx="40" cy="40" r="30" fill="CLR"/><path d="M52 40c10 4 14 14 10 24" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round"/><circle cx="34" cy="34" r="4" fill="#fff"/>' }
  }
  return "<svg width='$size' height='$size' viewBox='0 0 80 80'>" + $p.Replace('CLR', $color) + '</svg>'
}

$fonts = '<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">'
$base  = '*{box-sizing:border-box;margin:0;padding:0}body{overflow:hidden;font-family:"Bricolage Grotesque",sans-serif;-webkit-font-smoothing:antialiased}.d{font-family:"Bricolage Grotesque";font-weight:800;letter-spacing:-.04em;line-height:.92}.s{font-family:"Instrument Serif",serif}'

function Render($name, $w, $h, $css, $body, $transparent = $false) {
  $html = "<!doctype html><html><head><meta charset='utf-8'>$fonts<style>$base html,body{width:${w}px;height:${h}px}$css</style></head><body>$body</body></html>"
  $file = Join-Path $Tmp "$name.html"
  [System.IO.File]::WriteAllText($file, $html, (New-Object System.Text.UTF8Encoding $false))
  $png = Join-Path $Out "$name.png"
  $eargs = @('--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-first-run', "--user-data-dir=$Prof", '--virtual-time-budget=6000', "--window-size=$w,$h", "--screenshot=$png")
  if ($transparent) { $eargs += '--default-background-color=00000000' }
  $eargs += ('file:///' + $file.Replace('\', '/'))
  & $Edge @eargs 2>&1 | Out-Null
  if (Test-Path $png) { Write-Host "ok  $name.png" } else { Write-Host "FAIL $name" }
}

# ---------- Logos ----------
foreach ($b in $data.brands) {
  $sym = Symbol $b.symbol $b.color 150
  Render "logo-$($b.slug)" 600 400 "body{background:transparent;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:0 24px;text-align:center}.n{color:$($b.color);font-size:62px;line-height:.95}" "$sym<div class='n d'>$(Enc $b.name)</div>" $true
}

# ---------- Works ----------
if ($env:LOGOS_ONLY) { Write-Host "done"; exit }
foreach ($w in $data.works) {
  $b = $brands[$w.brand]; $c = $b.color; $a = $b.accent
  $hd = Enc $w.img.headline; $sub = Enc $w.img.sub; $k = Enc $w.img.kicker; $bn = Enc $b.name
  switch ($w.img.tpl) {
    'article' {
      $css = "body{background:$c;color:#f6f1e7;padding:70px;position:relative}.k{display:inline-block;padding:10px 20px;border:2px solid $a;color:$a;border-radius:99px;font-size:22px;font-weight:600;letter-spacing:.06em;text-transform:uppercase}.h{font-size:118px;margin-top:70px;max-width:900px}.sub{font-size:46px;font-style:italic;margin-top:24px;opacity:.85}.b{position:absolute;left:70px;bottom:60px;font-size:24px;font-weight:600;opacity:.8}.c{position:absolute;right:-90px;bottom:-90px;width:420px;height:420px;border-radius:50%;background:$a;display:grid;place-items:center}.c svg{margin:-60px 0 0 -60px}.lines{position:absolute;right:70px;top:70px;width:160px}.lines i{display:block;height:10px;border-radius:5px;background:rgba(255,255,255,.25);margin-bottom:14px}.lines i:nth-child(2){width:70%}.lines i:nth-child(3){width:85%}"
      Render "work-$($w.slug)" 1200 900 $css "<span class='k'>$k</span><div class='lines'><i></i><i></i><i></i></div><h1 class='h d'>$hd</h1><p class='sub s'>$sub</p><div class='b'>$bn</div><div class='c'>$(Symbol $b.symbol $c 150)</div>"
    }
    'social' {
      $emoji = @{ 'wave' = '&#9749;'; 'drop' = '&#127783;&#65039;'; 'circle' = '&#128024;'; 'bars' = '&#128184;'; 'spark' = '&#10024;'; 'roof' = '&#127969;' }[$b.symbol]
      $css = "body{background:$a;color:$c;padding:80px;position:relative;display:flex;flex-direction:column;justify-content:center}.blob{position:absolute;width:760px;height:760px;border-radius:50%;background:$c;right:-260px;top:-240px;opacity:.12}.blob2{position:absolute;width:420px;height:420px;border-radius:50%;border:28px solid $c;left:-160px;bottom:-140px;opacity:.18}.e{font-size:170px;line-height:1;margin-bottom:40px}.h{font-size:128px;position:relative}.sub{font-size:52px;font-style:italic;margin-top:36px;position:relative}.tag{position:absolute;left:80px;bottom:70px;display:flex;align-items:center;gap:16px;font-size:30px;font-weight:600}.tag span{width:20px;height:20px;border-radius:50%;background:$c}"
      Render "work-$($w.slug)" 1080 1350 $css "<div class='blob'></div><div class='blob2'></div><div class='e'>$emoji</div><h1 class='h d'>$hd</h1><p class='sub s'>$sub</p><div class='tag'><span></span>$bn</div>"
    }
    'campaign' {
      $css = "body{background:#151412;color:#f6f1e7;padding:80px;position:relative}.dots{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.07) 2px,transparent 2px);background-size:26px 26px}.g1{position:absolute;width:900px;height:900px;border-radius:50%;background:radial-gradient(circle,$c,transparent 70%);right:-200px;top:-260px;opacity:.9}.g2{position:absolute;width:620px;height:620px;border-radius:50%;background:radial-gradient(circle,$a,transparent 70%);left:-160px;bottom:-300px;opacity:.75}.k{position:relative;font-size:26px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:$a}.h{position:relative;font-size:168px;margin-top:180px;max-width:1300px}.row{position:relative;display:flex;gap:16px;margin-top:44px}.row span{padding:14px 26px;border-radius:99px;border:2px solid rgba(255,255,255,.4);font-size:28px}.b{position:absolute;right:80px;top:76px;font-size:28px;font-weight:600}"
      Render "work-$($w.slug)" 1600 1000 $css "<div class='dots'></div><div class='g1'></div><div class='g2'></div><div class='k'>$k</div><div class='b'>$bn</div><h1 class='h d'>$hd</h1><div class='row'><span>$sub</span><span>$($w.year)</span></div>"
    }
    'news' {
      $css = "body{background:#f4eee0;color:#1b1a17;padding:80px 90px;position:relative;font-family:'Instrument Serif',serif}body:before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(0,0,0,.05) 1px,transparent 1px);background-size:5px 5px}.top{position:relative;display:flex;justify-content:space-between;border-top:6px double #1b1a17;border-bottom:2px solid #1b1a17;padding:12px 0;font-family:'Bricolage Grotesque';font-size:22px;letter-spacing:.14em;text-transform:uppercase}.h{position:relative;font-size:178px;line-height:.9;letter-spacing:-.02em;margin-top:120px}.body{position:relative;margin-top:70px;font-size:34px;line-height:1.45;columns:2;column-gap:60px;column-rule:1px solid rgba(0,0,0,.25);text-align:justify}.foot{position:absolute;left:90px;right:90px;bottom:80px;display:flex;justify-content:space-between;align-items:center;border-top:2px solid #1b1a17;padding-top:30px}.lg{display:flex;align-items:center;gap:18px;font-family:'Bricolage Grotesque';font-weight:800;font-size:44px;letter-spacing:-.03em;color:$c}.tc{font-family:'Bricolage Grotesque';font-size:18px;opacity:.6}"
      Render "work-$($w.slug)" 1200 1600 $css "<div class='top'><span>$k</span><span>Advertisement</span><span>$($w.year)</span></div><h1 class='h'>$hd</h1><p class='body'>$sub</p><div class='foot'><div class='lg'>$(Symbol $b.symbol $c 70)$bn</div><div class='tc'>T&amp;C apply.</div></div>"
    }
    'brandboard' {
      $css = "body{display:grid;grid-template-columns:1fr 1fr;background:#f6f1e7}.l{background:$c;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:30px;color:#fff}.l .n{font-size:84px;color:#fff;text-align:center;padding:0 40px}.r{padding:80px 70px;display:flex;flex-direction:column;justify-content:space-between;color:#1b1a17}.k{font-size:22px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:$c}.t{font-size:96px;font-style:italic;line-height:1}.sw{display:flex;gap:16px}.sw div{flex:1;height:150px;border-radius:18px;padding:14px;font-size:18px;font-weight:600;display:flex;align-items:flex-end}.ty{display:flex;align-items:flex-end;gap:30px;font-size:22px}.ty b{font-size:120px;line-height:.8}"
      Render "work-$($w.slug)" 1600 1000 $css "<div class='l'>$(Symbol $b.symbol '#ffffff' 170)<div class='n d'>$bn</div></div><div class='r'><div class='k'>$sub</div><div class='t s'>&ldquo;$hd&rdquo;</div><div class='sw'><div style='background:$c;color:#fff'>$c</div><div style='background:$a'>$a</div><div style='background:#1b1a17;color:#fff'>#1B1A17</div><div style='background:#fff;box-shadow:inset 0 0 0 2px #ddd'>#FFFFFF</div></div><div class='ty'><b class='d'>Aa</b><span>Bricolage Grotesque<br>Instrument Serif</span></div></div>"
    }
  }
}
Write-Host 'done'
