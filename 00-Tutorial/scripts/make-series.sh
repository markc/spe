#!/usr/bin/env bash
# Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
# Assemble the single SPE tutorial from the nine rendered episodes (/tmp/ep/*):
#   intro card  →  [title card ⟶ episode] ×9  →  outro card  →  /tmp/ep/spe-tutorial.mp4
# Cards are HTML/CSS animations (cards/*.html) rendered frame-by-frame in headless
# Chromium (render-card.mjs); intro/outro narration is synthesised with the same
# Chirp 3 HD voice as the episodes, and each card lays itself out from the measured
# clip lengths. Every episode opens on ~0.5 s of desktop and then ~4 s of blank white
# while the kiosk Firefox makes its first paint; that whole stretch is replaced by a
# freeze of the first painted frame (detected per episode), under a dissolving title
# card, so the page is on screen from the first word of narration. Runs headless — no screen capture needed.
#
#   bash scripts/make-series.sh            # everything
#   bash scripts/make-series.sh cards      # synth + render intro/outro/titles only (titles | intro-outro)
#   bash scripts/make-series.sh segments   # per-chapter segments + intro/outro segments (chapters | cardsegs)
#   bash scripts/make-series.sh concat     # final join + chapter timestamps
#   RESYNTH=1 …                            # re-synthesise intro/outro narration
#   ENC=x264 …                             # CPU encode instead of VAAPI
set -uo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
TUT="$(cd "$HERE/.." && pwd)"
ROOT="$(cd "$TUT/.." && pwd)"
EP=/tmp/ep
OUT="$EP/series"
mkdir -p "$OUT/aud"
STEP="${1:-all}"
FPS=30
TITLE_DUR=3.2     # title card length
XF=0.6            # title → episode dissolve
SETTLE=0.4        # freeze point = first painted frame + SETTLE (see first_paint)
LEAD=0.6; TAIL=1.0

# VAAPI (Intel Arc, ~100 fps at 4K) by default; ENC=x264 for a CPU encode.
# HW = global device opts (before -i); VTAIL = filter suffix that uploads to the GPU.
if [ "${ENC:-vaapi}" = x264 ]; then
  VENC=(-c:v libx264 -preset medium -crf 18 -pix_fmt yuv420p); HW=(); VTAIL=""
else
  VENC=(-c:v h264_vaapi -qp 18 -profile:v high); HW=(-vaapi_device /dev/dri/renderD128); VTAIL=",format=nv12,hwupload"
fi
ENC_ALL=("${VENC[@]}" -r $FPS -g 60 -c:a aac -b:a 192k -ar 48000 -ac 2 -movflags +faststart)

mapfile -t CHAPS < <(jq -r '.chapters[].dir' "$ROOT/chapters.json")

speakable() {
  echo "$1" | sed -E '
    s/PHP 8\.5/P H P eight point five/g;
    s/\bPHP\b/P H P/g;
    s/\bgpu screen recorder\b/G P U screen recorder/g;
    s/\bffmpeg\b/F F M peg/g;
    s/\bJSON\b/jason/g;
    s/\bHTML\b/H T M L/g;
    s/\bCSS\b/C S S/g;
    s/\b4K\b/four K/g;
    s/\bSQLite\b/sequel-ite/g'
}

dur() { ffprobe -v error -show_entries format=duration -of default=nw=1:nk=1 "$1"; }

# First frame with real content: edge density of a blank page is 0, a painted page ≥ 3.
# Skip the first 0.8 s (the desktop leak has edges too).
first_paint() {
  ffmpeg -v error -t 12 -i "$1" -vf "scale=384:216,edgedetect=low=0.1:high=0.3,signalstats,metadata=print:key=lavfi.signalstats.YAVG:file=-" -f null - 2>/dev/null \
    | grep -oP 'pts_time:\K[0-9.]+|YAVG=\K[0-9.]+' | paste - - | awk '$1>0.8 && $2>1 {print $1; exit}'
}

# ---- narration for a card: one WAV per beat, cumulative beat times, loudnormed mix
synth_card() {
  local name=$1 json="$TUT/episodes/$1.json"
  mapfile -t NARR < <(jq -r '.scenes[].narr' "$json")
  if [ -f "$OUT/$name-beats.txt" ] && [ "${RESYNTH:-0}" != 1 ]; then
    echo "$name: reusing cached narration (RESYNTH=1 to redo)"; return
  fi
  echo "$name: synth ${#NARR[@]} beats…"
  local durs="[" list="$OUT/$name-audio.txt"; : > "$list"
  for i in "${!NARR[@]}"; do
    local wav="$OUT/aud/$name-$(printf '%03d' "$i").wav"
    bash "$HERE/google-tts.sh" "$(speakable "${NARR[$i]}")" "$wav" >/dev/null || { echo "TTS failed on $name beat $i"; exit 1; }
    durs="$durs$(dur "$wav"),"; echo "file 'aud/$name-$(printf '%03d' "$i").wav'" >> "$list"
    echo "  beat $i $(dur "$wav")s"
  done
  echo "${durs%,}]" > "$OUT/$name-durs.json"
  jq -r '[range(0; length+1)] as $ix | [ $ix[] as $i | (.[:$i] | add // 0) ] | map(.*1000|round/1000) | join(",")' \
    "$OUT/$name-durs.json" > "$OUT/$name-beats.txt"
  ( cd "$OUT" && ffmpeg -y -loglevel error -f concat -safe 0 -i "$name-audio.txt" -c copy "$name-raw.wav" )
  ffmpeg -y -loglevel error -i "$OUT/$name-raw.wav" -af "loudnorm=I=-16:TP=-1.5:LRA=11,adelay=$(awk -v l=$LEAD 'BEGIN{print int(l*1000)}'):all=1,apad=pad_dur=$TAIL" -ar 48000 -ac 2 "$OUT/$name.wav"
}

render() { node "$HERE/render-card.mjs" "$TUT/cards/$1.html" "$2" "$3" || { echo "render failed: $1"; exit 1; }; }

urlenc() { jq -rn --arg s "$1" '$s|@uri'; }

do_titles() {
  for c in "${CHAPS[@]}"; do
    local n=${c%%-*} name=${c#*-}
    local idea; idea=$(jq -r --arg d "$c" '.chapters[] | select(.dir==$d) | .idea' "$ROOT/chapters.json")
    render title "$OUT/title-$c.mp4" "n=$((10#$n))&name=$(urlenc "$name")&idea=$(urlenc "$idea")&dur=$TITLE_DUR"
  done
}

do_intro_outro() {
  synth_card intro
  synth_card outro
  render intro "$OUT/intro-v.mp4" "beats=$(cat "$OUT/intro-beats.txt")&lead=$LEAD&tail=$TAIL"
  render outro "$OUT/outro-v.mp4" "beats=$(cat "$OUT/outro-beats.txt")&lead=$LEAD&tail=$TAIL"
}

do_cards() { do_titles; do_intro_outro; }

# ---- segments: uniform encode so the final join is a stream copy
seg_card() {   # name  (intro|outro)
  local v="$OUT/$1-v.mp4" a="$OUT/$1.wav" T; T=$(dur "$v")
  local fo; fo=$(awk -v t="$T" 'BEGIN{print t-0.7}')
  ffmpeg -y -loglevel error "${HW[@]}" -i "$v" -i "$a" \
    -filter_complex "[0:v]fade=t=in:d=0.5,fade=t=out:st=$fo:d=0.7$VTAIL[v];[1:a]afade=t=out:st=$fo:d=0.7[a]" \
    -map '[v]' -map '[a]' "${ENC_ALL[@]}" -t "$T" "$OUT/seg-$2.mp4"
  echo "seg-$2: $(dur "$OUT/seg-$2.mp4")s"
}

seg_chapter() {   # 07-PDO  index
  local c=$1 ep="$EP/$1/episode-$1.mp4" title="$OUT/title-$1.mp4" still="$OUT/still-$1.png"
  [ -f "$ep" ] || { echo "missing episode: $ep"; exit 1; }
  local FREEZE; FREEZE=$(awk -v p="$(first_paint "$ep")" -v s=$SETTLE 'BEGIN{print (p==""?1.0:p)+s}')
  ffmpeg -y -loglevel error -ss "$FREEZE" -i "$ep" -frames:v 1 "$still"
  local L; L=$(dur "$ep")
  echo "  $c: first paint at $(first_paint "$ep")s → freeze $FREEZE s"
  local off end; off=$(awk -v t=$TITLE_DUR -v x=$XF 'BEGIN{print t-x}')
  end=$(awk -v t=$TITLE_DUR -v l="$L" -v x=$XF 'BEGIN{print t+l-x}')
  local fo; fo=$(awk -v e="$end" 'BEGIN{print e-0.6}')
  local ms; ms=$(awk -v o="$off" 'BEGIN{print int(o*1000)}')
  ffmpeg -y -loglevel error "${HW[@]}" -i "$title" -loop 1 -framerate $FPS -t "$FREEZE" -i "$still" -i "$ep" \
    -filter_complex "
      [1:v]format=yuv420p,setsar=1[st];
      [2:v]trim=start=$FREEZE,setpts=PTS-STARTPTS[epv];
      [st][epv]concat=n=2:v=1:a=0,fps=$FPS,settb=AVTB[ep];
      [0:v]format=yuv420p,fps=$FPS,settb=AVTB,fade=t=in:d=0.35[tt];
      [tt][ep]xfade=transition=fade:duration=$XF:offset=$off[vx];
      [vx]fade=t=out:st=$fo:d=0.6$VTAIL[v];
      [2:a]adelay=$ms:all=1,afade=t=out:st=$fo:d=0.6[a]" \
    -map '[v]' -map '[a]' "${ENC_ALL[@]}" -t "$end" "$OUT/seg-$2.mp4"
  echo "seg-$2 ($c): $(dur "$OUT/seg-$2.mp4")s"
}

do_chapters() {
  local i=1
  for c in "${CHAPS[@]}"; do seg_chapter "$c" "$(printf '%02d' $i)-$c"; i=$((i+1)); done
}
do_cardsegs() { seg_card intro 00-intro; seg_card outro 10-outro; }
do_segments() { do_cardsegs; do_chapters; }

do_concat() {
  cd "$OUT" || exit 1
  ls seg-*.mp4 | sort | sed "s/.*/file '&'/" > concat.txt
  ffmpeg -y -loglevel error -f concat -safe 0 -i concat.txt -c:v copy -c:a aac -b:a 192k -movflags +faststart "$EP/spe-tutorial.mp4"
  # YouTube chapter list (description) from the segment lengths
  local t=0 line; : > "$EP/spe-tutorial-chapters.txt"
  for s in $(ls seg-*.mp4 | sort); do
    case $s in
      seg-00-intro.mp4) line="Introduction — what SPE is and why";;
      seg-10-outro.mp4) line="How this video was made";;
      *) local c=${s#seg-??-}; c=${c%.mp4}; line="Chapter ${c%%-*} — ${c#*-}";;
    esac
    printf '%d:%02d %s\n' $((${t%.*}/60)) $((${t%.*}%60)) "$line" >> "$EP/spe-tutorial-chapters.txt"
    t=$(awk -v a="$t" -v b="$(dur "$s")" 'BEGIN{print a+b}')
  done
  echo "== done =="
  ffprobe -v error -show_entries stream=width,height,codec_name -show_entries format=duration -of default=nw=1 "$EP/spe-tutorial.mp4"
  cat "$EP/spe-tutorial-chapters.txt"
  echo; echo "MP4: $EP/spe-tutorial.mp4"
}

case $STEP in
  cards) do_cards;;            titles) do_titles;;      intro-outro) do_intro_outro;;
  segments) do_segments;;      chapters) do_chapters;;  cardsegs) do_cardsegs;;
  concat) do_concat;;
  all) do_cards; do_segments; do_concat;;
  *) echo "usage: $0 [all|cards|titles|intro-outro|segments|chapters|cardsegs|concat]"; exit 1;;
esac
