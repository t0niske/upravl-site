param([string]$Name, [string]$Subject)
$d = Split-Path -Parent $MyInvocation.MyCommand.Path
$style = "Realistic editorial photograph, even soft overcast daylight from a window, neutral exposure (neither dark nor overly bright), muted natural colors with a subtle cool blue-grey tint, plain modern interior with grey and white tones, minimal props, no flowers, no plants, no cozy decor, calm focused and businesslike mood, neither sad nor cheerful, shallow depth of field, no visible faces, no readable text, no logos, no watermark, wide horizontal 3:2."
& "$env:USERPROFILE\.claude\skills\codex-image\gen.ps1" -Prompt "$Subject $style" -OutDir $d -Name $Name
