param([string]$Name, [string]$Subject)
$d = Split-Path -Parent $MyInvocation.MyCommand.Path
$style = "Bright airy lifestyle photograph, high-key, soft natural morning daylight from a large window, light clean interior with white and pale blue tones, calm hopeful and reassuring mood, gentle shallow depth of field, fresh modern look, no visible faces, no readable text, no logos, no watermark, wide horizontal 3:2."
& "$env:USERPROFILE\.claude\skills\codex-image\gen.ps1" -Prompt "$Subject $style" -OutDir $d -Name $Name
