import fs from 'fs';
import path from 'path';

const SR = 44100;
const OUT = 'artifacts/nino-xo/assets/sounds';

fs.mkdirSync(OUT, { recursive: true });

function writeWav(filename, samples) {
  const data = Buffer.alloc(samples.length * 2);
  for (let i = 0; i < samples.length; i++) {
    const s = Math.max(-1, Math.min(1, samples[i]));
    data.writeInt16LE(Math.round(s * 32767), i * 2);
  }
  const header = Buffer.alloc(44);
  header.write('RIFF', 0);
  header.writeUInt32LE(36 + data.length, 4);
  header.write('WAVE', 8);
  header.write('fmt ', 12);
  header.writeUInt32LE(16, 16);
  header.writeUInt16LE(1, 20);    // PCM
  header.writeUInt16LE(1, 22);    // mono
  header.writeUInt32LE(SR, 24);
  header.writeUInt32LE(SR * 2, 28);
  header.writeUInt16LE(2, 32);
  header.writeUInt16LE(16, 34);
  header.write('data', 36);
  header.writeUInt32LE(data.length, 40);
  const out = path.join(OUT, filename);
  fs.writeFileSync(out, Buffer.concat([header, data]));
  console.log(`✓ ${filename}  (${(fs.statSync(out).size / 1024).toFixed(0)} KB)`);
}

// Build a tone: freq Hz, seconds, volume, optional harmonics weights
function makeTone(freq, secs, vol, harmonics = [1, 0.5, 0.15]) {
  const n = Math.ceil(SR * secs);
  const buf = new Float32Array(n);
  const total = harmonics.reduce((a, b) => a + b, 0);
  for (let i = 0; i < n; i++) {
    const t = i / SR;
    let s = 0;
    harmonics.forEach((h, k) => (s += h * Math.sin(2 * Math.PI * freq * (k + 1) * t)));
    // Envelope: short attack, exponential decay
    const att = Math.min(1, t / 0.008);
    const dec = Math.exp(-t * (1 / secs) * 3.5);
    buf[i] = (s / total) * vol * att * dec;
  }
  return buf;
}

// Concatenate Float32Arrays
function cat(...arrs) {
  const total = arrs.reduce((s, a) => s + a.length, 0);
  const out = new Float32Array(total);
  let off = 0;
  for (const a of arrs) { out.set(a, off); off += a.length; }
  return out;
}

// Mix Float32Arrays (auto-normalise if peak > 0.9)
function mix(...arrs) {
  const len = Math.max(...arrs.map(a => a.length));
  const out = new Float32Array(len);
  for (const a of arrs) for (let i = 0; i < a.length; i++) out[i] += a[i];
  let peak = 0;
  for (let i = 0; i < out.length; i++) { const v = Math.abs(out[i]); if (v > peak) peak = v; }
  if (peak > 0.9) for (let i = 0; i < out.length; i++) out[i] = (out[i] / peak) * 0.88;
  return out;
}

function sil(secs) { return new Float32Array(Math.ceil(SR * secs)); }

// Note frequencies
const N = {
  C3: 130.81, G3: 196.00, A3: 220.00, B3: 246.94,
  C4: 261.63, D4: 293.66, E4: 329.63, F4: 349.23,
  G4: 392.00, A4: 440.00, B4: 493.88,
  C5: 523.25, D5: 587.33, E5: 659.25, G5: 783.99,
};

// ── TAP  (UI button click) ─────────────────────────────────────────────────
writeWav('tap.wav', makeTone(N.A4, 0.07, 0.5, [1, 0.3]));

// ── MOVE (place X / O on board) ───────────────────────────────────────────
writeWav('move.wav', makeTone(N.G4, 0.12, 0.6, [1, 0.45, 0.1]));

// ── WIN  (victory fanfare) ────────────────────────────────────────────────
const winArp = cat(
  makeTone(N.C4, 0.11, 0.7), makeTone(N.E4, 0.11, 0.7), makeTone(N.G4, 0.11, 0.7),
  makeTone(N.C5, 0.11, 0.7), makeTone(N.E5, 0.11, 0.75),
);
const winChord = mix(
  makeTone(N.C4, 0.55, 0.4), makeTone(N.E4, 0.55, 0.4),
  makeTone(N.G4, 0.55, 0.45), makeTone(N.C5, 0.55, 0.5),
);
writeWav('win.wav', cat(winArp, winChord));

// ── LOSE (defeat) ─────────────────────────────────────────────────────────
writeWav('lose.wav', cat(
  makeTone(N.G4, 0.18, 0.55, [1, 0.3]),
  makeTone(N.E4, 0.18, 0.55, [1, 0.3]),
  makeTone(N.C4, 0.32, 0.60, [1, 0.3]),
));

// ── DRAW (neutral) ────────────────────────────────────────────────────────
writeWav('draw.wav', cat(
  makeTone(N.E4, 0.18, 0.5, [1, 0.25]),
  makeTone(N.D4, 0.28, 0.5, [1, 0.25]),
));

// ── BG MUSIC  (4-second looping ambient track) ────────────────────────────
// 120 BPM → quarter = 0.5s, eighth = 0.25s, half = 1.0s
// 2 bars of 4/4 = 4 seconds total

const t = (f, d, v = 0.18, h = [1, 0.5, 0.2]) => makeTone(f, d, v, h);
const bass = (f, d) => t(f, d, 0.13, [1, 0.3]);

const BG_BASS = cat(
  bass(N.C3, 1.0), bass(N.C3, 1.0),
  bass(N.G3, 1.0), bass(N.A3, 1.0),
);

// 16 eighth-notes = 4s
const BG_MID = cat(
  t(N.E4, 0.25), t(N.G4, 0.25), t(N.C4, 0.25), t(N.G4, 0.25),
  t(N.E4, 0.25), t(N.G4, 0.25), t(N.C4, 0.25), t(N.G4, 0.25),
  t(N.D4, 0.25), t(N.G4, 0.25), t(N.B3, 0.25), t(N.G4, 0.25),
  t(N.A3, 0.25), t(N.C4, 0.25), t(N.E4, 0.25), t(N.C4, 0.25),
);

// Melody over 4s
const BG_MELODY = cat(
  sil(0.25), t(N.C5, 0.25, 0.22), t(N.G4, 0.5, 0.20), t(N.A4, 0.5, 0.22), t(N.G4, 0.25, 0.18), sil(0.25),
  t(N.E4, 0.25, 0.20), t(N.G4, 0.5, 0.22), t(N.E4, 0.5, 0.20),
  sil(0.25), t(N.D4, 0.25, 0.20), t(N.E4, 0.5, 0.22), t(N.G4, 1.0, 0.22),
);

writeWav('bg.wav', mix(BG_BASS, BG_MID, BG_MELODY));

console.log('\nAll sounds generated successfully.');
